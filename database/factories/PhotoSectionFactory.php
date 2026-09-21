<?php

namespace Database\Factories;

use App\Models\PhotoGallery;
use App\Models\PhotoSection;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PhotoSection>
 */
class PhotoSectionFactory extends Factory
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
            'name' => fake()->words(2, true),
            'position' => 2,
            'is_default' => false,
        ];
    }
}
