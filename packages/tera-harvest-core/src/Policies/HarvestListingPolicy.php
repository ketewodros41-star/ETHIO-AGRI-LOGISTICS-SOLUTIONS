<?php

namespace Fleetbase\TeraHarvest\Policies;

use Fleetbase\TeraHarvest\Models\HarvestListing;
use Illuminate\Auth\Access\HandlesAuthorization;

class HarvestListingPolicy
{
    use HandlesAuthorization;

    public function viewAny($user): bool
    {
        return true; // All authenticated users can browse listings
    }

    public function view($user, HarvestListing $listing): bool
    {
        return true;
    }

    public function create($user): bool
    {
        return $this->hasRole($user, ['farmer', 'cooperative', 'admin']);
    }

    public function update($user, HarvestListing $listing): bool
    {
        if ($this->hasRole($user, ['admin'])) {
            return true;
        }
        return (string) $listing->farmer_id === (string) $user->id
            || (string) $listing->cooperative_id === (string) $user->id;
    }

    public function delete($user, HarvestListing $listing): bool
    {
        return $this->update($user, $listing);
    }

    private function hasRole($user, array $roles): bool
    {
        return method_exists($user, 'hasRole')
            ? $user->hasRole($roles)
            : true; // fallback: allow if role system not present
    }
}
