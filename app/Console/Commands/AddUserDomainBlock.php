<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\User;
use App\Models\DefaultDomainBlock;
use App\Models\UserDomainBlock;
use function Laravel\Prompts\text;
use function Laravel\Prompts\confirm;
use function Laravel\Prompts\progress;

class AddUserDomainBlock extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:add-user-domain-block';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Apply a domain block to all users';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $domain = text('Enter domain you want to block');
        $domain = strtolower($domain);
        $domain = $this->validateDomain($domain);
        if(!$domain || empty($domain)) {
            $this->error('Invalid domain');
            return;
        }
        $this->processBlocks($domain);
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
            $this->error('Invalid domain');
            return;
        }

        $confirmed = confirm('Are you sure you want to block ' . $domain . '?');
        if(!$confirmed) {
            return;
        }

        return $domain;
    }

    protected function processBlocks($domain)
    {
        DefaultDomainBlock::updateOrCreate([
            'domain' => $domain
        ]);
        progress(
            label: 'Updating user domain blocks...',
            steps: User::lazyById(500),
            callback: fn ($user) => $this->performTask($user, $domain),
        );
    }

    protected function performTask($user, $domain)
    {
        if(!$user->profile_id || $user->delete_after) {
            return;
        }

        if($user->status != null && $user->status != 'disabled') {
            return;
        }

        UserDomainBlock::updateOrCreate([
            'profile_id' => $user->profile_id,
            'domain' => $domain
        ]);
    }
}
