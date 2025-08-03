<?php

namespace Tests\Feature\Ticket;

use App\Models\Ticket;
use App\Models\User;
use App\Notifications\TicketCancelledNotification;
use App\TicketStatus;
use App\UserRole;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\Notification;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class CancelTicketTest extends TestCase
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

        $this->token = $this->auth_user->createToken('token')->plainTextToken;

        $this->assignee = User::factory()->create();

        $this->ticket = Ticket::factory()
            ->for($this->auth_user, 'reporter')
            ->create(['status' => TicketStatus::Pending->value]);

        $this->url = route('tickets.cancel', ['ticket' => $this->ticket->id]);

        $this->valid_input = ['notes' => fake()->sentence()];
    }

    #[DataProvider('cancellableTicketStatusProvider')]
    public function test_admins_can_cancel_cancellable_ticket(
        string $status
    ): void {
        Notification::fake();

        $this->auth_user->update(['role' => UserRole::Admin->value]);

        $reporter = User::factory()->create();

        $this->ticket->update([
            'reported_by_id' => $reporter->id,
            'assigned_to_id' => $status === TicketStatus::InProgress->value
                ? $this->assignee->id
                : null,
            'status' => $status,
        ]);

        $response = $this
            ->withToken($this->token)
            ->patchJson($this->url, $this->valid_input);

        $response->assertOk();

        $this->assertDatabaseHas('tickets', [
            'id' => $this->ticket->id,
            'status' => TicketStatus::Cancelled->value,
        ]);

        $this->assertDatabaseHas('ticket_logs', [
            'ticket_id' => $this->ticket->id,
            'from_status' => $status,
            'to_status' => TicketStatus::Cancelled->value,
        ]);

        if ($status === TicketStatus::Pending->value) {
            Notification::assertSentTo(
                $reporter,
                TicketCancelledNotification::class,
                function ($notification) {
                    return $notification->ticket->id === $this->ticket->id;
                }
            );
        }

        if ($status === TicketStatus::InProgress->value) {
            Notification::assertSentTo(
                $this->assignee,
                TicketCancelledNotification::class,
                function ($notification) {
                    return $notification->ticket->id === $this->ticket->id;
                }
            );
        }
    }

    #[DataProvider('cancellableTicketStatusProvider')]
    public function test_reporter_can_cancel_cancellable_ticket(
        string $status
    ): void {
        Notification::fake();

        $this->ticket->update([
            'assigned_to_id' => $status === TicketStatus::InProgress->value
                ? $this->assignee->id
                : null,
            'status' => $status,
        ]);

        $response = $this
            ->withToken($this->token)
            ->patchJson($this->url, $this->valid_input);

        $response->assertOk();

        $this->assertDatabaseHas('tickets', [
            'id' => $this->ticket->id,
            'status' => TicketStatus::Cancelled->value,
        ]);

        $this->assertDatabaseHas('ticket_logs', [
            'ticket_id' => $this->ticket->id,
            'from_status' => $status,
            'to_status' => TicketStatus::Cancelled->value,
        ]);

        if ($status === TicketStatus::InProgress->value) {
            Notification::assertSentTo(
                $this->assignee,
                TicketCancelledNotification::class,
                function ($notification) {
                    return $notification->ticket->id === $this->ticket->id;
                }
            );
        }
    }

    #[DataProvider('unauthorizedUsersProvider')]
    public function test_unauthorized_users_cannot_cancel_ticket(
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

    public static function cancellableTicketStatusProvider(): array
    {
        return [
            'pending' => [TicketStatus::Pending->value],
            'in progress' => [TicketStatus::InProgress->value],
        ];
    }

    public static function unauthorizedUsersProvider(): array
    {
        return [
            'staff but not reporter' => [UserRole::Staff->value, 999],
            'technician but not reporter' => [UserRole::Technician->value, 999],
        ];
    }

    public static function invalidNotesProvider(): array
    {
        return [
            'too long' => [str_repeat('a', 1001)],
        ];
    }
}
