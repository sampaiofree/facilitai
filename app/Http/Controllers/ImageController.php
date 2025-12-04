<?php

namespace App\Http\Controllers;

use App\Models\Image;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;

class ImageController extends Controller
{
    // Mostra a galeria de imagens do usuário
    public function index(Request $request)
    {
        $user = Auth::user();
        $folderId = $request->input('folder_id');
        $currentFolder = null;
        $showFolders = false;

        $imagesQuery = $user->images()->with('folder')->latest();

        if ($folderId === 'none') {
            $imagesQuery->whereNull('folder_id');
            $showFolders = false;
        } elseif ($folderId) {
            $currentFolder = $user->folders()->find($folderId);
            if ($currentFolder) {
                $imagesQuery->where('folder_id', $currentFolder->id);
            } else {
                $folderId = null;
            }
            $showFolders = false;
        } else {
            // Estado "todas": mostra as pastas e apenas os arquivos sem pasta
            $imagesQuery->whereNull('folder_id');
            $showFolders = true;
        }

        $images = $imagesQuery->paginate(100);
        $folders = $user->folders()->orderBy('name')->get();
        $selectedFolderId = $folderId;

        return view('images.index', compact('images', 'folders', 'selectedFolderId', 'currentFolder', 'showFolders')); 
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

        $files = [];
        if ($request->hasFile('images')) {
            $files = $request->file('images');
        } elseif ($request->hasFile('image')) {
            $files = [$request->file('image')];
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

        foreach ($files as $index => $file) {
            // Armazena o arquivo em uma pasta única para o usuário (storage/app/public/user_images/{user_id})
            $path = $file->store('user_images/' . $user->id, 'public');

            // Garante que o arquivo seja público (importante!)
            Storage::disk('public')->setVisibility($path, 'public');

            $user->images()->create([
                'folder_id' => $folders[$index] ?? $defaultFolder,
                'path' => $path,
                'original_name' => $file->getClientOriginalName(),
                'title' => $titles[$index] ?? $defaultTitle,
                'description' => $descriptions[$index] ?? $defaultDescription,
                'size' => round($file->getSize() / 1024), // Salva o tamanho em KB
            ]);
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

        $user->images()
            ->whereIn('id', $validated['images'])
            ->update(['folder_id' => $validated['folder_id'] ?? null]);

        return back()->with('success', 'Imagens movidas com sucesso.');
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
        $errors = [];

        foreach ($images as $image) {
            try {
                Storage::disk('public')->delete($image->path);
                $image->delete();
            } catch (\Exception $e) {
                $errors[] = $image->id;
                Log::error("Erro ao excluir imagem {$image->id}: " . $e->getMessage());
            }
        }

        if (!empty($errors)) {
            return back()->with('error', 'Algumas mídias não puderam ser excluídas.');
        }

        return back()->with('success', 'Mídias excluídas com sucesso.');
    }

    // Exclui uma imagem
    public function destroy(Image $image)
    {
        // Segurança: Garante que o usuário só pode excluir suas próprias imagens
        if ($image->user_id !== Auth::id()) {
            abort(403);
        }

        try {
            // 1. Exclui o arquivo físico do disco
            Storage::disk('public')->delete($image->path);

            // 2. Exclui o registro do banco de dados
            $image->delete();
            
            return back()->with('success', 'Imagem excluída com sucesso.');
        } catch (\Exception $e) {
            Log::error("Erro ao excluir imagem {$image->id}: " . $e->getMessage());
            return back()->with('error', 'Não foi possível excluir a imagem.');
        }
    }
}
