<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int|null $user_id
 * @property string|null $type
 * @property string|null $view
 * @property int|null $item_id
 * @property string|null $item_type
 * @property int|null $is_spam
 * @property int|null $in_violation
 * @property int|null $violation_id
 * @property int|null $email_notify
 * @property int|null $has_media
 * @property string|null $blurhash
 * @property string|null $message
 * @property string|null $violation_header
 * @property string|null $violation_body
 * @property string|null $meta
 * @property string|null $appeal_message
 * @property Carbon|null $appeal_requested_at
 * @property string|null $appeal_handled_at
 * @property Carbon|null $read_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property int|null $severity_index
 * @property int|null $thread_id
 * @property string|null $emailed_at
 * @property-read User|null $user
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AccountInterstitial newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AccountInterstitial newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AccountInterstitial query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AccountInterstitial whereAppealHandledAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AccountInterstitial whereAppealMessage($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AccountInterstitial whereAppealRequestedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AccountInterstitial whereBlurhash($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AccountInterstitial whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AccountInterstitial whereEmailNotify($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AccountInterstitial whereEmailedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AccountInterstitial whereHasMedia($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AccountInterstitial whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AccountInterstitial whereInViolation($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AccountInterstitial whereIsSpam($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AccountInterstitial whereItemId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AccountInterstitial whereItemType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AccountInterstitial whereMessage($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AccountInterstitial whereMeta($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AccountInterstitial whereReadAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AccountInterstitial whereSeverityIndex($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AccountInterstitial whereThreadId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AccountInterstitial whereType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AccountInterstitial whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AccountInterstitial whereUserId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AccountInterstitial whereView($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AccountInterstitial whereViolationBody($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AccountInterstitial whereViolationHeader($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AccountInterstitial whereViolationId($value)
 *
 * @mixin \Eloquent
 */
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
