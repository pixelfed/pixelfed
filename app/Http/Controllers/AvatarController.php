<?php

namespace App\Http\Controllers;

use App\Avatar;
use App\Jobs\AvatarPipeline\AvatarOptimize;
use Auth;
use Cache;
use Illuminate\Http\Request;
use Storage;

class AvatarController extends Controller
{
    public function __construct()
    {
        return $this->middleware('auth');
    }

    public function store(Request $request)
    {
        $this->validate($request, [
          'avatar' => 'required|mimetypes:image/jpeg,image/jpg,image/png|max:'.config('pixelfed.max_avatar_size'),
        ]);

        try {
            $user = Auth::user();
            $profile = $user->profile;
            $file = $request->file('avatar');
            $path = $this->getPath($user, $file);
            $dir = $path['root'];
            $name = $path['name'];
            $public = $path['storage'];
            $loc = $request->file('avatar')->storeAs($public, $name);

            $avatar = Avatar::firstOrNew(['profile_id' => $profile->id]);
            $currentAvatar = $avatar->recentlyCreated ? null : storage_path('app/'.$profile->avatar->media_path);
            $avatar->media_path = "$public/$name";
            $avatar->change_count = ++$avatar->change_count;
            $avatar->last_processed_at = null;
            $avatar->save();

            Cache::forget("avatar:{$profile->id}");
            Cache::forget('user:account:id:'.$user->id);
            AvatarOptimize::dispatch($user->profile, $currentAvatar);
        } catch (Exception $e) {
        }

        return redirect()->back()->with('status', 'Avatar updated successfully. It may take a few minutes to update across the site.');
    }

    public function getPath($user, $file)
    {
        $basePath = storage_path('app/public/avatars');
        $this->checkDir($basePath);

        $id = $user->profile->id;
        $path = $this->buildPath($id);
        $dir = storage_path('app/'.$path);
        $this->checkDir($dir);
        $name = str_random(20).'_avatar.'.$file->guessExtension();
        $res = ['root' => 'storage/app/'.$path, 'name' => $name, 'storage' => $path];

        return $res;
    }

    public function checkDir($path)
    {
        if (!is_dir($path)) {
            mkdir($path);
        }
    }

    public function buildPath($id)
    {
        $padded = str_pad($id, 19, 0, STR_PAD_LEFT);
        $parts = str_split($padded, 3);
        foreach ($parts as $k => $part) {
            if ($k == 0) {
                $prefix = storage_path('app/public/avatars/'.$parts[0]);
                $this->checkDir($prefix);
            }
            if ($k == 1) {
                $prefix = storage_path('app/public/avatars/'.$parts[0].'/'.$parts[1]);
                $this->checkDir($prefix);
            }
            if ($k == 2) {
                $prefix = storage_path('app/public/avatars/'.$parts[0].'/'.$parts[1].'/'.$parts[2]);
                $this->checkDir($prefix);
            }
            if ($k == 3) {
                $avatarpath = 'public/avatars/'.$parts[0].'/'.$parts[1].'/'.$parts[2].'/'.$parts[3];
                $prefix = storage_path('app/'.$avatarpath);
                $this->checkDir($prefix);
            }
            if ($k == 4) {
                $avatarpath = 'public/avatars/'.$parts[0].'/'.$parts[1].'/'.$parts[2].'/'.$parts[3].'/'.$parts[4];
                $prefix = storage_path('app/'.$avatarpath);
                $this->checkDir($prefix);
            }
            if ($k == 5) {
                $avatarpath = 'public/avatars/'.$parts[0].'/'.$parts[1].'/'.$parts[2].'/'.$parts[3].'/'.$parts[4].'/'.$parts[5];
                $prefix = storage_path('app/'.$avatarpath);
                $this->checkDir($prefix);
            }
            if ($k == 6) {
                $avatarpath = 'public/avatars/'.$parts[0].'/'.$parts[1].'/'.$parts[2].'/'.$parts[3].'/'.$parts[4].'/'.$parts[5].'/'.$parts[6];
                $prefix = storage_path('app/'.$avatarpath);
                $this->checkDir($prefix);
            }
        }

        return $avatarpath;
    }

    public function deleteAvatar(Request $request)
    {
        $user = Auth::user();
        $profile = $user->profile;

        $avatar = $profile->avatar;

        if( $avatar->media_path == 'public/avatars/default.png' || 
            $avatar->media_path == 'public/avatars/default.jpg'
        ) {
            return;
        }

        if(is_file(storage_path('app/' . $avatar->media_path))) {
            @unlink(storage_path('app/' . $avatar->media_path));
        }

        $avatar->media_path = 'public/avatars/default.jpg';
        $avatar->change_count = $avatar->change_count + 1;
        $avatar->save();

        Cache::forget('avatar:' . $avatar->profile_id);

        return response()->json(200);
    }
}
