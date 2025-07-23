<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\ResetPasswordRequest;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class ResetPasswordController extends Controller
{
    public function __invoke(ResetPasswordRequest $request)
    {
        User::query()
            ->where('email', $request->validated('email'))
            ->update([
                'password' => Hash::make($request->password),
            ]);

        return response()->json([
            'message' => 'New password has been saved',
        ]);
    }
}
