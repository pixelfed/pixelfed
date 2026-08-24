<?php

namespace App\Http\Controllers\OAuth;

use Laravel\Passport\Http\Controllers\AccessTokenController;
use Laravel\Passport\Token;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Symfony\Component\HttpFoundation\Response;

class ApiTokenController extends AccessTokenController
{
    public function issueToken(ServerRequestInterface $psrRequest, ResponseInterface $psrResponse): Response
    {
        $response = parent::issueToken($psrRequest, $psrResponse);

        $data = json_decode($response->getContent(), true);

        if (isset($data['access_token'])) {
            $tokenId = $this->getTokenIdFromJwt($data['access_token']);

            if ($tokenId) {
                $token = Token::find($tokenId);

                if ($token) {
                    $data['created_at'] = $token->created_at->toIso8601String();
                    $response->setContent(json_encode($data));
                }
            }
        }

        return $response;
    }

    private function getTokenIdFromJwt(string $jwt): ?string
    {
        try {
            $parts = explode('.', $jwt);
            if (count($parts) !== 3) {
                return null;
            }

            $payload = json_decode(base64_decode(strtr($parts[1], '-_', '+/')), true);

            return $payload['jti'] ?? null;
        } catch (\Exception $e) {
            return null;
        }
    }
}
