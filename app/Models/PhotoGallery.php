<?php

namespace App\Models;

use App\Services\ThumbnailService;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class PhotoGallery extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'description', 'password', 'access_code', 'cover_photo_id'];

    protected $hidden = ['password'];

    protected static function booted(): void
    {
        static::creating(function (PhotoGallery $photoGallery) {
            $photoGallery->access_code ??= static::generateAccessCode();
        });

        static::created(function (PhotoGallery $photoGallery) {
            PhotoSection::create([
                'photo_gallery_id' => $photoGallery->id,
                'name' => $photoGallery->name,
                'position' => 1,
                'is_default' => true,
            ]);
        });

        static::deleting(function (PhotoGallery $photoGallery) {
            $photoGallery->loadMissing('photos');

            foreach ($photoGallery->photos as $photo) {
                Storage::disk('photo')->delete($photo->path);
                app(ThumbnailService::class)->delete($photo->path);
            }
        });
    }

    protected static function generateAccessCode(): string
    {
        do {
            $accessCode = Str::upper(Str::random(8));
        } while (static::query()->where('access_code', $accessCode)->exists());

        return $accessCode;
    }

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
}
