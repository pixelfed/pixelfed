<?php


namespace GCS\OIDCClient\Auth;


use Illuminate\Auth\SessionGuard;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\Auth\Authenticatable as AuthenticatableContract;
use Illuminate\Contracts\Session\Session;
use Jumbojett\OpenIDConnectClient as Client;
use Jumbojett\OpenIDConnectClientException;
use Symfony\Component\HttpFoundation\Request;

class OIDCGuard extends SessionGuard
{
    /**
     * Instance of the OpenIDConnectClient
     *
     * @var Client
     */
    private $oidc;
    
    public function __construct($name, Client $oidc, OIDCUserProvider $provider, 
                                Session $session, Request $request = null)
    {
        parent::__construct($name, $provider, $session, $request);
        $this->oidc = $oidc;
    }
    
    public function redirect()
    {
        try {
            $this->oidc->authenticate();
        } catch (OpenIDConnectClientException $e) {
            throw $e;
        }
    }
    
    public function retrieveUserInfo()
    {
        try {
            $this->oidc->authenticate();
        } catch (OpenIDConnectClientException $e) {
            throw $e;
        }
        $userInfo = $this->oidc->requestUserInfo();
        return $userInfo;
    }
    
    public function generateUser($user_info)
    {
        $user = $this->provider->retrieveByInfo($user_info);
        return $user;
    }

    public function login(AuthenticatableContract $user, $remember = false)
    {
        $this->updateSession($user);

        if ($remember) {
            $this->ensureRememberTokenIsSet($user);
            $this->queueRecallerCookie($user);
        }
        $this->fireLoginEvent($user, $remember);

        $this->setUser($user);
        return true;
    }

    public function user()
    {
        if ($this->loggedOut) {
            return null;
        }

	// enable trusted device mode since there is no password
	$this->session->put('sudoTrustDevice', 1);

        if (! is_null($this->user)) {
            return $this->user;
        }

        $user = $this->session->get($this->getName());
        
        if (! is_null($user) && $this->user = $user) {
            $this->fireAuthenticatedEvent($this->user);
        }

        if (is_null($this->user) && ! is_null($recaller = $this->recaller())) {
            $this->user = $this->userFromRecaller($recaller);

            if ($this->user) {
                $this->updateSession($this->user);

                $this->fireLoginEvent($this->user, true);
            }
        }

        return $this->user;
    }
    
    protected function updateSession($user)
    {
        $this->session->put($this->getName(), $user);

        $this->session->migrate(true);
    }

    public function logout()
    {
        parent::logout();
    }
}
