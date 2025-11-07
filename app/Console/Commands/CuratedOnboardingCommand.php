<?php

namespace App\Console\Commands;

use App\Mail\CuratedRegisterConfirmEmail;
use App\Models\CuratedRegister;
use App\Models\CuratedRegisterActivity;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

use function Laravel\Prompts\confirm;
use function Laravel\Prompts\search;
use function Laravel\Prompts\select;
use function Laravel\Prompts\table;

class CuratedOnboardingCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:curated-onboarding';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Manage curated onboarding applications';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->line(' ');
        $this->info('   Welcome to the Curated Onboarding manager');
        $this->line(' ');

        $action = select(
            label: 'Select an action:',
            options: ['Stats', 'Edit'],
            default: 'Stats',
            hint: 'You can manage this via the admin dashboard.'
        );

        switch ($action) {
            case 'Stats':
                return $this->stats();

            case 'Edit':
                return $this->edit();

            default:
                exit;
        }
    }

    protected function stats()
    {
        $total = CuratedRegister::count();
        $approved = CuratedRegister::whereIsApproved(true)->whereIsRejected(false)->whereNotNull('email_verified_at')->count();
        $awaitingMoreInfo = CuratedRegister::whereIsAwaitingMoreInfo(true)->whereIsRejected(false)->whereIsClosed(false)->whereNotNull('email_verified_at')->count();
        $open = CuratedRegister::whereIsApproved(false)->whereIsRejected(false)->whereIsClosed(false)->whereNotNull('email_verified_at')->whereIsAwaitingMoreInfo(false)->count();
        $nonVerified = CuratedRegister::whereIsApproved(false)->whereIsRejected(false)->whereIsClosed(false)->whereNull('email_verified_at')->whereIsAwaitingMoreInfo(false)->count();
        table(
            ['Total', 'Approved', 'Open', 'Awaiting More Info', 'Unverified Emails'],
            [
                [$total, $approved, $open, $awaitingMoreInfo, $nonVerified],
            ]
        );
    }

    protected function edit()
    {
        $id = search(
            label: 'Search for a username or email',
            options: fn (string $value) => strlen($value) > 0
                ? CuratedRegister::where(function ($query) use ($value) {
                    $query->whereLike('username', "%{$value}%")
                        ->orWhereLike('email', "%{$value}%");
                })->get()
                    ->mapWithKeys(fn ($user) => [
                      $user->id => "{$user->username} ({$user->email})",
                  ])
                    ->all()
                : []
        );

        $register = CuratedRegister::findOrFail($id);
        if ($register->is_approved) {
            $status = 'Approved';
        } elseif ($register->is_rejected) {
            $status = 'Rejected';
        } elseif ($register->is_closed) {
            $status = 'Closed';
        } elseif ($register->is_awaiting_more_info) {
            $status = 'Awaiting more info';
        } elseif ($register->user_has_responded) {
            $status = 'Awaiting Admin Response';
        } else {
            $status = 'Unknown';
        }
        table(
            ['Field', 'Value'],
            [
                ['ID', $register->id],
                ['Username', $register->username],
                ['Email', $register->email],
                ['Status', $status],
                ['Created At', $register->created_at->format('Y-m-d H:i')],
                ['Updated At', $register->updated_at->format('Y-m-d H:i')],
            ]
        );
        if (in_array($status, ['Approved', 'Rejected', 'Closed'])) {
            return;
        }

        $options = ['Cancel', 'Delete'];

        if ($register->email_verified_at == null) {
            $options[] = 'Resend Email Verification';
        }

        $action = select(
            label: 'Select an action:',
            options: $options,
            default: 'Cancel',
        );

        if ($action === 'Resend Email Verification') {
            $confirmed = confirm('Are you sure you want to send another email to '.$register->email.' ?');

            if (! $confirmed) {
                $this->error('Aborting...');
                exit;
            }

            DB::transaction(function () use ($register) {
                $register->verify_code = Str::random(40);
                $register->created_at = now();
                $register->save();
                Mail::to($register->email)->send(new CuratedRegisterConfirmEmail($register));
                $this->info('Mail sent!');
            });
        } elseif ($action === 'Delete') {
            $confirmed = confirm('Are you sure you want to delete the application from '.$register->email.' ?');

            if (! $confirmed) {
                $this->error('Aborting...');
                exit;
            }

            DB::transaction(function () use ($register) {
                CuratedRegisterActivity::whereRegisterId($register->id)->delete();
                $register->delete();
                $this->info('Successfully deleted!');
            });
        } else {
            $this->info('Cancelled.');
            exit;
        }
    }
}
