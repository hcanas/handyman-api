<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\BanUserRequest;
use App\Models\User;
use Illuminate\Http\JsonResponse;

class BanUserController extends Controller
{
    public function __invoke(BanUserRequest $request, User $user): JsonResponse
    {
        if ($user->isBanned()) {
            return response()->json([
                'message' => 'User is already banned',
            ], 409);
        }

        $user->update([
            'banned_at' => now(),
            'ban_reason' => $request->ban_reason,
        ]);

        return response()->json([
            'message' => 'User has been banned',
        ]);
    }
}
