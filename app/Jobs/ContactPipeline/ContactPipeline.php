<?php

namespace App\Jobs\ContactPipeline;

use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use App\Contact;
use App\Mail\ContactAdmin;
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
        if(config('instance.contact.enabled') == false || $this->read_at !== null) {
            return;
        }
        $email = config('instance.email');
        Mail::to($email)->send(new ContactAdmin($contact));
    }
}
