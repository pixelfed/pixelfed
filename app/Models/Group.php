<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\HasSnowflakePrimary;
use App\Profile;
use App\Services\GroupService;
use Illuminate\Database\Eloquent\SoftDeletes;

class Group extends Model
{
    use HasSnowflakePrimary, HasFactory, SoftDeletes;

	/**
	 * Indicates if the IDs are auto-incrementing.
	 *
	 * @var bool
	 */
	public $incrementing = false;

	protected $casts = [
		'metadata' => 'json'
	];

	public function url()
	{
		return url("/groups/{$this->id}");
	}

	public function permalink($suffix = null)
	{
		if(!$this->local) {
			return $this->remote_url;
		}
		return $this->url() . $suffix;
	}

	public function members()
	{
		return $this->hasMany(GroupMember::class);
	}

	public function admin()
	{
		return $this->belongsTo(Profile::class, 'profile_id');
	}

	public function isMember($id = false)
	{
		$id = $id ?? request()->user()->profile_id;
		// return $this->members()->whereProfileId($id)->whereJoinRequest(false)->exists();
		return GroupService::isMember($this->id, $id);
	}

	public function getMembershipType()
	{
		return $this->is_private ? 'private' : ($this->is_local ? 'local' : 'all');
	}

	public function selfRole($id = false)
	{
		$id = $id ?? request()->user()->profile_id;
		return optional($this->members()->whereProfileId($id)->first())->role ?? null;
	}
}
