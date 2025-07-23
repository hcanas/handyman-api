<?php

namespace App;

enum TicketAction: string
{
    case StatusChange = 'status_change';
    case Assign = 'assign';
    case Reassign = 'reassign';
    case ReceivedAssignment = 'received_assignment';
    case Comment = 'comment';
}
