<?php

namespace App\Passport;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Cache;
use Laravel\Passport\Client;
use Laravel\Passport\ClientRepository;
use Laravel\Passport\Passport;
use RuntimeException;

class CachedPersonalAccessClientRepository extends ClientRepository
{
    public function personalAccessClient(string $provider): Client
    {
        $cacheKey = $this->cacheKey($provider);

        $cachedClientId = Cache::get($cacheKey);

        if ($cachedClientId) {
            $client = $this->findValidClientById($cachedClientId, $provider);

            if ($client) {
                return $client;
            }

            Cache::forget($cacheKey);
        }

        $client = $this->discoverPersonalAccessClient($provider);

        Cache::forever($cacheKey, $client->getKey());

        return $client;
    }

    private function findValidClientById(string|int $clientId, string $provider): ?Client
    {
        $client = Passport::client()
            ->newQuery()
            ->whereKey($clientId)
            ->where('revoked', false)
            ->first();

        if (! $client) {
            return null;
        }

        if (! $this->clientMatchesProvider($client, $provider)) {
            return null;
        }

        if (! $client->hasGrantType('personal_access')) {
            return null;
        }

        return $client;
    }

    private function discoverPersonalAccessClient(string $provider): Client
    {
        $model = Passport::client();

        $columns = $model
            ->getConnection()
            ->getSchemaBuilder()
            ->getColumnListing($model->getTable());

        $hasGrantTypesColumn = in_array('grant_types', $columns, true);
        $hasLegacyPersonalAccessColumn = in_array('personal_access_client', $columns, true);

        if (! $hasGrantTypesColumn && ! $hasLegacyPersonalAccessColumn) {
            throw new RuntimeException(
                'Unable to discover Passport personal access client: missing grant_types and personal_access_client columns.'
            );
        }

        $query = $model
            ->newQuery()
            ->where('revoked', false)
            ->where(function (Builder $query) use ($provider): void {
                $query
                    ->when($provider === config('auth.guards.api.provider'), function (Builder $query): void {
                        $query->orWhereNull('provider');
                    })
                    ->orWhere('provider', $provider);
            })
            ->where(function (Builder $query) use ($hasGrantTypesColumn, $hasLegacyPersonalAccessColumn): void {
                if ($hasGrantTypesColumn) {
                    $query->orWhere('grant_types', 'like', '%"personal_access"%');
                }

                if ($hasLegacyPersonalAccessColumn) {
                    $query->orWhere('personal_access_client', true);
                }
            });

        $client = $query
            ->latest('created_at')
            ->first();

        if (! $client) {
            throw new RuntimeException(
                "Personal access client not found for [{$provider}] user provider. Please create one with passport:client --personal."
            );
        }

        if (! $client->hasGrantType('personal_access')) {
            throw new RuntimeException(
                "Discovered Passport client [{$client->getKey()}] does not have the personal_access grant."
            );
        }

        return $client;
    }

    private function clientMatchesProvider(Client $client, string $provider): bool
    {
        return $client->provider === $provider
            || (
                $client->provider === null
                && $provider === config('auth.guards.api.provider')
            );
    }

    private function cacheKey(string $provider): string
    {
        return 'pf:passport:personal-access-client-id:'.$provider;
    }
}
