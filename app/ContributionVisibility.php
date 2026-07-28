<?php

namespace App;

enum ContributionVisibility: string
{
    case Public = 'public';
    case OwnerOnly = 'owner';
    case Hidden = 'hidden';
}
