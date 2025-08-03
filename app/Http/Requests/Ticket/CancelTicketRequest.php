<?php

namespace App\Http\Requests\Ticket;

use App\TicketStatus;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Validator;

class CancelTicketRequest extends BaseTicketStatusRequest
{
    public function authorize(): bool
    {
        return Gate::allows('cancel', $this->route('ticket'));
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
            if (!$this->isValidTransition($this->route('ticket')->status, TicketStatus::Cancelled)) {
                $validator->errors()->add('action', 'Ticket cannot be cancelled from its current status.');
            }
        });
    }
}
