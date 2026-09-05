<?php

namespace App\Services;

use App\Http\Controllers\StatusController;
use App\Jobs\StatusPipeline\NewStatusPipeline;
use App\Models\Media;
use App\Models\Profile;
use App\Models\Status;
use App\Models\User;
use App\Models\UserOidcMapping;
use App\Models\VinylHubStatusOperation;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class VinylHubStatusOperationService
{
    public function create(string $externalSubject, string $operationKey, array $payload): array
    {
        [$user, $profile] = $this->resolveAccount($externalSubject);

        $existing = $this->findOperation($profile->id, $operationKey);
        if ($existing) {
            return $this->result($existing);
        }

        $this->assertActiveAccount($user, $profile);
        $payload = $this->validatePayload($payload);
        $this->assertCanPost($user);
        $this->assertDailyComposeLimit($user);
        AccountService::setLastActive($user->id);

        try {
            [$operation, $status] = DB::transaction(function () use ($user, $profile, $operationKey, $payload) {
                $existing = VinylHubStatusOperation::where('profile_id', $profile->id)
                    ->where('operation_key', $operationKey)
                    ->lockForUpdate()
                    ->first();

                if ($existing) {
                    return [$existing, null];
                }

                $operation = VinylHubStatusOperation::create([
                    'profile_id' => $profile->id,
                    'operation_key' => $operationKey,
                    'state' => VinylHubStatusOperation::STATE_INCOMPLETE,
                ]);

                $status = $this->createStatus($user, $profile, $payload);

                $operation->state = VinylHubStatusOperation::STATE_ACCEPTED;
                $operation->status_id = $status->id;
                $operation->status_url = $status->url();
                $operation->save();

                return [$operation, $status];
            }, 1);
        } catch (QueryException $exception) {
            if (! $this->isUniqueConstraint($exception)) {
                throw $exception;
            }

            $operation = $this->findOperation($profile->id, $operationKey);
            if (! $operation) {
                throw $exception;
            }

            $status = null;
        }

        if ($status) {
            $this->forgetStatusCaches($user, $status);
            NewStatusPipeline::dispatch($status);
        }

        return $this->result($operation);
    }

    public function read(string $externalSubject, string $operationKey, bool $repair = true): array
    {
        [, $profile] = $this->resolveAccount($externalSubject);
        $operation = $this->findOperation($profile->id, $operationKey);

        if (! $operation) {
            return [
                'operation_key' => $operationKey,
                'state' => 'no_effect',
                'accepted' => false,
                'retry_safe' => true,
                'repairable' => false,
                'status_id' => null,
                'status_url' => null,
            ];
        }

        if ($repair && $operation->state === VinylHubStatusOperation::STATE_INCOMPLETE && $operation->status_id) {
            $status = Status::withTrashed()
                ->where('profile_id', $operation->profile_id)
                ->find($operation->status_id);

            if ($status) {
                $operation->state = VinylHubStatusOperation::STATE_ACCEPTED;
                $operation->status_url ??= $status->url();
                $operation->save();
            }
        }

        return $this->result($operation);
    }

    protected function resolveAccount(string $externalSubject): array
    {
        $mapping = UserOidcMapping::where('oidc_id', $externalSubject)->first();
        abort_unless($mapping, 404);

        $user = User::withTrashed()->find($mapping->user_id);
        $profile = $user ? Profile::withTrashed()->whereUserId($user->id)->first() : null;

        abort_if(! $user || ! $profile, 409, 'Community projection requires repair before publishing.');

        return [$user, $profile];
    }

    protected function assertActiveAccount(User $user, Profile $profile): void
    {
        abort_if($user->trashed() || $profile->trashed(), 409, 'Community account is deleted.');
        abort_if(in_array($user->status, ['suspended', 'delete', 'deleted'], true)
            || in_array($profile->status, ['suspended', 'delete'], true), 409, 'Community account is not active.');
    }

    protected function assertCanPost(User $user): void
    {
        if ($user->has_roles) {
            abort_if(! UserRoleService::can('can-post', $user->id), 403, 'Invalid permissions for this action');
        }
    }

    protected function validatePayload(array $payload): array
    {
        $payload = Validator::make($payload, [
            'status' => ['nullable', 'string', 'max:'.(int) config_cache('pixelfed.max_caption_length')],
            'media_ids' => ['sometimes', 'array', 'max:'.(int) config_cache('pixelfed.max_album_length')],
            'media_ids.*' => ['integer', 'distinct', 'min:1'],
            'sensitive' => ['nullable', 'boolean'],
            'visibility' => ['nullable', 'string', 'in:private,unlisted,public'],
            'spoiler_text' => ['sometimes', 'nullable', 'string', 'max:140'],
            'comments_disabled' => ['sometimes', 'boolean'],
        ])->validate();

        $caption = trim((string) ($payload['status'] ?? ''));
        $mediaIds = array_values($payload['media_ids'] ?? []);
        abort_if($caption === '' && $mediaIds === [], 422, 'Status text or media is required.');

        if (config('costar.enabled') == true && $caption !== '') {
            foreach ((array) config('costar.keyword.block') as $keyword) {
                if (Str::contains($caption, $keyword)) {
                    abort(400, 'Invalid object. Contains banned keyword.');
                }
            }
        }

        return $payload;
    }

    protected function createStatus(User $user, Profile $profile, array $payload): Status
    {
        $mediaIds = array_values($payload['media_ids'] ?? []);
        $media = collect();

        if ($mediaIds !== []) {
            $media = Media::whereUserId($user->id)
                ->whereNull('status_id')
                ->whereIn('id', $mediaIds)
                ->get()
                ->keyBy('id');

            abort_if($media->count() !== count($mediaIds), 400, 'Invalid media_ids');
            abort_if($media->contains(fn (Media $item) => (int) $item->profile_id !== (int) $profile->id), 403, 'Invalid media id');
        }

        $visibility = $profile->is_private ? 'private' : (
            $profile->unlisted == true && ($payload['visibility'] ?? 'public') === 'public'
                ? 'unlisted'
                : ($payload['visibility'] ?? 'public')
        );
        $caption = trim((string) ($payload['status'] ?? ''));
        $caption = $caption === '' ? '' : strip_tags($caption);
        $cw = $profile->cw == true || (bool) ($payload['sensitive'] ?? false);

        $status = new Status([
            'caption' => $caption,
            'rendered' => '',
            'profile_id' => $profile->id,
            'is_nsfw' => $cw,
            'cw_summary' => $cw && ! empty($payload['spoiler_text']) ? $payload['spoiler_text'] : null,
            'scope' => 'draft',
            'visibility' => 'draft',
            'comments_disabled' => (bool) ($payload['comments_disabled'] ?? false),
        ]);
        $status->save();

        foreach ($mediaIds as $position => $mediaId) {
            $item = $media->get($mediaId);
            $item->order = $position + 1;
            $item->status_id = $status->id;
            $item->save();
        }

        $status->scope = $visibility;
        $status->visibility = $visibility;
        $status->type = $media->isEmpty() ? 'text' : StatusController::mimeTypeCheck($media->pluck('mime')->all());
        $status->save();

        return $status->fresh();
    }

    protected function findOperation(int $profileId, string $operationKey): ?VinylHubStatusOperation
    {
        return VinylHubStatusOperation::where('profile_id', $profileId)
            ->where('operation_key', $operationKey)
            ->first();
    }

    protected function result(VinylHubStatusOperation $operation): array
    {
        $accepted = $operation->state === VinylHubStatusOperation::STATE_ACCEPTED && $operation->status_id !== null;

        return [
            'operation_key' => $operation->operation_key,
            'state' => $accepted ? VinylHubStatusOperation::STATE_ACCEPTED : VinylHubStatusOperation::STATE_INCOMPLETE,
            'accepted' => $accepted,
            'retry_safe' => false,
            'repairable' => ! $accepted,
            'status_id' => $operation->status_id ? (string) $operation->status_id : null,
            'status_url' => $operation->status_url,
        ];
    }

    protected function forgetStatusCaches(User $user, Status $status): void
    {
        Cache::forget('user:account:id:'.$user->id);
        Cache::forget('_api:statuses:recent_9:'.$status->profile_id);
        Cache::forget('profile:status_count:'.$status->profile_id);
        Cache::forget('profile:embed:'.$status->profile_id);
        Cache::forget('compose:rate-limit:store:'.$user->id);
    }

    protected function assertDailyComposeLimit(User $user): void
    {
        $limitKey = 'compose:rate-limit:store:'.$user->id;
        $limitReached = Cache::remember($limitKey, now()->addMinutes(15), function () use ($user) {
            $minId = SnowflakeService::byDate(now()->subDays(1));
            $dailyLimit = Status::whereProfileId($user->profile_id)
                ->where('id', '>', $minId)
                ->count();

            return $dailyLimit >= 1000;
        });

        abort_if($limitReached === true, 429);
    }

    protected function isUniqueConstraint(QueryException $exception): bool
    {
        return in_array((string) $exception->getCode(), ['23000', '19'], true)
            || str_contains(strtolower($exception->getMessage()), 'duplicate');
    }
}
