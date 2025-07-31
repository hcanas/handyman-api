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
            'page' => 'sometimes|numeric|min:1',
            'per_page' => 'sometimes|numeric|min:1',
            'order_by' => [
                'sometimes',
                Rule::in([
                    'created_at',
                    'updated_at',
                    'status',
                    'priority',
                ]),
            ],
            'order_dir' => [
                'sometimes',
                Rule::in([
                    'asc',
                    'desc',
                ]),
            ],
        ];
    }
}
