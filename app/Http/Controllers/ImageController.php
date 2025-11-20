<?php

namespace App\Http\Controllers;

use App\Models\Image;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

class ImageController extends Controller
{
    // Mostra a galeria de imagens do usuário
    public function index()
    {
        $images = Auth::user()->images()->latest()->paginate(12); // Paginação para não carregar tudo de vez
        return view('images.index', compact('images')); 
    } 

    // Salva a nova imagem
    public function store(Request $request)
    {
        // Validação: obrigatório, deve ser imagem, tipos permitidos, tamanho máximo de 2MB (2048 KB)
        $request->validate([
            'image' => [
                'required',
                // 'file' garante que é um arquivo válido
                'file',
                // 'mimetypes' aceita uma lista de tipos MIME para vídeo e imagem
                'mimetypes:video/mp4,video/quicktime,image/jpeg,image/png,image/jpg,application/pdf,audio/mpeg,audio/mp3',
                // Aumenta o limite para 20MB (20480 KB) - ajuste conforme necessário
                'max:20480', 
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
            'path' => $path,
            'original_name' => $file->getClientOriginalName(),
            'size' => round($file->getSize() / 1024), // Salva o tamanho em KB
        ]);

        return back()->with('success', 'Imagem enviada com sucesso!');
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