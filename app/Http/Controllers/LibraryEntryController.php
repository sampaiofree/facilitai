<?php

namespace App\Http\Controllers;

use App\Models\LibraryEntry;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

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

        $entries->getCollection()->transform(function (LibraryEntry $entry) {
            if (!$entry->public_edit_token) {
                $entry->forceFill(['public_edit_token' => (string) Str::uuid()])->save();
            }
            return $entry;
        });

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
            'public_edit_password' => ['nullable', 'string', 'max:255'],
            'public_edit_enabled' => ['sometimes', 'boolean'],
        ]);

        $slug = $this->generateUniqueSlug($validated['title']);

        Auth::user()->libraryEntries()->create([
            'title' => $validated['title'],
            'content' => $validated['content'],
            'slug' => $slug,
            'public_edit_token' => (string) Str::uuid(),
            'public_edit_enabled' => $request->boolean('public_edit_enabled', true),
            'public_edit_password_hash' => $validated['public_edit_password']
                ? Hash::make($validated['public_edit_password'])
                : null,
        ]);

        return redirect()->route('library.index')->with('success', 'Texto salvo com sucesso.');
    }

    public function edit(LibraryEntry $library)
    {
        $this->authorizeEntry($library);

        if (!$library->public_edit_token) {
            $library->forceFill(['public_edit_token' => (string) Str::uuid()])->save();
        }

        return view('library.edit', ['entry' => $library]);
    }

    public function update(Request $request, LibraryEntry $library)
    {
        $this->authorizeEntry($library);

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'content' => ['required', 'string', 'max:20000'],
            'public_edit_password' => ['nullable', 'string', 'max:255'],
            'public_edit_enabled' => ['sometimes', 'boolean'],
        ]);

        $slug = $this->generateUniqueSlug($validated['title'], $library->id);

        $updateData = [
            'title' => $validated['title'],
            'content' => $validated['content'],
            'slug' => $slug,
            'public_edit_enabled' => $request->boolean('public_edit_enabled', $library->public_edit_enabled ?? true),
        ];

        if (!$library->public_edit_token) {
            $updateData['public_edit_token'] = (string) Str::uuid();
        }

        if (!empty($validated['public_edit_password'])) {
            $updateData['public_edit_password_hash'] = Hash::make($validated['public_edit_password']);
        }

        $library->update($updateData);

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

    public function publicEditForm(Request $request, string $token)
    {
        $entry = LibraryEntry::where('public_edit_token', $token)
            ->where('public_edit_enabled', true)
            ->firstOrFail();

        $sessionKey = $this->publicSessionKey($token);
        $canEdit = session()->get($sessionKey, false);
        $content = $canEdit ? old('content', $entry->content) : '';

        return response()->view('library.public-edit', [
            'entry' => $entry,
            'content' => $content,
            'canEdit' => $canEdit,
            'errorMessage' => session('public_edit_error'),
            'successMessage' => session('public_edit_success'),
        ])->header('X-Robots-Tag', 'noindex, nofollow');
    }

    public function publicAuthenticate(Request $request, string $token)
    {
        $entry = LibraryEntry::where('public_edit_token', $token)
            ->where('public_edit_enabled', true)
            ->firstOrFail();

        $validated = $request->validate([
            'password' => ['required', 'string'],
        ]);

        if (!$entry->public_edit_password_hash || !Hash::check($validated['password'], $entry->public_edit_password_hash)) {
            return back()
                ->withInput($request->except('password'))
                ->with('public_edit_error', 'Senha incorreta ou edição pública não configurada.');
        }

        session()->put($this->publicSessionKey($token), true);

        return redirect()->route('library.public.edit', $token)
            ->with('public_edit_success', 'Senha verificada. Pode editar o conteúdo.');
    }

    public function publicLogout(string $token)
    {
        session()->forget($this->publicSessionKey($token));

        return redirect()->route('library.public.edit', $token);
    }

    public function publicUpdate(Request $request, string $token)
    {
        $entry = LibraryEntry::where('public_edit_token', $token)
            ->where('public_edit_enabled', true)
            ->firstOrFail();

        if (!session()->get($this->publicSessionKey($token), false)) {
            return redirect()
                ->route('library.public.edit', $token)
                ->with('public_edit_error', 'Sessão expirada ou não autorizada. Informe a senha novamente.');
        }

        $validated = $request->validate([
            'content' => ['required', 'string', 'max:20000'],
        ]);

        $entry->update([
            'content' => $validated['content'],
        ]);

        return back()
            ->with('public_edit_success', 'Conteúdo atualizado com sucesso.');
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

    private function publicSessionKey(string $token): string
    {
        return 'public_edit_allowed_' . $token;
    }
}
