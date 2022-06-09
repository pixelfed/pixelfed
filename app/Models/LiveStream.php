<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Storage;

class LiveStream extends Model
{
    use HasFactory;

    public function getHlsUrl()
    {
    	$path = Storage::url("live-hls/{$this->stream_id}/index.m3u8");
    	return url($path);
    }

    public function getStreamKeyUrl()
    {
    	$proto = 'rtmp://';
    	$host = config('livestreaming.server.host');
    	$port = ':' . config('livestreaming.server.port');
    	$path = '/' . config('livestreaming.server.path') . '?';
    	$query = http_build_query([
    		'name' => $this->stream_id,
    		'key' => $this->stream_key,
    		'ts' => time()
    	]);

    	return $proto . $host . $port . $path . $query;
    }

    public function getStreamRtmpUrl()
    {
    	$proto = 'rtmp://';
    	$host = config('livestreaming.server.host');
    	$port = ':' . config('livestreaming.server.port');
    	$path = '/' . config('livestreaming.server.path') . '/'. $this->stream_id . '?';
    	$query = http_build_query([
    		'key' => $this->stream_key,
    		'ts' => time()
    	]);

    	return $proto . $host . $port . $path . $query;
    }
}
