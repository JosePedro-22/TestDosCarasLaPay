<?php

namespace App\Observers;

use App\Models\User;

class UserObserver
{
    public function created(User $user): void
    {
        $user->wallet()->create(['balance' => 0]);
    }
}
