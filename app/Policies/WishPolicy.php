<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Wish;

class WishPolicy
{
    public function delete(User $user, Wish $wish): bool
    {
        return $user->id === $wish->user_id;
    }
}
