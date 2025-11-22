<?php

namespace App\Http\Controllers;

use App\Models\LibraryEntry;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use League\CommonMark\CommonMarkConverter;

class LibraryEntryController extends Controller
{
    public function index(Request $request)
    {
        $user = Auth::user();
        $search = $request->input('q');
        $sort = $request->input('sort', 'created_at');
        $direction = $request->input('direction', 'desc');

        $sortField = in_array($sort, ['title', 'created_at'], true) ? $sort : 'created_at';
        $sortDirection = $direction === 'asc' ? 'asc' : 'desc';

        $entries = $user->libraryEntries()
            ->when($search, function ($query) use ($search) {
                $query->where(function ($q) use ($search) {
                    $q->where('title', 'like', "%{$search}%")
                      ->orWhere('content', 'like', "%{$search}%");
                });
            })
            ->orderBy($sortField, $sortDirection)
            ->paginate(12)
            ->withQueryString();

        return view('library.index', compact('entries', 'search', 'sortField', 'sortDirection'));
    }

    public function create()
    {
        return view('library.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'content' => ['required', 'string', 'max:20000'],
        ]);

        $slug = $this->generateUniqueSlug($validated['title']);

        Auth::user()->libraryEntries()->create([
            'title' => $validated['title'],
            'content' => $validated['content'],
            'slug' => $slug,
        ]);

        return redirect()->route('library.index')->with('success', 'Texto salvo com sucesso.');
    }

    public function edit(LibraryEntry $library)
    {
        $this->authorizeEntry($library);

        return view('library.edit', ['entry' => $library]);
    }

    public function update(Request $request, LibraryEntry $library)
    {
        $this->authorizeEntry($library);

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'content' => ['required', 'string', 'max:20000'],
        ]);

        $slug = $this->generateUniqueSlug($validated['title'], $library->id);

        $library->update([
            'title' => $validated['title'],
            'content' => $validated['content'],
            'slug' => $slug,
        ]);

        return redirect()->route('library.index')->with('success', 'Texto atualizado com sucesso.');
    }

    public function destroy(LibraryEntry $library)
    {
        $this->authorizeEntry($library);
        $library->delete();

        return redirect()->route('library.index')->with('success', 'Texto excluído.');
    }

    public function publicShow(string $slug)
    {
        $entry = LibraryEntry::where('slug', $slug)->firstOrFail();

        return response($entry->content)
            ->header('Content-Type', 'text/plain; charset=utf-8')
            ->header('X-Robots-Tag', 'noindex, nofollow');
    }

    private function generateUniqueSlug(string $title, ?int $ignoreId = null): string
    {
        $base = Str::slug($title);
        if ($base === '') {
            $base = 'texto';
        }

        $slug = $base;
        $suffix = 1;

        while ($this->slugExists($slug, $ignoreId)) {
            $suffix++;
            $slug = "{$base}-{$suffix}";
        }

        return $slug;
    }

    private function slugExists(string $slug, ?int $ignoreId = null): bool
    {
        return LibraryEntry::where('slug', $slug)
            ->when($ignoreId, fn ($q) => $q->where('id', '!=', $ignoreId))
            ->exists();
    }

    private function authorizeEntry(LibraryEntry $entry): void
    {
        if ($entry->user_id !== Auth::id()) {
            abort(403);
        }
    }
}
