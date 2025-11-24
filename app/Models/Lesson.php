<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Lesson extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'video_url',
        'support_html',
        'page_match',
        'match_type',
        'locale',
        'position',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'position' => 'integer',
    ];

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('position')->orderBy('id');
    }

    public function getPageMatchesAttribute(): array
    {
        return collect(explode(',', (string) $this->page_match))
            ->map(fn ($path) => trim($path))
            ->filter()
            ->map(function (string $path) {
                $normalized = '/' . ltrim($path, '/');
                return rtrim($normalized, '/') ?: '/';
            })
            ->unique()
            ->values()
            ->all();
    }

    public function getEmbedUrlAttribute(): string
    {
        $youtubeId = $this->extractYoutubeId($this->video_url);

        if ($youtubeId) {
            return sprintf('https://www.youtube.com/embed/%s', $youtubeId);
        }

        return $this->video_url;
    }

    private function extractYoutubeId(string $url): ?string
    {
        if (preg_match('/(?:v=|youtu\\.be\\/|embed\\/)([\\w-]{11})/i', $url, $matches)) {
            return $matches[1];
        }

        return null;
    }
}
