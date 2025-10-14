<?php

namespace App\Console\Commands;

use App\Media;
use App\Models\ImportPost;
use App\Profile;
use App\Services\AccountService;
use App\Services\ImportService;
use App\Services\MediaPathService;
use App\Status;
use Illuminate\Console\Command;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Storage;

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
        if (! config('import.instagram.enabled')) {
            return;
        }

        $ips = ImportPost::whereNull('status_id')->where('skip_missing_media', '!=', true)->take(1500)->get();

        if (! $ips->count()) {
            return;
        }

        $localFs = config('filesystems.default') === 'local';
        $disk = $localFs ? Storage::disk('local') : Storage::disk(config('filesystems.default'));

        foreach ($ips as $ip) {
            $id = $ip->user_id;
            $pid = $ip->profile_id;
            $profile = Profile::find($pid);
            if (! $profile) {
                $ip->skip_missing_media = true;
                $ip->save();

                continue;
            }

            $exists = ImportPost::whereUserId($id)
                ->whereNotNull('status_id')
                ->where('filename', $ip->filename)
                ->where('creation_year', $ip->creation_year)
                ->where('creation_month', $ip->creation_month)
                ->where('creation_day', $ip->creation_day)
                ->exists();

            if ($exists == true) {
                $ip->skip_missing_media = true;
                $ip->save();

                continue;
            }

            if ($id > 999999) {
                $ip->skip_missing_media = true;
                $ip->save();

                continue;
            }
            if ($ip->creation_year < 9 || $ip->creation_year > (int) now()->addYear()->format('y')) {
                $ip->skip_missing_media = true;
                $ip->save();

                continue;
            }
            if ($ip->creation_month < 1 || $ip->creation_month > 12) {
                $ip->skip_missing_media = true;
                $ip->save();

                continue;
            }
            if ($ip->creation_day < 1 || $ip->creation_day > 31) {
                $ip->skip_missing_media = true;
                $ip->save();

                continue;
            }

            if ($disk->exists('imports/'.$id.'/'.$ip->filename) === false) {
                ImportService::clearAttempts($profile->id);
                ImportService::getPostCount($profile->id, true);
                $ip->skip_missing_media = true;
                $ip->save();

                continue;
            }

            $missingMedia = false;
            foreach ($ip->media as $ipm) {
                $fileName = last(explode('/', $ipm['uri']));
                $og = 'imports/'.$id.'/'.$fileName;
                if (! $disk->exists($og)) {
                    $missingMedia = true;
                }
            }

            if ($missingMedia === true) {
                $ip->skip_missing_media = true;
                $ip->save();

                continue;
            }

            $caption = $ip->caption ?? '';

            $mediaRecords = [];
            foreach ($ip->media as $ipm) {
                $fileName = last(explode('/', $ipm['uri']));
                $ext = last(explode('.', $fileName));
                $basePath = MediaPathService::get($profile);
                $og = 'imports/'.$id.'/'.$fileName;
                if (! $disk->exists($og)) {
                    $ip->skip_missing_media = true;
                    $ip->save();

                    continue 2;
                }
                $size = $disk->size($og);
                $mime = $disk->mimeType($og);
                $newFile = Str::random(40).'.'.$ext;
                $np = $basePath.'/'.$newFile;
                $disk->move($og, $np);

                $mediaRecords[] = [
                    'media_path' => $np,
                    'mime' => $mime,
                    'size' => $size,
                ];
            }

            try {
                DB::transaction(function () use ($ip, $profile, $id, $pid, $caption, $mediaRecords) {
                    $uniqueIdData = ImportService::getUniqueCreationId(
                        $id,
                        $ip->creation_year,
                        $ip->creation_month,
                        $ip->creation_day,
                        $ip->id
                    );

                    if (! $uniqueIdData) {
                        throw new \Exception("Could not generate unique creation_id for ImportPost ID {$ip->id}");
                    }

                    $statusId = $uniqueIdData['status_id'];

                    $status = new Status;
                    $status->profile_id = $pid;
                    $status->caption = $caption;
                    $status->type = $ip->post_type;
                    $status->scope = 'public';
                    $status->visibility = 'public';
                    $status->id = $statusId;
                    $status->created_at = now()->parse($ip->creation_date);
                    $status->saveQuietly();

                    foreach ($mediaRecords as $mediaData) {
                        $media = new Media;
                        $media->profile_id = $pid;
                        $media->user_id = $id;
                        $media->status_id = $status->id;
                        $media->media_path = $mediaData['media_path'];
                        $media->mime = $mediaData['mime'];
                        $media->size = $mediaData['size'];
                        $media->save();
                    }

                    $ip->status_id = $status->id;
                    $ip->creation_id = $uniqueIdData['incr'];

                    if ($uniqueIdData['year'] !== $ip->creation_year ||
                        $uniqueIdData['month'] !== $ip->creation_month ||
                        $uniqueIdData['day'] !== $ip->creation_day) {

                        $ip->creation_year = $uniqueIdData['year'];
                        $ip->creation_month = $uniqueIdData['month'];
                        $ip->creation_day = $uniqueIdData['day'];

                        $this->info("Date shifted for ImportPost ID {$ip->id} to {$uniqueIdData['year']}-{$uniqueIdData['month']}-{$uniqueIdData['day']}");
                    }

                    $ip->save();

                    $profile->status_count = $profile->status_count + 1;
                    $profile->save();
                });

                AccountService::del($profile->id);
                ImportService::clearAttempts($profile->id);
                ImportService::getPostCount($profile->id, true);

            } catch (QueryException $e) {
                $this->error("Database error for ImportPost ID {$ip->id}: ".$e->getMessage());
                $ip->skip_missing_media = true;
                $ip->save();

                foreach ($mediaRecords as $mediaData) {
                    if ($disk->exists($mediaData['media_path'])) {
                        $disk->delete($mediaData['media_path']);
                    }
                }

                continue;
            } catch (\Exception $e) {
                $this->error("Error processing ImportPost ID {$ip->id}: ".$e->getMessage());
                $ip->skip_missing_media = true;
                $ip->save();

                foreach ($mediaRecords as $mediaData) {
                    if ($disk->exists($mediaData['media_path'])) {
                        $disk->delete($mediaData['media_path']);
                    }
                }

                continue;
            }
        }
    }
}
