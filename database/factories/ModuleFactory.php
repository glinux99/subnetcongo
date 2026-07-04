<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Module>
 */
namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class ModuleFactory extends Factory
{
    public function definition(): array
    {
        return[
            'title' => 'Module : ' . $this->faker->catchPhrase(),
            'description' => $this->faker->sentence(),
            'order' => $this->faker->numberBetween(1, 10),
            // course_id sera passé depuis le Seeder
        ];
    }
}
