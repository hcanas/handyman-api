<?php

namespace App\Http\Requests\Ticket;

use App\TicketStatus;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Validator;

class ResolveTicketRequest extends BaseTicketStatusRequest
{
    public function authorize(): bool
    {
        return Gate::allows('resolve', $this->route('ticket'));
    }

    public function rules(): array
    {
        return [
            'notes' => 'nullable|string|max:1000',
        ];
    }

    protected function withValidator(Validator $validator): void
    {
        $validator->after(function ($validator) {
            $ticket_status = TicketStatus::tryFrom($this->route('ticket')->status->value);

            if (!$this->isValidTransition($ticket_status, TicketStatus::Resolved)) {
                $validator->errors()->add('action', 'Ticket cannot be resolved from its current status.');
            }
        });
    }
}
