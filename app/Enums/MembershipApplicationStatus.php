<?php

namespace App\Enums;

enum MembershipApplicationStatus: string
{
    case Pending = 'PENDING';
    case Approved = 'APPROVED';
    case Rejected = 'REJECTED';
}
