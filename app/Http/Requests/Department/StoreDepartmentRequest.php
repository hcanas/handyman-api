<?php

namespace App\Http\Requests\Department;

use App\Http\Requests\BaseFormRequest;
use App\Models\Department;
use Illuminate\Support\Facades\Gate;

class StoreDepartmentRequest extends BaseFormRequest
{
    public function authorize(): bool
    {
        return Gate::allows('create', Department::class);
    }

    public function rules(): array
    {
        return [
            'name' => 'required|max:255|unique:departments,name',
        ];
    }
}
