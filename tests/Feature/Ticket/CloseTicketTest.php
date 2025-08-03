<?php

namespace Tests\Feature\Ticket;

use App\Models\Ticket;
use App\Models\User;
use App\TicketAction;
use App\TicketStatus;
use App\UserRole;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class CloseTicketTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected string $url;

    protected User $auth_user;

    protected string $token;

    protected Ticket $ticket;

    protected User $assignee;

    protected array $valid_input;

    protected function setUp(): void
    {
        parent::setUp();

        $this->auth_user = User::factory()
            ->create(['role' => UserRole::Staff->value]);

        $this->token = $this->auth_user
            ->createToken('token')
            ->plainTextToken;

        $this->assignee = User::factory()
            ->create();

        $this->ticket = Ticket::factory()
            ->for($this->auth_user, 'reporter')
            ->for($this->assignee, 'assignee')
            ->create(['status' => TicketStatus::Resolved->value]);

        $this->url = route('tickets.close', ['ticket' => $this->ticket->id]);

        $this->valid_input = ['notes' => fake()->sentence()];
    }

    public function test_admins_can_close_resolved_ticket(): void
    {
        $this->auth_user->update(['role' => UserRole::Admin->value]);

        $response = $this
            ->withToken($this->token)
            ->patchJson($this->url, $this->valid_input);

        $response->assertOk();

        $this->assertDatabaseHas('tickets', [
            'id' => $this->ticket->id,
            'status' => TicketStatus::Closed->value,
        ]);

        $this->assertDatabaseHas('ticket_logs', [
            'ticket_id' => $this->ticket->id,
            'action' => TicketAction::StatusChange->value,
            'from_status' => TicketStatus::Resolved->value,
            'to_status' => TicketStatus::Closed->value,
        ]);
    }

    public function test_reporter_can_close_ticket(): void
    {
        $response = $this
            ->withToken($this->token)
            ->patchJson($this->url, $this->valid_input);

        $response->assertOk();

        $this->assertDatabaseHas('tickets', [
            'id' => $this->ticket->id,
            'status' => TicketStatus::Closed->value,
        ]);

        $this->assertDatabaseHas('ticket_logs', [
            'ticket_id' => $this->ticket->id,
            'action' => TicketAction::StatusChange->value,
            'from_status' => TicketStatus::Resolved->value,
            'to_status' => TicketStatus::Closed->value,
        ]);
    }

    #[DataProvider('unauthorizedUsersProvider')]
    public function test_unauthorized_users_cannot_close_ticket(
        string $role,
        int $reporter_id,
    ): void {
        $this->auth_user->update(['role' => $role]);
        $this->ticket->update(['reported_by_id' => $reporter_id]);

        $response = $this
            ->withToken($this->token)
            ->patchJson($this->url, $this->valid_input);

        $response->assertForbidden();
    }

    #[DataProvider('nonResolvedStatusProvider')]
    public function test_cannot_close_unresolved_ticket(string $status): void
    {
        $this->ticket->update(['status' => $status]);

        $response = $this
            ->withToken($this->token)
            ->patchJson($this->url, $this->valid_input);

        $response->assertForbidden();
    }

    public static function unauthorizedUsersProvider(): array
    {
        return [
            'staff but not reporter' => [UserRole::Staff->value, 999],
            'technician but not reporter' => [UserRole::Technician->value, 999],
        ];
    }

    public static function nonResolvedStatusProvider(): array
    {
        return [
            'pending' => [TicketStatus::Pending->value],
            'in progress' => [TicketStatus::InProgress->value],
            'closed' => [TicketStatus::Closed->value],
            'cancelled' => [TicketStatus::Cancelled->value],
        ];
    }
}
