<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\UpdatePasswordRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Knuckles\Scribe\Attributes\Group;

#[Group('Account Management')]
class UpdatePasswordController extends Controller
{
    /**
     * Update Password
     *
     * Sets a new password.
     */
    public function __invoke(UpdatePasswordRequest $request): JsonResponse
    {
        Auth::user()
            ->update([
                'password' => Hash::make($request->validated('password')),
            ]);

        return response()->json([
            'message' => 'Password has been changed',
        ]);
    }
}
