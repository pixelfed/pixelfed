<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $profile_id
 * @property string $service
 * @property string|null $uuid
 * @property string|null $storage_path
 * @property int $stage
 * @property string|null $media_json
 * @property string|null $completed_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Collection<int, ImportData> $files
 * @property-read int|null $files_count
 * @property-read Profile|null $profile
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ImportJob newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ImportJob newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ImportJob query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ImportJob whereCompletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ImportJob whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ImportJob whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ImportJob whereMediaJson($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ImportJob whereProfileId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ImportJob whereService($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ImportJob whereStage($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ImportJob whereStoragePath($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ImportJob whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ImportJob whereUuid($value)
 *
 * @mixin \Eloquent
 */
class ImportJob extends Model
{
    public function profile()
    {
        return $this->belongsTo(Profile::class, 'profile_id');
    }

    public function url()
    {
        return url("/i/import/job/{$this->uuid}/{$this->stage}");
    }

    public function files()
    {
        return $this->hasMany(ImportData::class, 'job_id');
    }

    public function mediaJson()
    {
        $path = storage_path("app/$this->media_json");

        return json_decode(file_get_contents($path), true);
    }
}
