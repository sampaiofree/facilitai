<?php

namespace App\Http\Controllers;

use App\Models\Folder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class FolderController extends Controller
{
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
        ]);

        Auth::user()->folders()->create($validated);

        return back()->with('success', 'Pasta criada com sucesso.');
    }

    public function update(Request $request, Folder $folder)
    {
        $this->authorizeFolder($folder);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
        ]);

        $folder->update($validated);

        return back()->with('success', 'Pasta renomeada com sucesso.');
    }

    public function destroy(Folder $folder)
    {
        $this->authorizeFolder($folder);

        if ($folder->images()->exists()) {
            return back()->with('error', 'Não é possível excluir: a pasta ainda contém arquivos.');
        }

        $folder->delete();

        return back()->with('success', 'Pasta excluída com sucesso.');
    }

    private function authorizeFolder(Folder $folder): void
    {
        if ($folder->user_id !== Auth::id()) {
            abort(403);
        }
    }
}
