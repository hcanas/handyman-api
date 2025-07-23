<?php

namespace Feature\Auth;

use App\Mail\SentPasswordResetOtp;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class SendResetPasswordOtpTest extends TestCase
{
    use RefreshDatabase;

    protected array $valid_input = [
        'email' => 'test@example.com',
    ];

    public function test_receives_otp_if_user_is_active(): void
    {
        Mail::fake();

        $user = User::factory()->create(['email' => $this->valid_input['email']]);

        $response = $this->postJson(route('request-otp'), [
            'email' => $user->email,
        ]);

        $response->assertOk();

        Mail::assertQueued(SentPasswordResetOtp::class, function ($mail) use ($user) {
            return $mail->hasTo($user->email);
        });
    }

    public function test_does_not_receive_otp_if_user_is_banned_or_not_registered(): void
    {
        Mail::fake();

        $response = $this->postJson(route('request-otp'), [
            'email' => $this->valid_input['email'],
        ]);

        $response->assertOk();
        Mail::assertNothingQueued();
    }
}
