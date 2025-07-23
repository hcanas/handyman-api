<?php

namespace App\Http\Requests\Ticket;

use App\Http\Requests\BaseFormRequest;
use App\Models\Ticket;
use App\TicketPriorityLevel;
use App\TicketStatus;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class StoreTicketRequest extends BaseFormRequest
{
    public function authorize(): bool
    {
        return Gate::allows('create', Ticket::class);
    }

    public function rules(): array
    {
        return [
            'title' => 'required|string|max:255',
            'description' => 'required|string|max:1000',
            'priority' => [
                'required',
                Rule::in(TicketPriorityLevel::cases()),
            ],
            'status' => [
                'required',
                Rule::in(TicketStatus::cases()),
            ],
            'reported_by_id' => 'required|exists:users,id',
        ];
    }
}
