<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\{Like, Status};
use DB;

class FixLikes extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'fix:likes';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Fix Like counts';

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
        $chunk = 100;
        $limit = Like::select('status_id')->groupBy('status_id')->get()->count();
        
        if($limit > 1000) {
            if($this->confirm('We have found more than 1000 records to update, this may take a few moments. Are you sure you want to continue?') == false) {
                $this->error('Cancelling command...');
                return;
            }
        }

        $bar = $this->output->createProgressBar($limit);
        $this->line(' ');
        $this->info(' Starting like count fix ...');
        $this->line(' ');
        $bar->start();

        Like::selectRaw('count(id) as count, status_id')
            ->groupBy(['status_id','id'])
            ->chunk($chunk, function($likes) use($bar) {
                foreach($likes as $like) {
                    $s = Status::find($like['status_id']);
                    if($s && $s->likes_count == 0) {
                        $s->likes_count = $like['count'];
                        $s->save();
                    }
                    $bar->advance();
                }
            });

        $bar->finish();
        $this->line(' ');
        $this->line(' ');
    }
}
