<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Media;
use App\Status;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use App\Services\MediaService;
use App\Services\StatusService;
use Symfony\Component\HttpFoundation\File\Exception\FileNotFoundException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class MediaS3GarbageCollector extends Command
{
    /**
    * The name and signature of the console command.
    *
    * @var string
    */
    protected $signature = 'media:s3gc {--limit=200} {--huge} {--log-errors}';

    /**
    * The console command description.
    *
    * @var string
    */
    protected $description = 'Delete (local) media uploads that exist on S3';

    /**
    * Create a new command instance.
    *
    * @return void
    */
    public function __construct()
    {
        parent::__construct();
    }

    /**
    * Execute the console command.
    *
    * @return int
    */
    public function handle()
    {
        $enabled = config('pixelfed.cloud_storage');
        if(!$enabled) {
            $this->error('Cloud storage not enabled. Exiting...');
            return;
        }

        $deleteEnabled = config('media.delete_local_after_cloud');
        if(!$deleteEnabled) {
            $this->error('Delete local storage after cloud upload is not enabled');
            return;
        }

        $limit = $this->option('limit');
        $hugeMode = $this->option('huge');
        $log = $this->option('log-errors');

        if($limit > 2000 && !$hugeMode) {
            $this->error('Limit exceeded, please use a limit under 2000 or run again with the --huge flag');
            return;
        }

        $minId = Media::orderByDesc('id')->where('created_at', '<', now()->subHours(12))->first()->id;

        return $hugeMode ?
            $this->hugeMode($minId, $limit, $log) :
            $this->regularMode($minId, $limit, $log);
    }

    protected function regularMode($minId, $limit, $log)
    {
        $gc = Media::whereRemoteMedia(false)
            ->whereNotNull(['status_id', 'cdn_url', 'replicated_at'])
            ->whereNot('version', '4')
            ->where('id', '<', $minId)
            ->inRandomOrder()
            ->take($limit)
            ->get();

        $totalSize = 0;
        $bar = $this->output->createProgressBar($gc->count());
        $bar->start();
        $cloudDisk = Storage::disk(config('filesystems.cloud'));
        $localDisk = Storage::disk('local');

        foreach($gc as $media) {
            try {
                if(
                    $cloudDisk->exists($media->media_path)
                ) {
                    if( $localDisk->exists($media->media_path)) {
                        $localDisk->delete($media->media_path);
                        $media->version = 4;
                        $media->save();
                        $totalSize = $totalSize + $media->size;
                        MediaService::del($media->status_id);
                        StatusService::del($media->status_id, false);
                        if($localDisk->exists($media->thumbnail_path)) {
                            $localDisk->delete($media->thumbnail_path);
                        }
                    } else {
                        $media->version = 4;
                        $media->save();
                    }
                } else {
                    if($log) {
                        Log::channel('media')->info('[GC] Local media not properly persisted to cloud storage', ['media_id' => $media->id]);
                    }
                }
                $bar->advance();
            } catch (FileNotFoundException $e) {
                $bar->advance();
                continue;
            } catch (NotFoundHttpException $e) {
                $bar->advance();
                continue;
            } catch (\Exception $e) {
                $bar->advance();
                continue;
            }
        }
        $bar->finish();
        $this->line(' ');
        $this->info('Finished!');
        if($totalSize) {
            $this->info('Cleared ' . $totalSize . ' bytes of media from local disk!');
        }
        return 0;
    }

    protected function hugeMode($minId, $limit, $log)
    {
        $cloudDisk = Storage::disk(config('filesystems.cloud'));
        $localDisk = Storage::disk('local');

        $bar = $this->output->createProgressBar($limit);
        $bar->start();

        Media::whereRemoteMedia(false)
            ->whereNotNull(['status_id', 'cdn_url', 'replicated_at'])
            ->whereNot('version', '4')
            ->where('id', '<', $minId)
            ->chunk(50, function($medias) use($cloudDisk, $localDisk, $bar, $log) {
                foreach($medias as $media) {
                    try {
                        if($cloudDisk->exists($media->media_path)) {
                            if( $localDisk->exists($media->media_path)) {
                                $localDisk->delete($media->media_path);
                                $media->version = 4;
                                $media->save();
                                MediaService::del($media->status_id);
                                StatusService::del($media->status_id, false);
                                if($localDisk->exists($media->thumbnail_path)) {
                                    $localDisk->delete($media->thumbnail_path);
                                }
                            } else {
                                $media->version = 4;
                                $media->save();
                            }
                        } else {
                            if($log) {
                                Log::channel('media')->info('[GC] Local media not properly persisted to cloud storage', ['media_id' => $media->id]);
                            }
                        }
                        $bar->advance();
                    } catch (FileNotFoundException $e) {
                        $bar->advance();
                        continue;
                    } catch (NotFoundHttpException $e) {
                        $bar->advance();
                        continue;
                    } catch (\Exception $e) {
                        $bar->advance();
                        continue;
                    }
                }
        });

        $bar->finish();
        $this->line(' ');
        $this->info('Finished!');
    }
}
