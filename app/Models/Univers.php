<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Univers extends Model
{
    protected $fillable = [
        'path',
        'source_path',
        'title',
        'description',
        'position',
        'focal_x',
        'focal_y',
        'processing_status',
        'derivatives',
    ];

    protected function casts(): array
    {
        return [
            'focal_x' => 'float',
            'focal_y' => 'float',
            'derivatives' => 'array',
        ];
    }

    public function getSourcePathAttribute(?string $value): string
    {
        return $value ?: $this->path;
    }

    protected static function booted(): void
    {
        static::creating(function (Univers $univers) {
            if ($univers->position === null) {
                $univers->position = (Univers::max('position') ?? 0) + 1;
            }
        });
    }
}
