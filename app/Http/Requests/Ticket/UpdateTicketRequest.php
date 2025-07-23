<?php

namespace App\Http\Requests\Ticket;

use App\Http\Requests\BaseFormRequest;
use Illuminate\Support\Facades\Gate;

class UpdateTicketRequest extends BaseFormRequest
{
    public function authorize(): bool
    {
        return Gate::allows('update', $this->route('ticket'));
    }

    public function rules(): array
    {
        return [
            'title' => 'sometimes|string|max:255',
            'description' => 'sometimes|string|max:1000',
        ];
    }
}
