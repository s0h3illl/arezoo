<?php

namespace App\Enums;

enum WithdrawalDecision: string
{
    case Accept = 'accept';
    case Pay = 'pay';
}
