<?php

namespace Tests\Feature\Ticket;

use App\Models\Ticket;
use App\Models\TicketLog;
use App\Models\User;
use App\Notifications\TicketAssignedNotification;
use App\Notifications\TicketAssignmentReceivedNotification;
use App\TicketAction;
use App\TicketStatus;
use App\UserRole;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\Notification;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class AssignTicketTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected string $url;

    protected User $auth_user;

    protected string $token;

    protected Ticket $target_ticket;

    protected User $reporter;

    protected User $assignee;

    protected array $valid_input;

    protected function setUp(): void
    {
        parent::setUp();

        $this->auth_user = User::factory()->create(['role' => UserRole::Admin->value]);
        $this->token = $this->auth_user->createToken('token')->plainTextToken;

        $this->assignee = User::factory()->create(['role' => UserRole::Technician->value]);
        $this->reporter = User::factory()->create();

        $this->target_ticket = Ticket::factory()->create([
            'status' => TicketStatus::Pending->value,
            'reported_by_id' => $this->reporter->id,
        ]);

        $this->url = route('tickets.assign', ['ticket' => $this->target_ticket->id]);

        $this->valid_input = [
            'assigned_to_id' => $this->assignee->id,
            'notes' => fake()->sentence(),
        ];
    }

    public function test_admins_can_assign_ticket_to_technician(): void
    {
        Notification::fake();

        $response = $this->withToken($this->token)->patchJson($this->url, $this->valid_input);

        $response->assertOk();

        $this->assertDatabaseHas('tickets', [
            'id' => $this->target_ticket->id,
            'assigned_to_id' => $this->assignee->id,
            'status' => TicketStatus::InProgress->value,
        ]);

        $this->assertDatabaseHas('ticket_logs', [
            'ticket_id' => $this->target_ticket->id,
            'user_id' => $this->auth_user->id,
            'action' => TicketAction::Assign->value,
            'notes' => $this->valid_input['notes'],
        ]);

        $this->assertDatabaseHas('ticket_logs', [
            'ticket_id' => $this->target_ticket->id,
            'user_id' => $this->assignee->id,
            'action' => TicketAction::ReceivedAssignment->value,
        ]);

        Notification::assertSentTo(
            $this->assignee,
            TicketAssignmentReceivedNotification::class,
            function ($notification) {
                return $notification->ticket->id === $this->target_ticket->id;
            }
        );

        Notification::assertSentTo(
            $this->reporter,
            TicketAssignedNotification::class,
            function ($notification) {
                return $notification->ticket->id === $this->target_ticket->id;
            }
        );
    }

    public function test_admins_can_reassign_ticket_to_technician(): void
    {
        Notification::fake();

        $previous_assignee = User::factory()->create();

        $this->target_ticket->update([
            'assigned_to_id' => $previous_assignee->id,
            'status' => TicketStatus::InProgress->value,
        ]);

        $response = $this->withToken($this->token)->patchJson($this->url, $this->valid_input);

        $response->assertOk();

        $this->assertDatabaseHas('tickets', [
            'id' => $this->target_ticket->id,
            'assigned_to_id' => $this->assignee->id,
            'status' => TicketStatus::InProgress->value,
        ]);

        $this->assertDatabaseHas('ticket_logs', [
            'ticket_id' => $this->target_ticket->id,
            'user_id' => $this->auth_user->id,
            'action' => TicketAction::Reassign->value,
            'notes' => $this->valid_input['notes'],
        ]);

        $this->assertDatabaseHas('ticket_logs', [
            'ticket_id' => $this->target_ticket->id,
            'user_id' => $this->assignee->id,
            'action' => TicketAction::ReceivedAssignment->value,
        ]);

        Notification::assertSentTo(
            $this->assignee,
            TicketAssignmentReceivedNotification::class,
            function ($notification) {
                return $notification->ticket->id === $this->target_ticket->id;
            }
        );

        Notification::assertSentTo(
            $this->reporter,
            TicketAssignedNotification::class,
            function ($notification) {
                return $notification->ticket->id === $this->target_ticket->id;
            }
        );
    }

    #[DataProvider(('nonAdminUsersProvider'))]
    public function test_non_admins_cannot_assign_ticket(?string $role): void
    {
        $this->auth_user->update(['role' => $role]);

        $response = $this->withToken($this->token)->patchJson($this->url, $this->valid_input);

        $response->assertForbidden();
    }

    public function test_guests_cannot_assign_ticket(): void
    {
        $response = $this->patchJson($this->url, $this->valid_input);

        $response->assertUnauthorized();
    }

    #[DataProvider('invalidAssignedToIdProvider')]
    public function test_invalid_assigned_to_id_fails_validation(mixed $assigned_to_id): void
    {
        $invalid_input = ['notes' => $this->valid_input['notes']];

        if ($assigned_to_id !== 'undefined') {
            if ($assigned_to_id === 'non-technician') {
                $this->assignee->update(['role' => UserRole::Staff->value]);
                $invalid_input['assigned_to_id'] = $this->assignee->id;
            } else {
                $invalid_input['assigned_to_id'] = $assigned_to_id;
            }
        }

        $response = $this->withToken($this->token)->patchJson($this->url, $invalid_input);

        $response->assertUnprocessable();
        $response->assertJsonValidationErrors(['assigned_to_id']);
    }

    #[DataProvider('invalidNotesProvider')]
    public function test_invalid_notes_fails_validation(string $notes): void
    {
        $invalid_input = ['assigned_to_id' => $this->valid_input['assigned_to_id']];

        if ($notes !== 'undefined') {
            $invalid_input['notes'] = $notes;    ;
        }

        $response = $this->withToken($this->token)->patchJson($this->url, $invalid_input);

        $response->assertUnprocessable();
        $response->assertJsonValidationErrors(['notes']);
    }

    public static function nonAdminUsersProvider(): array
    {
        return [
            'staff' => [UserRole::Staff->value],
            'technician' => [UserRole::Technician->value],
            'undesignated' => [null],
        ];
    }

    public static function invalidAssignedToIdProvider(): array
    {
        return [
            'missing' => ['undefined'],
            'null' => [null],
            'empty' => [''],
            'does not exist' => [999],
            'not a technician' => ['non-technician'],
        ];
    }

    public static function invalidNotesProvider(): array
    {
        return [
            'too long' => [str_repeat('a', 1001)],
        ];
    }
}
