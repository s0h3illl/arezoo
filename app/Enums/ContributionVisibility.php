<?php

namespace App\Enums;

enum ContributionVisibility: string
{
    case Public = 'public';
    case OwnerOnly = 'owner';
    case Hidden = 'hidden';
}
