<?php

namespace Database\Factories;

use App\TicketPriorityLevel;
use App\TicketStatus;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Ticket>
 */
class TicketFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'title' => $this->faker->sentence(),
            'description' => $this->faker->paragraph(),
            'priority_level' => $this->faker->randomElement(TicketPriorityLevel::cases()),
            'status' => $this->faker->randomElement(TicketStatus::cases()),
            'reported_by_id' => $this->faker->randomNumber(),
            'assigned_to_id' => null,
            'department_name_snapshot' => $this->faker->name,
            'resolved_at' => null,
        ];
    }
}
