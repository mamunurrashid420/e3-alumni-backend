<?php

namespace App\Enums;

enum EventStatus: string
{
    case Draft = 'draft';
    case Open = 'open';
    case Closed = 'closed';
}
