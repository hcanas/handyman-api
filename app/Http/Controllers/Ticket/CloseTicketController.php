<?php

namespace App\Http\Controllers\Ticket;

use App\Http\Controllers\Controller;
use App\Http\Requests\Ticket\CloseTicketRequest;
use App\Models\Ticket;
use App\Models\TicketLog;
use App\Notifications\TicketClosedNotification;
use App\TicketAction;
use App\TicketStatus;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Throwable;

class CloseTicketController extends Controller
{
    public function __invoke(CloseTicketRequest $request, Ticket $ticket)
    {
        try {
            DB::beginTransaction();

            $ticket->fill([
                'status' => TicketStatus::Closed->value,
            ]);

            $this->logActions($request, $ticket);
            $this->notifyUsers($ticket);
            $this->clearCache($ticket);

            $ticket->save();

            DB::commit();

            return response()->json([
                'message' => 'Ticket has been closed',
            ]);
        } catch (Throwable $e) {
            DB::rollBack();

            logger($e);

            return response()->json([
                'message' => 'Failed to perform action.',
            ], 500);
        }
    }

    private function logActions(CloseTicketRequest $request, Ticket $ticket): void
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
        $ticket->assignee->notify(new TicketClosedNotification($ticket));
    }

    private function clearCache(Ticket $ticket): void
    {
        Cache::tags(['logs', 'ticket:'.$ticket->id])->flush();
        Cache::tags(['tickets'])->flush();
    }
}
