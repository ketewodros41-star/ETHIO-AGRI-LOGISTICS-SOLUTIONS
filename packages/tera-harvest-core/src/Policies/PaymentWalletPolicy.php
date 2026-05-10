<?php

namespace Fleetbase\TeraHarvest\Policies;

use Fleetbase\TeraHarvest\Models\PaymentWallet;
use Illuminate\Auth\Access\HandlesAuthorization;

class PaymentWalletPolicy
{
    use HandlesAuthorization;

    public function view($user, PaymentWallet $wallet): bool
    {
        return (string) $wallet->owner_id === (string) $user->id
            || $this->isAdmin($user);
    }

    private function isAdmin($user): bool
    {
        return method_exists($user, 'hasRole') ? $user->hasRole('admin') : false;
    }
}
