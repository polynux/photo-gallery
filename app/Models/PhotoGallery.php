<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class PhotoGallery extends Model
{
    protected $fillable = ['name', 'description', 'password', 'access_code', 'cover_photo_id'];

    protected $hidden = ['password'];

    /**
     * @return HasMany<Photo,PhotoGallery>
     */
    public function photos(): HasMany
    {
        return $this->hasMany(Photo::class);
    }

    /**
     * @return HasMany<PhotoSection,PhotoGallery>
     */
    public function sections(): HasMany
    {
        return $this->hasMany(PhotoSection::class)->orderBy('position');
    }

    /**
     * @return BelongsTo<Photo,PhotoGallery>
     */
    public function coverPhoto(): BelongsTo
    {
        return $this->belongsTo(Photo::class, 'cover_photo_id');
    }

    protected static function booted(): void
    {
        static::creating(function (PhotoGallery $photoGallery) {
            $photoGallery->access_code = Str::random(8);
        });

        static::created(function (PhotoGallery $photoGallery) {
            PhotoSection::create([
                'photo_gallery_id' => $photoGallery->id,
                'name' => $photoGallery->name,
                'position' => 1,
                'is_default' => true,
            ]);
        });
    }
}
