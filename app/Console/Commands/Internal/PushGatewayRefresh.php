<?php

namespace App\Console\Commands\Internal;

use App\Services\NotificationAppGatewayService;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

use function Laravel\Prompts\select;

#[Signature('app:push-gateway-refresh')]
#[Description('Refresh push notification gateway support')]
class PushGatewayRefresh extends Command
{
    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Checking Push Notification support...');
        $this->line(' ');

        $currentState = NotificationAppGatewayService::enabled();

        if ($currentState) {
            $this->info('Push Notification support is active!');

            return;
        } else {
            $this->error('Push notification support is NOT active');

            $action = select(
                label: 'Do you want to force re-check?',
                options: ['Yes', 'No'],
                required: true
            );

            if ($action === 'Yes') {
                $recheck = NotificationAppGatewayService::forceSupportRecheck();
                if ($recheck) {
                    $this->info('Success! Push Notifications are now active!');

                    return;
                } else {
                    $this->error('Error, please ensure you have a valid API key.');
                    $this->line(' ');
                    $this->line('For more info, visit https://docs.pixelfed.org/running-pixelfed/push-notifications.html');
                    $this->line(' ');

                    return;
                }

                return;
            } else {
                exit;
            }

            return;
        }
    }
}
