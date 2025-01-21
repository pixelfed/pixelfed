<?php

namespace App\Services\Status;

use App\Media;
use App\Models\StatusEdit;
use App\ModLog;
use App\Services\MediaService;
use App\Services\MediaStorageService;
use App\Services\StatusService;
use App\Status;
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
        if (isset($attributes['status'])) {
            $cleaned = Purify::clean($attributes['status']);
            $status->caption = $cleaned;
        } else {
            $status->caption = null;
        }
        if (isset($attributes['sensitive'])) {
            if ($status->is_nsfw != (bool) $attributes['sensitive'] &&
              (bool) $attributes['sensitive'] == false) {
                $exists = ModLog::whereObjectType('App\Status::class')
                    ->whereObjectId($status->id)
                    ->whereAction('admin.status.moderate')
                    ->exists();
                if (! $exists) {
                    $status->is_nsfw = (bool) $attributes['sensitive'];
                }
            } else {
                $status->is_nsfw = (bool) $attributes['sensitive'];
            }
        }
        if (isset($attributes['spoiler_text'])) {
            $status->cw_summary = Purify::clean($attributes['spoiler_text']);
        } else {
            $status->cw_summary = null;
        }
        if (isset($attributes['location'])) {
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
        StatusService::del($status->id);
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
        $cleaned = isset($attributes['status']) ? Purify::clean($attributes['status']) : null;
        $spoiler_text = isset($attributes['spoiler_text']) ? Purify::clean($attributes['spoiler_text']) : null;
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
