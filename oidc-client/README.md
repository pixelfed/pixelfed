# OIDC Client

A Laravel package for delegating authentication to an OpenID Provider.

## How to install

Begin by adding this package to your depedencies with the command:

```
composer require gcs/oidc-client
```

Then add the following line to the list of registered service providers in `config/app.php`:

```
GCS\OIDCClient\OIDCServiceProvider::class
```

Edit your  `config/auth.php` file to use OpenID as the authentication method for your users:

```
'guards' => [
    'web' => [
        'driver' => 'oidc',
        ...
    ],
    ...
],
```

## How to configure

Run the following command to publish the `config/oidc.php` configuration file:

```
php artisan vendor:publish --provider="GCS\OIDCClient\OIDCServiceProvider"
```

The settings to configure are the following

- `client_id` This is the ID of your client application used by the OpenID provider. You should set this through an environment variable called `OIDC_CLIENT_ID`.
- `client_secret` This is the secret code of your client application only known by the OpenID provider. You should set this through an environment variable called `OIDC_CLIENT_SECRET`.
- `provider_url` This is base URL of the OpenID provider. You should set this through an environment variable called `OIDC_PROVIDER_URL`.
- `provider_name` This is a short name for your OpenID provider, which will only appears in your OpenID routes. Do not use spaces. You should set this through an environment variable called `OIDC_PROVIDER_NAME`.
- `scopes` This is a list of scopes your application will request from the OpenID provider. The `openid` scope is required. **Add any additional scopes.**


## How to use

Once everything is set up, you can replace your login system with a call to the route `route('oidc.signin')`. For logouts, use the route `route('oidc.signout')`.

You may want to overwrite the `$redirectTo` variable inside `OIDCController` in order to specify the route you want your users to be taken to upon successful authentication. 

Another important method you may need to overwrite is `retrieveByInfo` inside `OIDCUserProvider`. This function takes the array of user attributes retrieved from the OpenID provider, and returns a User model for the authenticated user. This is where you may want to merge the info you receive from the provider with the info you hold about that user in your application database.

---
*Developed by Cabinet Office Digital Development in October 2019.
*



