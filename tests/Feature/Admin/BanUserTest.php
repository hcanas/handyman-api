<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use App\UserRole;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class BanUserTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected User $auth_user;

    protected User $target_user;

    protected string $token;

    protected function setUp(): void
    {
        parent::setUp();

        $this->auth_user = User::factory()->create(['role' => UserRole::Admin->value]);

        $this->target_user = User::factory()->create(['role' => UserRole::Staff->value]);

        $this->token = $this->auth_user->createToken('web')->plainTextToken;
        Sanctum::actingAs($this->auth_user);
    }

    public function test_can_ban_if_authenticated_user_is_an_admin(): void
    {
        $response = $this
            ->withCookie('token', $this->token)
            ->patchJson(route('ban-user'));

        $this->assertTrue($this->auth_user->isAdmin());
        $response->assertUnprocessable();
    }

    public function test_can_not_ban_if_authenticated_user_is_not_an_admin(): void
    {
        $this->auth_user->update(['role' => UserRole::Staff->value]);

        $response = $this
            ->withCookie('token', $this->token)
            ->patchJson(route('ban-user'));

        $this->assertFalse($this->auth_user->isAdmin());
        $response->assertForbidden();
    }

    public function test_succeeds_if_target_user_is_active(): void
    {
        $response = $this
            ->withCookie('token', $this->token)
            ->patchJson(route('ban-user'), [
                'user_id' => $this->target_user->id,
                'ban_reason' => $this->faker->sentence(),
            ]);

        $this->target_user->refresh();

        $response->assertOk();
        $this->assertTrue($this->target_user->isBanned());
    }

    public function test_fails_if_target_user_is_already_banned(): void
    {
        $this->target_user->update([
            'banned_at' => now(),
            'ban_reason' => $this->faker->sentence(),
        ]);

        $response = $this
            ->withCookie('token', $this->token)
            ->patchJson(route('ban-user'), [
                'user_id' => $this->target_user->id,
                'ban_reason' => $this->faker->sentence(),
            ]);

        $response->assertConflict();
    }

    public function test_fails_if_input_is_empty(): void
    {
        $response = $this
            ->withCookie('token', $this->token)
            ->patchJson(route('ban-user'));

        $response->assertUnprocessable();
        $response->assertJsonValidationErrors([
            'user_id',
            'ban_reason',
        ]);
    }

    public function test_fails_if_target_user_is_not_registered(): void
    {
        $response = $this
            ->withCookie('token', $this->token)
            ->patchJson(route('ban-user'), [
                'user_id' => 999,
                'ban_reason' => $this->faker->sentence(),
            ]);

        $response->assertUnprocessable();
        $response->assertJsonValidationErrors(['user_id']);
    }
}
