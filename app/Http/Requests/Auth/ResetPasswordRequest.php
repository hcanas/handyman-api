<?php

namespace App\Http\Requests\Auth;

use App\Rules\ValidOtp;
use Illuminate\Foundation\Http\FormRequest;

class ResetPasswordRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'email' => 'bail|required|email|max:255',
            'password' => 'bail|required|confirmed|min:8',
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
