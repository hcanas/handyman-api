<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use App\UserRole;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Laravel\Sanctum\Sanctum;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class UnbanUserTest extends TestCase
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

        $this->target_user = User::factory()->create([
            'role' => UserRole::Staff->value,
            'banned_at' => now()->subMinutes(5),
            'ban_reason' => $this->faker->sentence(),
        ]);

        $this->url = route('user.unban', ['user' => $this->target_user->id]);
    }

    public function test_admins_can_unban_banned_user(): void
    {
        $response = $this->withToken($this->token)->patchJson($this->url);

        $response->assertOk();
        $this->assertDatabaseHas('users', [
            'id' => $this->target_user->id,
            'banned_at' => null,
            'ban_reason' => null,
        ]);
    }

    public function test_admins_cannot_unban_active_user(): void
    {
        $this->target_user->update([
            'banned_at' => null,
            'ban_reason' => null,
        ]);

        $response = $this->withToken($this->token)->patchJson($this->url);

        $response->assertConflict();
    }

    #[DataProvider('nonAdminUsersProvider')]
    public function test_non_admins_cannot_unban_user(UserRole $role): void
    {
        $this->auth_user->update(['role' => $role->value]);

        $response = $this->withToken($this->token)->patchJson($this->url);

        $response->assertForbidden();
    }

    public function test_undesignated_users_cannot_unban_user(): void
    {
        $this->auth_user->update(['role' => null]);

        $response = $this->withToken($this->token)->patchJson($this->url);

        $response->assertForbidden();
    }

    public function test_guests_cannot_unban_user(): void
    {
        $response = $this->patchJson($this->url);

        $response->assertUnauthorized();
    }

    public static function nonAdminUsersProvider(): array
    {
        return [
            'staff' => [UserRole::Staff],
            'technician' => [UserRole::Technician],
        ];
    }
}
