<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Auth;
use App\Contact;
use App\Jobs\ContactPipeline\ContactPipeline;

class ContactController extends Controller
{
    public function show(Request $request)
    {
        abort_if(!config('instance.email') && !config('instance.contact.enabled'), 404);
        return view('site.contact');
    }
    
    public function store(Request $request)
    {
        abort_if(!config('instance.contact.enabled'), 404);
        abort_if(!Auth::check(), 403);

        $this->validate($request, [
            'message' => 'required|string|min:5|max:500',
            'request_response' => 'string|max:3'
        ]);

        $message = $request->input('message');
        $request_response = $request->input('request_response') == 'on' ? true : false;
        $user = Auth::user();

        $max = config('instance.contact.max_per_day');
        $contact = Contact::whereUserId($user->id)
            ->whereDate('created_at', '>', now()->subDays($max))
            ->count();

        if ($contact >= $max) {
            return redirect()->back()->with('error', 'You have recently sent a message. Please try again later.');
        }

        $contact = new Contact;
        $contact->user_id = $user->id;
        $contact->response_requested = $request_response;
        $contact->message = $message;
        $contact->save();

        ContactPipeline::dispatchNow($contact);

        return redirect()->back()->with('status', 'Success - Your message has been sent to admins.');
    }
}
