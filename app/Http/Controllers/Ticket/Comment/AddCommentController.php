<?php

namespace App\Http\Controllers\Ticket\Comment;

use App\CommentType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Ticket\Comment\StoreCommentRequest;
use App\Http\Resources\CommentResource;
use App\Models\Comment;
use App\Models\Ticket;
use App\Models\TicketLog;
use App\Notifications\TicketCommentedNotification;
use App\TicketAction;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Throwable;

class AddCommentController extends Controller
{
    public function __invoke(StoreCommentRequest $request, Ticket $ticket): CommentResource|JsonResponse
    {
        try {
            DB::beginTransaction();

            if ($request->validated('message')) {
                $comment = $this->addTextComment($ticket, $request->validated());
            } elseif ($request->validated('file')) {
                $comment = $this->addFileComment($ticket, $request->file('file'));
            }

            $this->logActions($comment);
            $this->notifyUsers($ticket, $comment);
            $this->clearCache($comment);

            DB::commit();

            return new CommentResource($comment);
        } catch (Throwable $e) {
            DB::rollBack();

            logger($e);

            return response()->json([
                'message' => 'An unexpected error has occurred',
            ], 500);
        }
    }

    private function addTextComment(Ticket $ticket, array $data): Comment
    {
        return Auth::user()
            ->comments()
            ->create([
                ...$data,
                'ticket_id' => $ticket->id,
                'type' => CommentType::Text->value,
            ]);
    }

    private function addFileComment(Ticket $ticket, UploadedFile $file): Comment
    {
        try {
            $filename = $file->hashName();
            $path = $file->storeAs('comments/attachments/', $filename);

            $comment = Auth::user()
                ->comments()
                ->create([
                    'ticket_id' => $ticket->id,
                    'type' => CommentType::File->value,
                ]);

            $comment
                ->attachments()
                ->create([
                    'filename' => $filename,
                ]);

            $comment->load('attachments');

            return $comment;
        } catch (Throwable $e) {
            if (isset($path) && Storage::exists($path)) {
                Storage::delete($path);
            }

            throw $e;
        }
    }

    private function logActions(Comment $comment): void
    {
        TicketLog::create([
            'ticket_id' => $comment->ticket_id,
            'user_id' => Auth::id(),
            'action' => TicketAction::Comment->value,
        ]);
    }

    private function notifyUsers(Ticket $ticket, Comment $comment): void
    {
        $notifiable = $ticket->reported_by_id === $comment->user_id
            ? $ticket->assignee
            : $ticket->reporter;

        if ($notifiable) {
            $notifiable->notify(new TicketCommentedNotification($ticket));
        }
    }

    private function clearCache(Comment $comment): void
    {
        $cache_tags = ['comments', 'ticket:'.$comment->ticket_id];

        Cache::tags($cache_tags)->flush();
    }
}
