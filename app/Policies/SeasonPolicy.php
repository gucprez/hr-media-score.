<?php

namespace App\Policies;

class SeasonPolicy extends ClubOwnedPolicy
{
    protected function permission(): string
    {
        return 'seasons.manage';
    }
}
