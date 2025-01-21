<?php

namespace App\Jobs\CuratedOnboarding;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Models\CuratedRegister;
use App\User;
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
        if($aid = config_cache('instance.admin.pid')) {
            $admin = User::whereProfileId($aid)->first();
            if($admin && $admin->email) {
                Mail::to($admin->email)->send(new CuratedRegisterNotifyAdmin($cr));
            }
        }
    }
}
