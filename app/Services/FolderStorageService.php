<?php

namespace App\Services;

use App\Models\Folder;
use App\Models\Image;
use Illuminate\Support\Collection;

class FolderStorageService
{
    public function recalculateForFolder(?Folder $folder): int
    {
        if (!$folder) {
            return 0;
        }

        $usedMb = $this->calculateUsedMb($folder->id);
        $folder->forceFill(['storage_used_mb' => $usedMb])->save();

        return $usedMb;
    }

    public function recalculateForFolderId(?int $folderId): int
    {
        if (!$folderId) {
            return 0;
        }

        $folder = Folder::find($folderId);
        if (!$folder) {
            return 0;
        }

        return $this->recalculateForFolder($folder);
    }

    /**
     * @param array<int>|Collection $folderIds
     */
    public function recalculateForFolders($folderIds): void
    {
        $ids = collect($folderIds)
            ->filter()
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values();

        if ($ids->isEmpty()) {
            return;
        }

        $folders = Folder::whereIn('id', $ids)->get();
        foreach ($folders as $folder) {
            $this->recalculateForFolder($folder);
        }
    }

    protected function calculateUsedMb(int $folderId): int
    {
        $totalKb = (int) Image::where('folder_id', $folderId)->sum('size');
        return (int) ceil($totalKb / 1024);
    }
}
