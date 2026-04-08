<?php

namespace App\Http\Controllers;

use App\Models\Tag;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class TagController extends Controller
{
    private const LEGACY_READ_ONLY_MESSAGE = 'Tags globais legadas estão em modo somente leitura. Crie e gerencie tags vinculadas a um cliente.';

    public function index()
    {
        $tags = Auth::user()
            ->tags()
            ->whereNull('cliente_id')
            ->latest()
            ->get();

        return view('tags.index', compact('tags'));
    }

    public function store(Request $request)
    {
        return $this->rejectLegacyWrite($request);
    }

    public function update(Request $request, Tag $tag)
    {
        $this->authorizeTag($tag);
        return $this->rejectLegacyWrite($request);
    }

    public function destroy(Request $request, Tag $tag)
    {
        $this->authorizeTag($tag);
        return $this->rejectLegacyWrite($request);
    }

    private function authorizeTag(Tag $tag): void
    {
        if ($tag->user_id !== Auth::id() || $tag->cliente_id !== null) {
            abort(403);
        }
    }

    private function rejectLegacyWrite(Request $request)
    {
        if ($request->expectsJson()) {
            return response()->json([
                'message' => self::LEGACY_READ_ONLY_MESSAGE,
            ], 403);
        }

        return redirect()
            ->route('tags.index')
            ->with('warning', self::LEGACY_READ_ONLY_MESSAGE);
    }
}
