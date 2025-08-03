<?php

namespace Tests\Feature\Ticket\Comment;

use App\Models\Ticket;
use App\Models\User;
use App\TicketStatus;
use App\UserRole;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class ListCommentsTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected string $url;

    protected User $admin;

    protected User $reporter;

    protected User $assignee;

    protected Ticket $ticket;

    public function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()
            ->create(['role' => UserRole::Admin->value]);

        $this->reporter = User::factory()
            ->create(['role' => UserRole::Staff->value]);

        $this->assignee = User::factory()
            ->create(['role' => UserRole::Technician->value]);

        $this->ticket = Ticket::factory()
            ->for($this->reporter, 'reporter')
            ->for($this->assignee, 'assignee')
            ->create(['status' => TicketStatus::InProgress->value]);

        $this->url = route('ticket.comments.index', [
            'ticket' => $this->ticket->id,
        ]);
    }

    #[DataProvider('authorizedUsersProvider')]
    public function test_authorized_users_can_list_comments(string $role): void
    {
        $token = match ($role) {
            'admin' => $this->admin
                ->createToken('admin')
                ->plainTextToken,
            'reporter' => $this->reporter
                ->createToken('reporter')
                ->plainTextToken,
            'assignee' => $this->assignee
                ->createToken('assignee')
                ->plainTextToken,
        };

        $response = $this->withToken($token)->getJson($this->url);

        $response->assertOk();
        $response->assertJsonStructure([
            'data' => [
                '*' => [
                    'body',
                    'type',
                    'attachments',
                ]
            ],
            'meta',
            'links',
        ]);
    }

    public static function authorizedUsersProvider(): array
    {
        return [
            'admin' => ['admin'],
            'reporter' => ['reporter'],
            'assignee' => ['assignee'],
        ];
    }
}
