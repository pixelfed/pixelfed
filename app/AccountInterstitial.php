<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AccountInterstitial extends Model
{
    public const JSON_MESSAGE = 'Please use web browser to proceed.';

    protected function casts(): array
    {
        return [
            'read_at' => 'datetime',
            'appeal_requested_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function status()
    {
        if ($this->item_type != Status::class) {
            return;
        }

        return $this->hasOne(Status::class, 'id', 'item_id');
    }

    /**
     * Create an AccountInterstitial for a moderated status.
     *
     * @param  Status  $status  The status being moderated
     * @param  string  $type  The interstitial type (e.g. 'post.cw', 'post.unlist', 'post.removed', 'post.autospam')
     * @param  string  $view  The blade view for the interstitial
     */
    public static function createFromStatus(Status $status, string $type, string $view): self
    {
        $media = $status->media;

        $ai = new self;
        $ai->user_id = $status->profile->user_id;
        $ai->type = $type;
        $ai->view = $view;
        $ai->item_type = Status::class;
        $ai->item_id = $status->id;
        $ai->has_media = (bool) $media->count();
        $ai->blurhash = $media->count() ? $media->first()->blurhash : null;
        $ai->meta = json_encode([
            'caption' => $status->caption,
            'created_at' => $status->created_at,
            'type' => $status->type,
            'url' => $status->url(),
            'is_nsfw' => $status->is_nsfw,
            'scope' => $status->scope,
            'reblog' => $status->reblog_of_id,
            'likes_count' => $status->likes_count,
            'reblogs_count' => $status->reblogs_count,
        ]);
        $ai->save();

        $u = $status->profile->user;
        $u->has_interstitial = true;
        $u->save();

        return $ai;
    }
}
