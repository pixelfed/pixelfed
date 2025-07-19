<?php

namespace App\Policies;

use App\Models\CustomFilter;
use App\User;

class CustomFilterPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return false;
    }

    /**
     * Determine whether the user can view the custom filter.
     *
     * @return bool
     */
    public function view(User $user, CustomFilter $filter)
    {
        return $user->profile_id === $filter->profile_id;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return CustomFilter::whereProfileId($user->profile_id)->count() <= 100;
    }

    /**
     * Determine whether the user can update the custom filter.
     *
     * @return bool
     */
    public function update(User $user, CustomFilter $filter)
    {
        return $user->profile_id === $filter->profile_id;
    }

    /**
     * Determine whether the user can delete the custom filter.
     *
     * @return bool
     */
    public function delete(User $user, CustomFilter $filter)
    {
        return $user->profile_id === $filter->profile_id;
    }
}
