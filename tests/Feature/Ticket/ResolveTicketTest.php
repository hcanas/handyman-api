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

class ResolveTicketTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected string $url;

    protected User $auth_user;

    protected string $token;

    protected User $reporter;

    protected Ticket $target_ticket;

    protected array $valid_input;

    protected function setUp(): void
    {
        parent::setUp();

        $this->auth_user = User::factory()
            ->create(['role' => UserRole::Technician->value]);

        $this->token = $this->auth_user
            ->createToken('token')->plainTextToken;

        $this->reporter = User::factory()
            ->create();

        $this->target_ticket = Ticket::factory()
            ->for($this->reporter, 'reporter')
            ->for($this->auth_user, 'assignee')
            ->create(['status' => TicketStatus::InProgress->value]);

        $this->url = route('tickets.resolve', ['ticket' => $this->target_ticket->id]);

        $this->valid_input = ['notes' => fake()->sentence()];
    }

    public function test_assigned_technician_can_resolve_ticket(): void
    {
        $response = $this
            ->withToken($this->token)
            ->patchJson($this->url, $this->valid_input);

        $response->assertOk();

        $this->assertDatabaseHas('tickets', [
            'id' => $this->target_ticket->id,
            'assigned_to_id' => $this->auth_user->id,
            'status' => TicketStatus::Resolved->value,
        ]);

        $this->assertDatabaseHas('ticket_logs', [
            'ticket_id' => $this->target_ticket->id,
            'user_id' => $this->auth_user->id,
            'action' => TicketAction::StatusChange->value,
            'from_status' => TicketStatus::InProgress->value,
            'to_status' => TicketStatus::Resolved->value,
            'notes' => $this->valid_input['notes'],
        ]);
    }

    public function test_other_technicians_cannot_resolve_ticket(): void
    {
        $this->target_ticket->update(['assigned_to_id' => User::factory()->create()->id]);

        $response = $this
            ->withToken($this->token)
            ->patchJson($this->url, $this->valid_input);

        $response->assertForbidden();
    }

    #[DataProvider('nonTechniciansProvider')]
    public function test_non_technicians_cannot_resolve_ticket(?string $role): void
    {
        $this->auth_user->update(['role' => $role]);

        $response = $this
            ->withToken($this->token)
            ->patchJson($this->url, $this->valid_input);

        $response->assertForbidden();
    }

    public function test_guests_cannot_resolve_ticket(): void
    {
        $response = $this->patchJson($this->url, $this->valid_input);

        $response->assertUnauthorized();
    }

    #[DataProvider('invalidNotesProvider')]
    public function test_invalid_notes_fails_validation(string $notes): void
    {
        $invalid_input = ['notes' => $notes];

        $response = $this->withToken($this->token)->patchJson($this->url, $invalid_input);

        $response->assertUnprocessable();
        $response->assertJsonValidationErrors(['notes']);
    }

    public static function nonTechniciansProvider(): array
    {
        return [
            'staff' => [UserRole::Staff->value],
            'admin' => [UserRole::Admin->value],
            'undesignated' => [null],
        ];
    }

    public static function invalidNotesProvider(): array
    {
        return [
            'too long' => [str_repeat('a', 1001)],
        ];
    }
}
