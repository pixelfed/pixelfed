<?php

namespace App\Console\Commands;

use App\Instance;
use App\Profile;
use App\Transformer\ActivityPub\Verb\DeleteActor;
use App\User;
use App\Util\ActivityPub\HttpSignature;
use GuzzleHttp\Client;
use GuzzleHttp\Pool;
use Illuminate\Console\Command;
use League\Fractal;
use League\Fractal\Serializer\ArraySerializer;

use function Laravel\Prompts\confirm;
use function Laravel\Prompts\search;
use function Laravel\Prompts\table;

class UserAccountDelete extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:user-account-delete';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Federate Account Deletion';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $id = search(
            label: 'Search for the account to delete by username',
            placeholder: 'john.appleseed',
            options: fn (string $value) => strlen($value) > 0
                ? User::withTrashed()->whereStatus('deleted')->where('username', 'like', "%{$value}%")->pluck('username', 'id')->all()
                : [],
        );

        $user = User::withTrashed()->find($id);

        table(
            ['Username', 'Name', 'Email', 'Created'],
            [[$user->username, $user->name, $user->email, $user->created_at]]
        );

        $confirmed = confirm(
            label: 'Do you want to federate this account deletion?',
            default: false,
            yes: 'Proceed',
            no: 'Cancel',
            hint: 'This action is irreversible'
        );

        if (! $confirmed) {
            $this->error('Aborting...');
            exit;
        }

        $profile = Profile::withTrashed()->find($user->profile_id);

        $fractal = new Fractal\Manager();
        $fractal->setSerializer(new ArraySerializer());
        $resource = new Fractal\Resource\Item($profile, new DeleteActor());
        $activity = $fractal->createData($resource)->toArray();

        $audience = Instance::whereNotNull(['shared_inbox', 'nodeinfo_last_fetched'])
            ->where('nodeinfo_last_fetched', '>', now()->subHours(12))
            ->distinct()
            ->pluck('shared_inbox');

        $payload = json_encode($activity);

        $client = new Client([
            'timeout' => 10,
        ]);

        $version = config('pixelfed.version');
        $appUrl = config('app.url');
        $userAgent = "(Pixelfed/{$version}; +{$appUrl})";

        $requests = function ($audience) use ($client, $activity, $profile, $payload, $userAgent) {
            foreach ($audience as $url) {
                $headers = HttpSignature::sign($profile, $url, $activity, [
                    'Content-Type' => 'application/ld+json; profile="https://www.w3.org/ns/activitystreams"',
                    'User-Agent' => $userAgent,
                ]);
                yield function () use ($client, $url, $headers, $payload) {
                    return $client->postAsync($url, [
                        'curl' => [
                            CURLOPT_HTTPHEADER => $headers,
                            CURLOPT_POSTFIELDS => $payload,
                            CURLOPT_HEADER => true,
                            CURLOPT_SSL_VERIFYPEER => false,
                            CURLOPT_SSL_VERIFYHOST => false,
                        ],
                    ]);
                };
            }
        };

        $pool = new Pool($client, $requests($audience), [
            'concurrency' => 50,
            'fulfilled' => function ($response, $index) {
            },
            'rejected' => function ($reason, $index) {
            },
        ]);

        $promise = $pool->promise();

        $promise->wait();
    }
}
