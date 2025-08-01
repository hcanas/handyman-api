<?php

namespace App\Http\Controllers\Ticket;

use App\Http\Controllers\Controller;
use App\Http\Requests\Ticket\AssignTicketRequest;
use App\Models\Ticket;
use App\Models\TicketLog;
use App\Models\User;
use App\Notifications\TicketAssignedNotification;
use App\Notifications\TicketAssignmentReceivedNotification;
use App\TicketAction;
use App\TicketStatus;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Throwable;

class AssignTicketController extends Controller
{
    public function __invoke(AssignTicketRequest $request, Ticket $ticket): JsonResponse
    {
        try {
            DB::beginTransaction();

            $assigned_user = User::find($request->validated('assigned_to_id'));

            $ticket->fill([
                'assigned_to_id' => $assigned_user->id,
                'status' => TicketStatus::InProgress->value,
            ]);

            $this->logActions($request, $ticket, $assigned_user);
            $this->notifyUsers($ticket, $assigned_user);
            $this->clearCache($ticket);

            $ticket->save();

            DB::commit();

            return response()->json([
                'message' => 'Ticket has been assigned to '.$assigned_user->name,
            ]);
        } catch (Throwable $e) {
            DB::rollBack();

            logger($e);

            return response()->json([
                'message' => 'Failed to perform action',
            ], 500);
        }
    }

    private function logActions(AssignTicketRequest $request, Ticket $ticket, User $assigned_user): void
    {
        $action = $ticket->getOriginal('assigned_to_id') !== null
            ? TicketAction::Reassign
            : TicketAction::Assign;

        TicketLog::create([
            'ticket_id' => $ticket->id,
            'user_id' => Auth::id(),
            'action' => $action->value,
            'notes' => $request->validated('notes')
                ?? ($action === TicketAction::Reassign
                    ? 'Re-assigned from '.$ticket->assignee->name.' to '.$assigned_user->name
                    : 'Assigned to '.$assigned_user->name
                ),
        ]);

        TicketLog::create([
            'ticket_id' => $ticket->id,
            'user_id' => $assigned_user->id,
            'action' => TicketAction::ReceivedAssignment->value,
        ]);
    }

    private function notifyUsers(Ticket $ticket, User $assigned_user): void
    {
        $assigned_user->notify(new TicketAssignmentReceivedNotification($ticket));
        $ticket->reporter->notify(new TicketAssignedNotification($ticket));
    }

    private function clearCache(Ticket $ticket): void
    {
        Cache::tags(['logs', 'ticket:'.$ticket->id])->flush();
        Cache::tags(['tickets'])->flush();
    }
}
