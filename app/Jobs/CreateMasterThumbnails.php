<?php

namespace App\Jobs;

use App\Models\Master;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class CreateMasterThumbnails implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 3;
    public $timeout = 600;

    public function __construct(private readonly array $masterIds) {}

    public function handle(): int
    {
        $size = (int) config('images.thumb.size', 20);
        $dir = trim((string) config('images.thumb.dir', 'thumbnails'), '/');
        $done = 0;

        foreach ($this->masterIds as $id) {
            /** @var Master|null $master */
            $master = Master::find($id);
            if (! $master) { Log::debug('Thumb: master missing', ['id' => $id]); continue; }
            if (! $master->photo) { Log::debug('Thumb: no master photo', ['id' => $id]); continue; }

            try {
                $srcPath = $master->photo; // relative to public disk
                if (! Storage::disk('public')->exists($srcPath)) { Log::debug('Thumb: photo path missing', ['id' => $id, 'path' => $srcPath]); continue; }
                $binary = Storage::disk('public')->get($srcPath);

                // Create GD image
                $img = @imagecreatefromstring($binary);
                if (! $img) { Log::debug('Thumb: failed to create image from binary', ['id' => $id, 'path' => $srcPath]); continue; }
                $width = imagesx($img);
                $height = imagesy($img);
                $side = min($width, $height);
                $srcX = (int) max(0, ($width - $side) / 2);
                $srcY = (int) max(0, ($height - $side) / 2);

                $crop = imagecreatetruecolor($side, $side);
                imagecopyresampled($crop, $img, 0, 0, $srcX, $srcY, $side, $side, $side, $side);

                $thumb = imagecreatetruecolor($size, $size);
                imagecopyresampled($thumb, $crop, 0, 0, 0, 0, $size, $size, $side, $side);

                // Encode as PNG for consistency and sharpness
                ob_start();
                imagepng($thumb, null, 9);
                $thumbBinary = (string) ob_get_clean();

                imagedestroy($thumb);
                imagedestroy($crop);
                imagedestroy($img);

                $thumbPath = $dir . '/' . $master->id . '.png';
                Storage::disk('public')->put($thumbPath, $thumbBinary);

                $master->main_thumb_generated = true;
                $master->main_thumb_url = $thumbPath;
                $master->save();
                $done++;
            } catch (\Throwable $e) {
                Log::warning('Failed to build thumbnail', ['master_id' => $id, 'error' => $e->getMessage()]);
            }
        }
        return $done;
    }
}


