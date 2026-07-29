<?php

namespace App\Enums;

enum ContributionStatus: string
{
    case Pending = 'pending';
    case Paid = 'paid';
}
