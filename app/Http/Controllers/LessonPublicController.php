<?php

namespace App\Http\Controllers;

use App\Models\Lesson;
use Illuminate\Http\Request;

class LessonPublicController extends Controller
{
    public function forPage(Request $request)
    {
        $path = $request->get('path', '/');
        $path = '/' . ltrim(parse_url($path, PHP_URL_PATH) ?? '/', '/');

        $locale = $request->get('locale', app()->getLocale() ?? 'pt-BR');
        $fallbackLocale = 'pt-BR';
        $normalizedPath = $this->normalizePath($path);

        $lessons = Lesson::active()
            ->whereIn('locale', [$locale, $fallbackLocale])
            ->ordered()
            ->get()
            ->filter(function (Lesson $lesson) use ($normalizedPath) {
                $lessonPath = $this->normalizePath($lesson->page_match);

                if ($lesson->match_type === 'exact') {
                    return $lessonPath === $normalizedPath;
                }

                return str_starts_with($normalizedPath, $lessonPath);
            })
            ->sortBy(function (Lesson $lesson) use ($locale) {
                return [
                    $lesson->locale === $locale ? 0 : 1,
                    $lesson->position,
                    $lesson->id,
                ];
            })
            ->values();

        return response()->json([
            'lessons' => $lessons->map(function (Lesson $lesson) {
                return [
                    'id' => $lesson->id,
                    'title' => $lesson->title,
                    'embed_url' => $lesson->embed_url,
                    'support_html' => $lesson->support_html,
                    'locale' => $lesson->locale,
                    'page_match' => $lesson->page_match,
                ];
            }),
        ]);
    }

    private function normalizePath(string $path): string
    {
        $normalized = '/' . ltrim($path, '/');
        return rtrim($normalized, '/') ?: '/';
    }
}
