<?php

namespace App\Http\Controllers\Auth;

use App\ClientType;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Knuckles\Scribe\Attributes\Group;
use Knuckles\Scribe\Attributes\Subgroup;

#[Group('Account Management')]
class LogoutController extends Controller
{
    /**
     * Logout
     *
     * Deletes current token from the database.
     *
     * If the user logged in via "web" platform, it will delete the token inside the cookie as well.
     *
     * @header X-Client-Platform web
     */
    public function __invoke(Request $request): JsonResponse
    {
        $client = ClientType::tryFrom($request->header('X-Client-Platform'));

        Auth::user()
            ->currentAccessToken()
            ->delete();

        if (!$client) {
            return response()->json([
                'message' => 'The selected platform is invalid',
            ], 400);
        }

        if ($client === ClientType::Web) {
            return $this->generateWebClientResponse();
        } elseif ($client === ClientType::Mobile) {
            return $this->generateMobileClientResponse();
        }

        return response()->json([
            'message' => 'You have logged out successfully',
        ]);
    }

    private function generateWebClientResponse(): JsonResponse
    {
        return response()
            ->json([
                'message' => 'You have logged out successfully',
            ])
            ->withCookie(cookie()->forget('token'));
    }

    private function generateMobileClientResponse(): JsonResponse
    {
        return response()->json([
            'message' => 'You have logged out successfully',
        ]);
    }
}
