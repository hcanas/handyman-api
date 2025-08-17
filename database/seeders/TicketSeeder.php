<?php

namespace Database\Seeders;

use App\Models\Ticket;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class TicketSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Ticket::factory()
            ->count(50)
            ->create([
                'reported_by_id' => 1,
            ]);

        Ticket::factory()
            ->for(User::factory()->create(), 'reporter')
            ->count(50)
            ->create();
    }
}
