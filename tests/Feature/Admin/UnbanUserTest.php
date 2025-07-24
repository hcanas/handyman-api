<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use App\UserRole;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class UnbanUserTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected string $url;

    protected User $auth_user;

    protected User $target_user;

    protected string $token;

    protected function setUp(): void
    {
        parent::setUp();

        $this->auth_user = User::factory()->create(['role' => UserRole::Admin->value]);
        $this->token = $this->auth_user->createToken('web')->plainTextToken;
        Sanctum::actingAs($this->auth_user);

        $this->target_user = User::factory()->create([
            'role' => UserRole::Staff->value,
            'banned_at' => now()->subMinutes(5),
            'ban_reason' => $this->faker->sentence(),
        ]);

        $this->url = route('user.unban', ['user' => $this->target_user->id]);
    }

    public function test_can_unban_if_authenticated_user_is_an_admin(): void
    {
        $response = $this
            ->withCookie('token', $this->token)
            ->patchJson($this->url);

        $response->assertOk();
        $this->assertTrue($this->auth_user->isAdmin());
        $this->assertDatabaseHas('users', [
            'id' => $this->target_user->id,
            'banned_at' => null,
            'ban_reason' => null,
        ]);
    }

    public function test_cannot_unban_if_authenticated_user_is_not_an_admin(): void
    {
        $this->auth_user->update(['role' => UserRole::Staff->value]);

        $response = $this
            ->withCookie('token', $this->token)
            ->patchJson($this->url);

        $this->assertFalse($this->auth_user->isAdmin());
        $response->assertForbidden();
    }

    public function test_succeeds_if_target_user_is_banned(): void
    {
        $this->assertTrue($this->target_user->isBanned());

        $response = $this
            ->withCookie('token', $this->token)
            ->patchJson($this->url, [
                'user_id' => $this->target_user->id,
            ]);

        $this->target_user->refresh();
        $this->assertFalse($this->target_user->isBanned());
        $response->assertOk();
    }

    public function test_fails_if_target_user_is_active(): void
    {
        $this->target_user->update([
            'banned_at' => null,
            'ban_reason' => null,
        ]);

        $response = $this
            ->withCookie('token', $this->token)
            ->patchJson($this->url, [
                'user_id' => $this->target_user->id,
            ]);

        $this->assertFalse($this->target_user->isBanned());
        $response->assertConflict();
    }
}
