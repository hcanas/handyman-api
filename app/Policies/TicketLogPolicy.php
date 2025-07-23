<?php

namespace App\Policies;

use App\Models\Ticket;
use App\Models\User;

class TicketLogPolicy
{
    public function viewAny(User $user, Ticket $ticket): bool
    {
        return $user->isAdmin()
            || $ticket->logs()
                ->where('user_id', $user->id)
                ->where('action', 'received_assignment')
                ->first();
    }
}
