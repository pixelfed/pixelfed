<?php

namespace App\Console\Commands\User;

use App\Models\User;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('user:table {limit=10}')]
#[Description('Display latest users')]
class UserTable extends Command
{
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
        $limit = $this->argument('limit');

        $headers = ['ID', 'Username', 'Name', 'Registered'];

        $users = User::orderByDesc('id')->take($limit)->get(['id', 'username', 'name', 'created_at'])->toArray();

        $this->table($headers, $users);
    }
}
