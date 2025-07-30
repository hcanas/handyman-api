<?php

namespace App\Http\Controllers\Ticket;

use App\Http\Controllers\Controller;
use App\Http\Requests\Ticket\StoreTicketRequest;
use App\Http\Resources\TicketResource;
use App\Models\Ticket;
use App\Models\User;
use App\Notifications\TicketCreatedNotification;
use App\TicketStatus;
use App\UserRole;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Throwable;

class CreateTicketController extends Controller
{
    public function __invoke(StoreTicketRequest $request): TicketResource|JsonResponse
    {
        try {
            DB::beginTransaction();

            $ticket = Ticket::create([
                ...$request->validated(),
                'status' => TicketStatus::Pending->value,
                'department_name_snapshot' => Auth::user()->department->name,
            ]);

            $this->notifyUsers($ticket);
            $this->clearCache();

            DB::commit();

            return new TicketResource($ticket);
        } catch (Throwable $e) {
            DB::rollBack();

            logger($e);

            return response()->json([
                'message' => 'An unexpected error has occurred.',
            ], 500);
        }
    }

    private function notifyUsers(Ticket $ticket): void
    {
        $notifiables = User::query()
            ->where('role', UserRole::Admin)
            ->get();

        if ($notifiables->isNotEmpty()) {
            Notification::send($notifiables, new TicketCreatedNotification($ticket));
        }
    }

    private function clearCache(): void
    {
        Cache::tags(['tickets'])->flush();
    }
}
