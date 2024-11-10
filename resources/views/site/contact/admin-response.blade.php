@extends('layouts.blank')

@section('content')
<div class="container pt-5">
    <div class="row justify-content-center">
        <div class="col-12 col-lg-8">
            <div class="card shadow-none border">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <a class="btn btn-link font-weight-bold" href="/i/web">Back to Pixelfed</a>
                    <h1 class="h4 mb-0">Contact Form Response</h1>
                    <p class="d-none d-md-block mb-0 text-muted">Ticket ID #{{$contact->id}}</p>
                </div>

                <div class="list-group list-group-flush">
                    <div class="list-group-item">
                        <div class="media">
                            <img src="{{$contact->user->profile->avatarUrl()}}" class="mr-3 rounded-circle" width="40px" height="40px" onerror="this.onerror=null;this.src='/storage/avatars/default.png?v=2'">
                            <div class="media-body">
                                <h5 class="my-0">&commat;{{$contact->user->username}}</h5>
                                <span class="text-muted">{{$contact->user->name}}</span>
                            </div>
                        </div>
                        <p class="my-2 font-weight-bold">You sent the following inquiry:</p>
                        <div class="card shadow-none border bg-light rounded mb-2">
                            <div class="card-body">
                                {{ $contact->message }}
                            </div>
                        </div>
                        <p class="small text-muted">You sent this inquiry on {{$contact->created_at->format('M d, Y')}} at {{$contact->created_at->format('h:i:s a e')}}</p>
                    </div>

                    @if($contact->response)
                    <div class="list-group-item">
                        <p class="my-2 font-weight-bold">The admin(s) responded to your inquiry:</p>
                        <div class="card shadow-none border bg-light rounded mb-2">
                            <div class="card-body">
                                {{ $contact->response }}
                            </div>
                        </div>
                        @if($contact->responded_at)
                        <p class="small text-muted">The response was created on {{$contact->responded_at->format('M d, Y')}} at {{$contact->responded_at->format('h:i:s a e')}}</p>
                        @endif
                    </div>
                    <div class="list-group-item">
                        <div class="text-center">
                            <p class="mb-0 small text-muted font-weight-bold">If you would like to respond, use the <a href="/site/contact">contact form</a>.</p>
                        </div>
                    </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
