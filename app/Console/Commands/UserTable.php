<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\User;

class UserTable extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'user:table {limit=10}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Display latest users';

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
