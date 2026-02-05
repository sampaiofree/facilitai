<?php

namespace App\Http\Controllers;

use App\Models\Image;
use App\Models\User;
use App\Services\FolderStorageService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;

class ImageController extends Controller
{
    protected string $imagesRouteBase = 'images';
    protected string $foldersRouteBase = 'folders';
    protected string $viewName = 'images.index';

    // Mostra a galeria de imagens do usuário
    public function index(Request $request)
    {
        $user = Auth::user();
        $folderId = $request->input('folder_id');
        $clienteId = $request->input('cliente_id');
        $currentFolder = null;
        $showFolders = false;

        $imagesQuery = $user->images()->with('folder.cliente')->latest();

        if ($folderId === 'none') {
            $imagesQuery->whereNull('folder_id');
        } elseif ($folderId) {
            $currentFolder = $user->folders()->find($folderId);
            if ($currentFolder) {
                $imagesQuery->where('folder_id', $currentFolder->id);
            } else {
                $folderId = null;
            }
        }
        $showFolders = true;

        if ($clienteId) {
            $imagesQuery->whereHas('folder', function ($query) use ($clienteId) {
                $query->where('cliente_id', $clienteId);
            });
        }

        $images = $imagesQuery->paginate(100);
        $folders = $user->folders()->with('cliente')->orderBy('name')->get();
        $clients = $user->clientes()->orderBy('nome')->get();
        $selectedFolderId = $folderId;
        $selectedClienteId = $clienteId;

        $availableUsers = collect();
        if ($this->imagesRouteBase === 'agencia.images') {
            $availableUsers = User::query()
                ->select('id', 'name', 'email', 'plan_id')
                ->with('plan:id,storage_limit_mb')
                ->orderBy('name')
                ->get();
        }

        return view($this->viewName, [
            'images' => $images,
            'folders' => $folders,
            'selectedFolderId' => $selectedFolderId,
            'currentFolder' => $currentFolder,
            'showFolders' => $showFolders,
            'selectedClienteId' => $selectedClienteId,
            'imagesRouteBase' => $this->imagesRouteBase,
            'foldersRouteBase' => $this->foldersRouteBase,
            'availableUsers' => $availableUsers,
            'clients' => $clients,
        ]); 
    } 

    // Salva a nova imagem
    public function store(Request $request)
    {
        $mimeTypes = 'mimetypes:video/mp4,video/quicktime,image/jpeg,image/png,image/jpg,application/pdf,audio/mpeg,audio/mp3';

        $validated = $request->validate([
            // Upload único (compatibilidade)
            'image' => [
                'required_without:images',
                'file',
                $mimeTypes,
                'max:10240',
            ],
            'title' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:500'],
            'folder_id' => [
                'nullable',
                'integer',
                Rule::exists('folders', 'id')->where('user_id', Auth::id()),
            ],
            // Upload múltiplo
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
                Rule::exists('folders', 'id')->where('user_id', Auth::id()),
            ],
        ]);

        $user = Auth::user();
        $plan = $user->plan;

        $files = [];
        if ($request->hasFile('images')) {
            $files = $request->file('images');
        } elseif ($request->hasFile('image')) {
            $files = [$request->file('image')];
        }

        if (!$plan || empty($plan->storage_limit_mb) || $plan->storage_limit_mb <= 0) {
            return back()->with('error', 'Selecione um plano para liberar o envio de midias.');
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

        // Garante que a pasta do usuário existe com permissão correta
        $directory = 'user_images/' . $user->id;
        Storage::disk('public')->makeDirectory($directory);

        $titles = $request->input('titles', []);
        $descriptions = $request->input('descriptions', []);
        $folders = $request->input('folders', []);
        $defaultFolder = $validated['folder_id'] ?? null;
        $defaultTitle = $validated['title'] ?? null;
        $defaultDescription = $validated['description'] ?? null;

        $storedPaths = [];
        $affectedFolderIds = [];

        DB::beginTransaction();
        try {
            foreach ($files as $index => $file) {
                // Armazena o arquivo em uma pasta unica para o usuario (storage/app/public/user_images/{user_id})
                $path = $file->store('user_images/' . $user->id, 'public');

                // Garante que o arquivo seja publico (importante!)
                Storage::disk('public')->setVisibility($path, 'public');
                $storedPaths[] = $path;

                $folderId = $folders[$index] ?? $defaultFolder;
                $user->images()->create([
                    'folder_id' => $folderId,
                    'path' => $path,
                    'original_name' => $file->getClientOriginalName(),
                    'title' => $titles[$index] ?? $defaultTitle,
                    'description' => $descriptions[$index] ?? $defaultDescription,
                    'size' => $fileSizesKb[$index] ?? $this->calculateFileSizeKb($file),
                ]);
                if (!empty($folderId)) {
                    $affectedFolderIds[] = $folderId;
                }
            }

            $this->recalculateStorageUsedMb($user);
            app(FolderStorageService::class)->recalculateForFolders($affectedFolderIds);
            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            foreach ($storedPaths as $path) {
                Storage::disk('public')->delete($path);
            }
            Log::error('Erro ao enviar midias', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);
            return back()->with('error', 'Nao foi possivel enviar as midias.');
        }


        $count = count($files);
        $message = $count > 1 ? 'Mídias enviadas com sucesso!' : 'Mídia enviada com sucesso!';

        return back()->with('success', $message);
    } 

    public function update(Request $request, Image $image)
    {
        if ($image->user_id !== Auth::id()) {
            abort(403);
        }

        $validated = $request->validate([
            'title' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:500'],
        ]);

        $image->update($validated);

        return back()->with('success', 'Midia atualizada com sucesso.');
    }

    public function move(Request $request)
    {
        $user = Auth::user();

        $validated = $request->validate([
            'images' => ['required', 'array', 'min:1'],
            'images.*' => [
                'integer',
                Rule::exists('images', 'id')->where('user_id', $user->id),
            ],
            'folder_id' => [
                'nullable',
                'integer',
                Rule::exists('folders', 'id')->where('user_id', $user->id),
            ],
        ]);

        $images = $user->images()->whereIn('id', $validated['images'])->get(['id', 'folder_id']);
        $previousFolderIds = $images->pluck('folder_id')->filter()->unique()->all();

        $user->images()
            ->whereIn('id', $validated['images'])
            ->update(['folder_id' => $validated['folder_id'] ?? null]);

        $nextFolderIds = array_filter([$validated['folder_id'] ?? null]);
        app(FolderStorageService::class)->recalculateForFolders(array_merge($previousFolderIds, $nextFolderIds));

        return back()->with('success', 'Imagens movidas com sucesso.');
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

    public function bulkDestroy(Request $request)
    {
        $user = Auth::user();

        $validated = $request->validate([
            'images' => ['required', 'array', 'min:1'],
            'images.*' => [
                'integer',
                Rule::exists('images', 'id')->where('user_id', $user->id),
            ],
        ]);

        $images = $user->images()->whereIn('id', $validated['images'])->get();
        if ($images->isEmpty()) {
            return back()->with('error', 'Nenhuma midia encontrada para exclusao.');
        }

        $paths = $images->pluck('path')->all();
        $folderIds = $images->pluck('folder_id')->filter()->unique()->all();

        DB::beginTransaction();
        try {
            Image::whereIn('id', $images->pluck('id'))->delete();
            $this->recalculateStorageUsedMb($user);
            app(FolderStorageService::class)->recalculateForFolders($folderIds);
            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('Erro ao excluir midias em lote', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);
            return back()->with('error', 'Nao foi possivel excluir as midias.');
        }

        foreach ($paths as $path) {
            try {
                Storage::disk('public')->delete($path);
            } catch (\Throwable $e) {
                Log::warning('Erro ao remover arquivo de midia', [
                    'user_id' => $user->id,
                    'path' => $path,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return back()->with('success', 'Midias excluidas com sucesso.');
    }

    // Exclui uma imagem
    public function destroy(Image $image)
    {
        if ($image->user_id !== Auth::id()) {
            abort(403);
        }

        $user = Auth::user();
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
            Log::error("Erro ao excluir midia {$image->id}: " . $e->getMessage());
            return back()->with('error', 'Nao foi possivel excluir a midia.');
        }

        try {
            Storage::disk('public')->delete($path);
        } catch (\Throwable $e) {
            Log::warning('Erro ao remover arquivo de midia', [
                'user_id' => $user->id,
                'path' => $path,
                'error' => $e->getMessage(),
            ]);
        }

        return back()->with('success', 'Imagem excluida com sucesso.');
    }
}
