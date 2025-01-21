@extends('admin.partial.template-full')

@section('section')
<div class="title">
	<div class="d-flex justify-content-between align-items-center">
		<div class="font-weight-bold"># {{$message->id}}</div>
		<div class="font-weight-bold h3">Contact Form Message</div>
		<div></div>
	</div>
</div>

<hr class="mt-0">

<div class="row mb-3">
	<div class="col-12 col-md-4">
        <div class="card">
            <div class="list-group list-group-flush">
                @if($message->responded_at)
                <div class="list-group-item">
                    <div class="d-flex justify-content-between">
                        <div class="small text-muted">Admin Response Sent</div>
                        <div>
                            <span class="font-weight-bold" title="{{$message->responded_at}}" data-toggle="tooltip">
                                {{$message->responded_at->diffForHumans()}}
                            </span>
                        </div>
                    </div>
                </div>
                @endif

                <div class="list-group-item">
                    <div class="d-flex justify-content-between">
                        <div class="small text-muted">Status</div>
                        @if($message->read_at == null)
                        <div class="text-success font-weight-bold">Open</div>
                        @else
                        <div class="text-muted">Closed</div>
                        @endif
                    </div>
                </div>
                <div class="list-group-item">
                    <div class="d-flex justify-content-between">
                        <div class="small text-muted">Response Requested</div>
                        @if($message->response_requested == 1)
                        <div class="font-weight-bold">Yes</div>
                        @else
                        <div class="text-muted">No</div>
                        @endif
                    </div>
                </div>

                <div class="list-group-item">
                    <div class="d-flex justify-content-between">
                        <div class="small text-muted">Created</div>
                        <div>
                            <span class="font-weight-bold" title="{{$message->created_at}}" data-toggle="tooltip">
                                {{$message->created_at->diffForHumans()}}
                            </span>
                        </div>
                    </div>
                </div>

                @if($message->user && $message->user->last_active_at)
                <div class="list-group-item">
                    <div class="d-flex justify-content-between">
                        <div class="small text-muted">User Last Active</div>
                        <div>
                            <span class="font-weight-bold" title="{{$message->user->last_active_at}}" data-toggle="tooltip">
                                {{$message->user->last_active_at->diffForHumans()}}
                            </span>
                        </div>
                    </div>
                </div>
                @endif

                @if(!$message->read_at)
                <div class="list-group-item">
                    <button type="button" class="btn btn-outline-primary btn-block" id="markRead">Mark Read</button>
                </div>
                @endif
            </div>
        </div>
	</div>

	<div class="col-12 col-md-8">
		<div class="row">
			<div class="col-12">
				<div class="card shadow-none border">
					<div class="card-header bg-white">
						<div class="media">
							<img
                                src="{{$message->user->profile->avatarUrl()}}"
                                class="mr-3 rounded-circle"
                                width="40px"
                                height="40px"
                                onerror="this.onerror=null;this.src='/storage/avatars/default.png?v=0';">
							<div class="media-body">
								<h5 class="my-0">&commat;{{$message->user->username}}</h5>
								<span class="text-muted">{{$message->user->email}}</span>
							</div>
						</div>
					</div>
					<div class="card-body">
						<p class="text-uppercase text-muted small mb-2">Message Body</p>
						<p class="mb-0">{{$message->message}}</p>

                        <hr>
                        <p class="text-uppercase text-muted small mb-2">Admin Reply:</p>

                        @if($message->responded_at)
                        <p class="mb-0">{{$message->response}}</p>
                        @else
                        @if(config('mail.default') === 'log')
                        <div class="alert alert-danger">
                        <p class="mb-0">You need to configure your mail driver before you can send outgoing emails.</p>
                        </div>
                        @else
                        <form method="post" id="mform">
    						@csrf
    						<div class="form-group">
    							<textarea
                                    class="form-control"
                                    name="message"
                                    id="message"
                                    rows="4"
                                    style="resize: none;"
                                    maxlength="500"
                                    placeholder="Reply to &commat;{{$message->user->username}} via email ..."></textarea>
    							@if ($errors->any())
    							@foreach ($errors->all() as $error)
    							<p class="invalid-feedback mb-0" style="display:block;">
    								<strong>{{ $error }}</strong>
    							</p>
    							@endforeach
    							@endif
    						</div>
    						<div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <button type="button" class="btn btn-primary font-weight-bold submit-btn">Send</button>
    								<button type="button" class="btn btn-outline-primary font-weight-bold preview-btn">Preview</button>
                                </div>

                                <div>
        							<span class="small text-muted font-weight-bold">
        								<span id="messageCount">0</span>/500
        							</span>
                                </div>
    						</div>
    					</form>
                        @endif
                        @endif
    				</div>
                </div>
			</div>
		</div>
	</div>
</div>
@endsection

@push('scripts')
@if($message->responded_at == null)
<script type="text/javascript">
	$('#markRead').on('click', function(e) {
		e.preventDefault();

		axios.post('/i/admin/messages/mark-read', {
			id: '{{$message->id}}',
		}).then(res => {
			window.location.href = '/i/admin/messages/home';
		})	
	})

    const submitBtn = document.querySelector('.submit-btn');
    submitBtn.addEventListener('click', () => {
        const form = document.getElementById('mform');
        form.action = '/i/admin/messages/show/{{$message->id}}';
        form.submit()
    });

    const previewBtn = document.querySelector('.preview-btn');
    previewBtn.addEventListener('click', () => {
        const form = document.getElementById('mform');
        form.action = '/i/admin/messages/preview/{{$message->id}}';
        form.submit()
    });

    function countChars() {
        const input = document.getElementById('message');
        const counter = document.getElementById('messageCount');

        input.addEventListener('input', function() {
            counter.textContent = input.value.length;
        });
    }

    countChars();
</script>
@endif
@endpush
