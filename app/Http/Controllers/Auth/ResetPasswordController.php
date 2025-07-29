<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\ResetPasswordRequest;
use App\Models\PasswordOtp;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class ResetPasswordController extends Controller
{
    public function __invoke(ResetPasswordRequest $request)
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
