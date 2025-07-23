<?php

namespace Database\Factories;

use App\CommentType;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Comment>
 */
class CommentFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $type = $this->faker->randomElement(CommentType::cases());

        return [
            'ticket_id' => null,
            'user_id' => null,
            'body' => $type === CommentType::Text
                ? $this->faker->paragraph()
                : null,
            'type' => $type,
        ];
    }
}
