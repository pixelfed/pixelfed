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

	public function getStreamServer()
	{
		$proto = 'rtmp://';
		$host = config('livestreaming.server.host');
		$port = ':' . config('livestreaming.server.port');
		$path = '/' . config('livestreaming.server.path');
		return $proto . $host . $port . $path;
	}

	public function getStreamKeyUrl()
	{
		$path = $this->getStreamServer() . '?';
		$query = http_build_query([
			'name' => $this->stream_key,
		]);
		return $path . $query;
	}

	public function getStreamRtmpUrl()
	{
		return $this->getStreamServer() . '/' . $this->stream_id;
	}
}
