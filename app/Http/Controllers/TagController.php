<?php

namespace App\Http\Controllers;

use App\Models\Tag;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class TagController extends Controller
{
    public function index()
    {
        $tags = Auth::user()->tags()->latest()->get();

        return view('tags.index', compact('tags'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:50'],
            'color' => ['nullable', 'string', 'max:20'],
            'description' => ['nullable', 'string', 'max:255'],
        ]);

        $tag = Tag::updateOrCreate(
            ['user_id' => Auth::id(), 'name' => $validated['name']],
            [
                'color' => $validated['color'] ?? null,
                'description' => $validated['description'] ?? null,
            ]
        );

        if ($request->expectsJson()) {
            return response()->json($tag);
        }

        return redirect()->route('tags.index')->with('success', 'Tag salva com sucesso.');
    }

    public function update(Request $request, Tag $tag)
    {
        $this->authorizeTag($tag);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:50'],
            'color' => ['nullable', 'string', 'max:20'],
            'description' => ['nullable', 'string', 'max:255'],
        ]);

        // Garantir unicidade por usuário/nome
        $exists = Tag::where('user_id', Auth::id())
            ->where('name', $validated['name'])
            ->where('id', '!=', $tag->id)
            ->exists();

        if ($exists) {
            return redirect()->back()->with('warning', 'Já existe uma tag com esse nome.');
        }

        $tag->update($validated);

        return redirect()->route('tags.index')->with('success', 'Tag atualizada.');
    }

    public function destroy(Tag $tag)
    {
        $this->authorizeTag($tag);

        $tag->delete();

        return redirect()->route('tags.index')->with('success', 'Tag removida.');
    }

    private function authorizeTag(Tag $tag): void
    {
        if ($tag->user_id !== Auth::id()) {
            abort(403);
        }
    }
}
