<?php

namespace App\Policies;

class VenuePolicy extends ClubOwnedPolicy
{
    protected function permission(): string
    {
        return 'venues.manage';
    }
}
