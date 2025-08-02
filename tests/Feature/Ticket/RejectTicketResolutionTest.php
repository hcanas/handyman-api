<?php

namespace Tests\Feature\Ticket;

use App\Models\Ticket;
use App\Models\User;
use App\Notifications\TicketResolutionRejectedNotification;
use App\TicketAction;
use App\TicketStatus;
use App\UserRole;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\Notification;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class RejectTicketResolutionTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected string $url;

    protected User $auth_user;

    protected string $token;

    protected User $assignee;

    protected Ticket $ticket;

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

        $this->url = route('tickets.reject-resolution', ['ticket' => $this->ticket->id]);

        $this->valid_input = ['notes' => fake()->sentence()];
    }

    public function test_reporter_can_reject_ticket_resolution(): void
    {
        Notification::fake();

        $response = $this
            ->withToken($this->token)
            ->patchJson($this->url, $this->valid_input);

        $response->assertOk();

        $this->assertDatabaseHas('tickets', [
            'id' => $this->ticket->id,
            'reported_by_id' => $this->auth_user->id,
            'status' => TicketStatus::InProgress->value,
        ]);

        $this->assertDatabaseHas('ticket_logs', [
            'ticket_id' => $this->ticket->id,
            'user_id' => $this->auth_user->id,
            'action' => TicketAction::StatusChange->value,
            'from_status' => TicketStatus::Resolved->value,
            'to_status' => TicketStatus::InProgress->value,
            'notes' => $this->valid_input['notes'],
        ]);

        Notification::assertSentTo(
            $this->assignee,
            TicketResolutionRejectedNotification::class,
            function ($notification) {
                return $notification->ticket->id === $this->ticket->id;
            }
        );
    }

    public function test_cannot_reject_if_status_is_not_resolved(): void
    {
        $this->ticket->update(['status' => TicketStatus::InProgress->value]);

        $response = $this
            ->withToken($this->token)
            ->patchJson($this->url, $this->valid_input);

        $response->assertForbidden();
    }

    public function test_non_reporters_cannot_reject_ticket_resolution(): void
    {
        $this->ticket->update(['reported_by_id' => User::factory()->create()->id]);

        $response = $this
            ->withToken($this->token)
            ->patchJson($this->url, $this->valid_input);

        $response->assertForbidden();
    }

    #[DataProvider('invalidNotesProvider')]
    public function test_invalid_notes_fails_validation(string $notes): void
    {
        $invalid_input = ['notes' => $notes];

        $response = $this
            ->withToken($this->token)
            ->patchJson($this->url, $invalid_input);

        $response->assertUnprocessable();
        $response->assertJsonValidationErrors(['notes']);
    }

    public static function invalidNotesProvider(): array
    {
        return [
            'too long' => [str_repeat('a', 1001)],
        ];
    }
}
