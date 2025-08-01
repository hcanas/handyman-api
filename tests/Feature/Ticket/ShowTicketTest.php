<?php

namespace Tests\Feature\Ticket;

use App\Models\Ticket;
use App\Models\TicketLog;
use App\Models\User;
use App\TicketAction;
use App\UserRole;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class ShowTicketTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected string $url;

    protected User $auth_user;

    protected string $token;

    protected Ticket $target_ticket;

    protected array $expected_data_structure;

    protected function setUp(): void
    {
        parent::setUp();

        $this->auth_user = User::factory()->create();
        $this->token = $this->auth_user->createToken('token')->plainTextToken;

        $this->target_ticket = Ticket::factory()->create();

        $this->url = route('tickets.show', ['ticket' => $this->target_ticket->id]);

        $this->expected_data_structure = [
            'id',
            'title',
            'description',
            'priority_level',
            'reported_by',
            'assigned_to',
            'department_name',
            'resolved_at',
            'status',
        ];
    }

    public function test_admins_can_view_ticket(): void
    {
        $this->auth_user->update(['role' => UserRole::Admin->value]);

        $response = $this->withToken($this->token)->get($this->url);

        $response->assertOk();
        $response->assertJsonStructure(['data' => $this->expected_data_structure]);
    }

    public function test_reporter_can_view_ticket(): void
    {
        $this->target_ticket->update(['reported_by_id' => $this->auth_user->id]);

        $response = $this->withToken($this->token)->get($this->url);

        $response->assertOk();
        $response->assertJsonStructure(['data' => $this->expected_data_structure]);
    }

    public function test_assignee_can_view_ticket(): void
    {
        $this->target_ticket->update(['assigned_to_id' => $this->auth_user->id]);

        $response = $this->withToken($this->token)->get($this->url);

        $response->assertOk();
        $response->assertJsonStructure(['data' => $this->expected_data_structure]);
    }

    public function test_previous_assignees_can_view_ticket(): void
    {
        $assigned_to_id = $this->auth_user->id;
        $reassigned_to_id = User::factory()->create()->id;

        $this->target_ticket->update(['assigned_to_id' => $reassigned_to_id]);

        TicketLog::factory()
            ->for($this->target_ticket)
            ->count(2)
            ->state(function () use ($assigned_to_id, $reassigned_to_id) {
                static $assigned = false;

                $user_id = $assigned ? $reassigned_to_id : $assigned_to_id;

                $assigned = !$assigned;

                return [
                    'user_id' => $user_id,
                    'action' => TicketAction::ReceivedAssignment,
                ];
            })
            ->create();

        $response = $this->withToken($this->token)->getJson($this->url);

        $response->assertOk();
        $response->assertJsonStructure(['data' => $this->expected_data_structure]);
    }

    public function test_guests_cannot_view_ticket(): void
    {
        $response = $this->getJson($this->url);

        $response->assertUnauthorized();
    }
}
