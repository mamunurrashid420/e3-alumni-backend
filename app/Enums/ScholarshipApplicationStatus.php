<?php

namespace App\Enums;

enum ScholarshipApplicationStatus: string
{
    case Pending = 'PENDING';
    case Approved = 'APPROVED';
    case Rejected = 'REJECTED';
}
