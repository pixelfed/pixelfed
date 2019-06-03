@extends('layouts.app')

@section('content')

<div class="container">
	<div class="row">
		<div class="col-12 py-5 d-flex justify-content-between align-items-center">
			<p class="h1 mb-0"><i class="far fa-circle"></i> Create Circle</p>
		</div>
		<div class="col-12 col-md-10 offset-md-1">
			<div class="card">
				<div class="card-body px-5">
					@if ($errors->any())
					<div class="alert alert-danger">
						<ul>
							@foreach ($errors->all() as $error)
							<li>{{ $error }}</li>
							@endforeach
						</ul>
					</div>
					@endif

					<form method="post">
						@csrf
						<div class="form-group row">
							<label class="col-sm-2 col-form-label font-weight-bold text-muted">Name</label>
							<div class="col-sm-10">
								<input type="text" class="form-control" placeholder="Circle Name" name="name" autocomplete="off">
							</div>
						</div>
						<div class="form-group row">
							<label class="col-sm-2 col-form-label font-weight-bold text-muted">Description</label>
							<div class="col-sm-10">
								<textarea class="form-control" placeholder="Optional description visible only to you" rows="3" name="description"></textarea>
							</div>
						</div>
						<div class="form-group row">
							<label class="col-sm-2 col-form-label font-weight-bold text-muted">Visibility</label>
							<div class="col-sm-10">
								<select class="form-control" name="scope">
									<option value="public">Public</option>
									<option value="unlisted">Unlisted</option>
									<option value="private">Followers Only</option>
									<option value="exclusive">Circle Only</option>
								</select>
								<p class="help-text font-weight-bold text-muted small">Who can view posts from this circle</p>
							</div>
						</div>
						<div class="form-group row">
							<div class="col-sm-2 font-weight-bold text-muted">BCC Mode</div>
							<div class="col-sm-10">
								<div class="form-check">
									<input class="form-check-input" type="checkbox" name="bcc">
									<label class="form-check-label"></label>
								</div>
								<p class="help-text mb-0 small text-muted">Send posts without mentioning other circle recipients.</p>
							</div>
						</div>
						<hr>
						<div class="form-group row">
							<label class="col-sm-2 col-form-label font-weight-bold text-muted">Members</label>
							<div class="col-sm-10">
								<input type="text" class="form-control" placeholder="">
							</div>
						</div>
						<hr>
						<div class="form-group row">
							<div class="col-sm-10 offset-sm-2">
								<div class="custom-control custom-switch">
									<input type="checkbox" class="custom-control-input" name="active" id="activeSwitch">
									<label class="custom-control-label font-weight-bold text-muted" for="activeSwitch">Active</label>
								</div>
							</div>
						</div>
						<div class="form-group text-right mb-0">
							<button type="submit" class="btn btn-primary btn-sm py-1 font-weight-bold">Create</button>
						</div>
					</form>
				</div>
			</div>
		</div>
	</div>
</div>

@endsection

@push('scripts')
<script type="text/javascript">
	


</script>
@endpush