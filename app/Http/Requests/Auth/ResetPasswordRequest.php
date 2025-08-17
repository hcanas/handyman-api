<?php

namespace App\Http\Requests\Auth;

use App\Http\Requests\BaseFormRequest;
use App\Rules\ValidOtp;

/**
 * @bodyParam email string required Must be a valid email address.
 * @bodyParam password string required Must be at least 8 characters. Example: -0pBNvYgxw
 * @bodyParam password_confirmation string required Must be same as password. Example: -0pBNvYgxw
 * @bodyParam otp int required 6-digit code sent to your email address upon request. Example: 123456
 */
class ResetPasswordRequest extends BaseFormRequest
{
    public function rules(): array
    {
        return [
            'email' => 'bail|required|email|max:255',
            'password' => 'bail|required|confirmed|min:8',
            'password_confirmation' => 'bail|required|same:password',
            'otp' => [
                'bail',
                'required',
                'numeric',
                'digits:6',
                new ValidOtp($this->input('email', '')),
            ],
        ];
    }
}
