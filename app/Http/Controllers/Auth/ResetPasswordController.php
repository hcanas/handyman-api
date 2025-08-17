<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\ResetPasswordRequest;
use App\Models\PasswordOtp;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Knuckles\Scribe\Attributes\Group;

#[Group('Account Management')]
class ResetPasswordController extends Controller
{
    /**
     * Reset Password
     *
     * A valid OTP is required to set a new password.
     */
    public function __invoke(ResetPasswordRequest $request): JsonResponse
    {
        try {
            DB::beginTransaction();

            User::query()
                ->where('email', $request->validated('email'))
                ->update(['password' => Hash::make($request->password)]);

            PasswordOtp::query()
                ->where('email', $request->validated('email'))
                ->where('otp', $request->validated('otp'))
                ->delete();

            DB::commit();

            // do not return an error if user doesn't exist for security purposes
            return response()->json([
                'message' => 'New password has been saved',
            ]);
        } catch (\Throwable $e) {
            DB::rollBack();

            logger($e);

            return response()->json([
                'message' => 'An unexpected error occurred',
            ], 500);
        }
    }
}
