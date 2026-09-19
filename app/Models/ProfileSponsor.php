<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property int $profile_id
 * @property string|null $sponsors
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Profile|null $profile
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProfileSponsor newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProfileSponsor newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProfileSponsor query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProfileSponsor whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProfileSponsor whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProfileSponsor whereProfileId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProfileSponsor whereSponsors($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProfileSponsor whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class ProfileSponsor extends Model
{
    public $fillable = ['profile_id'];

    public function profile()
    {
        return $this->belongsTo(Profile::class);
    }
}
