<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Place;
use DB;
use Illuminate\Support\Str;

class ImportCities extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'import:cities';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Import Cities to database';

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
        $path = storage_path('app/cities.json');
        if(!is_file($path)) {
            $this->error('Missing storage/app/cities.json file!');
            return;
        }

        if(Place::count() > 10) {
            $this->error('Cities already imported, aborting operation...');
            return;
        }
        $this->info('Importing city data into database ...');

        $cities = file_get_contents($path);
        $cities = json_decode($cities);
        $count = count($cities);
        $this->info("Found {$count} cities to insert ...");
        $bar = $this->output->createProgressBar($count);
        $bar->start();

        foreach ($cities as $city) {
            $country = $city->country == 'XK' ? 'Kosovo' : (new \League\ISO3166\ISO3166)->alpha2($city->country)['name'];
            DB::transaction(function () use ($city, $country) {
                $place = new Place();
                $place->name = $city->name;
                $place->slug = Str::slug($city->name);
                $place->country = $country;
                $place->lat = $city->lat;
                $place->long = $city->lng;
                $place->save();
            });
            $bar->advance();
        }
        $bar->finish();
        return;
    }
}
