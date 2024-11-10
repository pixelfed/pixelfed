<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use App\Models\Group;
use App\Models\GroupPost;
use App\Status;
use App\Models\InstanceActor;
use App\Services\MediaService;

class GroupFederationController extends Controller
{
	public function getGroupObject(Request $request, $id)
	{
		$group = Group::whereLocal(true)->whereActivitypub(true)->findOrFail($id);
		$res = $this->showGroupObject($group);
		return response()->json($res, 200, [], JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES);
	}

	public function showGroupObject($group)
	{
		return Cache::remember('ap:groups:object:' . $group->id, 3600, function() use($group) {
			return [
				'@context' => 'https://www.w3.org/ns/activitystreams',
				'id' => $group->url(),
				'inbox' => $group->permalink('/inbox'),
				'name' => $group->name,
				'outbox' => $group->permalink('/outbox'),
				'summary' => $group->description,
				'type' => 'Group',
				'attributedTo' => [
					'type' => 'Person',
					'id' => $group->admin->permalink()
				],
				// 'endpoints' => [
				// 	'sharedInbox' => config('app.url') . '/f/inbox'
				// ],
				'preferredUsername' => 'gid_' . $group->id,
				'publicKey' => [
					'id' => $group->permalink('#main-key'),
					'owner' => $group->permalink(),
					'publicKeyPem' => InstanceActor::first()->public_key,
				],
				'url' => $group->permalink()
			];

			if($group->metadata && isset($group->metadata['avatar'])) {
				$res['icon'] = [
					'type' => 'Image',
					'url' => $group->metadata['avatar']['url']
				];
			}

			if($group->metadata && isset($group->metadata['header'])) {
				$res['image'] = [
					'type' => 'Image',
					'url' => $group->metadata['header']['url']
				];
			}
			ksort($res);
			return $res;
		});
	}

	public function getStatusObject(Request $request, $gid, $sid)
	{
		$group = Group::whereLocal(true)->whereActivitypub(true)->findOrFail($gid);
		$gp = GroupPost::whereGroupId($gid)->findOrFail($sid);
		$status = Status::findOrFail($gp->status_id);
		// permission check

		$res = [
			'@context' => 'https://www.w3.org/ns/activitystreams',
			'id' => $gp->url(),

			'type' => 'Note',

			'summary'   => null,
			'content'   => $status->rendered ?? $status->caption,
			'inReplyTo' => null,

			'published'    => $status->created_at->toAtomString(),
			'url'          => $gp->url(),
			'attributedTo' => $status->profile->permalink(),
			'to'           => [
				'https://www.w3.org/ns/activitystreams#Public',
				$group->permalink('/followers'),
			],
			'cc' => [],
			'sensitive'        => (bool) $status->is_nsfw,
			'attachment'       => MediaService::activitypub($status->id),
			'target' => [
				'type' => 'Collection',
				'id' => $group->permalink('/wall'),
				'attributedTo' => $group->permalink()
			]
		];
		// ksort($res);
		return response()->json($res, 200, [], JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES);
	}
}
