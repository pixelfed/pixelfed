<?php

namespace App\Jobs\ContactPipeline;

use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use App\Contact;
use App\Mail\ContactAdmin;
use Illuminate\Support\Facades\Log;
use Mail;

class ContactPipeline implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $contact;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(Contact $contact)
    {
        $this->contact = $contact;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $contact = $this->contact;
        
        // Verify contact exists
        if (!$contact) {
            Log::info("ContactPipeline: Contact no longer exists, skipping job");
            return;
        }

        $email = config('instance.email');
        if(config('instance.contact.enabled') == false || $contact->read_at !== null || filter_var($email, FILTER_VALIDATE_EMAIL) == false) {
            return;
        }

        try {
            Mail::to($email)->send(new ContactAdmin($contact));
        } catch (\Exception $e) {
            Log::warning("ContactPipeline: Failed to send contact email for contact {$contact->id}: " . $e->getMessage());
            throw $e;
        }
    }
}
