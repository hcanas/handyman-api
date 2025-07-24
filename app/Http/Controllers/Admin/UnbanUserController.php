<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Support\Facades\Gate;

class UnbanUserController extends Controller
{
    public function __invoke(User $user)
    {
        Gate::authorize('unban', $user);

        if (!$user->isBanned()) {
            return response()->json([
                'message' => 'User is already active',
            ], 409);
        }

        $user->update([
            'banned_at' => null,
            'ban_reason' => null,
        ]);

        return response()->json([
            'message' => 'User has been unbanned',
        ]);
    }
}
