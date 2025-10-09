<?php

namespace App\Http\Services\Admin;

use App\Models\MasterGallery;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class GalleryMaintenanceService
{
    /**
     * Delete DB records for gallery photos that no longer exist on disk.
     *
     * @return array{checked:int,deleted:int,masters:array<int,int>}
     */
    public function cleanupMissingFiles(): array
    {
        $checked = 0;
        $deleted = 0;
        $perMasterDeleted = [];

        MasterGallery::query()->orderBy('id')->chunkById(500, function ($chunk) use (&$checked, &$deleted, &$perMasterDeleted) {
            foreach ($chunk as $photo) {
                $checked++;
                $path = (string) $photo->photo;
                if (! Storage::disk('public')->exists($path)) {
                    DB::transaction(function () use ($photo, &$deleted, &$perMasterDeleted) {
                        $masterId = (int) $photo->master_id;
                        $photo->delete();
                        $deleted++;
                        $perMasterDeleted[$masterId] = ($perMasterDeleted[$masterId] ?? 0) + 1;
                    });
                }
            }
        });

        return [
            'checked' => $checked,
            'deleted' => $deleted,
            'masters' => $perMasterDeleted,
        ];
    }
}
