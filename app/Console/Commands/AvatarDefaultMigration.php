<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Avatar;
use Cache, DB;
use Illuminate\Support\Str;

class AvatarDefaultMigration extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'fix:avatars';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Replace old svg identicon avatars with default png avatar';

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
        $this->info('Running avatar migration...');
        $count = Avatar::whereChangeCount(0)->count();

        if($count == 0) {
            $this->info('Found no avatars needing to be migrated!');
            exit;
        }

        $bar = $this->output->createProgressBar($count);
        $this->info("Found {$count} avatars that may need to be migrated");

        Avatar::whereChangeCount(0)->chunk(50, function($avatars) use ($bar) {
            foreach($avatars as $avatar) {
                if( $avatar->media_path == 'public/avatars/default.png' || 
                    $avatar->thumb_path == 'public/avatars/default.png' ||
                    $avatar->media_path == 'public/avatars/default.jpg' || 
                    $avatar->thumb_path == 'public/avatars/default.jpg'
                ) {
                    continue;
                }

                if(Str::endsWith($avatar->media_path, '/avatar.svg') == false) {
                    // do not modify non-default avatars
                    continue;
                }
                
                DB::transaction(function() use ($avatar, $bar) {

                    if(is_file(storage_path('app/' . $avatar->media_path))) {
                        @unlink(storage_path('app/' . $avatar->media_path));
                    }

                    if(is_file(storage_path('app/' . $avatar->thumb_path))) {
                        @unlink(storage_path('app/' . $avatar->thumb_path));
                    }

                    $avatar->media_path = 'public/avatars/default.jpg';
                    $avatar->thumb_path = 'public/avatars/default.jpg';
                    $avatar->change_count = $avatar->change_count + 1;
                    $avatar->save();

                    Cache::forget('avatar:' . $avatar->profile_id);
                    $bar->advance();
                });
            }
        });

        $bar->finish();
    }
}
