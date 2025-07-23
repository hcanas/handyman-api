<?php

namespace App\Http\Requests\Ticket;

use App\Http\Requests\BaseFormRequest;
use App\Models\Ticket;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class ListTicketsRequest extends BaseFormRequest
{
    public function authorize(): bool
    {
        return Gate::allows('viewAny', Ticket::class);
    }

    public function rules(): array
    {
        return [
            'page' => 'sometimes|numeric|min:0',
            'per_page' => 'sometimes|numeric|min:0',
            'order_by' => [
                'sometimes',
                'required_with:order_dir',
                Rule::in([
                    'created_at',
                    'updated_at',
                    'status',
                    'priority',
                ]),
            ],
            'order_dir' => [
                'sometimes',
                'required_with:order_by',
                Rule::in([
                    'asc',
                    'desc',
                ]),
            ],
        ];
    }
}
