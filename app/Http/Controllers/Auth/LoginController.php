<?php

namespace App\Http\Controllers\Auth;

use App\ClientType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class LoginController extends Controller
{
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
        $token = Auth::user()->createToken('web')->plainTextToken;
        $cookie = cookie('token', $token, 60 * 8);

        return response()
            ->json([
                'message' => 'You have logged in successfully',
            ])
            ->withCookie($cookie);
    }

    private function generateMobileClientResponse(): JsonResponse
    {
        $token = Auth::user()->createToken('mobile')->plainTextToken;

        return response()->json([
            'token' => $token,
            'message' => 'You have logged in successfully',
        ]);
    }
}
