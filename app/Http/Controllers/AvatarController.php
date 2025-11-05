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
}
