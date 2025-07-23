<?php

namespace Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class LogoutTest extends TestCase
{
    use RefreshDatabase;

    protected array $valid_input = [
        'email' => 'test@example.com',
        'password' => '12345678',
    ];

    public function test_web_logout_succeeds_if_user_is_authenticated(): void
    {
        $user = User::factory()->create([
            'name' => $this->valid_input['email'],
            'password' => Hash::make($this->valid_input['password']),
        ]);

        $token = $user->createToken('web')->plainTextToken;

        Sanctum::actingAs($user);

        $response = $this
            ->withCookie('token', $token)
            ->postJson(
                uri: route('logout'),
                headers: [
                    'X-Client-Platform' => 'web',
                ],
            );

        $response->assertOk();
    }

    public function test_mobile_logout_succeeds_if_user_is_authenticated(): void
    {
        $user = User::factory()->create([
            'name' => $this->valid_input['email'],
            'password' => Hash::make($this->valid_input['password']),
        ]);

        $token = $user->createToken('mobile')->plainTextToken;

        $response = $this
            ->postJson(
                uri: route('logout'),
                headers: [
                    'Authorization' => 'Bearer ' . $token,
                    'X-Client-Platform' => 'mobile',
                ],
            );

        $response->assertOk();
    }

    public function test_fails_if_platform_is_invalid(): void
    {
        $user = User::factory()->create([
            'name' => $this->valid_input['email'],
            'password' => Hash::make($this->valid_input['password']),
        ]);

        $token = $user->createToken('web')->plainTextToken;

        Sanctum::actingAs($user);

        $response = $this
            ->withCookie('token', $token)
            ->postJson(route('logout'));

        $response->assertBadRequest();
    }

    public function test_fails_if_user_is_unauthenticated(): void
    {
        $response = $this->postJson(route('logout'));

        $response->assertUnauthorized();
    }
}
