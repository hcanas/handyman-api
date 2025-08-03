<?php

namespace Tests\Feature\Ticket\Comment;

use App\Models\Ticket;
use App\Models\User;
use App\Notifications\TicketCommentedNotification;
use App\TicketAction;
use App\TicketStatus;
use App\UserRole;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class AddCommentTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected string $url;

    protected User $admin;

    protected User $reporter;

    protected User $assignee;

    protected Ticket $ticket;

    protected function setUp(): void
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

        $this->url = route('ticket.comments.store', [
            'ticket' => $this->ticket->id,
        ]);
    }

    #[DataProvider('commentTypeProvider')]
    public function test_admin_can_add_comment(
        string $type
    ): void {
        Storage::fake('comments/attachments');
        Notification::fake();

        if ($type === 'text') {
            $input['message'] = fake()->sentence();
        } else {
            $input['file'] = $this->generateMockFile($type);
        }

        $token = $this->admin->createToken('admin')->plainTextToken;

        $response = $this
            ->withToken($token)
            ->postJson($this->url, $input);

        $response->assertCreated();

        $this->assertDatabaseHas('ticket_logs', [
            'ticket_id' => $this->ticket->id,
            'user_id' => $this->admin->id,
            'action' => TicketAction::Comment->value,
        ]);

        Notification::assertSentTo(
            [
                $this->reporter,
                $this->assignee,
            ],
            TicketCommentedNotification::class,
            function ($notification) {
                return $notification->ticket->id === $this->ticket->id;
            }
        );

        if ($type !== 'text') {
            $filename = $input['file']->hashName();
            Storage::assertExists('comments/attachments/'.$filename);
        }
    }

    #[DataProvider('commentTypeProvider')]
    public function test_reporter_can_add_comment(string $type): void
    {
        Storage::fake('comments/attachments');
        Notification::fake();

        if ($type === 'text') {
            $input['message'] = fake()->sentence();
        } else {
            $input['file'] = $this->generateMockFile($type);
        }

        $token = $this->reporter->createToken('reporter')->plainTextToken;

        $response = $this
            ->withToken($token)
            ->postJson($this->url, $input);

        $response->assertCreated();

        $this->assertDatabaseHas('ticket_logs', [
            'ticket_id' => $this->ticket->id,
            'user_id' => $this->reporter->id,
            'action' => TicketAction::Comment->value,
        ]);

        Notification::assertSentTo(
            $this->assignee,
            TicketCommentedNotification::class,
            function ($notification) {
                return $notification->ticket->id === $this->ticket->id;
            }
        );

        if ($type !== 'text') {
            $filename = $input['file']->hashName();
            Storage::assertExists('comments/attachments/'.$filename);
        }
    }

    #[DataProvider('commentTypeProvider')]
    public function test_assignee_can_add_comment(string $type): void
    {
        Storage::fake('comments/attachments');
        Notification::fake();

        if ($type === 'text') {
            $input['message'] = fake()->sentence();
        } else {
            $input['file'] = $this->generateMockFile($type);
        }

        $token = $this->assignee->createToken('assignee')->plainTextToken;

        $response = $this
            ->withToken($token)
            ->postJson($this->url, $input);

        $response->assertCreated();

        $this->assertDatabaseHas('ticket_logs', [
            'ticket_id' => $this->ticket->id,
            'user_id' => $this->assignee->id,
            'action' => TicketAction::Comment->value,
        ]);

        Notification::assertSentTo(
            $this->reporter,
            TicketCommentedNotification::class,
            function ($notification) {
                return $notification->ticket->id === $this->ticket->id;
            }
        );

        if ($type !== 'text') {
            $filename = $input['file']->hashName();
            Storage::assertExists('comments/attachments/'.$filename);
        }
    }

    public static function commentTypeProvider(): array
    {
        return [
            'text' => ['text'],
            'pdf' => ['pdf'],
            'docx' => ['docx'],
            'jpg' => ['jpg'],
            'jpeg' => ['jpeg'],
            'png' => ['png'],
            'gif' => ['gif'],
            'webp' => ['webp'],
        ];
    }

    private function generateMockFile(string $type): UploadedFile
    {
        return match ($type) {
            'pdf' => UploadedFile::fake()
                ->create('document.pdf', 100, 'application/pdf'),
            'docx' => UploadedFile::fake()
                ->create(
                    'word.docx',
                    150,
                    'application/vnd.openxmlformats-officedocument.wordprocessingml.document'
                ),
            'jpg' => UploadedFile::fake()->image('image.jpg'),
            'jpeg' => UploadedFile::fake()->image('photo.jpeg'),
            'png' => UploadedFile::fake()->image('graphic.png'),
            'gif' => UploadedFile::fake()->image('animation.gif'),
            'webp' => UploadedFile::fake()->image('picture.webp'),
        };
    }
}
