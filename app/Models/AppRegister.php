<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $email
 * @property string $verify_code
 * @property string|null $email_delivered_at
 * @property string|null $email_verified_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property int $uses
 * @property int $failed_attempts
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AppRegister newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AppRegister newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AppRegister query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AppRegister whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AppRegister whereEmail($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AppRegister whereEmailDeliveredAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AppRegister whereEmailVerifiedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AppRegister whereFailedAttempts($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AppRegister whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AppRegister whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AppRegister whereUses($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AppRegister whereVerifyCode($value)
 *
 * @mixin \Eloquent
 */
class AppRegister extends Model
{
    protected $guarded = [];
}
