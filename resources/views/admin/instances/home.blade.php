@extends('admin.partial.template-full')

@section('section')
<div class="title d-flex justify-content-between align-items-center">
	<h3 class="font-weight-bold mr-5">Instances</h3>
	<div class="btn-group btn-group-sm">
		<a class="btn btn-{{!request()->filled('filter')||request()->query('filter')=='all'?'primary':'outline-primary'}} font-weight-bold" href="?filter=all">All</a>
		{{-- <a class="btn btn-{{request()->query('filter')=='popular'?'primary':'outline-primary'}} font-weight-bold" href="?filter=popular">Popular</a> --}}
		<a class="btn btn-{{request()->query('filter')=='new'?'primary':'outline-primary'}} font-weight-bold" href="?filter=new">New</a>
		<a class="btn btn-{{request()->query('filter')=='cw'?'primary':'outline-primary'}} font-weight-bold" href="?filter=cw">CW</a>
		<a class="btn btn-{{request()->query('filter')=='banned'?'primary':'outline-primary'}} font-weight-bold" href="?filter=banned">Banned</a>
		<a class="btn btn-{{request()->query('filter')=='unlisted'?'primary':'outline-primary'}} font-weight-bold" href="?filter=unlisted">Unlisted</a>
	</div>
	<div class="">
	</div>
	<form class="" method="get">
		<input class="form-control rounded-pill" name="q" value="{{request()->query('q')}}" placeholder="Search domain">
	</form>
</div>

<hr>

<div class="row">
	<div class="col-12 col-md-8 offset-md-2">
		@if($instances->count() == 0 && !request()->has('filter') && !request()->has('q'))
		<div class="alert alert-warning mb-3">
			<p class="lead font-weight-bold mb-0">Warning</p>
			<p class="font-weight-lighter mb-0">No instances were found.</p>
		</div>
		<p class="font-weight-lighter">Do you want to scan and populate instances from Profiles and Statuses?</p>
		<p>
			<form method="post">
				@csrf
				<button type="submit" class="btn btn-primary py-1 font-weight-bold">Run Scan</button>
			</form>
		</p>
		@else
		<ul class="list-group">
			@foreach($instances as $instance)
			<li class="list-group-item">
				<div>
					<div class="d-flex justify-content-between align-items-center">
						<p class="h4 font-weight-light mb-0 text-break mr-2">
							{{$instance->domain}}
						</p>
						<p class="mb-0 text-right" style="min-width: 210px;">
							@if($instance->unlisted)
							<i class="fas fa-minus-circle text-danger" data-toggle="tooltip" title="Unlisted from timelines"></i>
							@endif
							@if($instance->auto_cw)
							<i class="fas fa-eye-slash text-danger" data-toggle="tooltip" title="CW applied to all media"></i>
							@endif
							@if($instance->banned)
							<i class="fas fa-shield-alt text-danger" data-toggle="tooltip" title="Instance is banned"></i>
							@endif
							<a class="btn btn-outline-primary btn-sm py-0 font-weight-normal ml-2" href="{{$instance->getUrl()}}">Overview</a>
							<button class="btn btn-outline-secondary btn-sm py-0 font-weight-normal btn-action"
							data-instance-id="{{$instance->id}}"
							data-instance-domain="{{$instance->domain}}"
							data-instance-unlisted="{{$instance->unlisted}}"
							data-instance-autocw="{{$instance->auto_cw}}"
							data-instance-banned="{{$instance->banned}}"
							>Actions</button>
						</p>
					</div>
				</div>
			</li>
			@endforeach
		</ul>
		<div class="d-flex justify-content-center mt-5 small">
			{{$instances->links()}}
		</div>
		@endif

		@if(request()->filled('q') && $instances->count() == 0)
		<p class="text-center lead mb-0">No results found</p>
		<p class="text-center font-weight-bold mb-0"><a href="/i/admin/instances">Go back</a></p>
		@endif
		@if(request()->filled('filter') && $instances->count() == 0)
		<p class="text-center lead mb-0">No results found</p>
		<p class="text-center font-weight-bold mb-0"><a href="/i/admin/instances">Go back</a></p>
		@endif
	</div>
</div>
@endsection

@push('scripts')
<script type="text/javascript" src="{{mix('js/components.js')}}"></script>
<script type="text/javascript">
	$(document).ready(function() {
		$('.filesize').each(function(k,v) {
			$(this).text(filesize(v.getAttribute('data-size')))
		});

		$('.btn-action').on('click', function(e) {
			let id = this.getAttribute('data-instance-id');
			let instanceDomain = this.getAttribute('data-instance-domain');
			let text = 'Domain: ' + instanceDomain;
			let unlisted = this.getAttribute('data-instance-unlisted');
			let autocw = this.getAttribute('data-instance-autocw');
			let banned = this.getAttribute('data-instance-banned');
			swal({
				title: 'Instance Actions',
				text: text,
				icon: 'warning',
				buttons: {
					unlist: {
						text: unlisted == 0 ? "Unlist" : "Re-list",
						className: "bg-warning",
						value: "unlisted",
					},
					cw: {
						text: autocw == 0 ? "CW Media" : "Remove AutoCW",
						className: "bg-warning",
						value: "autocw",
					},
					ban: {
						text: banned == 0 ? "Ban" : "Unban",
						className: "bg-danger",
						value: "ban",
					},
				},
			})
			.then((value) => {
				switch (value) {
					case "unlisted":
					swal({
						title: "Are you sure?",
						text: unlisted == 0 ?
							"Are you sure you want to unlist " + instanceDomain + " ?" :
							"Are you sure you want to remove the unlisted rule of " + instanceDomain + " ?",
						icon: "warning",
						buttons: true,
						dangerMode: true,
					})
					.then((unlist) => {
						if (unlist) {
							axios.post('/i/admin/instances/edit/' + id, {
								action: 'unlist'
							}).then((res) => {
								swal("Domain action was successful! The page will now refresh.", {
									icon: "success",
								});
								setTimeout(function() {
									window.location.href = window.location.href;
								}, 5000);
							}).catch((err) => {
								swal("Something went wrong!", "Please try again later.", "error");
							})
						} else {
								swal("Action Cancelled", "You successfully cancelled this action.", "error");
						}
					});
					break;
					case "autocw":
					swal({
						title: "Are you sure?",
						text: autocw == 0 ?
							"Are you sure you want to auto CW all media from " + instanceDomain + " ?" :
							"Are you sure you want to remove the auto cw rule for " + instanceDomain + " ?",
						icon: "warning",
						buttons: true,
						dangerMode: true,
					})
					.then((res) => {
						if (res) {
							axios.post('/i/admin/instances/edit/' + id, {
								action: 'autocw'
							}).then((res) => {
								swal("Domain action was successful! The page will now refresh.", {
									icon: "success",
								});
								setTimeout(function() {
									window.location.href = window.location.href;
								}, 5000);
							}).catch((err) => {
								swal("Something went wrong!", "Please try again later.", "error");
							})
						} else {
								swal("Action Cancelled", "You successfully cancelled this action.", "error");
						}
					});
					break;
					case "ban":
					swal({
						title: "Are you sure?",
						text: autocw == 0 ?
							"Are you sure you want to ban " + instanceDomain + " ?" :
							"Are you sure you want unban " + instanceDomain + " ?",
						icon: "warning",
						buttons: true,
						dangerMode: true,
					})
					.then((res) => {
						if (res) {
							axios.post('/i/admin/instances/edit/' + id, {
								action: 'ban'
							}).then((res) => {
								swal("Domain action was successful! The page will now refresh.", {
									icon: "success",
								});
								setTimeout(function() {
									window.location.href = window.location.href;
								}, 5000);
							}).catch((err) => {
								swal("Something went wrong!", "Please try again later.", "error");
							})
						} else {
								swal("Action Cancelled", "You successfully cancelled this action.", "error");
						}
					});
					break;

				}
			});
		})
	});
</script>
@endpush
