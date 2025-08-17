<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Gate;
use Knuckles\Scribe\Attributes\Group;

#[Group('User Management')]
class UnbanUserController extends Controller
{
    /**
     * Unban User
     *
     * Only admins can unban users.
     */
    public function __invoke(User $user): JsonResponse
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
