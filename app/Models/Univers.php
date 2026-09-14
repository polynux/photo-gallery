<?php

namespace App\Models;

use App\Jobs\GenerateUniversDerivatives;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\URL;

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

    protected static function booted(): void
    {
        static::saving(function (Univers $univers): void {
            if ($univers->isDirty('path') && ! $univers->isDirty('source_path')) {
                $univers->source_path = $univers->path;
            }
        });

        static::creating(function (Univers $univers) {
            if ($univers->position === null) {
                $univers->position = (Univers::max('position') ?? 0) + 1;
            }
        });

        static::created(function (Univers $univers): void {
            GenerateUniversDerivatives::dispatch($univers)->afterCommit();
        });

        static::updated(function (Univers $univers): void {
            if ($univers->wasChanged(['path', 'source_path'])) {
                GenerateUniversDerivatives::dispatch($univers)->afterCommit();
            }
        });
    }

    public function getSourcePathAttribute(?string $value): string
    {
        return $value ?: $this->path;
    }

    public function getPreviewUrlAttribute(): string
    {
        return URL::temporarySignedRoute('univers.source', now()->addMinutes(30), $this);
    }

    protected function casts(): array
    {
        return [
            'focal_x' => 'float',
            'focal_y' => 'float',
            'derivatives' => 'array',
        ];
    }
}
