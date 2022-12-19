<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Media;
use App\Services\MediaStorageService;
use App\Util\Lexer\PrettyNumber;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\File\Exception\FileNotFoundException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class CloudMediaMigrate extends Command
{
    public $totalSize = 0;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'media:migrate2cloud {--limit=200} {--huge}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Move older media to cloud storage';

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

        $limit = $this->option('limit');
        $hugeMode = $this->option('huge');

        if($limit > 500 && !$hugeMode) {
            $this->error('Max limit exceeded, use a limit lower than 500 or run again with the --huge flag');
            return;
        }

        $bar = $this->output->createProgressBar($limit);
        $bar->start();

        Media::whereNot('version', '4')
            ->where('created_at', '<', now()->subDays(2))
            ->whereRemoteMedia(false)
            ->whereNotNull(['status_id', 'profile_id'])
            ->whereNull(['cdn_url', 'replicated_at'])
            ->orderByDesc('size')
            ->take($limit)
            ->get()
            ->each(function($media) use($bar) {
                if(Storage::disk('local')->exists($media->media_path)) {
                    $this->totalSize = $this->totalSize + $media->size;
                    try {
                        MediaStorageService::store($media);
                    } catch (FileNotFoundException $e) {
                        return;
                    } catch (NotFoundHttpException $e) {
                        return;
                    } catch (\Exception $e) {
                        return;
                    }
                }
                $bar->advance();
            });

        $bar->finish();
        $this->line(' ');
        $this->info('Finished!');
        if($this->totalSize) {
            $this->info('Uploaded ' . PrettyNumber::size($this->totalSize) . ' of media to cloud storage!');
            $this->line(' ');
            $this->info('These files are still stored locally, and will be automatically removed.');
        }
        return Command::SUCCESS;
    }
}
