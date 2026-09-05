<?php

namespace App\Services;

use App\Models\Profile;
use App\Models\User;
use App\Models\UserOidcMapping;
use App\Services\Account\AccountInitializer;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Laravel\Passport\Passport;
use Laravel\Passport\Token;
use Purify;

class VinylHubAccountEdgeService
{
    public const LIFECYCLE_ACTIVE = 'active';

    public const LIFECYCLE_SUSPENDED = 'suspended';

    public const LIFECYCLE_DELETE_REQUESTED = 'delete_requested';

    public const LIFECYCLE_DELETED = 'deleted';

    public const LIFECYCLE_REPAIR_REQUIRED = 'repair_required';

    public const LIFECYCLE_MISSING = 'missing';

    public function __construct(protected AccountInitializer $initializer) {}

    public function provision(string $externalSubject, string $technicalHandle, ?string $displaySeed): array
    {
        $mapping = UserOidcMapping::where('oidc_id', $externalSubject)->first();

        if ($mapping) {
            return $this->existingProvision($mapping);
        }

        try {
            return DB::transaction(function () use ($externalSubject, $technicalHandle, $displaySeed) {
                $mapping = UserOidcMapping::where('oidc_id', $externalSubject)->lockForUpdate()->first();

                if ($mapping) {
                    return $this->existingProvision($mapping);
                }

                $user = User::create([
                    'name' => Purify::clean($displaySeed ?: $technicalHandle),
                    'username' => $technicalHandle,
                    'email' => $this->compatibilityEmail($technicalHandle),
                    'password' => Hash::make(Str::password(64)),
                    'email_verified_at' => now(),
                    'register_source' => 'vinylhub',
                ]);

                $profile = $this->initializer->initialize($user);

                $mapping = UserOidcMapping::create([
                    'user_id' => $user->id,
                    'oidc_id' => $externalSubject,
                ]);

                return $this->projection($mapping, $user, $profile, true);
            }, 3);
        } catch (QueryException $exception) {
            if (! $this->isUniqueConstraint($exception)) {
                throw $exception;
            }

            $mapping = UserOidcMapping::where('oidc_id', $externalSubject)->first();

            if (! $mapping) {
                throw $exception;
            }

            return $this->existingProvision($mapping);
        }
    }

    public function read(string $externalSubject, bool $repair = true): array
    {
        $mapping = UserOidcMapping::where('oidc_id', $externalSubject)->first();

        if (! $mapping) {
            return [
                'projection_exists' => false,
                'lifecycle' => self::LIFECYCLE_MISSING,
                'repair_required' => false,
                'external_subject' => $externalSubject,
            ];
        }

        $user = User::withTrashed()->find($mapping->user_id);

        if (! $user) {
            return $this->projection($mapping, null, null, false);
        }

        $profile = Profile::withTrashed()->whereUserId($user->id)->first();

        if ($repair && ! $profile && ! in_array($user->status, ['delete', 'deleted'], true)) {
            $profile = $this->initializer->initialize($user);
        }

        return $this->projection($mapping, $user, $profile, false);
    }

    public function renew(string $externalSubject): array
    {
        $resolved = $this->resolveExisting($externalSubject);
        $user = $resolved['user'];

        abort_if($resolved['user'] === null || $resolved['profile'] === null, 409, 'Community projection requires repair before credential renewal.');
        abort_if($resolved['lifecycle'] !== self::LIFECYCLE_ACTIVE, 409, 'Community credential cannot be renewed for this lifecycle state.');

        return DB::transaction(function () use ($user, $resolved) {
            $oldToken = $this->activeCredential($user);
            $scopes = $oldToken ? $this->credentialScopes($oldToken) : $this->scopes();
            $result = $this->issueCredential($user, $scopes);

            if ($oldToken) {
                $oldToken->revoke();
            }

            $response = $this->projection($resolved['mapping'], $user, $resolved['profile'], false);
            $response['credential'] = $this->credentialMetadata($result->token, true, $result->accessToken);
            $response['renewed_token_id'] = $oldToken?->id;

            return $response;
        });
    }

    public function revoke(string $externalSubject): array
    {
        $resolved = $this->resolveExisting($externalSubject);
        abort_if($resolved['user'] === null, 409, 'Community projection requires repair before credential revocation.');
        $revoked = 0;

        $resolved['user']->tokens()
            ->where('name', $this->tokenName())
            ->where('revoked', false)
            ->get()
            ->each(function (Token $token) use (&$revoked) {
                $token->revoke();
                $revoked++;
            });

        $response = $this->projection($resolved['mapping'], $resolved['user'], $resolved['profile'], false);
        $response['credential'] = [
            'status' => 'revoked',
            'revoked_count' => $revoked,
        ];

        return $response;
    }

    public function suspend(string $externalSubject): array
    {
        return $this->setLifecycle($externalSubject, self::LIFECYCLE_SUSPENDED);
    }

    public function resume(string $externalSubject): array
    {
        $resolved = $this->resolveExisting($externalSubject);

        abort_if($resolved['user'] === null || $resolved['profile'] === null, 409, 'Community projection requires repair before resuming.');
        abort_if(in_array($resolved['lifecycle'], [self::LIFECYCLE_DELETED, self::LIFECYCLE_DELETE_REQUESTED], true), 409, 'Community account cannot resume from its deletion lifecycle.');

        DB::transaction(function () use ($resolved) {
            $resolved['user']->status = null;
            $resolved['user']->save();
            $resolved['profile']->status = null;
            $resolved['profile']->save();
        });

        return $this->read($externalSubject, false);
    }

    public function delete(string $externalSubject): array
    {
        $resolved = $this->resolveExisting($externalSubject);

        abort_if($resolved['user'] === null || $resolved['profile'] === null, 409, 'Community projection requires repair before deletion.');
        DB::transaction(function () use ($resolved) {
            $deleteAfter = now()->addMonth();
            $resolved['user']->status = 'delete';
            $resolved['user']->delete_after = $deleteAfter;
            $resolved['user']->save();
            $resolved['profile']->status = 'delete';
            $resolved['profile']->delete_after = $deleteAfter;
            $resolved['profile']->save();
        });

        $this->revoke($externalSubject);

        return $this->read($externalSubject, false);
    }

    protected function existingProvision(UserOidcMapping $mapping): array
    {
        $resolved = $this->resolveMapping($mapping);

        if ($resolved['lifecycle'] === self::LIFECYCLE_REPAIR_REQUIRED && $resolved['user'] && $resolved['profile'] === null) {
            $resolved['profile'] = $this->initializer->initialize($resolved['user']);
        }

        return $this->projection($mapping, $resolved['user'], $resolved['profile'], false);
    }

    protected function resolveExisting(string $externalSubject): array
    {
        $mapping = UserOidcMapping::where('oidc_id', $externalSubject)->firstOrFail();

        return $this->resolveMapping($mapping);
    }

    protected function resolveMapping(UserOidcMapping $mapping): array
    {
        $user = User::withTrashed()->find($mapping->user_id);
        $profile = $user ? Profile::withTrashed()->whereUserId($user->id)->first() : null;

        return [
            'mapping' => $mapping,
            'user' => $user,
            'profile' => $profile,
            'lifecycle' => $this->lifecycle($user, $profile),
        ];
    }

    protected function projection(UserOidcMapping $mapping, ?User $user, ?Profile $profile, bool $includeCredential): array
    {
        $response = [
            'projection_exists' => $user !== null && $profile !== null,
            'mapping_id' => $mapping->id,
            'external_subject' => $mapping->oidc_id,
            'user_id' => $user?->id,
            'profile_id' => $profile?->id ?: $user?->profile_id,
            'actor_uri' => $profile?->permalink(),
            'technical_handle' => $profile?->username ?: $user?->username,
            'lifecycle' => $this->lifecycle($user, $profile),
            'repair_required' => $user === null || $profile === null,
            'credential' => $user ? $this->credentialMetadata($this->activeCredential($user)) : [
                'status' => 'unavailable',
            ],
        ];

        if ($includeCredential && $user && $profile && $response['lifecycle'] === self::LIFECYCLE_ACTIVE) {
            $result = $this->issueCredential($user, $this->scopes());
            $response['credential'] = $this->credentialMetadata($result->token, true, $result->accessToken);
        }

        return $response;
    }

    protected function setLifecycle(string $externalSubject, string $lifecycle): array
    {
        $resolved = $this->resolveExisting($externalSubject);

        abort_if($resolved['user'] === null || $resolved['profile'] === null, 409, 'Community projection requires repair before a lifecycle transition.');
        abort_if(in_array($resolved['lifecycle'], [self::LIFECYCLE_DELETED, self::LIFECYCLE_DELETE_REQUESTED], true), 409, 'Community account cannot transition from its deletion lifecycle.');

        DB::transaction(function () use ($resolved, $lifecycle) {
            $resolved['user']->status = $lifecycle === self::LIFECYCLE_SUSPENDED ? 'suspended' : null;
            $resolved['user']->save();
            $resolved['profile']->status = $lifecycle === self::LIFECYCLE_SUSPENDED ? 'suspended' : null;
            $resolved['profile']->save();
        });

        if ($lifecycle === self::LIFECYCLE_SUSPENDED) {
            $this->revoke($externalSubject);
        }

        return $this->read($externalSubject, false);
    }

    protected function issueCredential(User $user, array $scopes)
    {
        $scopes = array_values(array_intersect($scopes, $this->scopes()));

        if ($scopes === [] || count($scopes) !== count(array_unique($scopes))) {
            throw new \RuntimeException('No valid VinylHub Community credential scopes are configured.');
        }

        return $user->createToken($this->tokenName(), $scopes);
    }

    protected function activeCredential(User $user): ?Token
    {
        return $user->tokens()
            ->where('name', $this->tokenName())
            ->where('revoked', false)
            ->where(function ($query) {
                $query->whereNull('expires_at')->orWhere('expires_at', '>', now());
            })
            ->latest('created_at')
            ->first();
    }

    protected function credentialScopes(Token $token): array
    {
        return array_values(array_intersect((array) ($token->scopes ?? []), $this->scopes()));
    }

    protected function credentialMetadata(?Token $token, bool $includeSecret = false, ?string $secret = null): array
    {
        if (! $token) {
            return ['status' => 'missing'];
        }

        $metadata = [
            'id' => $token->id,
            'status' => $token->revoked || ($token->expires_at && $token->expires_at->isPast()) ? 'inactive' : 'active',
            'scopes' => $this->credentialScopes($token),
            'created_at' => optional($token->created_at)->toIso8601String(),
            'expires_at' => optional($token->expires_at)->toIso8601String(),
        ];

        if ($includeSecret) {
            $metadata['access_token'] = $secret;
        }

        return $metadata;
    }

    protected function scopes(): array
    {
        return array_values(array_intersect(
            ['read', 'write', 'follow'],
            Passport::scopeIds(),
        ));
    }

    protected function tokenName(): string
    {
        return (string) config('vinylhub.account_edge.token_name', 'VinylHub Community');
    }

    protected function compatibilityEmail(string $technicalHandle): string
    {
        return Str::lower($technicalHandle).'@community.invalid';
    }

    protected function lifecycle(?User $user, ?Profile $profile): string
    {
        if (! $user || ! $profile) {
            return self::LIFECYCLE_REPAIR_REQUIRED;
        }

        if ($user->status === 'deleted' || $profile->deleted_at) {
            return self::LIFECYCLE_DELETED;
        }

        if ($user->status === 'delete' || $profile->status === 'delete') {
            return self::LIFECYCLE_DELETE_REQUESTED;
        }

        if ($user->status === 'suspended' || $profile->status === 'suspended') {
            return self::LIFECYCLE_SUSPENDED;
        }

        return self::LIFECYCLE_ACTIVE;
    }

    protected function isUniqueConstraint(QueryException $exception): bool
    {
        return in_array((string) $exception->getCode(), ['23000', '19'], true)
            || str_contains(strtolower($exception->getMessage()), 'duplicate');
    }
}
