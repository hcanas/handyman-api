<?php

namespace App\Http\Controllers\Ticket;

use App\Http\Controllers\Controller;
use App\Http\Requests\Ticket\ListTicketsRequest;
use App\Http\Resources\TicketResource;
use App\Models\Ticket;
use App\TicketAction;
use Illuminate\Http\Resources\Json\ResourceCollection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Knuckles\Scribe\Attributes\Group;

#[Group('Ticket Management')]
class ListTicketsController extends Controller
{
    /**
     * List Tickets
     */
    public function __invoke(ListTicketsRequest $request): ResourceCollection
    {
        $user = Auth::user();

        $cache_tags = ['tickets', 'user:'.$user->id];
        $cache_key = $request->fullUrl();
        $cache_ttl = now()->addMinutes(5);

        $tickets = Cache::tags($cache_tags)
            ->remember($cache_key, $cache_ttl, function () use ($request, $user) {
                $query = Ticket::query()
                    ->where(function ($query) use ($request) {
                        $query->where('id', $request->validated('keyword'))
                            ->orWhere('title', 'LIKE', '%' . $request->validated('keyword') . "%")
                            ->orWhere('description', 'LIKE', '%' . $request->validated('keyword') . "%")
                            ->orWhere('status', $request->validated('keyword'))
                            ->orWhere('priority_level', $request->validated('keyword'));
                    })
                    ->with(['reporter', 'assignee']);

                if (!$user->isAdmin()) {
                    $query = $query->where(function ($query) use ($user) {
                        $query->where('reported_by_id', $user->id)
                            ->orWhere('assigned_to_id', $user->id)
                            ->orWhereHas('logs', function ($logs_query) use ($user) {
                                $logs_query
                                    ->where('action', TicketAction::ReceivedAssignment->value)
                                    ->where('user_id', $user->id);
                            });
                        });
                }

                return $query
                    ->orderBy(
                        $request->validated('order_by', 'created_at'),
                        $request->validated('order_dir', 'desc'),
                    )->paginate($request->validated('per_page', 15));
            });

        $tickets->withQueryString();

        return TicketResource::collection($tickets);
    }
}
