<?php

namespace App\Enums;

enum WithdrawalStatus: string
{
    case Requested = 'requested';
    case Accepted = 'accepted';
    case Paid = 'paid';
    case Rejected = 'rejected';
}
