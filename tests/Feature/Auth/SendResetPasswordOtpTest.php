<?php

namespace Feature\Auth;

use App\Mail\SentPasswordResetOtp;
use App\Models\PasswordOtp;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\Mail;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class SendResetPasswordOtpTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected string $url;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->url = route('request-otp');

        $this->user = User::factory()->create();
    }

    public function test_can_request_otp_if_user_is_active(): void
    {
        Mail::fake();

        $response = $this->postJson($this->url, ['email' => $this->user->email]);

        $response->assertOk();
        Mail::assertQueued(SentPasswordResetOtp::class, function ($mail) {
            return $mail->hasTo($this->user->email);
        });
    }

    public function test_previous_otps_are_deleted_on_request(): void
    {
        Mail::fake();

        $old_otp = PasswordOtp::factory()->create(['email' => $this->user->email]);

        $response = $this->postJson($this->url, ['email' => $this->user->email]);

        $response->assertOk();

        $this->assertDatabaseMissing('password_otps', [
            'email' => $old_otp->email,
            'otp' => $old_otp->otp,
        ]);

        Mail::assertQueued(SentPasswordResetOtp::class, function ($mail) {
            return $mail->hasTo($this->user->email);
        });
    }

    public function test_no_otp_is_mailed_if_user_is_banned(): void
    {
        Mail::fake();

        $this->user->update(['banned_at' => now()]);

        $response = $this->postJson($this->url, ['email' => $this->user->email]);

        $response->assertOk();
        Mail::assertNothingQueued();
    }

    public function test_no_otp_is_mailed_if_user_is_not_registered(): void
    {
        Mail::fake();

        $response = $this->postJson($this->url, ['email' => fake()->safeEmail()]);

        $response->assertOk();
        Mail::assertNothingQueued();
    }

    #[DataProvider('invalidEmailProvider')]
    public function test_invalid_email_fails_validation(?string $email = null): void
    {
        $response = $this->postJson($this->url, ['email' => $email]);

        $response->assertUnprocessable();
        $response->assertJsonValidationErrors(['email']);
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
}
