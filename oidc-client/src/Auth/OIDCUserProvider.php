<?php


namespace GCS\OIDCClient\Auth;


use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\Auth\UserProvider;


class OIDCUserProvider implements UserProvider
{
    private function is_admin($user_info)
    {
	    if (!property_exists($user_info, "resource_access"))
		    return false;
	    if (!property_exists($user_info->resource_access, "pixelfed"))
		    return false;
	    if (!property_exists($user_info->resource_access->pixelfed, "roles"))
		    return false;
	    return in_array("admin", $user_info->resource_access->pixelfed->roles);
    }

    public function retrieveByInfo($user_info)
    {
        \Log::info(print_r($user_info, true));
        $model = config('auth.providers.users.model');
	$user = $model::where('email', $user_info->email)->first();
	if ($user) {
		// user exists, make sure the other fields match
		$need_save = false;
		if ($user->name != $user_info->name)
		{
			$user->name = $user_info->name;
			$need_save = true;
		}
		if ($user->username != $user_info->preferred_username)
		{
			$user->username = $user_info->preferred_username;
			$need_save = true;
		}

		$is_admin = $this->is_admin($user_info);
		if ($user->is_admin != $is_admin)
		{
			$user->is_admin = $is_admin;
			$need_save = true;
		}


		if ($need_save)
			$user->save();
	} else {
		/* Not a user, create them */
		$user = new $model;
		$user->username = $user_info->preferred_username;
		$user->name = $user_info->name;
		$user->email = $user_info->email;
		$user->password = 'INVALID-PASSWORD'; // bcrypt($password);
		$user->is_admin = $this->is_admin($user_info);
		$user->email_verified_at = now();
		$user->save();

		// reload them to fill in all the fields
		$user = $model::where('email', $user_info->email)->first();
		\Log::info('Created new user! ' . $user->name);
	}

	\Log::info(print_r($user, true));
        return $user;
    }

    /**
     * Retrieve a user by their unique identifier.
     *
     * @param mixed $identifier
     * @return \Illuminate\Contracts\Auth\Authenticatable|null
     */
    public function retrieveById($identifier)
    {
        return null;
    }

    /**
     * Retrieve a user by their unique identifier and "remember me" token.
     *
     * @param mixed $identifier
     * @param string $token
     * @return \Illuminate\Contracts\Auth\Authenticatable|null
     */
    public function retrieveByToken($identifier, $token)
    {
        return null;
    }

    /**
     * Update the "remember me" token for the given user in storage.
     *
     * @param \Illuminate\Contracts\Auth\Authenticatable $user
     * @param string $token
     * @return void
     */
    public function updateRememberToken(Authenticatable $user, $token)
    {
        return;
    }

    /**
     * Retrieve a user by the given credentials.
     *
     * @param array $credentials
     * @return \Illuminate\Contracts\Auth\Authenticatable|null
     */
    public function retrieveByCredentials(array $credentials)
    {
        return null;
    }

    /**
     * Validate a user against the given credentials.
     *
     * @param \Illuminate\Contracts\Auth\Authenticatable $user
     * @param array $credentials
     * @return bool
     */
    public function validateCredentials(Authenticatable $user, array $credentials)
    {
        return true;
    }
}
