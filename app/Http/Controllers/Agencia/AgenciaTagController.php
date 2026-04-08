<?php

namespace App\Http\Controllers\Agencia;

use App\Http\Controllers\Controller;
use App\Models\Cliente;
use App\Models\Tag;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class AgenciaTagController extends Controller
{
    public function index(Request $request)
    {
        $tags = Tag::with('cliente')
            ->where('user_id', $request->user()->id)
            ->orderBy('name')
            ->get();

        $clientes = Cliente::where('user_id', $request->user()->id)
            ->orderBy('nome')
            ->get();

        return view('agencia.tags.index', compact('tags', 'clientes'));
    }

    public function store(Request $request)
    {
        if ($request->input('cliente_id') === '') {
            $request->merge(['cliente_id' => null]);
        }

        $existingTag = null;

        if ($request->filled('tag_id')) {
            $existingTag = Tag::where('user_id', $request->user()->id)
                ->findOrFail((int) $request->input('tag_id'));
        }

        $nameRule = Rule::unique('tags', 'name')
            ->where(fn ($query) => $query->where('user_id', $request->user()->id));

        if ($existingTag) {
            $nameRule = $nameRule->ignore($existingTag->id);
        }

        $data = $request->validate([
            'tag_id' => ['nullable', 'integer'],
            'name' => ['required', 'string', 'max:50', $nameRule],
            'color' => ['nullable', 'string', 'max:50'],
            'description' => ['nullable', 'string'],
            'cliente_id' => [
                'nullable',
                'integer',
                Rule::exists('clientes', 'id')->where('user_id', $request->user()->id),
            ],
        ], [
            'name.unique' => 'Já existe uma tag com esse nome na sua conta.',
        ]);

        $payload = [
            'user_id' => $request->user()->id,
            'name' => $data['name'],
            'color' => $data['color'] ?? null,
            'description' => $data['description'] ?? null,
            'cliente_id' => $data['cliente_id'] ?? null,
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
                ->withErrors(['name' => 'Já existe uma tag com esse nome na sua conta.'])
                ->withInput();
        }

        return redirect()->route('agencia.tags.index')->with('success', $message);
    }

    public function destroy(Request $request, Tag $tag)
    {
        abort_unless($tag->user_id === $request->user()->id, 403);
        $tag->delete();
        return redirect()->route('agencia.tags.index')->with('success', 'Tag removida com sucesso.');
    }
}
