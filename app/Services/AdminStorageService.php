<?php

namespace App\Services;

use App\Models\Image;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

class AdminStorageService
{
    /** Inventory only the library and inbound WhatsApp directories. */
    public function inventory(array $filters): array
    {
        $images = Image::with('user:id,name,email')->get()->keyBy('path');
        $users = User::select('id', 'name', 'email')->get()->keyBy('id');
        $locations = [['public', 'user_images', 'Biblioteca']];
        foreach (['uazapi_media', 'evolution_oficial_media', 'whatsapp_cloud_media'] as $directory) {
            $locations[] = [config('media.disk', 'local'), $directory, 'WhatsApp'];
        }

        $files = [];
        $errors = [];
        foreach ($locations as [$diskName, $directory, $origin]) {
            try {
                $disk = Storage::disk($diskName);
                foreach ($disk->allFiles($directory) as $path) {
                    if (! $this->isAllowedPath($diskName, $path)) {
                        continue;
                    }

                    $image = $origin === 'Biblioteca' ? $images->get($path) : null;
                    $user = $image?->user;
                    if (! $user && $origin === 'Biblioteca' && preg_match('#^user_images/(\d+)/#', $path, $matches)) {
                        $user = $users->get((int) $matches[1]);
                    }

                    $modified = $disk->lastModified($path);
                    $date = Carbon::createFromTimestamp($modified, 'America/Sao_Paulo')->format('Y-m-d');
                    if (($filters['user'] ?? '') === 'unknown' && $user) {
                        continue;
                    }
                    if (is_numeric($filters['user'] ?? '') && (int) $filters['user'] !== $user?->id) {
                        continue;
                    }
                    if ((! empty($filters['date_from']) && $date < $filters['date_from']) ||
                        (! empty($filters['date_to']) && $date > $filters['date_to'])) {
                        continue;
                    }

                    $id = hash('sha256', $diskName.'|'.$path);
                    $files[$id] = [
                        'id' => $id,
                        'disk' => $diskName,
                        'path' => $path,
                        'name' => $image?->original_name ?: basename($path),
                        'origin' => $origin,
                        'user_id' => $user?->id,
                        'user_name' => $user?->name,
                        'user_email' => $user?->email,
                        'size' => $disk->size($path),
                        'modified' => $modified,
                    ];
                }
            } catch (\Throwable $exception) {
                report($exception);
                $errors[] = "Não foi possível concluir a leitura de {$directory} no disco {$diskName}.";
            }
        }

        uasort($files, fn ($a, $b) => ($b['modified'] <=> $a['modified']) ?: strcmp($a['id'], $b['id']));

        return ['files' => $files, 'errors' => $errors];
    }

    public function isAllowedPath(string $diskName, string $path): bool
    {
        if (str_contains($path, '..') || str_contains($path, '\\') || str_starts_with($path, '/')) {
            return false;
        }

        $allowed = $diskName === 'public' && str_starts_with($path, 'user_images/');
        if ($diskName === config('media.disk', 'local')) {
            $allowed = $allowed || preg_match('#^(uazapi_media|evolution_oficial_media|whatsapp_cloud_media)/#', $path);
        }
        if (! $allowed) {
            return false;
        }

        // A local symlink must never expose files outside its permitted directory.
        if (config("filesystems.disks.{$diskName}.driver") === 'local') {
            $disk = Storage::disk($diskName);
            $directory = explode('/', $path)[0];
            $root = realpath($disk->path(''));
            $actual = realpath($disk->path($path));
            if (! $root || ! $actual || ! str_starts_with($actual, $root.DIRECTORY_SEPARATOR.$directory.DIRECTORY_SEPARATOR)) {
                return false;
            }
        }

        return true;
    }

    public function deleteFile(array $file): void
    {
        if (! $this->isAllowedPath($file['disk'], $file['path'])) {
            throw new RuntimeException('Arquivo indisponível ou fora dos diretórios permitidos.');
        }

        $disk = Storage::disk($file['disk']);
        if (! $disk->exists($file['path']) || $disk->size($file['path']) !== $file['size'] ||
            $disk->lastModified($file['path']) !== $file['modified']) {
            throw new RuntimeException('O arquivo mudou ou não está mais disponível. Atualize a listagem.');
        }

        if (! $disk->delete($file['path'])) {
            throw new RuntimeException('Não foi possível excluir o arquivo do storage.');
        }

        if ($file['disk'] === 'public' && str_starts_with($file['path'], 'user_images/')) {
            DB::transaction(function () use ($file) {
                $images = Image::where('path', $file['path'])->get();
                $userIds = $images->pluck('user_id')->unique();
                $folderIds = $images->pluck('folder_id')->filter()->unique();
                Image::whereIn('id', $images->pluck('id'))->delete();
                foreach ($userIds as $userId) {
                    $usedMb = (int) ceil(((int) Image::where('user_id', $userId)->sum('size')) / 1024);
                    User::whereKey($userId)->update(['storage_used_mb' => $usedMb]);
                }
                app(FolderStorageService::class)->recalculateForFolders($folderIds);
            });
        }

        Log::info('Mídia excluída pelo administrador no storage.', [
            'admin_id' => auth()->id(),
            'disk' => $file['disk'],
            'path' => $file['path'],
        ]);
    }
}
