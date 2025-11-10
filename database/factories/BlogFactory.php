<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Blog>
 */
class BlogFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $directory = storage_path('app/public/blogs');

        if (! is_dir($directory)) {
            mkdir($directory, 0755, true);
        }

        $image = $this->faker->image($directory, 1280, 720, null, false);

        return [
            'user_id' => User::factory(),
            'title' => $this->faker->sentence(8),
            'description' => $this->faker->paragraphs(3, true),
            'image_path' => 'blogs/'.$image,
        ];
    }
}
