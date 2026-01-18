<?php

namespace App;

enum PrimaryMemberType: string
{
    case General = 'GENERAL';
    case Lifetime = 'LIFETIME';
    case Associate = 'ASSOCIATE';
}
