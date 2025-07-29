<?php

namespace Feature\Auth;

use App\Models\PasswordOtp;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\Hash;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class ResetPasswordTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected string $url;

    protected User $user;

    protected PasswordOtp $password_otp;

    protected array $valid_input;

    protected function setUp(): void
    {
        parent::setUp();

        $this->url = route('reset-password');

        $this->user = User::factory()->create();
        $this->password_otp = PasswordOtp::factory()->create(['email' => $this->user->email]);

        $this->valid_input = [
            'email' => $this->user->email,
            'password' => '12345678',
            'password_confirmation' => '12345678',
            'otp' => $this->password_otp->otp,
        ];
    }

    public function test_can_reset_password_with_valid_input(): void
    {
        $response = $this->patchJson($this->url, $this->valid_input);

        $this->user->refresh();

        $response->assertOk();
        $this->assertTrue(Hash::check($this->valid_input['password'], $this->user->password));
        $this->assertDatabaseMissing('password_otps', [
            'email' => $this->password_otp->email,
            'otp' => $this->password_otp->otp,
        ]);
    }

    #[DataProvider('invalidEmailProvider')]
    public function test_invalid_email_fails_validation(?string $email = null): void
    {
        $invalid_input = [
            ...$this->valid_input,
            'email' => $email,
        ];

        $response = $this->patchJson($this->url, $invalid_input);

        $response->assertUnprocessable();
        $response->assertJsonValidationErrors(['email']);
    }

    #[DataProvider('invalidPasswordProvider')]
    public function test_invalid_password_fails_validation(array $input): void
    {
        $invalid_input = [
            ...$this->valid_input,
            'password' => $input['password'] ?? null,
            'password_confirmation' => $input['password_confirmation'] ?? null,
        ];

        $response = $this->patchJson($this->url, $invalid_input);

        $response->assertUnprocessable();
        $response->assertJsonValidationErrors(['password']);
    }

    #[DataProvider('invalidOtpProvider')]
    public function test_invalid_otp_fails_validation(mixed $otp = null): void
    {
        if ($otp === 'expired') {
            PasswordOtp::query()
                ->where('email', $this->user->email)
                ->where('otp', $this->password_otp->otp)
                ->update(['expired_at' => now()->subMinute()]);

            $otp = $this->password_otp->otp;
        }

        $invalid_input = [
            ...$this->valid_input,
            'otp' => $otp,
        ];

        $response = $this->patchJson($this->url, $invalid_input);

        $response->assertUnprocessable();
        $response->assertJsonValidationErrors(['otp']);
    }

    public static function invalidEmailProvider(): array
    {
        return [
            'undefined' => [],
            'empty' => [''],
            'missing_at_symbol' => ['abc.def.com'],
            'missing_username' => ['@domain.com'],
            'missing_domain' => ['abc@'],
            'double_at_symbols' => ['abc@@domain.com'],
            'space_in_email' => ['abc def@domain.com'],
            'starting_dot' => ['.abc@domain.com'],
            'ending_dot' => ['abc.@domain.com'],
            'double_dot' => ['abc..def@domain.com'],
            'special_chars' => ['abc@domain!.com'],
            'no_domain' => ['abc@.com'],
            'just_at' => ['@'],
            'characters_over_limit' => [str_repeat('a', 256)],
        ];
    }

    public static function invalidPasswordProvider(): array
    {
        return [
            'undefined' => [[]],
            'empty' => [['password' => '', 'password_confirmation' => '']],
            'too_short' => [['password' => '123', 'password_confirmation' => '123']],
            'mismatch' => [['password' => '12345678', 'password_confirmation' => '87654321']],
        ];
    }

    public static function invalidOtpProvider(): array
    {
        return [
            'undefined' => [],
            'empty' => [''],
            'too_short' => ['12345'],
            'too_long' => ['1234567'],
            'not_numeric' => ['abcdef'],
            'contains_letters' => ['12a456'],
            'null_value' => [null],
            'boolean_true' => [true],
            'boolean_false' => [false],
            'array_value' => [[123456]],
            'expired' => ['expired'],
        ];
    }
}
