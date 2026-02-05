<?php

namespace App\Http\Controllers;

use App\Models\Folder;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class FolderController extends Controller
{
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'cliente_id' => ['nullable', 'integer', Rule::exists('clientes', 'id')->where('user_id', Auth::id())],
            'user_id' => ['nullable', 'integer', Rule::exists('users', 'id')],
            'storage_limit_mb' => ['nullable', 'integer', 'min:0'],
        ]);

        $targetUserId = $validated['user_id'] ?? Auth::id();
        $targetUser = User::with('plan')->find($targetUserId);
        if (!$targetUser) {
            return back()->withErrors(['user_id' => 'Usuário inválido.']);
        }

        $planLimitMb = (int) ($targetUser->plan?->storage_limit_mb ?? 0);
        $folderLimitMb = (int) ($validated['storage_limit_mb'] ?? 0);
        if ($folderLimitMb > $planLimitMb) {
            return back()->withErrors([
                'storage_limit_mb' => 'O limite informado excede o limite do plano do usuário.',
            ]);
        }

        $targetUser->folders()->create([
            'name' => $validated['name'],
            'storage_limit_mb' => $folderLimitMb,
            'cliente_id' => $validated['cliente_id'] ?? null,
        ]);

        return back()->with('success', 'Pasta criada com sucesso.');
    }

    public function update(Request $request, Folder $folder)
    {
        $this->authorizeFolder($folder);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'cliente_id' => ['nullable', 'integer', Rule::exists('clientes', 'id')->where('user_id', Auth::id())],
            'user_id' => ['nullable', 'integer', Rule::exists('users', 'id')],
            'storage_limit_mb' => ['nullable', 'integer', 'min:0'],
        ]);

        $targetUserId = $validated['user_id'] ?? $folder->user_id;
        $targetUser = User::with('plan')->find($targetUserId);
        if (!$targetUser) {
            return back()->withErrors(['user_id' => 'Usuário inválido.']);
        }

        $planLimitMb = (int) ($targetUser->plan?->storage_limit_mb ?? 0);
        $folderLimitMb = (int) ($validated['storage_limit_mb'] ?? $folder->storage_limit_mb ?? 0);
        if ($folderLimitMb > $planLimitMb) {
            return back()->withErrors([
                'storage_limit_mb' => 'O limite informado excede o limite do plano do usuário.',
            ]);
        }

        $folder->update([
            'name' => $validated['name'],
            'user_id' => $targetUserId,
            'storage_limit_mb' => $folderLimitMb,
            'cliente_id' => $validated['cliente_id'] ?? null,
        ]);

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
