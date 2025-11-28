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

        $images = $imagesQuery->paginate(12);
        $folders = $user->folders()->orderBy('name')->get();
        $selectedFolderId = $folderId;

        return view('images.index', compact('images', 'folders', 'selectedFolderId', 'currentFolder', 'showFolders')); 
    } 

    // Salva a nova imagem
    public function store(Request $request)
    {
        // Validação: obrigatório, deve ser imagem, tipos permitidos, tamanho máximo de 10MB (10240 KB)
        $request->validate([
            'image' => [
                'required',
                // 'file' garante que é um arquivo válido
                'file',
                // 'mimetypes' aceita uma lista de tipos MIME para vídeo e imagem
                'mimetypes:video/mp4,video/quicktime,image/jpeg,image/png,image/jpg,application/pdf,audio/mpeg,audio/mp3',
                // Limite de 10MB (10240 KB) - ajuste conforme necessario
                'max:10240', 
            ],
            'title' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:500'],
            'folder_id' => [
                'nullable',
                'integer',
                Rule::exists('folders', 'id')->where('user_id', Auth::id()),
            ],
        ]);

        $file = $request->file('image');
        $user = Auth::user();

        // Garante que a pasta do usuário existe com permissão correta
        $directory = 'user_images/' . $user->id;
        Storage::disk('public')->makeDirectory($directory);
        
        // Armazena o arquivo em uma pasta única para o usuário (storage/app/public/user_images/{user_id})
        $path = $file->store('user_images/' . $user->id, 'public');

        // Garante que o arquivo seja público (importante!)
        Storage::disk('public')->setVisibility($path, 'public');

        // Cria o registro no banco de dados
        $user->images()->create([
            'folder_id' => $request->input('folder_id'),
            'path' => $path,
            'original_name' => $file->getClientOriginalName(),
            'title' => $request->input('title'),
            'description' => $request->input('description'),
            'size' => round($file->getSize() / 1024), // Salva o tamanho em KB
        ]);

        return back()->with('success', 'Mídia enviada com sucesso!');
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
