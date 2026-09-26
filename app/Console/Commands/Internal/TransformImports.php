<?php

namespace App\Console\Commands\Internal;

use App\Models\ImportPost;
use App\Models\Media;
use App\Models\Profile;
use App\Models\Status;
use App\Services\AccountService;
use App\Services\ImportService;
use App\Services\MediaPathService;
use Illuminate\Console\Command;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

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
                $fileName = $this->sanitizeFilename(last(explode('/', $ipm['uri'])));
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

            $caption = strip_tags($ip->caption ?? '');

            $mediaRecords = [];
            foreach ($ip->media as $ipm) {
                $fileName = $this->sanitizeFilename(last(explode('/', $ipm['uri'])));
                $ext = strtolower(last(explode('.', $fileName)));
                if (! in_array($ext, ['jpg', 'jpeg', 'png', 'webp', 'mp4'], true)) {
                    continue;
                }
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
                // Copy, not move: the source in imports/ must survive until the
                // DB transaction commits. A move followed by delete-on-failure
                // destroys the only copy and makes the import unrecoverable.
                $disk->copy($og, $np);

                $mediaRecords[] = [
                    'media_path' => $np,
                    'source_path' => $og,
                    'mime' => $mime,
                    'size' => $size,
                ];
            }

            try {
                $runImport = function () use ($ip, $profile, $id, $pid, $caption, $mediaRecords) {
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
                    $status->rendered = '';
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

                    if (
                        $uniqueIdData['year'] !== $ip->creation_year ||
                        $uniqueIdData['month'] !== $ip->creation_month ||
                        $uniqueIdData['day'] !== $ip->creation_day
                    ) {

                        $ip->creation_year = $uniqueIdData['year'];
                        $ip->creation_month = $uniqueIdData['month'];
                        $ip->creation_day = $uniqueIdData['day'];

                        $this->info("Date shifted for ImportPost ID {$ip->id} to {$uniqueIdData['year']}-{$uniqueIdData['month']}-{$uniqueIdData['day']}");
                    }

                    $ip->save();

                    $profile->status_count = $profile->status_count + 1;
                    $profile->save();
                };

                // A concurrent import can win the statuses.id insert race,
                // failing this transaction with a unique violation (SQLSTATE
                // 23000). That is recoverable: retry so getUniqueCreationId()
                // picks the next id from the now-committed row, instead of
                // permanently marking the post skipped.
                $maxAttempts = 3;
                for ($attempt = 1; ; $attempt++) {
                    try {
                        DB::transaction($runImport);
                        break;
                    } catch (QueryException $e) {
                        if ($e->getCode() === '23000' && $attempt < $maxAttempts) {
                            usleep(random_int(100, 1000));

                            continue;
                        }
                        throw $e;
                    }
                }

                // Commit succeeded: now it is safe to remove the originals from
                // imports/. Until this point the source copy is the fallback
                // that lets a failed import be retried.
                foreach ($mediaRecords as $mediaData) {
                    if (isset($mediaData['source_path']) && $disk->exists($mediaData['source_path'])) {
                        $disk->delete($mediaData['source_path']);
                    }
                }

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

            // Files for this import post were moved OUT of imports/{userId}.
            // Remove that directory once it holds no more files so completed
            // imports don't leave an empty folder behind.
            $importDir = 'imports/'.$id;
            if ($disk->exists($importDir) && empty($disk->files($importDir))) {
                $disk->deleteDirectory($importDir);
            }
        }
    }

    /**
     * Sanitize a filename to match how ImportPostController::storeMedia wrote
     * it to disk. The uploaded file is stored under a name with unsafe chars
     * replaced by underscores, so a lookup built from the raw import URI (which
     * may contain spaces/special chars) would miss the file and wrongly skip
     * the post as "missing media".
     */
    private function sanitizeFilename(string $filename): string
    {
        $parts = explode('.', $filename);
        $extension = array_pop($parts);
        $originalName = implode('.', $parts);

        $safeFilename = preg_replace('/[^a-zA-Z0-9_.-]/', '_', $originalName);

        return $safeFilename.'.'.$extension;
    }
}
