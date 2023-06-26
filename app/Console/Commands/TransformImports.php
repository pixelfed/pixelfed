<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\ImportPost;
use App\Services\ImportService;
use App\Media;
use App\Profile;
use App\Status;
use Storage;
use App\Services\MediaPathService;
use Illuminate\Support\Str;
use App\Util\Lexer\Autolink;

class TransformImports extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:transform-imports';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Transform imports into statuses';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        if(!config('import.instagram.enabled')) {
            return;
        }

        $ips = ImportPost::whereNull('status_id')->where('skip_missing_media', '!=', true)->take(200)->get();

        if(!$ips->count()) {
            return;
        }

        foreach($ips as $ip) {
            $id = $ip->user_id;
            $pid = $ip->profile_id;
            $profile = Profile::find($pid);
            if(!$profile) {
                $ip->skip_missing_media = true;
                $ip->save();
                continue;
            }

            $idk = ImportService::getId($ip->user_id, $ip->creation_year, $ip->creation_month, $ip->creation_day);
            $exists = ImportPost::whereUserId($id)->where('filename', $ip->filename)->first();
            if($exists) {
                $cYear = str_pad($exists->creation_year, 2, 0, STR_PAD_LEFT);
                $cMonth = str_pad($exists->creation_month, 2, 0, STR_PAD_LEFT);
                $cDay = str_pad($exists->creation_day, 2, 0, STR_PAD_LEFT);
                if( $cYear == $idk['year'] &&
                    $cMonth == $idk['month'] &&
                    $cDay == $idk['day']
                ) {
                    $ip->skip_missing_media = true;
                    $ip->save();
                    continue;
                }
            }

            if(Storage::exists('imports/' . $id . '/' . $ip->filename) === false) {
                ImportService::clearAttempts($profile->id);
                ImportService::getPostCount($profile->id, true);
                $ip->skip_missing_media = true;
                $ip->save();
                continue;
            }

            $missingMedia = false;
            foreach($ip->media as $ipm) {
                $fileName = last(explode('/', $ipm['uri']));
                $og = 'imports/' . $id . '/' . $fileName;
                if(!Storage::exists($og)) {
                    $missingMedia = true;
                }
            }

            if($missingMedia === true) {
                $ip->skip_missing_media = true;
                $ip->save();
                continue;
            }

            $caption = $ip->caption;
            $status = new Status;
            $status->profile_id = $pid;
            $status->caption = $caption;
            $status->rendered = strlen(trim($caption)) ? Autolink::create()->autolink($ip->caption) : null;
            $status->type = $ip->post_type;

            $status->scope = 'unlisted';
            $status->visibility = 'unlisted';
            $status->id = $idk['id'];
            $status->created_at = now()->parse($ip->creation_date);
            $status->save();

            foreach($ip->media as $ipm) {
                $fileName = last(explode('/', $ipm['uri']));
                $ext = last(explode('.', $fileName));
                $basePath = MediaPathService::get($profile);
                $og = 'imports/' . $id . '/' . $fileName;
                if(!Storage::exists($og)) {
                    $ip->skip_missing_media = true;
                    $ip->save();
                    continue;
                }
                $size = Storage::size($og);
                $mime = Storage::mimeType($og);
                $newFile = Str::random(40) . '.' . $ext;
                $np = $basePath . '/' . $newFile;
                Storage::move($og, $np);
                $media = new Media;
                $media->profile_id = $pid;
                $media->user_id = $id;
                $media->status_id = $status->id;
                $media->media_path = $np;
                $media->mime = $mime;
                $media->size = $size;
                $media->save();
            }

            $ip->status_id = $status->id;
            $ip->creation_id = $idk['incr'];
            $ip->save();

            ImportService::clearAttempts($profile->id);
            ImportService::getPostCount($profile->id, true);
        }
    }
}
