<?php

namespace Database\Factories;

use App\Models\Photo;
use App\Models\PhotoGallery;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Photo>
 */
class PhotoFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'photo_gallery_id' => PhotoGallery::factory(),
            'photo_section_id' => fn (array $attributes): ?int => PhotoGallery::query()
                ->find($attributes['photo_gallery_id'])
                ?->sections()
                ->where('is_default', true)
                ->value('id'),
            'path' => fn (array $attributes): string => $attributes['photo_gallery_id'].'/'.Str::lower(fake()->bothify('photo-####??')).'.png',
            'alt' => fake()->sentence(),
            'position' => null,
        ];
    }

    public function forGallery(PhotoGallery $gallery): static
    {
        return $this->state(fn (): array => [
            'photo_gallery_id' => $gallery->id,
            'photo_section_id' => $gallery->sections()->where('is_default', true)->value('id'),
            'path' => $gallery->id.'/'.Str::lower(fake()->bothify('photo-####??')).'.png',
        ]);
    }
}
