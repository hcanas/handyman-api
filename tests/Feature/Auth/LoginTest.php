<?php

namespace Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class LoginTest extends TestCase
{
    use RefreshDatabase;

    protected array $valid_input = [
        'email' => 'test@example.com',
        'password' => '12345678',
    ];

    protected array $validation_fields = [
        'email',
        'password',
    ];

    public function test_web_login_succeeds_if_input_is_valid(): void
    {
        User::factory()->create([
            'email' => $this->valid_input['email'],
            'password' => Hash::make($this->valid_input['password']),
        ]);

        $response = $this->postJson(
            uri: route('login'),
            data: $this->valid_input,
            headers: [
                'X-Client-Platform' => 'web',
            ],
        );

        $response->assertOk();
        $response->assertCookie('token');
    }

    public function test_mobile_login_succeeds_if_input_is_valid(): void
    {
        User::factory()->create([
            'email' => $this->valid_input['email'],
            'password' => Hash::make($this->valid_input['password']),
        ]);

        $response = $this->postJson(
            uri: route('login'),
            data: $this->valid_input,
            headers: [
                'X-Client-Platform' => 'mobile',
            ],
        );

        $response->assertOk();
        $response->assertJsonStructure(['token']);
    }

    public function test_fails_if_client_platform_is_invalid(): void
    {
        $response = $this->postJson(route('login'), $this->valid_input);

        $response->assertBadRequest();
    }

    public function test_fails_if_input_is_empty(): void
    {
        $response = $this->postJson(route('login'));

        $response->assertUnprocessable();
        $response->assertJsonValidationErrors(['auth']);
    }

    public function test_fails_if_email_is_invalid(): void
    {
        $response = $this->postJson(route('login'), [
            ...$this->valid_input,
            'email' => 'invalid',
        ]);

        $response->assertUnprocessable();
        $response->assertJsonValidationErrors(['auth']);
    }

    public function test_fails_if_password_is_too_short(): void
    {
        $response = $this->postJson(route('login'), [
            ...$this->valid_input,
            'password' => '1',
        ]);

        $response->assertUnprocessable();
        $response->assertJsonValidationErrors(['auth']);
    }

    public function test_fails_if_credentials_are_incorrect(): void
    {
        User::factory()->create([
            'email' => $this->valid_input['email'],
            'password' => Hash::make($this->valid_input['password']),
        ]);

        $response = $this->postJson(
            uri: route('login'),
            data: [
                'email' => 'wrong@email.com',
                'password' => 'wrong-password',
            ],
            headers: [
                'X-Client-Platform' => 'web',
            ],
        );

        $response->assertUnauthorized();
    }
}
