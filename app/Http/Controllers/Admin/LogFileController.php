<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class LogFileController extends Controller
{
    private const MAX_BYTES = 512000; // 500 KB

    public function index(Request $request)
    {
        $dir = storage_path('logs');
        $files = [];
        $dirMissing = false;

        if (!File::exists($dir)) {
            $dirMissing = true;
        } else {
            foreach (File::files($dir) as $file) {
                if (!$file->isFile()) {
                    continue;
                }

                $size = $file->getSize();
                $modified = $file->getMTime();

                $files[] = [
                    'name' => $file->getFilename(),
                    'size' => $size,
                    'size_human' => $this->formatBytes($size),
                    'modified_at' => date('d/m/Y H:i', $modified),
                    'modified_ts' => $modified,
                ];
            }

            usort($files, function (array $a, array $b) {
                return $b['modified_ts'] <=> $a['modified_ts'];
            });

            $search = trim((string) $request->input('nome', ''));
            if ($search !== '') {
                $files = array_values(array_filter($files, function (array $file) use ($search) {
                    return stripos($file['name'], $search) !== false;
                }));
            }
        }

        return view('admin.logs.index', [
            'files' => $files,
            'dirMissing' => $dirMissing,
            'maxBytes' => self::MAX_BYTES,
            'maxBytesHuman' => $this->formatBytes(self::MAX_BYTES),
            'search' => $request->input('nome', ''),
        ]);
    }

    public function show(string $file): JsonResponse
    {
        $path = $this->resolveLogPath($file);

        if ($path === null || !File::exists($path) || !File::isFile($path)) {
            return response()->json(['message' => 'Arquivo não encontrado.'], 404);
        }

        $size = File::size($path);
        $content = $this->readTail($path, self::MAX_BYTES, $size);

        return response()->json([
            'name' => basename($path),
            'size' => $size,
            'size_human' => $this->formatBytes($size),
            'modified_at' => date('d/m/Y H:i', File::lastModified($path)),
            'content' => $content,
            'truncated' => $size > self::MAX_BYTES,
            'bytes_shown' => min($size, self::MAX_BYTES),
            'bytes_shown_human' => $this->formatBytes(min($size, self::MAX_BYTES)),
            'max_bytes' => self::MAX_BYTES,
        ], 200, [], JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
    }

    public function download(string $file): BinaryFileResponse
    {
        $path = $this->resolveLogPath($file);

        if ($path === null || !File::exists($path) || !File::isFile($path)) {
            abort(404);
        }

        return response()->download($path);
    }

    private function resolveLogPath(string $file): ?string
    {
        if ($file === '') {
            return null;
        }

        if ($file !== basename($file)) {
            return null;
        }

        if (str_contains($file, '..') || str_contains($file, '/') || str_contains($file, '\\')) {
            return null;
        }

        return storage_path('logs'.DIRECTORY_SEPARATOR.$file);
    }

    private function readTail(string $path, int $maxBytes, int $size): string
    {
        if ($size <= $maxBytes) {
            return File::get($path);
        }

        $handle = fopen($path, 'rb');
        if ($handle === false) {
            return '';
        }

        $offset = $size - $maxBytes;
        if ($offset > 0) {
            fseek($handle, $offset);
        }

        $data = stream_get_contents($handle);
        fclose($handle);

        return $data === false ? '' : $data;
    }

    private function formatBytes(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $index = 0;
        $value = (float) $bytes;

        while ($value >= 1024 && $index < count($units) - 1) {
            $value /= 1024;
            $index++;
        }

        $decimals = $index === 0 ? 0 : 2;
        $formatted = number_format($value, $decimals, '.', '');
        $formatted = rtrim(rtrim($formatted, '0'), '.');

        return $formatted.' '.$units[$index];
    }
}
