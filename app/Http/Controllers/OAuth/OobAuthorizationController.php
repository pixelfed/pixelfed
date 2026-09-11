<?php

namespace App\Http\Controllers\OAuth;

use Illuminate\Http\Request;
use Laravel\Passport\Http\Controllers\ApproveAuthorizationController;
use League\OAuth2\Server\Exception\OAuthServerException;
use League\OAuth2\Server\RequestTypes\AuthorizationRequest;
use Psr\Http\Message\ResponseInterface;
use Symfony\Component\HttpFoundation\Response;

class OobAuthorizationController extends ApproveAuthorizationController
{
    /**
     * Approve the authorization request.
     */
    public function approve(Request $request, ResponseInterface $psrResponse): Response
    {
        $authRequest = $this->getAuthRequestFromSession($request);
        $authRequest->setAuthorizationApproved(true);

        return $this->withErrorHandling(function () use ($authRequest, $psrResponse) {
            $response = $this->server->completeAuthorizationRequest($authRequest, $psrResponse);

            if ($this->isOutOfBandRequest($authRequest)) {
                $code = $this->extractAuthorizationCode($response);

                return response()->json([
                    'code' => $code,
                    'state' => $authRequest->getState(),
                ]);
            }

            return $this->convertResponse($response);
        }, $authRequest->getGrantTypeId() === 'implicit');
    }

    /**
     * Check if the request is an out-of-band OAuth request.
     *
     * @param  AuthorizationRequest  $authRequest
     * @return bool
     */
    protected function isOutOfBandRequest($authRequest)
    {
        $redirectUri = $authRequest->getRedirectUri();

        if ($redirectUri === 'urn:ietf:wg:oauth:2.0:oob') {
            return true;
        }

        // RFC 6749 §3.1.2.3 permits a client with a single registered redirect
        // URI to omit redirect_uri on the authorize request, in which case the
        // league server leaves the auth request's redirect URI null. Fall back
        // to the client's registered redirect URIs to still detect an OOB-only
        // client. Passport's client entity types getRedirectUri() as string|array.
        if ($redirectUri === null) {
            $registered = $authRequest->getClient()->getRedirectUri();
            $registered = is_array($registered) ? $registered : [$registered];

            return count($registered) === 1 && $registered[0] === 'urn:ietf:wg:oauth:2.0:oob';
        }

        return false;
    }

    /**
     * Extract the authorization code from the PSR-7 response.
     *
     * @param  ResponseInterface  $response
     * @return string
     *
     * @throws OAuthServerException
     */
    protected function extractAuthorizationCode($response)
    {
        $location = $response->getHeader('Location')[0] ?? '';

        if (empty($location)) {
            throw OAuthServerException::serverError('Missing authorization code in response');
        }

        parse_str(parse_url($location, PHP_URL_QUERY), $params);

        if (! isset($params['code'])) {
            throw OAuthServerException::serverError('Invalid authorization code format');
        }

        return $params['code'];
    }
}
