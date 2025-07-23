<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\RegisterUserRequest;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Hash;

class RegisterController extends Controller
{
    public function __invoke(RegisterUserRequest $request): JsonResponse
    {
        User::create([
            ...$request->validated(),
            'password' => Hash::make($request->password),
        ]);

        return response()->json([
            'message' => 'Your account has been created.',
        ], 201);
    }
}
