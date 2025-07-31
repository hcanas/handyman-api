<?php

namespace Tests\Feature\Ticket;

use App\Models\Ticket;
use App\Models\User;
use App\TicketStatus;
use App\UserRole;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class UpdateTicketTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected string $url;

    protected User $auth_user;

    protected string $token;

    protected Ticket $target_ticket;

    protected array $valid_input;

    protected function setUp(): void
    {
        parent::setUp();

        $this->auth_user = User::factory()->create(['role' => UserRole::Staff->value]);
        $this->token = $this->auth_user->createToken('token')->plainTextToken;

        $this->target_ticket = Ticket::factory()->create([
            'reported_by_id' => $this->auth_user->id,
            'status' => TicketStatus::Pending->value,
        ]);

        $this->url = route('tickets.update', $this->target_ticket->id);

        $this->valid_input = [
            'title' => fake()->sentence(),
            'description' => fake()->paragraph(),
        ];
    }

    public function test_staff_can_update_pending_ticket(): void
    {
        $response = $this->withToken($this->token)->patchJson($this->url, $this->valid_input);

        $response->assertOk();
        $this->assertDatabaseHas('tickets', [
            'id' => $this->target_ticket->id,
            'title' => $this->valid_input['title'],
            'description' => $this->valid_input['description'],
        ]);
    }

    #[DataProvider('nonPendingTicketProvider')]
    public function test_staff_cannot_update_non_pending_ticket(TicketStatus $status): void
    {
        $this->target_ticket->update(['status' => $status->value]);

        $response = $this->withToken($this->token)->patchJson($this->url, $this->valid_input);

        $response->assertForbidden();
    }

    #[DataProvider('combinedUserRolesAndTicketStatusesProvider')]
    public function test_non_staff_cannot_update_ticket(?UserRole $role, TicketStatus $status): void
    {
        $this->auth_user->update(['role' => $role?->value]);
        $this->target_ticket->update(['status' => $status->value]);

        $response = $this->withToken($this->token)->patchJson($this->url, $this->valid_input);

        $response->assertForbidden();
    }

    #[DataProvider('allTicketStatusesProvider')]
    public function test_guests_cannot_update_ticket(TicketStatus $status): void
    {
        $this->target_ticket->update(['status' => $status->value]);

        $response = $this->patchJson($this->url, $this->valid_input);

        $response->assertUnauthorized();
    }

    #[DataProvider('invalidTitleProvider')]
    public function test_invalid_title_fails_validation(string $title): void
    {
        $response = $this->withToken($this->token)->patchJson($this->url, ['title' => $title]);

        $response->assertUnprocessable();
        $response->assertJsonValidationErrors(['title']);
    }

    #[DataProvider('invalidDescriptionProvider')]
    public function test_invalid_description_fails_validation(string $description): void
    {
        $response = $this->withToken($this->token)->patchJson($this->url, ['description' => $description]);

        $response->assertUnprocessable();
        $response->assertJsonValidationErrors(['description']);
    }

    public static function nonPendingTicketProvider(): array
    {
        return self::ticketStatusesProvider(TicketStatus::Pending);
    }

    public static function allTicketStatusesProvider(): array
    {
        return self::ticketStatusesProvider();
    }

    public static function combinedUserRolesAndTicketStatusesProvider(): array
    {
        $ret = [];

        foreach (self::userRolesProvider(UserRole::Staff) AS $role_label => $role_value) {
            foreach (self::ticketStatusesProvider() AS $status_label => $status_value) {
                $ret[$role_label . ' x ' . $status_label] = [$role_value[0], $status_value[0]];
            }
        }

        return $ret;
    }

    public static function ticketStatusesProvider(?TicketStatus $except = null): array
    {
        $cases = array_filter(TicketStatus::cases(), fn ($case) => $case !== $except);

        return array_reduce($cases, function ($ret, TicketStatus $case) {
            $ret[$case->value] = [$case];
            return $ret;
        });
    }

    public static function userRolesProvider(?UserRole $except = null): array
    {
        $cases = array_filter(UserRole::cases(), fn ($case) => $case !== $except);

        $result = array_reduce($cases, function ($ret, UserRole $case) {
            $ret[$case->value] = [$case];
            return $ret;
        });

        $result['undesignated'] = [null];

        return $result;
    }

    public static function invalidTitleProvider(): array
    {
        return [
            'empty' => [''],
            'too long' => [str_repeat('a', 256)],
        ];
    }

    public static function invalidDescriptionProvider(): array
    {
        return [
            'empty' => [''],
            'too long' => [str_repeat('a', 1001)],
        ];
    }
}
