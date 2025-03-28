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
     * @return BelongsTo<Photo,PhotoGallery>
     */
    public function coverPhoto(): BelongsTo
    {
        return $this->belongsTo(Photo::class, 'cover_photo_id');
    }

    protected static function booted(): void
    {
        static::creating(function ($photoGallery) {
            $photoGallery->access_code = Str::random(8);
        });
    }
}
