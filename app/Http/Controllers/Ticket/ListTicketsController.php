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

class ListTicketsController extends Controller
{
    public function __invoke(ListTicketsRequest $request): ResourceCollection
    {
        $user = Auth::user();

        $cache_tags = ['tickets', 'user:'.$user->id];
        $cache_key = $request->fullUrl();
        $cache_ttl = now()->addMinutes(5);

        $tickets = Cache::tags($cache_tags)
            ->remember($cache_key, $cache_ttl, function () use ($request, $user) {
                $query = Ticket::query();

                if ($user->isStaff()) {
                    $query = $query->where('reported_by_id', $user->id);
                } elseif ($user->isTechnician()) {
                    $query = $query->whereHas('logs', function ($logs_query) use ($user) {
                        $logs_query
                            ->where('action', TicketAction::ReceivedAssignment->value)
                            ->where('user_id', $user->id);
                    });
                }

                return $query
                    ->orderBy(
                        $request->validated('order_by', 'created_at'),
                        $request->validated('order_dir', 'desc'),
                    )->paginate($request->validated('per_page', 15));
            });

        return TicketResource::collection($tickets);
    }
}
