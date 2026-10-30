<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\AdminStorageService;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class StorageController extends Controller
{
    public function index(Request $request, AdminStorageService $storage)
    {
        $filters = $request->validate([
            'user' => ['nullable', 'string', function ($attribute, $value, $fail) {
                if ($value !== 'unknown' && (! ctype_digit($value) || ! User::whereKey($value)->exists())) {
                    $fail('Selecione um usuário válido.');
                }
            }],
            'date_from' => ['nullable', 'date_format:Y-m-d'],
            'date_to' => ['nullable', 'date_format:Y-m-d', Rule::when($request->filled('date_from'), 'after_or_equal:date_from')],
            'snapshot' => ['nullable', 'uuid'],
            'page' => ['nullable', 'integer', 'min:1'],
        ]);
        $filters = array_filter(collect($filters)->only(['user', 'date_from', 'date_to'])->all(), fn ($value) => $value !== null && $value !== '');

        $token = $request->input('snapshot');
        $snapshot = $token ? Cache::get($this->cacheKey($request, $token)) : null;
        if (! $snapshot || $snapshot['filters'] !== $filters) {
            $snapshot = $storage->inventory($filters) + ['filters' => $filters];
            $token = (string) Str::uuid();
            Cache::put($this->cacheKey($request, $token), $snapshot, now()->addMinutes(30));
        }

        $allFiles = collect($snapshot['files'])->values();
        $page = max(1, min((int) $request->input('page', 1), max(1, (int) ceil($allFiles->count() / 50))));
        $files = new LengthAwarePaginator($allFiles->forPage($page, 50)->values(), $allFiles->count(), 50, $page, [
            'path' => route('adm.storage.index'),
            'query' => $filters + ['snapshot' => $token],
        ]);

        return view('admin.storage.index', [
            'files' => $files,
            'totalSize' => $allFiles->sum('size'),
            'users' => User::orderBy('name')->get(['id', 'name', 'email']),
            'filters' => $filters,
            'snapshotToken' => $token,
            'inventoryErrors' => $snapshot['errors'],
        ]);
    }

    public function show(Request $request, string $file, AdminStorageService $storage)
    {
        $snapshot = $this->snapshot($request);
        $entry = $snapshot['files'][$file] ?? null;
        abort_unless($entry && $storage->isAllowedPath($entry['disk'], $entry['path']), 404);
        $disk = Storage::disk($entry['disk']);
        abort_unless($disk->exists($entry['path']), 404);

        $mime = $disk->mimeType($entry['path']) ?: 'application/octet-stream';
        $inline = in_array($mime, [
            'image/jpeg', 'image/png', 'image/gif', 'image/webp', 'image/avif',
            'audio/mpeg', 'audio/ogg', 'audio/wav', 'audio/x-wav', 'audio/mp4', 'audio/webm',
            'video/mp4', 'video/webm', 'video/ogg', 'application/pdf',
        ], true);
        $stream = $disk->readStream($entry['path']);
        abort_unless(is_resource($stream), 404);

        return response()->stream(function () use ($stream) {
            try {
                fpassthru($stream);
            } finally {
                fclose($stream);
            }
        }, 200, [
            'Content-Type' => $mime,
            'Content-Disposition' => \Symfony\Component\HttpFoundation\HeaderUtils::makeDisposition(
                $inline ? 'inline' : 'attachment', basename($entry['name']), 'media'
            ),
            'X-Content-Type-Options' => 'nosniff',
            'Cache-Control' => 'private, no-store',
            'Content-Security-Policy' => "sandbox; default-src 'none'",
        ]);
    }

    public function destroy(Request $request, string $file, AdminStorageService $storage)
    {
        $snapshot = $this->snapshot($request);
        abort_unless(isset($snapshot['files'][$file]), 404);

        return $this->delete($request, $storage, $snapshot, [$file]);
    }

    public function bulkDestroy(Request $request, AdminStorageService $storage)
    {
        $snapshot = $this->snapshot($request);
        $data = $request->validate([
            'mode' => ['required', Rule::in(['selected', 'all'])],
            'selection' => ['required', 'json'],
        ]);
        $selection = json_decode($data['selection']);
        if (! is_array($selection) || ! array_is_list($selection) ||
            collect($selection)->contains(fn ($id) => ! is_string($id) || ! isset($snapshot['files'][$id]))) {
            throw ValidationException::withMessages(['selection' => 'Seleção de arquivos inválida. Atualize a listagem.']);
        }

        // In all mode, selection contains the explicitly unchecked exceptions.
        $ids = $data['mode'] === 'all'
            ? array_values(array_diff(array_keys($snapshot['files']), $selection))
            : array_values(array_unique($selection));
        if (! $ids) {
            throw ValidationException::withMessages(['selection' => 'Selecione pelo menos um arquivo.']);
        }

        return $this->delete($request, $storage, $snapshot, $ids);
    }

    private function delete(Request $request, AdminStorageService $storage, array $snapshot, array $ids)
    {
        $deleted = 0;
        $failed = 0;
        foreach ($ids as $id) {
            try {
                $storage->deleteFile($snapshot['files'][$id]);
                $deleted++;
            } catch (\Throwable $exception) {
                Log::error('Falha ao concluir exclusão de mídia pelo administrador.', [
                    'admin_id' => $request->user()->id,
                    'disk' => $snapshot['files'][$id]['disk'],
                    'path' => $snapshot['files'][$id]['path'],
                    'exception' => $exception,
                ]);
                $failed++;
            }
        }
        Cache::forget($this->cacheKey($request, $request->input('snapshot')));
        $response = redirect()->route('adm.storage.index', $snapshot['filters']);
        if ($deleted) {
            $response->with('success', "{$deleted} arquivo(s) excluído(s).");
        }
        if ($failed) {
            $response->with('error', "Não foi possível concluir a exclusão de {$failed} arquivo(s). Consulte os logs e atualize a listagem antes de tentar novamente.");
        }

        return $response;
    }

    private function snapshot(Request $request): array
    {
        $request->validate(['snapshot' => ['required', 'uuid']]);
        $snapshot = Cache::get($this->cacheKey($request, $request->input('snapshot')));
        if (! $snapshot) {
            throw ValidationException::withMessages(['snapshot' => 'A listagem expirou. Atualize a página e selecione os arquivos novamente.']);
        }

        return $snapshot;
    }

    private function cacheKey(Request $request, string $token): string
    {
        return 'admin-storage:'.$request->user()->id.':'.$token;
    }
}
