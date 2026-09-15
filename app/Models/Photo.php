<?php

namespace App\Models;

use App\Jobs\GeneratePhotoThumbnail;
use App\Services\PhotoPositionService;
use App\Services\ThumbnailService;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;
use Throwable;

class Photo extends Model
{
    use HasFactory;

    protected $fillable = ['photo_gallery_id', 'photo_section_id', 'path', 'alt', 'position', 'width', 'height'];

    protected ?int $previousSectionId = null;

    protected static function booted(): void
    {
        static::creating(function (Photo $photo) {
            if ($photo->position === null && $photo->photo_section_id) {
                $photo->position = app(PhotoPositionService::class)->appendPosition($photo->photo_section_id);
            }
        });

        static::updating(function (Photo $photo) {
            if ($photo->isDirty('photo_section_id') && $photo->previousSectionId === null) {
                $photo->previousSectionId = $photo->getOriginal('photo_section_id');

                $photo->position = app(PhotoPositionService::class)->appendPosition($photo->photo_section_id);
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
                    app(ThumbnailService::class)->delete($originalPath);
                }
            }

            if ($photo->wasChanged('path') && $photo->shouldGenerateThumbnail()) {
                GeneratePhotoThumbnail::dispatch($photo)->afterCommit();
            }
        });

        static::deleting(function (Photo $photo) {
            Storage::disk('photo')->delete($photo->path);
            app(ThumbnailService::class)->delete($photo->path);

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
     * Generate the WebP derivatives (grid thumbnail and display image) for this photo.
     *
     * @throws Throwable
     */
    public function generateThumbnail(): void
    {
        app(ThumbnailService::class)->generate($this);
    }

    /**
     * Delete the stored derivatives (grid thumbnail, display image and legacy file).
     */
    public function deleteThumbnail(): void
    {
        app(ThumbnailService::class)->delete($this->path);
    }

    protected function casts(): array
    {
        return [
            'width' => 'integer',
            'height' => 'integer',
        ];
    }

    protected function reindexSectionPositions(int $sectionId): void
    {
        app(PhotoPositionService::class)->reindexAllInSection($sectionId);
    }

    protected function shouldGenerateThumbnail(): bool
    {
        return config('gallery.generate_thumbnails');
    }
}
