<?php

namespace App\Http\Controllers\Ticket\Log;

use App\Http\Controllers\Controller;
use App\Http\Requests\Ticket\Log\ShowTicketLogsRequest;
use App\Http\Resources\TicketLogResource;
use App\Models\Ticket;
use Illuminate\Http\Resources\Json\ResourceCollection;
use Illuminate\Support\Facades\Cache;
use Knuckles\Scribe\Attributes\Group;

#[Group('Ticket Management')]
class ShowTicketLogsController extends Controller
{
    /**
     * View Ticket Logs
     *
     * Only admins can view ticket logs.
     */
    public function __invoke(ShowTicketLogsRequest $request, Ticket $ticket): ResourceCollection
    {
        $cache_tags = ['logs', 'ticket:'.$ticket->id];
        $cache_key = $request->fullUrl();
        $cache_ttl = now()->addMinutes(5);

        $logs = Cache::tags($cache_tags)
            ->remember($cache_key, $cache_ttl, function () use ($request, $ticket) {
                return $ticket
                    ->logs()
                    ->paginate($request->validated('per_page', 10));
            });

        return TicketLogResource::collection($logs);
    }
}
