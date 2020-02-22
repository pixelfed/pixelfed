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
    protected $signature = 'import:cities {chunk=1000}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Import Cities to database';

    /**
     * Checksum of city dataset.
     *
     */
    const CHECKSUM = 'e203c0247538788b2a91166c7cf4b95f58291d998f514e9306d315aa72b09e48bfd3ddf310bf737afc4eefadca9083b8ff796c67796c6bd8e882a3d268bd16af';

    /**
     * List of shortened countries.
     *
     * @var array
     */
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
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
        ini_set('memory_limit', '256M');
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $path = storage_path('app/cities.json');

        if (hash_file('sha512', $path) !== self::CHECKSUM) {
            $this->error('Invalid or corrupt storage/app/cities.json data.');
            $this->line('');
            $this->info('Run the following command to fix:');
            $this->info('git checkout storage/app/cities.json');
            return;
        }

        if (!is_file($path)) {
            $this->error('Missing storage/app/cities.json file!');
            return;
        }

        if (Place::count() > 0) {
            DB::table('places')->truncate();
        }

        $this->info('Importing city data into database ...');

        $cities = json_decode(file_get_contents($path));
        $cityCount = count($cities);

        $this->line('');
        $this->info("Found {$cityCount} cities to insert ...");
        $this->line('');
        
        $bar = $this->output->createProgressBar($cityCount);
        $bar->start();
        
        $buffer = [];
        $count = 0;
        
        foreach ($cities as $city) {
            $buffer[] = [
                "name" => $city->name,
                "slug" => Str::slug($city->name),
                "country" => $this->codeToCountry($city->country),
                "lat" => $city->lat,
                "long" => $city->lng
            ];

            $count++;

            if ($count % $this->argument('chunk') == 0) {
                $this->insertBuffer($buffer);
                $bar->advance(count($buffer));
                $buffer = [];
            }
        }
        $this->insertBuffer($buffer);

        $bar->advance(count($buffer));

        $bar->finish();

        $this->line('');
        $this->line('');
        $this->info('Successfully imported ' . $cityCount . ' entries!');
        $this->line('');
        return;
    }

    private function insertBuffer($buffer)
    {
        DB::table('places')->insert($buffer);
    }

    private function codeToCountry($code)
    {
        $countries = $this->countries;
        if (isset($countries[$code])) {
            return $countries[$code];
        }

        $country = (new \League\ISO3166\ISO3166)->alpha2($code);
        $this->countries[$code] = $country['name'];
        return $this->countries[$code];
    }
}
