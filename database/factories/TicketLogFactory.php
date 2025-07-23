<?php

namespace Database\Factories;

use App\TicketAction;
use App\TicketStatus;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\TicketLog>
 */
class TicketLogFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'ticket_id' => null,
            'user_id' => null,
            'action' => $this->faker->randomElement(TicketAction::cases()),
            'from_status' => TicketStatus::Pending,
            'to_status' => TicketStatus::InProgress,
            'notes' => null,
        ];
    }
}
