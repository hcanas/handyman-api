<?php

namespace App\Http\Requests\Department;

use App\Http\Requests\BaseFormRequest;
use App\Models\Department;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class UpdateDepartmentRequest extends BaseFormRequest
{
    public function authorize(): bool
    {
        return Gate::allows('update', $this->route('department'));
    }

    public function rules(): array
    {
        return [
            'name' => [
                'required',
                'max:255',
                Rule::unique('departments', 'name')
                    ->ignore($this->route('department')),
            ],
        ];
    }
}
