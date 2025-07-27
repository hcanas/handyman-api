<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use App\UserRole;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class BanUserTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected string $url;

    protected User $auth_user;

    protected string $token;

    protected User $target_user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->auth_user = User::factory()->create(['role' => UserRole::Admin->value]);
        $this->token = $this->auth_user->createToken('web')->plainTextToken;

        $this->target_user = User::factory()->create(['role' => UserRole::Staff->value]);

        $this->url = route('user.ban', ['user' => $this->target_user->id]);
    }

    public function test_admin_can_ban_active_user(): void
    {
        $ban_reason = fake()->sentence();

        $response = $this->withToken($this->token)->patchJson($this->url, ['ban_reason' => $ban_reason]);

        $this->target_user->refresh();
        $this->assertTrue($this->target_user->isBanned());
        $this->assertEquals($ban_reason, $this->target_user->ban_reason);
        $response->assertOk();
    }

    public function test_admin_cannot_ban_already_banned_user(): void
    {
        $this->target_user->update(['banned_at' => now()]);

        $ban_reason = fake()->sentence();

        $response = $this->withToken($this->token)->patchJson($this->url, ['ban_reason' => $ban_reason]);

        $response->assertConflict();
    }

    #[DataProvider('nonAdminUsersProvider')]
    public function test_non_admins_cannot_ban_user(UserRole $role): void
    {
        $this->auth_user->update(['role' => $role->value]);

        $ban_reason = fake()->sentence();

        $response = $this->withToken($this->token)->patchJson($this->url, ['ban_reason' => $ban_reason]);

        $response->assertForbidden();
    }

    public function test_undesignated_users_cannot_ban_user(): void
    {
        $this->auth_user->update(['role' => null]);

        $ban_reason = fake()->sentence();

        $response = $this->withToken($this->token)->patchJson($this->url, ['ban_reason' => $ban_reason]);

        $response->assertForbidden();
    }

    public function test_guests_cannon_ban_user(): void
    {
        $ban_reason = fake()->sentence();

        $response = $this->patchJson($this->url, ['ban_reason' => $ban_reason]);

        $response->assertUnauthorized();
    }

    #[DataProvider('invalidInputProvider')]
    public function test_fails_all_validation_rules(array $input): void
    {
        $ban_reason = $input['ban_reason'] ?? [];

        $response = $this->withToken($this->token)->patchJson($this->url, ['ban_reason' => $ban_reason]);

        $response->assertUnprocessable();
        $response->assertJsonValidationErrors(['ban_reason']);
    }

    public static function nonAdminUsersProvider(): array
    {
        return [
            'staff' => [UserRole::Staff],
            'technician' => [UserRole::Technician],
        ];
    }

    public static function invalidInputProvider(): array
    {
        return [
            'empty_payload' => [[]],
            'empty_fields' => [['ban_reason' => '']],
            'string' => [['ban_reason' => []]],
        ];
    }
}
