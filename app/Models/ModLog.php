<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $user_id
 * @property string|null $user_username
 * @property int|null $object_uid
 * @property int|null $object_id
 * @property string|null $object_type
 * @property string|null $action
 * @property string|null $message
 * @property string|null $metadata
 * @property string|null $access_level
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read User|null $admin
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ModLog newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ModLog newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ModLog query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ModLog whereAccessLevel($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ModLog whereAction($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ModLog whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ModLog whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ModLog whereMessage($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ModLog whereMetadata($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ModLog whereObjectId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ModLog whereObjectType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ModLog whereObjectUid($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ModLog whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ModLog whereUserId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ModLog whereUserUsername($value)
 *
 * @mixin \Eloquent
 */
class ModLog extends Model
{
    protected $visible = ['id'];

    protected $guarded = [];

    public function admin()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function actionToText(): string
    {
        $msg = 'Unknown action';

        switch ($this->action) {
            case 'admin.user.mail':
                $msg = 'Sent Message';
                break;

            case 'admin.user.action.cw.warn':
                $msg = 'Sent CW reminder';
                break;

            case 'admin.user.edit':
                $msg = 'Changed Profile';
                break;

            case 'admin.user.moderate':
                $msg = 'Moderation';
                break;

            case 'admin.user.delete':
                $msg = 'Deleted Account';
                break;

            default:
                $msg = 'Unknown action';
                break;
        }

        return $msg;
    }
}
