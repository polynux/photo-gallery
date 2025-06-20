<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\Drivers\Gd\Driver;
use Intervention\Image\ImageManager;

class Photo extends Model
{
    protected $fillable = ['photo_gallery_id', 'path', 'alt'];

    public function photoGallery()
    {
        return $this->belongsTo(PhotoGallery::class);
    }

    public function galleries()
    {
        return $this->hasMany(PhotoGallery::class, 'cover_photo_id');
    }

    protected static function booted()
    {
        static::creating(function ($photo) {
            if (env('GENERATE_THUMBNAILS', true)) {
                $photo->generateThumbnail();
            }
        });

        static::updating(function ($photo) {
            if (env('GENERATE_THUMBNAILS', true) && $photo->isDirty('path')) {
                $photo->generateThumbnail();
            }
        });

        static::deleting(function ($photo) {
            $photo->deleteThumbnail();
        });
    }

    public function generateThumbnail()
    {
        try {
            $disk = Storage::disk('private');
            $manager = new ImageManager(new Driver());
            $image = $manager->read(Storage::disk('photo')->path($this->path));
            $image->scale(1920);

            $image->toJpeg(80);
            $thumbnailPath = $disk->path('thumbnails/' . $this->path);
            if (!file_exists(dirname($thumbnailPath))) {
                mkdir(dirname($thumbnailPath), 0755, true);
            }
            $image->save($thumbnailPath);
        } catch (\Exception $e) {
            report($e);
        }
    }

    public function deleteThumbnail()
    {
        if ($this->path && Storage::disk('private')->exists('thumbnails/' . $this->path)) {
            Storage::disk('private')->delete('thumbnails/' . $this->path);
        }
    }
}
