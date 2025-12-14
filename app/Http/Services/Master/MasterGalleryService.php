<?php

namespace App\Http\Services\Master;

use App\Helpers\PhotoHelper;
use App\Models\Master;
use App\Models\MasterGallery;
use Illuminate\Support\Facades\Storage;

class MasterGalleryService
{
    public function __construct(
        private readonly PhotoHelper $photoHelper
    ) {}

    /**
     * Add photos to master gallery.
     *
     * @param  array<string>  $photos  Array of base64 encoded images
     */
    public function addPhotos(Master $master, array $photos): void
    {
        foreach ($photos as $img) {
            $fl = !empty($master->app) ? (string) $master->app : null;
            $path = $this->photoHelper->saveBase64($img, $fl);
            MasterGallery::create([
                'master_id' => $master->id,
                'photo' => $path,
            ]);
        }
    }

    /**
     * Delete photo from master gallery.
     *
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException
     */
    public function deletePhoto(Master $master, int $photoId): void
    {
        $photo = MasterGallery::where('master_id', $master->id)
            ->where('id', $photoId)
            ->firstOrFail();

        // Delete file from storage
        Storage::disk('public')->delete($photo->photo);

        // Delete record from database
        $photo->delete();
    }
}
