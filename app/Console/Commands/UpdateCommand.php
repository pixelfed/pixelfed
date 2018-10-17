<?php

namespace App\Console\Commands;

use Schema;
use App\{Media, Status};
use Illuminate\Console\Command;
use App\Jobs\ImageOptimizePipeline\ImageThumbnail;

class UpdateCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'update';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Run pixelfed schema updates between versions.';

    protected $versions = [
        '0.1.0',
        '0.1.2',
        '0.1.3',
        '0.1.4',
        '0.1.5',
        '0.1.6',
        '0.1.7',
        '0.1.8',
        '0.1.9',
    ];

    protected $version;

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
     * @return mixed
     */
    public function handle()
    {
        $this->verifyVersion();
    }

    public function verifyVersion()
    {
        $this->callSilent('config:cache');
        $this->version = config('pixelfed.version');

        $known = in_array($this->version, $this->versions);
        if($known == false) {
            $this->error('Unknown version found, aborting...');
            exit;   
        }
        $this->updateStrategy($this->version);
    }

    public function updateStrategy($version)
    {
        switch ($version) {
            case '0.1.8':
                $this->info('You are running an older version that doesn\'t require any updates!');
                break;
            case '0.1.9':
                $this->update019();
                break;

            default:
                # code...
                break;
        }
    }

    public function update019()
    {
        $this->buildVersionFile();
        $v = $this->getVersionFile();
        if($v['updated'] == true) {
            $this->info('Already up to date!');
            exit;
        }
        $exists = Schema::hasColumn('statuses','scope');
        if(!$exists) {
            $this->error('You need to run the migrations before you proceed with this update.');
            if($this->confirm('Do you want to run the migrations?')) {
                $this->callSilent('migrate');
            } else {
                exit;
            }
        }
        $statusCount = Status::count();
        $this->info('Running updates ...');
        $bar = $this->output->createProgressBar($statusCount);
        Status::chunk(200, function($statuses) use ($bar) {

            foreach($statuses as $status) {
                $ts = $status->updated_at;
                $status->scope = $status->visibility;
                $status->updated_at = $ts;
                $status->save();

                if($status->firstMedia()) {
                    $media = $status->firstMedia();
                    if(in_array($media->mime, ['image/jpeg', 'image/png'])) {
                        ImageThumbnail::dispatch($media);
                    }
                }
                $bar->advance();
            }
        });
        $this->updateVersionFile('0.1.9', true);
        $bar->finish();
    }

    protected function buildVersionFile()
    {
        $path = storage_path('app/version.json');
        if(is_file($path) == false) {
            $contents = json_encode([
                'version' => $this->version,
                'updated' => false,
                'timestamp' => date('c')
            ], JSON_PRETTY_PRINT);
            file_put_contents($path, $contents);
        }
    }

    protected function getVersionFile()
    {
        $path = storage_path('app/version.json');
        if(is_file($path) == false) {
            $contents = [
                'version' => $this->version,
                'updated' => false,
                'timestamp' => date('c')
            ];
            $json = json_encode($contents, JSON_PRETTY_PRINT);
            file_put_contents($path, $json);
            return $contents;
        } else {
            return json_decode(file_get_contents($path), true);
        }
    }

    protected function updateVersionFile($version, $updated = false) {
        $path = storage_path('app/version.json');
        if(is_file($path) == false) {
            return;
        }
        $contents = [
            'version' => $version,
            'updated' => $updated,
            'timestamp' => date('c')
        ];
        $json = json_encode($contents, JSON_PRETTY_PRINT);
        file_put_contents($path, $json);
    }
}
