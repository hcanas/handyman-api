<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;

class BanUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Gate::allows('ban', $this->route('user'));
    }

    public function rules(): array
    {
        return [
            'ban_reason' => 'required|string',
        ];
    }
}
