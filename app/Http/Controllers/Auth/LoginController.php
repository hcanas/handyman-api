<?php

namespace App\Http\Controllers\Auth;

use App\ClientType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Knuckles\Scribe\Attributes\Group;

#[Group('Account Management')]
class LoginController extends Controller
{
    /**
     * Login
     *
     * Returns the token value depending on specified X-Client-Platform.
     * - web &mdash; returns token inside a secure cookie
     * - mobile &mdash; returns token as json variable
     *
     * @unauthenticated
     * @header X-Client-Platform web
     */
    public function __invoke(LoginRequest $request): JsonResponse
    {
        $credentials = $request->only(['email', 'password']);
        $client = ClientType::tryFrom($request->header('X-Client-Platform'));

        if (!$client) {
            return response()->json([
                'message' => 'The selected platform is invalid',
            ], 400);
        }

        if (Auth::attemptWhen($credentials, fn(User $user) => !$user->isBanned())) {
            if ($client === ClientType::Web) {
                return $this->generateWebClientResponse();
            } elseif ($client === ClientType::Mobile) {
                return $this->generateMobileClientResponse();
            }
        }

        return response()->json([
            'message' => 'Your email or password is incorrect',
        ], 401);
    }

    private function generateWebClientResponse(): JsonResponse
    {
        $is_production = app()->environment('production');

        $expires_at = now()->addHours(8);
        $token = Auth::user()->createToken('web', ['*'], $expires_at)->plainTextToken;

        $cookie = cookie(
            name: 'token',
            value: $token,
            minutes: 60 * 10,
            secure: $is_production,
            sameSite: $is_production ? 'none' : 'lax',
        );

        return response()
            ->json([
                'message' => 'You have logged in successfully',
                'user' => [
                    'name' => Auth::user()->name,
                    'email' => Auth::user()->email,
                    'role' => Auth::user()->role,
                ],
            ])
            ->withCookie($cookie);
    }

    private function generateMobileClientResponse(): JsonResponse
    {
        $expires_at = now()->addHours(8);
        $token = Auth::user()->createToken('mobile', ['*'], $expires_at)->plainTextToken;

        return response()->json([
            'token' => $token,
            'user' => [
                'name' => Auth::user()->name,
                'email' => Auth::user()->email,
                'role' => Auth::user()->role,
            ],
            'message' => 'You have logged in successfully',
        ]);
    }
}
