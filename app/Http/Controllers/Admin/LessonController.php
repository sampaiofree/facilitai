<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Lesson;
use Illuminate\Http\Request;

class LessonController extends Controller
{
    public function index()
    {
        $lessons = Lesson::orderBy('locale')
            ->ordered()
            ->paginate(20);

        return view('admin.lessons.index', compact('lessons'));
    }

    public function create()
    {
        $lesson = new Lesson([
            'locale' => app()->getLocale() ?? 'pt-BR',
            'match_type' => 'prefix',
            'position' => (Lesson::max('position') ?? 0) + 1,
        ]);

        return view('admin.lessons.create', compact('lesson'));
    }

    public function store(Request $request)
    {
        $data = $this->validatedData($request);

        Lesson::create($data);

        return redirect()->route('admin.lessons.index')
            ->with('success', 'Aula criada com sucesso.');
    }

    public function edit(Lesson $lesson)
    {
        return view('admin.lessons.edit', compact('lesson'));
    }

    public function update(Request $request, Lesson $lesson)
    {
        $data = $this->validatedData($request);

        $lesson->update($data);

        return redirect()->route('admin.lessons.index')
            ->with('success', 'Aula atualizada com sucesso.');
    }

    public function destroy(Lesson $lesson)
    {
        $lesson->delete();

        return redirect()->route('admin.lessons.index')
            ->with('success', 'Aula removida com sucesso.');
    }

    private function validatedData(Request $request): array
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'video_url' => ['required', 'string', 'max:255'],
            'support_html' => ['nullable', 'string'],
            'page_match' => ['required', 'string', 'max:255'],
            'match_type' => ['required', 'in:exact,prefix'],
            //'locale' => ['required', 'string', 'max:10'],
            'position' => ['nullable', 'integer', 'min:0'],
            'is_active' => ['sometimes', 'boolean'],
        ]);

        $data['page_match'] = $this->normalizePath($data['page_match']);
        $data['position'] = $data['position'] ?? (Lesson::max('position') ?? 0) + 1;
        $data['is_active'] = $request->boolean('is_active', true);

        return $data;
    }

    private function normalizePath(string $path): string
    {
        $normalized = '/' . ltrim($path, '/');
        return rtrim($normalized, '/') ?: '/';
    }
}
