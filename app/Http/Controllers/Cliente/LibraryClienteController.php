<?php

namespace App\Http\Controllers\Cliente;

use App\Http\Controllers\Controller;
use App\Models\LibraryEntry;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class LibraryClienteController extends Controller
{
    public function index(): View
    {
        $cliente = auth('client')->user();

        $entries = LibraryEntry::where('cliente_id', $cliente->id)
            ->orderByDesc('created_at')
            ->paginate(20);

        return view('cliente.library.index', [
            'entries' => $entries,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $cliente = auth('client')->user();
        $userId = $cliente->user_id; // dono da conta

        $validated = $this->validateEntry($request);

        LibraryEntry::create([
            'user_id' => $userId,
            'cliente_id' => $cliente->id,
            'title' => $validated['title'],
            'content' => $validated['content'],
            'slug' => $this->makeUniqueSlug($validated['title']),
        ]);

        return redirect()
            ->route('cliente.library.index')
            ->with('success', 'Registro criado com sucesso.');
    }

    public function update(Request $request, LibraryEntry $libraryEntry): RedirectResponse
    {
        $cliente = auth('client')->user();
        $this->ensureOwner($libraryEntry, $cliente->id);

        $validated = $this->validateEntry($request);

        $libraryEntry->update([
            'title' => $validated['title'],
            'content' => $validated['content'],
            'slug' => $this->makeUniqueSlug($validated['title'], $libraryEntry->id),
        ]);

        return redirect()
            ->route('cliente.library.index')
            ->with('success', 'Registro atualizado com sucesso.');
    }

    public function destroy(Request $request, LibraryEntry $libraryEntry): RedirectResponse
    {
        $cliente = auth('client')->user();
        $this->ensureOwner($libraryEntry, $cliente->id);

        $libraryEntry->delete();

        return redirect()
            ->route('cliente.library.index')
            ->with('success', 'Registro removido com sucesso.');
    }

    private function validateEntry(Request $request): array
    {
        return $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'content' => ['required', 'string', 'max:1500'],
        ]);
    }

    private function ensureOwner(LibraryEntry $entry, int $clienteId): void
    {
        if ($entry->cliente_id !== $clienteId) {
            abort(403);
        }
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
            ->when($exceptId, fn($query) => $query->where('id', '!=', $exceptId))
            ->exists()) {
            $slug = "{$base}-{$counter}";
            $counter++;
        }

        return $slug;
    }
}
