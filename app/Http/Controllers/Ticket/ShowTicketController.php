<?php

namespace App\Http\Controllers\Ticket;

use App\Http\Controllers\Controller;
use App\Http\Resources\TicketResource;
use App\Models\Ticket;
use Illuminate\Support\Facades\Gate;
use Knuckles\Scribe\Attributes\Group;

#[Group('Ticket Management')]
class ShowTicketController extends Controller
{
    /**
     * View Ticket
     *
     * Only admins, reporter, assignee, and previous assignee can view ticket details.
     */
    public function __invoke(int $ticket_id): TicketResource
    {
        $ticket = Ticket::query()
            ->with(['reporter', 'assignee'])
            ->findOrFail($ticket_id);

        Gate::authorize('view', $ticket);

        return new TicketResource($ticket);
    }
}
