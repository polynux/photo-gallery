<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\Drivers\Gd\Driver;
use Intervention\Image\ImageManager;

class Photo extends Model
{
    protected $fillable = ['photo_gallery_id', 'photo_section_id', 'path', 'alt', 'position'];

    /**
     * @return BelongsTo<PhotoGallery,Photo>
     */
    public function photoGallery(): BelongsTo
    {
        return $this->belongsTo(PhotoGallery::class);
    }

    /**
     * @return BelongsTo<PhotoSection,Photo>
     */
    public function photoSection(): BelongsTo
    {
        return $this->belongsTo(PhotoSection::class);
    }

    /**
     * @return HasMany<PhotoGallery,Photo>
     */
    public function galleries()
    {
        return $this->hasMany(PhotoGallery::class, 'cover_photo_id');
    }

    protected static function booted(): void
    {
        static::creating(function (Photo $photo) {
            if ($photo->position === null && $photo->photo_section_id) {
                $photo->position = Photo::where('photo_section_id', $photo->photo_section_id)
                    ->max('position') + 1 ?? 1;
            }

            if (env('GENERATE_THUMBNAILS', true)) {
                \App\Jobs\GeneratePhotoThumbnail::dispatch($photo);
            }
        });

        static::updating(function (Photo $photo) {
            if (env('GENERATE_THUMBNAILS', true) && $photo->isDirty('path')) {
                \App\Jobs\GeneratePhotoThumbnail::dispatch($photo);
            }
        });

        static::deleting(function (Photo $photo) {
            $photo->deleteThumbnail();
        });
    }

    public function generateThumbnail(): void
    {
        try {
            $disk = Storage::disk('private');
            $thumbnailPath = $disk->path('thumbnails/'.$this->path);

            if (file_exists($thumbnailPath)) {
                return;
            }

            $manager = new ImageManager(new Driver);
            $image = $manager->read(Storage::disk('photo')->path($this->path));
            $image->scale(1920);

            $image->toJpeg(80);

            if (! file_exists(dirname($thumbnailPath))) {
                mkdir(dirname($thumbnailPath), 0755, true);
            }
            $image->save($thumbnailPath);
        } catch (\Exception $e) {
            report($e);
        }
    }

    public function deleteThumbnail(): void
    {
        if ($this->path && Storage::disk('private')->exists('thumbnails/'.$this->path)) {
            Storage::disk('private')->delete('thumbnails/'.$this->path);
        }
    }
}
