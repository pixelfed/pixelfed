<?php

namespace App\Jobs\CuratedOnboarding;

use App\Mail\CuratedRegisterNotifyAdminUserResponse;
use App\Models\CuratedRegisterActivity;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;

class CuratedOnboardingNotifyAdminUserResponsePipeline implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public bool $deleteWhenMissingModels = true;

    protected $activity;

    /**
     * Create a new job instance.
     */
    public function __construct(CuratedRegisterActivity $activity)
    {
        $this->activity = $activity;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $activity = $this->activity;

        if (! config('instance.curated_registration.notify.admin.on_user_response')) {
            return;
        }

        if ($activity->admin_notified_at) {
            return;
        }

        // Same recipients as the new application email
        $adminUsernames = config('instance.curated_registration.notify.admin.on_verify_email.to_usernames');
        $ccAddresses = config('instance.curated_registration.notify.admin.on_verify_email.cc_addresses');

        $ccEmails = ! empty($ccAddresses) ? array_filter(array_map('trim', explode(',', $ccAddresses))) : [];

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
                Mail::to($admin->email)->send(new CuratedRegisterNotifyAdminUserResponse($activity));
            }
        }

        if ($ccEmails) {
            Mail::to($ccEmails)->send(new CuratedRegisterNotifyAdminUserResponse($activity));
        }

        $activity->admin_notified_at = now();
        $activity->save();
    }
}
