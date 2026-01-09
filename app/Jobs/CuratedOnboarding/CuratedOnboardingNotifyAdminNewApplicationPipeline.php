<?php

namespace App\Jobs\CuratedOnboarding;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Models\CuratedRegister;
use App\User;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use App\Mail\CuratedRegisterNotifyAdmin;

class CuratedOnboardingNotifyAdminNewApplicationPipeline implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $cr;

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
        if(!config('instance.curated_registration.notify.admin.on_verify_email.enabled')) {
            return;
        }

        config('instance.curated_registration.notify.admin.on_verify_email.bundle') ?
            $this->handleBundled() :
            $this->handleUnbundled();
    }

    protected function handleBundled()
    {
        $cr = $this->cr;
        
        try {
            $disk = Storage::disk('local');
            $filePath = 'conanap.json';
            
            $data = json_encode([
                'id' => $cr->id,
                'email' => $cr->email,
                'created_at' => $cr->created_at ? $cr->created_at->toIso8601String() : null,
                'updated_at' => $cr->updated_at ? $cr->updated_at->toIso8601String() : null,
            ]);
            
            // Use JSONL format (one JSON object per line)
            // Note: This file should be processed by a scheduled task to send
            // bundled notifications to all admins
            $disk->append($filePath, $data . "\n");
        } catch (\Exception $e) {
            Log::error('CuratedOnboardingNotifyAdminNewApplicationPipeline: Failed to write bundled notification', [
                'error' => $e->getMessage(),
                'curated_register_id' => $cr->id ?? null,
            ]);
        }
    }

    protected function handleUnbundled()
    {
        $cr = $this->cr;
        
        // Get all admins with email addresses and their settings
        $admins = User::whereIsAdmin(true)
            ->whereNotNull('email')
            ->where('email', '!=', '')
            ->with('settings')
            ->get();
        
        // Also check if a specific admin is configured
        $configuredAdmin = null;
        if($aid = config_cache('instance.admin.pid')) {
            $configuredAdmin = User::whereProfileId($aid)
                ->whereIsAdmin(true)
                ->whereNotNull('email')
                ->where('email', '!=', '')
                ->with('settings')
                ->first();
        }
        
        // Filter out admins who have opted out of notifications
        $adminEmails = $admins->filter(function($admin) {
            // If settings don't exist or opt_out is false/null, include the admin
            return !$admin->settings || !$admin->settings->opt_out_curated_onboarding_notifications;
        })->pluck('email')->unique()->filter()->toArray();
        
        // Add configured admin email if not already in the list and not opted out
        if($configuredAdmin && $configuredAdmin->email && !in_array($configuredAdmin->email, $adminEmails)) {
            $optedOut = $configuredAdmin->settings && $configuredAdmin->settings->opt_out_curated_onboarding_notifications;
            if(!$optedOut) {
                $adminEmails[] = $configuredAdmin->email;
            }
        }
        
        // Get CC addresses from config if set
        $ccAddresses = config('instance.curated_registration.notify.admin.on_verify_email.cc_addresses');
        $ccEmails = [];
        if($ccAddresses) {
            $ccEmails = array_filter(array_map('trim', explode(',', $ccAddresses)));
        }
        
        // Send email to all admins
        if(!empty($adminEmails)) {
            foreach($adminEmails as $email) {
                try {
                    $mail = new CuratedRegisterNotifyAdmin($cr);
                    $mailer = Mail::to($email);
                    if(!empty($ccEmails)) {
                        $mailer->cc($ccEmails);
                    }
                    $mailer->send($mail);
                } catch (\Exception $e) {
                    Log::error('CuratedOnboardingNotifyAdminNewApplicationPipeline: Failed to send notification email', [
                        'error' => $e->getMessage(),
                        'admin_email' => $email,
                        'curated_register_id' => $cr->id ?? null,
                    ]);
                }
            }
        } else {
            Log::warning('CuratedOnboardingNotifyAdminNewApplicationPipeline: No admin emails found to send notification', [
                'curated_register_id' => $cr->id ?? null,
            ]);
        }
    }
}
