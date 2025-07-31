<?php

namespace App\Http\Requests\Ticket\Log;

use App\Http\Requests\BaseFormRequest;
use App\Models\TicketLog;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class ShowTicketLogsRequest extends BaseFormRequest
{
    public function authorize(): bool
    {
        return Gate::allows('viewAny', [TicketLog::class, $this->route('ticket')]);
    }

    public function rules(): array
    {
        return [
            'page' => 'sometimes|numeric|min:1',
            'per_page' => 'sometimes|numeric|min:1',
            'order_by' => [
                'sometimes',
                'required_with:order_dir',
                Rule::in([
                    'created_at',
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
