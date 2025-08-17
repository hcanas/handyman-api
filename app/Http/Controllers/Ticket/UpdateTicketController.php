<?php

namespace App\Http\Controllers\Ticket;

use App\Http\Controllers\Controller;
use App\Http\Requests\Ticket\UpdateTicketRequest;
use App\Http\Resources\TicketResource;
use App\Models\Ticket;
use Illuminate\Support\Facades\Cache;
use Knuckles\Scribe\Attributes\Group;

#[Group('Ticket Management')]
class UpdateTicketController extends Controller
{
    /**
     * Update Ticket
     *
     * Only reporter can update ticket's title and/or description.
     */
    public function __invoke(UpdateTicketRequest $request, Ticket $ticket): TicketResource
    {
        $ticket
            ->fill($request->validated())
            ->save();

        Cache::tags(['tickets'])->flush();

        return new TicketResource($ticket);
    }
}
