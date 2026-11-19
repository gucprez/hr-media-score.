<?php

namespace App\Policies;

class TeamPolicy extends ClubOwnedPolicy
{
    protected function permission(): string
    {
        return 'teams.manage';
    }
}
