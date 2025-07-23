<?php

namespace App\Models;

use App\TicketPriorityLevel;
use App\TicketStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Ticket extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'description',
        'priority_level',
        'status',
        'reported_by_id',
        'assigned_to_id',
        'department_name_snapshot',
        'resolved_at',
    ];

    protected function casts(): array
    {
        return [
            'priority' => TicketPriorityLevel::class,
            'status' => TicketStatus::class,
        ];
    }

    public function isPending(): bool
    {
        return $this->status === TicketStatus::Pending;
    }

    public function isInProgress(): bool
    {
        return $this->status === TicketStatus::InProgress;
    }

    public function isResolved(): bool
    {
        return $this->status === TicketStatus::Resolved;
    }

    public function isClosed(): bool
    {
        return $this->status === TicketStatus::Closed;
    }

    public function isCancelled(): bool
    {
        return $this->status === TicketStatus::Cancelled;
    }

    public function reporter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reported_by_id');
    }

    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to_id');
    }

    public function comments(): HasMany
    {
        return $this->hasMany(Comment::class);
    }

    public function logs(): HasMany
    {
        return $this->hasMany(TicketLog::class);
    }
}
