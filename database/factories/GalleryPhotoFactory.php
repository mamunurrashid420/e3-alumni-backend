<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\GalleryPhoto>
 */
class GalleryPhotoFactory extends Factory
{
    protected $model = \App\Models\GalleryPhoto::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'image' => 'gallery/'.fake()->unique()->uuid().'.jpg',
            'category' => fake()->randomElement(['Event', 'Old Memories', 'Our Picnic', 'Recent']),
            'sort_order' => fake()->numberBetween(0, 100),
        ];
    }
}
