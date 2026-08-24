<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Laravel\Passport\Passport;
use Laravel\Passport\Token;

class PersonalAccessTokenController extends Controller
{
    public function scopes(Request $request): JsonResponse
    {
        $scopes = collect(Passport::scopes())
            ->filter(function ($scope) use ($request) {
                return $this->userCanUseScope($request->user(), $scope->id);
            })
            ->map(function ($scope) {
                return [
                    'id' => $scope->id,
                    'description' => $scope->description,
                ];
            })
            ->values();

        return response()->json($scopes);
    }

    public function index(Request $request): JsonResponse
    {
        $tokens = $request->user()
            ->tokens()
            ->with('client')
            ->where('revoked', false)
            ->where(function ($query) {
                $query->whereNull('expires_at')
                    ->orWhere('expires_at', '>', now());
            })
            ->latest('created_at')
            ->get()
            ->filter(function (Token $token) {
                return $this->isPersonalAccessToken($token);
            })
            ->map(function (Token $token) {
                return $this->serializeToken($token);
            })
            ->values();

        return response()->json($tokens);
    }

    public function store(Request $request): JsonResponse
    {
        $allowedScopes = collect(Passport::scopeIds())
            ->filter(function (string $scope) use ($request) {
                return $this->userCanUseScope($request->user(), $scope);
            })
            ->values()
            ->all();

        $validated = $request->validate([
            'name' => ['required', 'string', 'min:2', 'max:255'],
            'scopes' => ['nullable', 'array'],
            'scopes.*' => ['string', Rule::in($allowedScopes)],
        ]);

        $scopes = array_values(array_unique($validated['scopes'] ?? []));

        $result = $request->user()->createToken(
            $validated['name'],
            $scopes
        );

        return response()->json([
            'accessToken' => $result->accessToken,
            'token' => $this->serializeToken($result->token),
        ]);
    }

    public function renew(Request $request, string $token_id): JsonResponse
    {
        $oldToken = $request->user()
            ->tokens()
            ->with('client')
            ->whereKey($token_id)
            ->firstOrFail();

        abort_unless($this->isPersonalAccessToken($oldToken), 404);

        abort_if($oldToken->revoked, 422, 'This token has already been revoked.');

        $scopes = array_values(array_unique($oldToken->scopes ?? []));

        $result = $request->user()->createToken(
            $oldToken->name,
            $scopes
        );

        $oldToken->revoke();

        return response()->json([
            'accessToken' => $result->accessToken,
            'token' => $this->serializeToken($result->token),
            'renewedTokenId' => $oldToken->id,
        ]);
    }

    public function destroy(Request $request, string $token)
    {
        $token = $request->user()
            ->tokens()
            ->with('client')
            ->whereKey($token)
            ->firstOrFail();

        abort_unless($this->isPersonalAccessToken($token), 404);

        $token->revoke();

        return response()->noContent();
    }

    private function serializeToken(Token $token): array
    {
        return [
            'id' => $token->id,
            'name' => $token->name,
            'scopes' => $token->scopes ?? [],
            'revoked' => (bool) $token->revoked,
            'created_at' => optional($token->created_at)->toJSON(),
            'updated_at' => optional($token->updated_at)->toJSON(),
            'expires_at' => optional($token->expires_at)->toJSON(),
        ];
    }

    private function isPersonalAccessToken(Token $token): bool
    {
        $client = $token->client;

        if (! $client) {
            return false;
        }

        if (method_exists($client, 'hasGrantType')) {
            return $client->hasGrantType('personal_access');
        }

        if (isset($client->personal_access_client)) {
            return (bool) $client->personal_access_client;
        }

        return false;
    }

    private function userCanUseScope($user, string $scope): bool
    {
        if (str_starts_with($scope, 'admin:')) {
            return (bool) (
                $user->is_admin
                ?? false
            );
        }

        return true;
    }
}
