<?php

namespace App\Enums;

enum SelfDeclarationStatus: string
{
    case Pending = 'PENDING';
    case Approved = 'APPROVED';
    case Rejected = 'REJECTED';
}
