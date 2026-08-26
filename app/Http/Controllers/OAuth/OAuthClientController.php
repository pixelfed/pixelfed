<?php

namespace App\Http\Controllers\OAuth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Laravel\Passport\ClientRepository;
use Laravel\Passport\Passport;

class OAuthClientController extends Controller
{
    public function destroy(Request $request, string|int $clientId): Response
    {
        $clients = app(ClientRepository::class);

        $client = $request->user()->clients()->where('revoked', false)->find($clientId);

        if (! $client) {
            return new Response('', 404);
        }

        if ($client->hasGrantType('personal_access')) {
            return new Response(json_encode([
                'error' => 'Cannot delete the personal access client. This client is required for personal access token functionality.',
            ]), 403, ['Content-Type' => 'application/json']);
        }

        // Check legacy column as well
        if (isset($client->personal_access_client) && $client->personal_access_client) {
            return new Response(json_encode([
                'error' => 'Cannot delete the personal access client. This client is required for personal access token functionality.',
            ]), 403, ['Content-Type' => 'application/json']);
        }

        $clients->delete($client);

        return new Response('', Response::HTTP_NO_CONTENT);
    }
}
