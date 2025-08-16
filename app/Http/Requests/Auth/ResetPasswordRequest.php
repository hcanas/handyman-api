<?php

namespace App\Http\Requests\Auth;

use App\Http\Requests\BaseFormRequest;
use App\Rules\ValidOtp;

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
