<?php

namespace App;

enum ContributionStatus: string
{
    case Pending = 'pending';
    case Paid = 'paid';
}
