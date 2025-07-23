<?php

namespace App\Http\Requests\Ticket;

use App\Http\Requests\BaseFormRequest;
use App\TicketStatus;

abstract class BaseTicketStatusRequest extends BaseFormRequest
{
    protected function isValidTransition(?TicketStatus $from, ?TicketStatus $to): bool
    {
        return match ($from) {
            TicketStatus::Pending => in_array($to, [
                TicketStatus::InProgress,
                TicketStatus::Cancelled,
            ]),
            TicketStatus::InProgress => in_array($to, [
                TicketStatus::Resolved,
                TicketStatus::Cancelled,
            ]),
            TicketStatus::Resolved => in_array($to, [
                TicketStatus::Closed,
                TicketStatus::InProgress,
            ]),
            TicketStatus::Closed => in_array($to, [
                TicketStatus::InProgress,
            ]),
            TicketStatus::Cancelled => false,
            default => false,
        };
    }
}
