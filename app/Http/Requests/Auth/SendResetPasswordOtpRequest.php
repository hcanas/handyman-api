<?php

namespace App\Http\Requests\Auth;

use App\Http\Requests\BaseFormRequest;

/**
 * @bodyParam email string required Must be a valid email address.
 */
class SendResetPasswordOtpRequest extends BaseFormRequest
{
    public function rules(): array
    {
        return [
            'email' => 'required|email|max:255',
        ];
    }
}
