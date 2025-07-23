<?php

namespace App\Http\Controllers\Ticket;

use App\Http\Controllers\Controller;
use App\Http\Requests\Ticket\UpdateTicketRequest;
use App\Http\Resources\TicketResource;
use App\Models\Ticket;
use Illuminate\Support\Facades\Cache;

class UpdateTicketController extends Controller
{
    public function __invoke(UpdateTicketRequest $request, Ticket $ticket): TicketResource
    {
        $ticket
            ->fill($request->validated())
            ->save();

        Cache::tags(['tickets'])->flush();

        return new TicketResource($ticket);
    }
}
