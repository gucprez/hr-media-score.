<?php

namespace App\Policies;

class GamePolicy extends ClubOwnedPolicy
{
    protected function permission(): string
    {
        return 'games.manage';
    }
}
