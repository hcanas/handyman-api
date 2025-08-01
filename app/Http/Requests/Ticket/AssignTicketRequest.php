<?php

namespace App\Http\Requests\Ticket;

use App\TicketStatus;
use App\UserRole;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class AssignTicketRequest extends BaseTicketStatusRequest
{
    public function authorize(): bool
    {
        return Gate::allows('assign', $this->route('ticket'));
    }

    public function rules(): array
    {
        return [
            'assigned_to_id' => [
                'required',
                Rule::exists('users', 'id')
                    ->where('role', UserRole::Technician->value),
            ],
            'notes' => 'nullable|string|max:1000',
        ];
    }

    protected function withValidator(Validator $validator): void
    {
        $validator->after(function ($validator) {
            $ticket_status = TicketStatus::tryFrom($this->route('ticket')->status->value);

            if (
                $ticket_status !== TicketStatus::InProgress &&
                !$this->isValidTransition($ticket_status, TicketStatus::InProgress)
            ) {
                $validator->errors()->add('action', 'You can only re-assign if the ticket is pending or in progress');
            }
        });
    }
}
