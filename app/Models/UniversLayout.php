<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UniversLayout extends Model
{
    protected $fillable = ['mode', 'version', 'layout'];

    public static function singleton(): self
    {
        return static::query()->firstOrCreate(
            ['id' => 1],
            [
                'mode' => 'generic',
                'version' => 1,
                'layout' => ['preset' => null, 'items' => []],
            ],
        );
    }

    protected function casts(): array
    {
        return [
            'version' => 'integer',
            'layout' => 'array',
        ];
    }
}
