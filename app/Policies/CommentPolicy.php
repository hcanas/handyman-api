<?php

namespace App\Policies;

use App\Models\Comment;
use App\Models\Ticket;
use App\Models\User;

class CommentPolicy
{
    public function viewAny(User $user, Ticket $ticket): bool
    {
        return $user->isAdmin()
            || $ticket->reported_by_id === $user->id
            || $ticket->assigned_to_id === $user->id
            || $ticket->logs()
                ->where('user_id', $user->id)
                ->where('action', 'received_assignment')
                ->first();
    }

    public function create(User $user, Ticket $ticket): bool
    {
        return $user->isAdmin()
            || $ticket->reported_by_id === $user->id
            || $ticket->assigned_to_id === $user->id;
    }
}
