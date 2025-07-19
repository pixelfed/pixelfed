<?php

namespace App\Http\Controllers\OAuth;

use Illuminate\Http\Request;
use Laravel\Passport\Http\Controllers\ApproveAuthorizationController;
use League\OAuth2\Server\Exception\OAuthServerException;
use Nyholm\Psr7\Response as Psr7Response;

class OobAuthorizationController extends ApproveAuthorizationController
{
    /**
     * Approve the authorization request.
     *
     * @return \Illuminate\Http\Response
     */
    public function approve(Request $request)
    {
        $this->assertValidAuthToken($request);

        $authRequest = $this->getAuthRequestFromSession($request);
        $authRequest->setAuthorizationApproved(true);

        return $this->withErrorHandling(function () use ($authRequest) {
            $response = $this->server->completeAuthorizationRequest($authRequest, new Psr7Response);

            if ($this->isOutOfBandRequest($authRequest)) {
                $code = $this->extractAuthorizationCode($response);

                return response()->json([
                    'code' => $code,
                    'state' => $authRequest->getState(),
                ]);
            }

            return $this->convertResponse($response);
        });
    }

    /**
     * Check if the request is an out-of-band OAuth request.
     *
     * @param  \League\OAuth2\Server\RequestTypes\AuthorizationRequest  $authRequest
     * @return bool
     */
    protected function isOutOfBandRequest($authRequest)
    {
        return $authRequest->getRedirectUri() === 'urn:ietf:wg:oauth:2.0:oob';
    }

    /**
     * Extract the authorization code from the PSR-7 response.
     *
     * @param  \Psr\Http\Message\ResponseInterface  $response
     * @return string
     *
     * @throws \League\OAuth2\Server\Exception\OAuthServerException
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

    /**
     * Handle OAuth errors for both redirect and OOB flows.
     *
     * @param  \Closure  $callback
     * @return \Illuminate\Http\Response
     */
    protected function withErrorHandling($callback)
    {
        try {
            return $callback();
        } catch (OAuthServerException $e) {
            if ($this->isOutOfBandRequest($this->getAuthRequestFromSession(request()))) {
                return response()->json([
                    'error' => $e->getErrorType(),
                    'message' => $e->getMessage(),
                    'hint' => $e->getHint(),
                ], $e->getHttpStatusCode());
            }

            return $this->convertResponse(
                $e->generateHttpResponse(new Psr7Response)
            );
        }
    }
}
