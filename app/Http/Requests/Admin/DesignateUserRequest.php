<?php

namespace App\Http\Requests\Admin;

use App\UserRole;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class DesignateUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Gate::allows('designate', $this->route('user'));
    }

    public function rules(): array
    {
        return [
            'role' => [
                'required',
                Rule::in(UserRole::cases()),
            ],
            'department_id' => 'required|integer|exists:departments,id',
        ];
    }
}
