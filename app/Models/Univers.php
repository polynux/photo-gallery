<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Univers extends Model
{
    protected $fillable = ['path', 'title', 'description', 'position'];

    protected static function booted(): void
    {
        static::creating(function (Univers $univers) {
            if ($univers->position === null) {
                $univers->position = Univers::max('position') + 1 ?? 1;
            }
        });
    }
}
