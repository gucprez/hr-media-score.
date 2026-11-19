<?php

namespace App\Policies;

class PlayerPolicy extends ClubOwnedPolicy
{
    protected function permission(): string
    {
        return 'players.manage';
    }
}
