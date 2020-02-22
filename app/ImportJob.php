<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

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
