<?php
namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Place;
use DB;
use Illuminate\Support\Str;

class ImportCities extends Command
{
    protected $countries = [
        'AE' => 'UAE',
        'BA' => 'Bosnia-Herzegovina',
        'BO' => 'Bolivia',
        'CD' => 'Democratic Republic of Congo',
        'CG' => 'Republic of Congo',
        'FM' => 'Micronesia',
        'GB' => 'United Kingdom',
        'IR' => 'Iran',
        'KP' => 'DRPK',
        'KR' => 'South Korea',
        'LA' => 'Laos',
        'MD' => 'Moldova',
        'PS' => 'Palestine',
        'RU' => 'Russia',
        'SH' => 'Saint Helena',
        'SY' => 'Syria',
        'TW' => 'Taiwan',
        'TZ' => 'Tanzania',
        'US' => 'USA',
        'VE' => 'Venezuela',
        'XK' => 'Kosovo'
    ];
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'import:cities {chunk=1000}';
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
        if (!is_file($path)) {
            $this->error('Missing storage/app/cities.json file!');
            return;
        }
        if (Place::count() > 0) {
            DB::table('places')->truncate();
        }
        $this->info('Importing city data into database ...');
        $cities = file_get_contents($path);
        $cities = json_decode($cities);
        $cityCount = count($cities);
        $this->info("Found {$cityCount} cities to insert ...");
        $bar = $this->output->createProgressBar($cityCount);
        $bar->start();
        $buffer = [];
        $count = 0;
        foreach ($cities as $city) {
            $country = $this->codeToCountry($city->country);
            $buffer[] = ["name" => $city->name, "slug" => Str::slug($city->name), "country" => $country, "lat" => $city->lat, "long" => $city->lng];
            $count++;
            if ($count % $this->argument('chunk') == 0) {
                $this->insertBuffer($buffer, $count);
                $bar->advance(count($buffer));
                $buffer = [];
            }
        }
        $this->insertBuffer($buffer, $count);
        $bar->advance(count($buffer));
        $bar->finish();
        $this->info('Successfully imported ' . $count . ' entries.');
        return;
    }

    private function insertBuffer($buffer, $count)
    {
        DB::table('places')->insert($buffer);
    }

    private function codeToCountry($code)
    {
        $countries = $this->countries;
        if(isset($countries[$code])) {
            return $countries[$code];
        }

        $country = (new \League\ISO3166\ISO3166)->alpha2($code);
        $this->countries[$code] = $country['name'];
        return $this->countries[$code];
    }
}
