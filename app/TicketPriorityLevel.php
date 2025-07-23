<?php

namespace App;

enum TicketPriorityLevel: string
{
    case Low = 'low';
    case Medium = 'medium';
    case High = 'high';
}
