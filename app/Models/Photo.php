<?php

namespace App\Models;

use App\Jobs\GeneratePhotoThumbnail;
use App\Services\ThumbnailService;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class Photo extends Model
{
    use HasFactory;

    protected $fillable = ['photo_gallery_id', 'photo_section_id', 'path', 'alt', 'position'];

    protected ?int $previousSectionId = null;

    protected static function booted(): void
    {
        static::creating(function (Photo $photo) {
            if ($photo->position === null && $photo->photo_section_id) {
                $photo->position = (Photo::where('photo_section_id', $photo->photo_section_id)
                    ->max('position') ?? 0) + 1;
            }
        });

        static::updating(function (Photo $photo) {
            if ($photo->isDirty('photo_section_id')) {
                $photo->previousSectionId = $photo->getOriginal('photo_section_id');

                $photo->position = (Photo::where('photo_section_id', $photo->photo_section_id)
                    ->max('position') ?? 0) + 1;
            }
        });

        static::created(function (Photo $photo) {
            if ($photo->shouldGenerateThumbnail()) {
                GeneratePhotoThumbnail::dispatch($photo)->afterCommit();
            }
        });

        static::updated(function (Photo $photo) {
            if ($photo->previousSectionId) {
                $photo->reindexSectionPositions($photo->previousSectionId);
            }

            if ($photo->wasChanged('path')) {
                $originalPath = $photo->getOriginal('path');

                if ($originalPath) {
                    Storage::disk('photo')->delete($originalPath);
                    Storage::disk('thumbnails')->delete($originalPath);
                }
            }

            if ($photo->wasChanged('path') && $photo->shouldGenerateThumbnail()) {
                GeneratePhotoThumbnail::dispatch($photo)->afterCommit();
            }
        });

        static::deleting(function (Photo $photo) {
            Storage::disk('photo')->delete($photo->path);
            Storage::disk('thumbnails')->delete($photo->path);

            $sectionId = $photo->photo_section_id;
            if ($sectionId) {
                Photo::where('photo_section_id', $sectionId)
                    ->where('position', '>', $photo->position)
                    ->decrement('position');
            }
        });
    }

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
     * Generate a JPEG thumbnail for this photo (max 1920px, never upscaled).
     *
     * @throws \Throwable
     */
    public function generateThumbnail(): void
    {
        app(ThumbnailService::class)->generate($this);
    }

    /**
     * Delete the stored thumbnail for this photo.
     */
    public function deleteThumbnail(): void
    {
        app(ThumbnailService::class)->delete($this->path);
    }

    protected function reindexSectionPositions(int $sectionId): void
    {
        $photos = Photo::where('photo_section_id', $sectionId)
            ->orderBy('position')
            ->get();

        foreach ($photos as $index => $photo) {
            if ($photo->position !== $index + 1) {
                $photo->update(['position' => $index + 1]);
            }
        }
    }

    protected function shouldGenerateThumbnail(): bool
    {
        return config('gallery.generate_thumbnails');
    }
}
