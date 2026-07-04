<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Course>
 */
namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class CourseFactory extends Factory
{
    public function definition(): array
    {
        $title = $this->faker->sentence(rand(4, 8));
        return[
            'title' => $title,
            'slug' => Str::slug($title) . '-' . uniqid(),
            'description' => $this->faker->paragraphs(3, true),
            'thumbnail_path' => 'lms/thumbnails/seed-' . rand(1, 1000) . '.jpg',
            'price' => $this->faker->randomElement([0, 19.99, 49.99, 99.99, 149.00]),
            'promo_price' => $this->faker->optional(0.3)->randomElement([9.99, 14.99, 29.99]),
            'level' => $this->faker->randomElement(['beginner', 'intermediate', 'advanced', 'all']),
            'status' => $this->faker->randomElement(['published', 'published', 'draft', 'pending']),
            'is_featured' => $this->faker->boolean(20), // 20% de chances d'être mis en avant
            'short_info' => $this->faker->sentence(),
            'tags' => json_encode($this->faker->words(3)),
            'seo_metadata' => json_encode([
                'title' => $title,
                'description' => $this->faker->sentence(),
                'keywords' => implode(',', $this->faker->words(5))
            ]),
            'category_id' => \App\Models\Category::inRandomOrder()->first()?->id ?? null,
            'created_by' => \App\Models\User::role('admin')->inRandomOrder()->first()?->id ?? null,
        ];
    }
}
