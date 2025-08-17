<?php

namespace App\Http\Requests\Auth;

use App\Http\Requests\BaseFormRequest;

/**
 * @bodyParam name string required Must be under 255 characters.
 * @bodyParam email string required Must be a valid email address.
 * @bodyParam password string required Must be at least 8 characters. Example: -0pBNvYgxw
 * @bodyParam password_confirmation string required Must be same as password. Example: -0pBNvYgxw
 * @return string[]
 */
class RegisterUserRequest extends BaseFormRequest
{
    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email|max:255',
            'password' => 'required|string|confirmed|min:8',
            'password_confirmation' => 'required|same:password',
        ];
    }
}
