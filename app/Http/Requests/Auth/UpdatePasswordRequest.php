<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;

/**
 * @bodyParam password string required Must be at least 8 characters. Example: -0pBNvYgxw
 * @bodyParam password_confirmation string required Must be the same as password. Example: -0pBNvYgxw
 */
class UpdatePasswordRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'password' => 'required|string|min:8|confirmed',
            'password_confirmation' => 'required|same:password',
        ];
    }
}
