<?php

namespace App\Http\Controllers\Ticket\Comment;

use App\Http\Controllers\Controller;
use App\Http\Requests\Ticket\Comment\ListCommentsRequest;
use App\Http\Resources\CommentResource;
use App\Models\Ticket;
use Illuminate\Http\Resources\Json\ResourceCollection;
use Illuminate\Support\Facades\Cache;

class ListCommentsController extends Controller
{
    public function __invoke(ListCommentsRequest $request, Ticket $ticket): ResourceCollection
    {
        $cache_tags = ['comments', 'ticket:'.$ticket->id];
        $cache_key = $request->fullUrl();
        $cache_ttl = now()->addMinutes(5);

        $comments = Cache::tags($cache_tags)
            ->remember($cache_key, $cache_ttl, function () use ($request, $ticket) {
                return $ticket
                    ->comments()
                    ->orderBy(
                        $request->validated('order_by', 'created_at'),
                        $request->validated('order_dir', 'desc'),
                    )->paginate($request->validated('per_page', 10));
            });

        return CommentResource::collection($comments);
    }
}
