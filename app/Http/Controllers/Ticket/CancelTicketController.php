<?php

namespace App\Http\Controllers\Ticket;

use App\Http\Controllers\Controller;
use App\Http\Requests\Ticket\CancelTicketRequest;
use App\Models\Ticket;
use App\Models\TicketLog;
use App\Notifications\TicketCancelledNotification;
use App\TicketAction;
use App\TicketStatus;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Throwable;

class CancelTicketController extends Controller
{
    public function __invoke(CancelTicketRequest $request, Ticket $ticket)
    {
        try {
            DB::beginTransaction();

            $ticket->fill([
                'status' => TicketStatus::Cancelled->value,
            ]);

            $this->logActions($request, $ticket);
            $this->notifyUsers($ticket);
            $this->clearCache($ticket);

            $ticket->save();

            DB::commit();

            return response()->json([
                'message' => 'Ticket has been cancelled',
            ]);
        } catch (Throwable $e) {
            DB::rollBack();

            logger($e);

            return response()->json([
                'message' => 'Failed to perform action.',
            ], 500);
        }
    }

    private function logActions(CancelTicketRequest $request, Ticket $ticket): void
    {
        TicketLog::create([
            'ticket_id' => $ticket->id,
            'user_id' => Auth::id(),
            'action' => TicketAction::StatusChange->value,
            'from_status' => $ticket->getOriginal('status'),
            'to_status' => $ticket->status->value,
            'notes' => $request->validated('notes'),
        ]);
    }

    private function notifyUsers(Ticket $ticket): void
    {
        $notifiables = collect([]);

        if (Auth::user()->isAdmin()) {
            $notifiables->push($ticket->reporter);
        }

        if ($ticket->getOriginal('status') === TicketStatus::InProgress
            && $ticket->assigned_to_id
        ) {
            $notifiables->push($ticket->assignee);
        }

        if ($notifiables->isNotEmpty()) {
            Notification::send($notifiables, new TicketCancelledNotification($ticket));
        }
    }

    private function clearCache(Ticket $ticket): void
    {
        Cache::tags(['logs', 'ticket:'.$ticket->id])->flush();
        Cache::tags(['tickets'])->flush();
    }
}
