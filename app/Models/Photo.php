<?php

namespace App\Models;

use App\Jobs\GeneratePhotoThumbnail;
use Exception;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\Drivers\Gd\Driver;
use Intervention\Image\ImageManager;

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

            if ($photo->wasChanged('path') && $photo->shouldGenerateThumbnail()) {
                GeneratePhotoThumbnail::dispatch($photo)->afterCommit();
            }
        });

        static::deleting(function (Photo $photo) {
            $photo->deleteThumbnail();

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

    public function generateThumbnail(): void
    {
        try {
            $disk = Storage::disk('private');
            $thumbnailPath = $disk->path('thumbnails/' . $this->path);

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
        } catch (Exception $e) {
            report($e);
        }
    }

    public function deleteThumbnail(): void
    {
        if ($this->path && Storage::disk('private')->exists('thumbnails/' . $this->path)) {
            Storage::disk('private')->delete('thumbnails/' . $this->path);
        }
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
