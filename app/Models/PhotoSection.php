<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PhotoSection extends Model
{
    protected $fillable = ['photo_gallery_id', 'name', 'position', 'is_default'];

    protected static function booted(): void
    {
        static::creating(function (PhotoSection $section) {
            if ($section->position === null) {
                $section->position = PhotoSection::where('photo_gallery_id', $section->photo_gallery_id)
                    ->max('position') + 1 ?? 1;
            }
        });

        static::deleting(function (PhotoSection $section) {
            if ($section->is_default) {
                return false;
            }
        });
    }

    /**
     * @return BelongsTo<PhotoGallery,PhotoSection>
     */
    public function photoGallery(): BelongsTo
    {
        return $this->belongsTo(PhotoGallery::class);
    }

    /**
     * @return HasMany<Photo,PhotoSection>
     */
    public function photos(): HasMany
    {
        return $this->hasMany(Photo::class)->orderBy('position');
    }
}
