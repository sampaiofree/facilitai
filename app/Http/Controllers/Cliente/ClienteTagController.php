<?php

namespace App\Http\Controllers\Cliente;

use App\Http\Controllers\Controller;
use App\Models\Tag;
use App\Support\TagScope;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\Request;

class ClienteTagController extends Controller
{
    public function index()
    {
        $cliente = auth('client')->user();

        $tags = Tag::where('cliente_id', $cliente->id)
            ->where('user_id', $cliente->user_id)
            ->orderBy('name')
            ->get();

        return view('cliente.tags.index', compact('tags'));
    }

    public function store(Request $request)
    {
        $cliente = auth('client')->user();
        $existingTag = null;

        if ($request->filled('tag_id')) {
            $existingTag = Tag::where('cliente_id', $cliente->id)
                ->where('user_id', $cliente->user_id)
                ->findOrFail((int) $request->input('tag_id'));
        }

        $data = $request->validate([
            'tag_id' => ['nullable', 'integer'],
            'name' => ['required', 'string', 'max:50'],
            'color' => ['nullable', 'string', 'max:50'],
            'description' => ['nullable', 'string', 'max:255'],
        ]);

        $duplicateMessage = TagScope::duplicateMessage($cliente->id);

        if (TagScope::hasConflict($cliente->user_id, $data['name'], $cliente->id, $existingTag?->id)) {
            return back()
                ->withErrors(['name' => $duplicateMessage])
                ->withInput();
        }

        $payload = [
            'user_id' => $cliente->user_id,
            'cliente_id' => $cliente->id,
            'name' => $data['name'],
            'color' => $data['color'] ?? null,
            'description' => $data['description'] ?? null,
        ];

        try {
            if ($existingTag) {
                $existingTag->update($payload);
                $message = 'Tag atualizada com sucesso.';
            } else {
                Tag::create($payload);
                $message = 'Tag criada com sucesso.';
            }
        } catch (UniqueConstraintViolationException) {
            return back()
                ->withErrors(['name' => $duplicateMessage])
                ->withInput();
        }

        return redirect()->route('cliente.tags.index')->with('success', $message);
    }

    public function destroy(Request $request, Tag $tag)
    {
        $cliente = auth('client')->user();

        abort_unless($tag->cliente_id === $cliente->id, 403);

        $tag->delete();

        return redirect()->route('cliente.tags.index')->with('success', 'Tag removida com sucesso.');
    }
}
