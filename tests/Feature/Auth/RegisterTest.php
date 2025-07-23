<?php

namespace Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class RegisterTest extends TestCase
{
    use RefreshDatabase;

    protected array $valid_input = [
        'name' => 'John Doe',
        'email' => 'test@example.com',
        'password' => '12345678',
        'password_confirmation' => '12345678',
    ];

    protected array $validation_fields = [
        'name',
        'email',
        'password',
    ];

    public function test_succeeds_if_input_is_valid(): void
    {
        $response = $this->postJson(route('register'), $this->valid_input);

        $response->assertCreated();
        $this->assertDatabaseHas('users', [
            'name' => $this->valid_input['name'],
            'email' => $this->valid_input['email'],
        ]);

        $user = User::query()
            ->where('email', $this->valid_input['email'])
            ->first();

        $this->assertTrue(Hash::check($this->valid_input['password'], $user->password));
    }

    public function test_fails_if_input_is_empty(): void
    {
        $response = $this->postJson(route('register'), []);

        $response->assertUnprocessable();
        $response->assertJsonValidationErrors($this->validation_fields);
    }

    public function test_fails_if_email_is_invalid(): void
    {
        $response = $this->postJson(route('register'), [
            ...$this->valid_input,
            'email' => 'invalid',
        ]);

        $response->assertUnprocessable();
        $response->assertJsonValidationErrors(['email']);
    }

    public function test_fails_if_email_is_duplicate(): void
    {
        User::factory()->create(['email' => $this->valid_input['email']]);

        $response = $this->postJson(route('register'), $this->valid_input);

        $response->assertUnprocessable();
        $response->assertJsonValidationErrors(['email']);
    }

    public function test_fails_if_password_is_too_short(): void
    {
        $response = $this->postJson(route('register'), [
            ...$this->valid_input,
            'password' => '1',
            'password_confirmation' => '1',
        ]);

        $response->assertUnprocessable();
        $response->assertJsonValidationErrors(['password']);
    }

    public function test_fails_if_passwords_do_not_match(): void
    {
        $response = $this->postJson(route('register'), [
            ...$this->valid_input,
            'password_confirmation' => 'mismatch',
        ]);

        $response->assertUnprocessable();
        $response->assertJsonValidationErrors(['password']);
    }
}
