<?php

use App\Federation\ActivityBuilders\FollowActivityBuilder;
use App\Models\Profile;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;

uses(LazilyRefreshDatabase::class);

beforeEach(function () {
    $user = User::factory()->create();
    $user->refresh();

    $this->follower = $user->profile;
    $this->target = Profile::factory()->remote()->create([
        'sharedInbox' => 'https://remote.example/inbox',
        'inbox_url' => 'https://remote.example/users/x/inbox',
    ]);
});

it('builds a Follow whose id the Undo object cites', function () {
    $builder = new FollowActivityBuilder;

    $follow = $builder->build($this->follower, $this->target);
    $undo = $builder->buildUndo($this->follower, $this->target);

    expect($follow['type'])->toBe('Follow');
    expect($undo['type'])->toBe('Undo');
    expect($undo['object']['id'])->toBe($follow['id']);
    expect($undo['id'])->not->toBe($follow['id']);
    expect($follow['id'])->toBe($this->follower->permalink('#follows/'.$this->target->id));
});

it('nests the same Follow activity inside the Undo', function () {
    $builder = new FollowActivityBuilder;

    $follow = $builder->build($this->follower, $this->target);
    $undo = $builder->buildUndo($this->follower, $this->target);

    unset($follow['@context']);

    expect($undo['object'])->toBe($follow);
    expect($undo['actor'])->toBe($this->follower->permalink());
    expect($follow['object'])->toBe($this->target->permalink());
});
