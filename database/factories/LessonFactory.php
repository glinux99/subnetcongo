<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Lesson>
 */
namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class LessonFactory extends Factory
{
    public function definition(): array
    {
        $title = $this->faker->sentence(rand(3, 6));
        return[
            'title' => $title,
            'slug' => Str::slug($title) . '-' . uniqid(),
            'content' => '<p>' . implode('</p><p>', $this->faker->paragraphs(4)) . '</p>',
            'video_url' => $this->faker->boolean(70) ? 'https://www.youtube.com/watch?v=dQw4w9WgXcQ' : null, // 70% ont une vidéo
            'duration_minutes' => $this->faker->numberBetween(5, 45),
            'order' => $this->faker->numberBetween(1, 10),
            'is_free_preview' => $this->faker->boolean(15), // 15% de leçons gratuites
            // module_id sera passé depuis le Seeder
        ];
    }
}
