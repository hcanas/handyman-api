<?php

namespace App\Policies;

use App\Models\Ticket;
use App\Models\User;
use App\TicketAction;

class TicketPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isAdmin()
            || $user->isStaff()
            || $user->isTechnician();
    }

    public function view(User $user, Ticket $ticket): bool
    {
        return $user->isAdmin()
            || $ticket->reported_by_id === $user->id
            || $ticket->assigned_to_id === $user->id
            || $ticket->logs()
                ->where('user_id', $user->id)
                ->where('action', TicketAction::ReceivedAssignment->value)
                ->exists();
    }

    public function create(User $user): bool
    {
        return $user->isStaff();
    }

    public function update(User $user, Ticket $ticket): bool
    {
        return $ticket->isPending() &&
            $ticket->reported_by_id === $user->id &&
            $user->isStaff();
    }

    public function assign(User $user, Ticket $ticket): bool
    {
        return ($ticket->isPending() || $ticket->isInProgress())
            && $user->isAdmin();
    }

    public function resolve(User $user, Ticket $ticket): bool
    {
        return $ticket->isInProgress()
            && $user->isAdmin() || $ticket->assigned_to_id === $user->id;
    }

    public function rejectResolution(User $user, Ticket $ticket): bool
    {
        return $ticket->isResolved()
            && $user->isAdmin() || $ticket->reported_by_id === $user->id;
    }

    public function close(User $user, Ticket $ticket): bool
    {
        return $ticket->isResolved()
            && $user->isAdmin() || $ticket->reported_by_id === $user->id;
    }

    public function cancel(User $user, Ticket $ticket): bool
    {
        return ($ticket->isPending() || $ticket->isInProgress())
            && ($user->isAdmin() || $ticket->reported_by_id === $user->id);
    }
}
