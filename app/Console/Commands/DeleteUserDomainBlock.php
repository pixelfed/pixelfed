<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\User;
use App\Models\DefaultDomainBlock;
use App\Models\UserDomainBlock;
use function Laravel\Prompts\text;
use function Laravel\Prompts\confirm;
use function Laravel\Prompts\progress;

class DeleteUserDomainBlock extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:delete-user-domain-block';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Remove a domain block for all users';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $domain = text('Enter domain you want to unblock');
        $domain = strtolower($domain);
        $domain = $this->validateDomain($domain);
        if(!$domain || empty($domain)) {
            $this->error('Invalid domain');
            return;
        }
        $this->processUnblocks($domain);
        return;
    }

    protected function validateDomain($domain)
    {
        if(!strpos($domain, '.')) {
            return;
        }

        if(str_starts_with($domain, 'https://')) {
            $domain = str_replace('https://', '', $domain);
        }

        if(str_starts_with($domain, 'http://')) {
            $domain = str_replace('http://', '', $domain);
        }

        $domain = strtolower(parse_url('https://' . $domain, PHP_URL_HOST));

        $valid = filter_var($domain, FILTER_VALIDATE_DOMAIN, FILTER_FLAG_HOSTNAME|FILTER_NULL_ON_FAILURE);
        if(!$valid) {
            return;
        }

        if($domain === config('pixelfed.domain.app')) {
            return;
        }

        $confirmed = confirm('Are you sure you want to unblock ' . $domain . '?');
        if(!$confirmed) {
            return;
        }

        return $domain;
    }

    protected function processUnblocks($domain)
    {
        DefaultDomainBlock::whereDomain($domain)->delete();
        if(!UserDomainBlock::whereDomain($domain)->count()) {
            $this->info('No results found!');
            return;
        }
        progress(
            label: 'Updating user domain blocks...',
            steps: UserDomainBlock::whereDomain($domain)->lazyById(500),
            callback: fn ($domainBlock) => $this->performTask($domainBlock),
        );
    }

    protected function performTask($domainBlock)
    {
        $domainBlock->deleteQuietly();
    }
}
