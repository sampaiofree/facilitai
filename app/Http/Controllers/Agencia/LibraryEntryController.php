<?php

namespace App\Http\Controllers\Agencia;

use App\Http\Controllers\Controller;
use App\Models\LibraryEntry;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Illuminate\Validation\Rule;

class LibraryEntryController extends Controller
{
    public function index(): View
    {
        $user = Auth::user();
        $entries = LibraryEntry::with('cliente')
            ->where('user_id', $user->id)
            ->orderByDesc('created_at')
            ->paginate(20);

        $clientes = $user->clientes()->orderBy('nome')->get();

        return view('agencia.library.index', [
            'entries' => $entries,
            'clientes' => $clientes,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $user = Auth::user();

        $validated = $this->validateEntry($request, $user);

        LibraryEntry::create([
            'user_id' => $user->id,
            'cliente_id' => $validated['cliente_id'],
            'title' => $validated['title'],
            'content' => $validated['content'],
            'slug' => $this->makeUniqueSlug($validated['title']),
        ]);

        return redirect()
            ->route('agencia.library.index')
            ->with('success', 'Registro criado com sucesso.');
    }

    public function update(Request $request, LibraryEntry $libraryEntry): RedirectResponse
    {
        $user = Auth::user();

        if ($libraryEntry->user_id !== $user->id) {
            abort(403);
        }

        $validated = $this->validateEntry($request, $user);

        $libraryEntry->update([
            'cliente_id' => $validated['cliente_id'],
            'title' => $validated['title'],
            'content' => $validated['content'],
            'slug' => $this->makeUniqueSlug($validated['title'], $libraryEntry->id),
        ]);

        return redirect()
            ->route('agencia.library.index')
            ->with('success', 'Registro atualizado com sucesso.');
    }

    public function destroy(Request $request, LibraryEntry $libraryEntry): RedirectResponse
    {
        $user = Auth::user();

        if ($libraryEntry->user_id !== $user->id) {
            abort(403);
        }

        $libraryEntry->delete();

        return redirect()
            ->route('agencia.library.index')
            ->with('success', 'Registro removido com sucesso.');
    }

    private function validateEntry(Request $request, $user): array
    {
        return $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'content' => ['required', 'string', 'max:1500'],
            'cliente_id' => [
                'nullable',
                'integer',
                Rule::exists('clientes', 'id')->where('user_id', $user->id),
            ],
        ]);
    }

    private function makeUniqueSlug(string $title, ?int $exceptId = null): string
    {
        $base = Str::slug($title);
        if ($base === '') {
            $base = 'entrada';
        }

        $slug = $base;
        $counter = 1;

        while (LibraryEntry::where('slug', $slug)
            ->when($exceptId, fn ($query) => $query->where('id', '!=', $exceptId))
            ->exists()) {
            $slug = "{$base}-{$counter}";
            $counter++;
        }

        return $slug;
    }
}
