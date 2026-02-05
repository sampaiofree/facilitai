<?php

namespace App\Http\Controllers\Cliente;

use App\Http\Controllers\Controller;
use App\Models\Folder;
use App\Models\Image;
use App\Services\FolderStorageService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class ClienteImageController extends Controller
{
    protected string $imagesRouteBase = 'cliente.images';
    protected string $viewName = 'cliente.images.index';

    public function index(Request $request)
    {
        $cliente = Auth::guard('client')->user();
        $folderId = $request->input('folder_id');
        $currentFolder = null;

        $folders = Folder::query()
            ->where('cliente_id', $cliente->id)
            ->where('user_id', $cliente->user_id)
            ->with('cliente')
            ->orderBy('name')
            ->get();

        $imagesQuery = Image::query()
            ->where('user_id', $cliente->user_id)
            ->whereHas('folder', function ($query) use ($cliente) {
                $query->where('cliente_id', $cliente->id);
            })
            ->with('folder.cliente')
            ->latest();

        if ($folderId) {
            $currentFolder = $folders->firstWhere('id', (int) $folderId);
            if ($currentFolder) {
                $imagesQuery->where('folder_id', $currentFolder->id);
            } else {
                $folderId = null;
            }
        }

        $images = $imagesQuery->paginate(100);
        $selectedFolderId = $folderId;

        return view($this->viewName, [
            'images' => $images,
            'folders' => $folders,
            'selectedFolderId' => $selectedFolderId,
            'currentFolder' => $currentFolder,
            'imagesRouteBase' => $this->imagesRouteBase,
        ]);
    }

    public function store(Request $request)
    {
        $cliente = Auth::guard('client')->user();
        $mimeTypes = 'mimetypes:video/mp4,video/quicktime,image/jpeg,image/png,image/jpg,application/pdf,audio/mpeg,audio/mp3';

        $allowedFolders = Folder::query()
            ->where('cliente_id', $cliente->id)
            ->where('user_id', $cliente->user_id)
            ->get(['id', 'storage_limit_mb', 'storage_used_mb']);
        $allowedFolderIds = $allowedFolders->pluck('id')->all();

        if (empty($allowedFolderIds)) {
            return back()->with('error', 'Nenhuma pasta disponível para envio de mídias.');
        }

        $validated = $request->validate([
            'image' => [
                'required_without:images',
                'file',
                $mimeTypes,
                'max:10240',
            ],
            'title' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:500'],
            'folder_id' => [
                'required',
                'integer',
                Rule::in($allowedFolderIds),
            ],
            'images' => ['required_without:image', 'array', 'min:1'],
            'images.*' => [
                'file',
                $mimeTypes,
                'max:10240',
            ],
            'titles' => ['array'],
            'titles.*' => ['nullable', 'string', 'max:255'],
            'descriptions' => ['array'],
            'descriptions.*' => ['nullable', 'string', 'max:500'],
            'folders' => ['array'],
            'folders.*' => [
                'nullable',
                'integer',
                Rule::in($allowedFolderIds),
            ],
        ]);

        $user = $cliente->user;
        $plan = $user?->plan;

        $files = [];
        if ($request->hasFile('images')) {
            $files = $request->file('images');
        } elseif ($request->hasFile('image')) {
            $files = [$request->file('image')];
        }

        if (!$plan || empty($plan->storage_limit_mb) || $plan->storage_limit_mb <= 0) {
            return back()->with('error', 'Selecione um plano para liberar o envio de mídias.');
        }

        $incomingKb = 0;
        $fileSizesKb = [];
        foreach ($files as $file) {
            $sizeKb = $this->calculateFileSizeKb($file);
            $fileSizesKb[] = $sizeKb;
            $incomingKb += $sizeKb;
        }

        $currentKb = (int) $user->images()->sum('size');
        $nextUsedMb = (int) ceil(($currentKb + $incomingKb) / 1024);

        if ($nextUsedMb > $plan->storage_limit_mb) {
            return back()->with('error', 'Limite de armazenamento do plano excedido. Ajuste seu plano para continuar.');
        }

        $titles = $request->input('titles', []);
        $descriptions = $request->input('descriptions', []);
        $foldersInput = $request->input('folders', []);
        $defaultFolder = $validated['folder_id'] ?? null;

        $folderSizeKb = [];
        foreach ($files as $index => $file) {
            $folderId = $foldersInput[$index] ?? $defaultFolder;
            if (empty($folderId)) {
                return back()->with('error', 'Selecione uma pasta para todos os arquivos.');
            }
            $folderSizeKb[$folderId] = ($folderSizeKb[$folderId] ?? 0) + ($fileSizesKb[$index] ?? $this->calculateFileSizeKb($file));
        }

        foreach ($folderSizeKb as $folderId => $totalKb) {
            $folder = $allowedFolders->firstWhere('id', (int) $folderId);
            if (!$folder) {
                return back()->with('error', 'Pasta inválida para envio.');
            }
            $limitMb = (int) ($folder->storage_limit_mb ?? 0);
            if ($limitMb <= 0) {
                return back()->with('error', 'Esta pasta não permite envio de mídias.');
            }
            $usedMb = (int) ($folder->storage_used_mb ?? 0);
            $incomingMb = (int) ceil($totalKb / 1024);
            if (($usedMb + $incomingMb) > $limitMb) {
                return back()->with('error', 'Limite de armazenamento da pasta excedido.');
            }
        }

        $directory = 'user_images/' . $user->id;
        Storage::disk('public')->makeDirectory($directory);

        $folders = $foldersInput;
        $defaultTitle = $validated['title'] ?? null;
        $defaultDescription = $validated['description'] ?? null;

        $storedPaths = [];
        $affectedFolderIds = [];

        DB::beginTransaction();
        try {
            foreach ($files as $index => $file) {
                $folderId = $folders[$index] ?? $defaultFolder;
                $path = $file->store('user_images/' . $user->id, 'public');
                Storage::disk('public')->setVisibility($path, 'public');
                $storedPaths[] = $path;

                $user->images()->create([
                    'folder_id' => $folderId,
                    'path' => $path,
                    'original_name' => $file->getClientOriginalName(),
                    'title' => $titles[$index] ?? $defaultTitle,
                    'description' => $descriptions[$index] ?? $defaultDescription,
                    'size' => $fileSizesKb[$index] ?? $this->calculateFileSizeKb($file),
                ]);
                $affectedFolderIds[] = $folderId;
            }

            $this->recalculateStorageUsedMb($user);
            app(FolderStorageService::class)->recalculateForFolders($affectedFolderIds);
            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            foreach ($storedPaths as $path) {
                Storage::disk('public')->delete($path);
            }
            Log::error('Erro ao enviar mídias (cliente)', [
                'cliente_id' => $cliente->id,
                'error' => $e->getMessage(),
            ]);
            return back()->with('error', 'Não foi possível enviar as mídias.');
        }

        $count = count($files);
        $message = $count > 1 ? 'Mídias enviadas com sucesso!' : 'Mídia enviada com sucesso!';

        return back()->with('success', $message);
    }

    public function update(Request $request, Image $image)
    {
        $cliente = Auth::guard('client')->user();
        if (!$this->imageBelongsToClient($image, $cliente)) {
            abort(403);
        }

        $validated = $request->validate([
            'title' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:500'],
        ]);

        $image->update($validated);

        return back()->with('success', 'Mídia atualizada com sucesso.');
    }

    public function move(Request $request)
    {
        $cliente = Auth::guard('client')->user();
        $allowedFolders = Folder::query()
            ->where('cliente_id', $cliente->id)
            ->where('user_id', $cliente->user_id)
            ->get(['id', 'storage_limit_mb', 'storage_used_mb']);
        $allowedFolderIds = $allowedFolders->pluck('id')->all();

        $validated = $request->validate([
            'images' => ['required', 'array', 'min:1'],
            'images.*' => [
                'integer',
                Rule::exists('images', 'id')->where('user_id', $cliente->user_id),
            ],
            'folder_id' => [
                'required',
                'integer',
                Rule::in($allowedFolderIds),
            ],
        ]);

        $images = Image::query()
            ->where('user_id', $cliente->user_id)
            ->whereIn('id', $validated['images'])
            ->whereHas('folder', function ($query) use ($cliente) {
                $query->where('cliente_id', $cliente->id);
            })
            ->get(['id', 'folder_id', 'size']);

        if ($images->isEmpty()) {
            return back()->with('error', 'Nenhuma mídia encontrada para mover.');
        }

        $destinationFolder = $allowedFolders->firstWhere('id', (int) $validated['folder_id']);
        if (!$destinationFolder) {
            return back()->with('error', 'Pasta de destino inválida.');
        }

        $limitMb = (int) ($destinationFolder->storage_limit_mb ?? 0);
        if ($limitMb <= 0) {
            return back()->with('error', 'Esta pasta não permite envio de mídias.');
        }

        $movingKb = (int) $images->where('folder_id', '!=', (int) $validated['folder_id'])->sum('size');
        $movingMb = (int) ceil($movingKb / 1024);
        $usedMb = (int) ($destinationFolder->storage_used_mb ?? 0);
        if (($usedMb + $movingMb) > $limitMb) {
            return back()->with('error', 'Limite de armazenamento da pasta excedido.');
        }

        $previousFolderIds = $images->pluck('folder_id')->filter()->unique()->all();

        Image::query()
            ->whereIn('id', $images->pluck('id'))
            ->update(['folder_id' => $validated['folder_id']]);

        app(FolderStorageService::class)->recalculateForFolders(array_merge($previousFolderIds, [$validated['folder_id']]));

        return back()->with('success', 'Mídias movidas com sucesso.');
    }

    public function bulkDestroy(Request $request)
    {
        $cliente = Auth::guard('client')->user();

        $validated = $request->validate([
            'images' => ['required', 'array', 'min:1'],
            'images.*' => [
                'integer',
                Rule::exists('images', 'id')->where('user_id', $cliente->user_id),
            ],
        ]);

        $images = Image::query()
            ->where('user_id', $cliente->user_id)
            ->whereIn('id', $validated['images'])
            ->whereHas('folder', function ($query) use ($cliente) {
                $query->where('cliente_id', $cliente->id);
            })
            ->get();

        if ($images->isEmpty()) {
            return back()->with('error', 'Nenhuma mídia encontrada para exclusão.');
        }

        $paths = $images->pluck('path')->all();
        $folderIds = $images->pluck('folder_id')->filter()->unique()->all();

        DB::beginTransaction();
        try {
            Image::whereIn('id', $images->pluck('id'))->delete();
            $this->recalculateStorageUsedMb($cliente->user);
            app(FolderStorageService::class)->recalculateForFolders($folderIds);
            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('Erro ao excluir mídias em lote (cliente)', [
                'cliente_id' => $cliente->id,
                'error' => $e->getMessage(),
            ]);
            return back()->with('error', 'Não foi possível excluir as mídias.');
        }

        foreach ($paths as $path) {
            try {
                Storage::disk('public')->delete($path);
            } catch (\Throwable $e) {
                Log::warning('Erro ao remover arquivo de mídia (cliente)', [
                    'cliente_id' => $cliente->id,
                    'path' => $path,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return back()->with('success', 'Mídias excluídas com sucesso.');
    }

    public function destroy(Image $image)
    {
        $cliente = Auth::guard('client')->user();
        if (!$this->imageBelongsToClient($image, $cliente)) {
            abort(403);
        }

        $user = $cliente->user;
        $path = $image->path;
        $folderId = $image->folder_id;

        DB::beginTransaction();
        try {
            $image->delete();
            $this->recalculateStorageUsedMb($user);
            app(FolderStorageService::class)->recalculateForFolderId($folderId);
            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error("Erro ao excluir mídia {$image->id} (cliente): " . $e->getMessage());
            return back()->with('error', 'Não foi possível excluir a mídia.');
        }

        try {
            Storage::disk('public')->delete($path);
        } catch (\Throwable $e) {
            Log::warning('Erro ao remover arquivo de mídia (cliente)', [
                'cliente_id' => $cliente->id,
                'path' => $path,
                'error' => $e->getMessage(),
            ]);
        }

        return back()->with('success', 'Mídia excluída com sucesso.');
    }

    protected function calculateFileSizeKb($file): int
    {
        return (int) round($file->getSize() / 1024);
    }

    protected function recalculateStorageUsedMb($user): int
    {
        $totalKb = (int) Image::where('user_id', $user->id)->sum('size');
        $usedMb = (int) ceil($totalKb / 1024);

        $user->forceFill(['storage_used_mb' => $usedMb])->save();

        return $usedMb;
    }

    protected function imageBelongsToClient(Image $image, $cliente): bool
    {
        if ($image->user_id !== $cliente->user_id) {
            return false;
        }

        return $image->folder()
            ->where('cliente_id', $cliente->id)
            ->exists();
    }
}
