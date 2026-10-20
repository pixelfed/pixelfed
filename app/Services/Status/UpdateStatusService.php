<?php

namespace App\Services\Status;

use App\Models\Media;
use App\Models\ModLog;
use App\Models\Profile;
use App\Models\Status;
use App\Models\StatusEdit;
use App\Services\AdminShadowFilterService;
use App\Services\MediaService;
use App\Services\MediaStorageService;
use App\Services\NetworkTimelineService;
use App\Services\PlaceService;
use App\Services\PublicTimelineService;
use App\Services\StatusService;
use Purify;

class UpdateStatusService
{
    public static function call(Status $status, $attributes)
    {
        self::createPreviousEdit($status);
        self::updateMediaAttachements($status, $attributes);
        self::handleImmediateAttributes($status, $attributes);
        self::createEdit($status, $attributes);

        return StatusService::get($status->id);
    }

    public static function updateMediaAttachements(Status $status, $attributes)
    {
        $count = $status->media()->count();
        if ($count === 0 || $count === 1) {
            return;
        }

        $oids = $status->media()->orderBy('order')->pluck('id')->map(function ($m) {
            return (string) $m;
        });
        $nids = collect($attributes['media_ids']);

        if ($oids->toArray() === $nids->toArray()) {
            return;
        }

        foreach ($oids->diff($nids)->values()->toArray() as $mid) {
            $media = Media::find($mid);
            if (! $media) {
                continue;
            }
            $media->status_id = null;
            $media->save();
            MediaStorageService::delete($media, true);
        }

        $nids->each(function ($nid, $idx) {
            $media = Media::find($nid);
            if (! $media) {
                return;
            }
            $media->order = $idx;
            $media->save();
        });
        MediaService::del($status->id);
    }

    public static function handleImmediateAttributes(Status $status, $attributes)
    {
        $wasNsfw = (bool) $status->getOriginal('is_nsfw');

        if (isset($attributes['status'])) {
            $cleaned = Purify::clean($attributes['status']);
            $status->caption = $cleaned;
        }
        if (isset($attributes['sensitive'])) {
            if ($status->is_nsfw != (bool) $attributes['sensitive'] &&
              (bool) $attributes['sensitive'] === false) {
                // Same admin NSFW lock as StatusRemoteUpdatePipeline: accept
                // both object_type literals and object_id conventions, and gate
                // on metadata.action = 'cw' so only a genuine NSFW-add re-locks.
                $exists = ModLog::whereAction('admin.status.moderate')
                    ->whereIn('object_type', ['App\Status::class', 'App\Models\Status::class'])
                    ->where(function ($q) use ($status) {
                        $q->where('object_id', $status->id)
                            ->orWhere('object_id', $status->profile_id);
                    })
                    ->where('metadata->action', 'cw')
                    ->exists();
                if (! $exists) {
                    $status->is_nsfw = (bool) $attributes['sensitive'];
                }
            } else {
                $status->is_nsfw = (bool) $attributes['sensitive'];
            }
            $profile = Profile::find($status->profile_id);
            if (! $profile || $profile->cw == true) {
                $status->is_nsfw = true;
            }
        }
        if (isset($attributes['spoiler_text'])) {
            $status->cw_summary = Purify::clean($attributes['spoiler_text']);
        }
        $oldPlaceId = null;
        if (array_key_exists('location', $attributes)) {
            $oldPlaceId = $status->getOriginal('place_id');
            if (isset($attributes['location']['id'])) {
                $status->place_id = $attributes['location']['id'];
            } else {
                $status->place_id = null;
            }
        }
        if ($status->cw_summary && ! $status->is_nsfw) {
            $status->cw_summary = null;
        }
        $status->edited_at = now();
        $status->save();
        if (isset($attributes['location']) && $oldPlaceId != $status->place_id) {
            if ($oldPlaceId) {
                PlaceService::clearStatusesByPlaceId($oldPlaceId);
            }
            if ($status->place_id) {
                PlaceService::clearStatusesByPlaceId($status->place_id);
            }
        }
        StatusService::del($status->id);

        self::reconcileFeedEligibility($status, $wasNsfw);
    }

    protected static function reconcileFeedEligibility(Status $status, bool $wasNsfw): void
    {
        if (! config('instance.hide_nsfw_on_public_feeds')) {
            return;
        }

        $isNsfw = (bool) $status->is_nsfw;

        if ($isNsfw === $wasNsfw) {
            return;
        }

        if ($status->scope !== 'public' || $status->in_reply_to_id !== null || $status->reblog_of_id !== null) {
            return;
        }

        $service = $status->uri ? NetworkTimelineService::class : PublicTimelineService::class;

        $types = [
            'photo',
            'photo:album',
            'video',
            'video:album',
            'photo:video:album',
        ];

        if ($isNsfw) {
            $service::rem($status->id);
        } elseif (in_array($status->type, $types) &&
            AdminShadowFilterService::canAddToPublicFeedByProfileId($status->profile_id)
        ) {
            $service::add($status->id);
        }
    }

    public static function createPreviousEdit(Status $status)
    {
        if (! $status->edits()->count()) {
            StatusEdit::create([
                'status_id' => $status->id,
                'profile_id' => $status->profile_id,
                'caption' => $status->caption,
                'spoiler_text' => $status->cw_summary,
                'is_nsfw' => $status->is_nsfw,
                'ordered_media_attachment_ids' => $status->media()->orderBy('order')->pluck('id')->toArray(),
                'created_at' => $status->created_at,
            ]);
        }
    }

    public static function createEdit(Status $status, $attributes)
    {
        $cleaned = isset($attributes['status']) ? Purify::clean($attributes['status']) : $status->caption;
        $spoiler_text = isset($attributes['spoiler_text']) ? Purify::clean($attributes['spoiler_text']) : $status->cw_summary;
        $sensitive = isset($attributes['sensitive']) ? $attributes['sensitive'] : null;
        $mids = $status->media()->count() ? $status->media()->orderBy('order')->pluck('id')->toArray() : null;
        StatusEdit::create([
            'status_id' => $status->id,
            'profile_id' => $status->profile_id,
            'caption' => $cleaned,
            'spoiler_text' => $spoiler_text,
            'is_nsfw' => $sensitive,
            'ordered_media_attachment_ids' => $mids,
        ]);
    }
}
