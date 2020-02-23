<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Util\Media\Filter;
use App\Media;

class MediaFix extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'media:fix';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Fix media on v0.10.8+';

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
        if(!version_compare(config('pixelfed.version'), '0.10.8', 'ge')) {
            $this->error('Please update to version 0.10.8 or newer.');
            exit;
        }

        $classes = Filter::classes();

        Media::whereNotNull('filter_class')
            ->chunk(50, function($filters) use($classes) {
                foreach($filters as $filter) {
                    $match = $filter->filter_class ? in_array($filter->filter_class, $classes) : true;
                    if(!$match) {
                        $filter->filter_class = null;
                        $filter->filter_name = null;
                        $filter->save();
                    }
                }
        });
    }
}
