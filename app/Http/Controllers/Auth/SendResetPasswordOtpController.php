<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\SendResetPasswordOtpRequest;
use App\Mail\SentPasswordResetOtp;
use App\Models\PasswordOtp;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Throwable;

class SendResetPasswordOtpController extends Controller
{
    public function __invoke(SendResetPasswordOtpRequest $request): JsonResponse
    {
        try {
            DB::beginTransaction();

            $user = User::query()
                ->where('email', $request->validated('email'))
                ->whereNull('banned_at')
                ->first();

            // only send otps to active users
            if ($user) {
                $otp = $this->generateOtp($request->validated('email'));

                Mail::to($request->validated('email'))
                    ->queue(new SentPasswordResetOtp($otp));
            }

            DB::commit();

            // return success response to non-existing/banned users as well for security purposes
            return response()->json([
                'An OTP has been sent to your email address.',
            ]);
        } catch (Throwable $e) {
            DB::rollback();

            logger($e);

            return response()->json([
                'message' => 'An unexpected error has occurred, please try again later.',
            ], 500);
        }
    }

    private function generateOtp(string $email): ?string
    {
        $this->expireCurrentOtp($email);

        $otp = rand(100000, 999999);

        PasswordOtp::create([
            'email' => $email,
            'otp' => $otp,
            'expired_at' => now()->addMinutes(10),
        ]);

        return $otp;
    }

    private function expireCurrentOtp(string $email): void
    {
        PasswordOtp::query()
            ->where('email', $email)
            ->where('expired_at', '>', now())
            ->update(['expired_at' => now()]);
    }
}
