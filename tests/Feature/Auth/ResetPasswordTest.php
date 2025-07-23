<?php

namespace Feature\Auth;

use App\Models\PasswordOtp;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class ResetPasswordTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected array $valid_input = [
        'email' => 'test@example.com',
        'password' => '12345678',
        'password_confirmation' => '12345678',
    ];

    protected array $validation_fields = [
        'email',
        'password',
        'otp',
    ];

    public function test_succeeds_if_input_is_valid(): void
    {
        $user = User::factory()->create(['email' => $this->valid_input['email']]);

        $password_otp = PasswordOtp::factory()->create(['email' => $user->email]);

        $response = $this->patchJson(route('reset-password'), [
            ...$this->valid_input,
            'otp' => $password_otp->otp,
        ]);

        $response->assertOk();
    }

    public function test_fails_if_input_is_empty(): void
    {
        $response = $this->patchJson(route('reset-password'));

        $response->assertUnprocessable();
        $response->assertJsonValidationErrors($this->validation_fields);
    }

    public function test_fails_if_password_is_too_short(): void
    {
        $user = User::factory()->create(['email' => $this->valid_input['email']]);
        $password_otp = PasswordOtp::factory()->create(['email' => $user->email]);

        $response = $this->patchJson(route('reset-password'), [
            ...$this->valid_input,
            'otp' => $password_otp->otp,
            'password' => '1',
        ]);

        $response->assertUnprocessable();
        $response->assertJsonValidationErrors(['password']);
    }

    public function test_fails_if_passwords_do_not_match(): void
    {
        $user = User::factory()->create(['email' => $this->valid_input['email']]);
        $password_otp = PasswordOtp::factory()->create(['email' => $user->email]);

        $response = $this->patchJson(route('reset-password'), [
            ...$this->valid_input,
            'otp' => $password_otp->otp,
            'password_confirmation' => 'mismatch',
        ]);

        $response->assertUnprocessable();
        $response->assertJsonValidationErrors(['password']);
    }

    public function test_fails_if_otp_is_invalid(): void
    {
        $user = User::factory()->create(['email' => $this->valid_input['email']]);
        PasswordOtp::factory()->create(['email' => $user->email]);
        $invalid_otp = $this->faker->unique()->randomNumber(6, true);

        $response = $this->patchJson(route('reset-password'), [
            $this->valid_input,
            'otp' => $invalid_otp,
        ]);

        $response->assertUnprocessable();
        $response->assertJsonValidationErrors(['otp']);
    }
}
