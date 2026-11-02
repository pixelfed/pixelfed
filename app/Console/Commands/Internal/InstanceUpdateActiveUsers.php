<?php

namespace App\Console\Commands\Internal;

use App\Util\Site\Nodeinfo;
use Illuminate\Console\Command;

class InstanceUpdateActiveUsers extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:instance-update-active-users';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Recalculate the monthly and half-year active user counts reported in nodeinfo and the instance API';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $counts = Nodeinfo::refreshActiveUsers();

        $this->info('Active users updated');
        $this->line("  activeMonth:    {$counts['activeMonth']}");
        $this->line("  activeHalfyear: {$counts['activeHalfyear']}");

        return self::SUCCESS;
    }
}
