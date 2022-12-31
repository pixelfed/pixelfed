@extends('admin.partial.template-full')

@section('section')
</div>
<div class="header bg-primary pb-3 mt-n4">
    <div class="container-fluid">
        <div class="header-body">
            <div class="row align-items-center py-4">
                <div class="col-lg-6 col-7">
                    <p class="display-1 text-white mb-0">Newsroom - Edit</p>
                    <p class="lead text-white my-0">Editing #{{$news->id}}</p>
                </div>

                <div class="col-lg-6 col-5">
                    <div class="text-right">
                        <button class="btn btn-danger px-4 mr-3 mb-1" style="font-size:13px;" id="btn-delete">Delete</button>
                        @if($news->published_at)
                        <a class="btn btn-dark px-4 mr-3 mb-1" style="font-size:13px;" href="{{$news->permalink()}}">View</a>
                        @endif
                        <button class="btn btn-success px-5 mb-1" style="font-size:13px;" onclick="saveForm()">Save</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<div class="container-fluid mt-4">
    <div class="row justify-content-center">
    	<div class="col-md-6 col-12">
    		<div>
    			<form method="post" id="editForm">
    				@csrf
    				<div class="form-group">
    					<label for="title" class="small font-weight-bold text-muted text-uppercase">Title</label>
    					<input type="text" class="form-control" id="title" name="title" value="{{$news->title}}">
    					<p class="help-text mb-0 small font-weight-bold text-lighter">We recommend titles shorter than 80 characters.</p>
    				</div>
    				<div class="form-group">
    					<label for="summary" class="small font-weight-bold text-muted text-uppercase">Summary</label>
    					<textarea class="form-control" id="summary" name="summary" rows="3">{{$news->summary}}</textarea>
    				</div>
    				<div class="form-group">
    					<label for="body" class="small font-weight-bold text-muted text-uppercase">Body</label>
    					<textarea class="form-control" id="body" name="body" rows="6">{{$news->body}}</textarea>
    				</div>
    				<div class="form-group">
    					<label for="category" class="small font-weight-bold text-muted text-uppercase">Category</label>
    					<input type="text" class="form-control" id="category" name="category" value="{{$news->category}}">
    				</div>

                    <div class="form-group">
                        <div class="custom-control custom-switch ml-5">
                            <input type="checkbox" class="custom-control-input" id="published" name="published" {{$news->published_at ? 'checked="checked"' : ''}}>
                            <label class="custom-control-label font-weight-bold text-uppercase text-muted" for="published">Published</label>
                        </div>
                    </div>
                    <div class="form-group">
                        <div class="custom-control custom-switch ml-5">
                            <input type="checkbox" class="custom-control-input" id="show_timeline" name="show_timeline" {{$news->show_timeline ? 'checked="checked"' : ''}}>
                            <label class="custom-control-label font-weight-bold text-uppercase text-muted" for="show_timeline">Show On Timelines</label>
                        </div>
                    </div>
                    <div class="form-group">
                        <div class="custom-control custom-switch ml-5">
                            <input type="checkbox" class="custom-control-input" id="auth_only" name="auth_only" {{$news->auth_only ? 'checked="checked"' : ''}}>
                            <label class="custom-control-label font-weight-bold text-uppercase text-muted" for="auth_only">Logged in users only</label>
                        </div>
                    </div>
                    <div class="form-group">
                        <div class="custom-control custom-switch ml-5">
                            <input type="checkbox" class="custom-control-input" id="show_link" name="show_link" {{$news->show_link ? 'checked="checked"' : ''}}>
                            <label class="custom-control-label font-weight-bold text-uppercase text-muted" for="show_link">Show Read More Link</label>
                        </div>
                    </div>
                </form>
    		</div>
    	</div>
    </div>
</div>
<form id="delete-form" method="post">
@method('delete')
@csrf
</form>
@endsection

@push('scripts')
<script type="text/javascript">
    function saveForm() {
        if(!window.confirm('Are you sure you want to save?')) {
            return;
        }
        document.getElementById('editForm').submit();
    }
	$('#title').on('change keyup paste',function(e) {
		let el = $(this);
		let title = el.val()
		$('#preview_title').text(title);
	});

	$('#summary').on('change keyup paste',function(e) {
		let el = $(this);
		let title = el.val()
		$('#preview_summary').text(title);
	});

	$('#btn-delete').on('click', function(e) {
		e.preventDefault();
		if(window.confirm('Are you sure you want to delete this post?') == true) {
			document.getElementById('delete-form').submit();
		}
	})

</script>
@endpush
