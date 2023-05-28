<?php

namespace App\Models;

// Financial Accounts
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

abstract class Account extends Model
{
    public function processToWallet(User $user)
    {
        // TODO: use token
    }

    public function processToOnboardedOrganization(Organization $organization)
    {
        // TODO: use token
    }

    public function processToOrganization(Organization $organization)
    {
        $this->processToWallet($this->user);
        // Account -> Wallet
        // Wallet -> Organization (will be pending)
    }

    public function owner(): MorphTo
    {
        return $this->morphTo();
    }
}
