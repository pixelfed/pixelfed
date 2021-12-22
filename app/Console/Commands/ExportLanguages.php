<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class ExportLanguages extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'i18n:export';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Build and export js localization files.';

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
    	if(config('app.env') !== 'local') {
    		$this->error('This command is meant for development purposes and should only be run in a local environment');
    		return Command::FAILURE;
    	}

    	$path = base_path('resources/lang');
    	$langs = [];

    	foreach (new \DirectoryIterator($path) as $io) {
    		$name = $io->getFilename();
    		$skip = ['vendor'];
    		if($io->isDot() || in_array($name, $skip)) {
    			continue;
    		}

    		if($io->isDir()) {
    			array_push($langs, $name);
    		}
    	}

    	$exportDir = resource_path('assets/js/i18n/');
    	$exportDirAlt = public_path('_lang/');

    	foreach($langs as $lang) {
    		$strings = \Lang::get('web', [], $lang);
    		$json = json_encode($strings, JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES);
    		$path = "{$exportDir}{$lang}.json";
    		file_put_contents($path, $json);
    		$pathAlt = "{$exportDirAlt}{$lang}.json";
    		file_put_contents($pathAlt, $json);
    	}

    	return Command::SUCCESS;
    }
}
