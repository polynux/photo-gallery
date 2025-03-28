<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

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
}
