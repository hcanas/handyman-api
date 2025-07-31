<?php

namespace Tests\Feature\Ticket;

use App\Models\Department;
use App\Models\User;
use App\Notifications\TicketCreatedNotification;
use App\TicketPriorityLevel;
use App\UserRole;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\Notification;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class CreateTicketTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected string $url;

    protected User $auth_user;

    protected string $token;

    protected array $valid_input;

    protected function setUp(): void
    {
        parent::setUp();

        $this->url = route('tickets.store');

        $this->auth_user = User::factory()->create([
            'role' => UserRole::Staff->value,
            'department_id' => Department::factory()->create()->id,
        ]);

        $this->token = $this->auth_user->createToken('token')->plainTextToken;

        $this->valid_input = [
            'title' => fake()->sentence(),
            'description' => fake()->paragraph(),
            'priority_level' => TicketPriorityLevel::Low->value,
        ];
    }

    public function test_staff_can_create_ticket(): void
    {
        Notification::fake();

        User::factory()->count(2)->create(['role' => UserRole::Admin->value]);

        $response = $this->withToken($this->token)->postJson($this->url, $this->valid_input);

        $response->assertCreated();
        $this->assertDatabaseHas('tickets', $this->valid_input);

        Notification::assertSentTo(
            User::where('role', UserRole::Admin->value)->get(),
            TicketCreatedNotification::class,
            function ($notification) use ($response) {
                return $notification->ticket->id === $response->json('data.id');
            }
        );
    }

    #[DataProvider('nonStaffUsersProvider')]
    public function test_non_staff_cannot_create_ticket(UserRole $role): void
    {
        $this->auth_user->update(['role' => $role->value]);

        $response = $this->withToken($this->token)->postJson($this->url, $this->valid_input);

        $response->assertForbidden();
    }

    public function test_undesignated_user_cannot_create_ticket(): void
    {
        $this->auth_user->update(['role' => null]);

        $response = $this->withToken($this->token)->postJson($this->url, $this->valid_input);

        $response->assertForbidden();
    }

    public function test_guests_cannot_create_ticket(): void
    {
        $response = $this->postJson($this->url, $this->valid_input);

        $response->assertUnauthorized();
    }

    #[DataProvider('invalidTitleProvider')]
    public function test_invalid_title_fails_validation(?string $title = null): void
    {
        $invalid_input = [
            ...$this->valid_input,
            'title' => $title,
        ];

        $response = $this->withToken($this->token)->postJson($this->url, $invalid_input);

        $response->assertUnprocessable();
        $response->assertJsonValidationErrors(['title']);
    }

    #[DataProvider('invalidDescriptionProvider')]
    public function test_invalid_description_fails_validation(?string $description = null): void
    {
        $invalid_input = [
            ...$this->valid_input,
            'description' => $description,
        ];

        $response = $this->withToken($this->token)->postJson($this->url, $invalid_input);

        $response->assertUnprocessable();
        $response->assertJsonValidationErrors(['description']);
    }

    #[DataProvider('invalidPriorityLevelProvider')]
    public function test_invalid_priority_level_fails_validation(?string $priority_level = null): void
    {
        $invalid_input = [
            ...$this->valid_input,
            'priority_level' => $priority_level,
        ];

        $response = $this->withToken($this->token)->postJson($this->url, $invalid_input);

        $response->assertUnprocessable();
        $response->assertJsonValidationErrors(['priority_level']);
    }

    public static function nonStaffUsersProvider(): array
    {
        return [
            'admin' => [UserRole::Admin],
            'technician' => [UserRole::Technician],
        ];
    }

    public static function invalidTitleProvider(): array
    {
        return [
            'undefined' => [],
            'empty' => [''],
            'characters_over_limit' => [str_repeat('a', 256)],
        ];
    }

    public static function invalidDescriptionProvider(): array
    {
        return [
            'undefined' => [],
            'empty' => [''],
            'characters_over_limit' => [str_repeat('a', 1001)],
        ];
    }

    public static function invalidPriorityLevelProvider(): array
    {
        return [
            'undefined' => [],
            'empty' => [''],
            'invalid' => ['invalid'],
        ];
    }
}
