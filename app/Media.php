<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Storage;

class Media extends Model
{
    public function url()
    {
      $path = $this->media_path;
      $url = Storage::url($path);
      return url($url);
    }
}
