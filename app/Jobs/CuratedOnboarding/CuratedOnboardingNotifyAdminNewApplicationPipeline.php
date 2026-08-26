<?php

namespace App\Jobs\CuratedOnboarding;

use App\Mail\CuratedRegisterNotifyAdmin;
use App\Models\CuratedRegister;
use App\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;

class CuratedOnboardingNotifyAdminNewApplicationPipeline implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public bool $deleteWhenMissingModels = true;

    protected $cr;

    /**
     * Create a new job instance.
     */
    public function __construct(CuratedRegister $cr)
    {
        $this->cr = $cr;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $cr = $this->cr;

        // Verify curated registration exists
        if (! $cr) {
            Log::info('CuratedOnboardingNotifyAdminNewApplicationPipeline: Curated registration no longer exists, skipping job');

            return;
        }

        if (! config('instance.curated_registration.notify.admin.on_verify_email.enabled')) {
            return;
        }

        config('instance.curated_registration.notify.admin.on_verify_email.bundle') ?
            $this->handleBundled() :
            $this->handleUnbundled();
    }

    protected function handleBundled()
    {
        $cr = $this->cr;
        Storage::append('conanap.json', json_encode([
            'id' => $cr->id,
            'email' => $cr->email,
            'created_at' => $cr->created_at,
            'updated_at' => $cr->updated_at,
        ]));
    }

    protected function handleUnbundled()
    {
        $cr = $this->cr;

        $adminUsernames = config('instance.curated_registration.notify.admin.on_verify_email.to_usernames');
        $ccAddresses = config('instance.curated_registration.notify.admin.on_verify_email.cc_addresses');

        $ccEmails = ! empty($ccAddresses) ? array_filter(array_map('trim', explode(',', $ccAddresses))) : [];

        // If specific admin usernames are configured, notify only those admins.
        // Otherwise, fall back to notifying all admin users.
        if (! empty($adminUsernames)) {
            $usernames = array_filter(array_map('trim', explode(',', $adminUsernames)));
            $admins = User::where('is_admin', true)->whereIn('username', $usernames)->get();
        } else {
            $admins = User::where('is_admin', true)->get();
        }

        if ($admins->isEmpty() && empty($ccEmails)) {
            return;
        }

        foreach ($admins as $admin) {
            if ($admin->email) {
                Mail::to($admin->email)->send(new CuratedRegisterNotifyAdmin($cr));
            }
        }

        if ($ccEmails) {
            Mail::to($ccEmails)->send(new CuratedRegisterNotifyAdmin($cr));
        }
    }
}
