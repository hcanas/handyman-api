<?php

namespace Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class UpdatePasswordTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected string $token;

    protected array $valid_input = [
        'password' => '12345678',
        'password_confirmation' => '12345678',
    ];

    protected array $validation_fields = [
        'password',
    ];

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        Sanctum::actingAs($this->user);

        $this->token = $this->user->createToken('web')->plainTextToken;
    }

    public function test_succeeds_if_input_is_valid(): void
    {
        $response = $this
            ->withCookie('token', $this->token)
            ->patchJson(route('update-password'), $this->valid_input);

        $response->assertOk();
    }

    public function test_fails_if_input_is_empty(): void
    {
        $response = $this
            ->withCookie('token', $this->token)
            ->patchJson(route('update-password'));

        $response->assertUnprocessable();
        $response->assertJsonValidationErrors(['password']);
    }

    public function test_fails_if_passwords_do_not_match(): void
    {
        $response = $this
            ->withCookie('token', $this->token)
            ->patchJson(route('update-password'), [
                'password' => '12345678',
                'password_confirmation' => '87654321',
            ]);

        $response->assertUnprocessable();
        $response->assertJsonValidationErrors(['password']);
    }
}
