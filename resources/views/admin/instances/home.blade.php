@extends('admin.partial.template-full')

@section('section')
<div class="title">
	<h3 class="font-weight-bold d-inline-block">Instances</h3>
	<span class="float-right">
		<div class="dropdown">
			<button class="btn btn-light btn-sm dropdown-toggle font-weight-bold" type="button" id="filterDropdown" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
			  <i class="fas fa-filter"></i>
			</button>
			<div class="dropdown-menu dropdown-menu-right" aria-labelledby="filterDropdown">
				<a class="dropdown-item font-weight-light" href="{{route('admin.instances')}}?filter=unlisted">Show only Unlisted</a>
				<a class="dropdown-item font-weight-light" href="{{route('admin.instances')}}?filter=autocw">Show only Auto CW</a>
				<a class="dropdown-item font-weight-light" href="{{route('admin.instances')}}?filter=banned">Show only Banned</a>
				<a class="dropdown-item font-weight-light" href="{{route('admin.instances')}}">Show all</a>
				<div class="dropdown-divider"></div>
				<form class="" method="post">
					@csrf
					<button type="submit" class="btn btn-primary py-1 font-weight-bold btn-sm btn-block">Run Scan</button>
				</form>
			</div>
		</div>
	</span>
</div>

<hr>
@if($instances->count() == 0 && request()->has('filter') == false)
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
		<div class="d-flex justify-content-between align-items-center">
			<div>
				<p class="h4 font-weight-normal mb-1">
					{{$instance->domain}}
				</p>
				<p class="mb-0">
					<a class="btn btn-outline-primary btn-sm py-0 font-weight-normal" href="{{$instance->getUrl()}}">Overview</a>
					<button class="btn btn-outline-secondary btn-sm py-0 font-weight-normal btn-action mr-3" 
					data-instance-id="{{$instance->id}}" 
					data-instance-domain="{{$instance->domain}}" 
					data-instance-unlisted="{{$instance->unlisted}}"
					data-instance-autocw="{{$instance->auto_cw}}"
					data-instance-banned="{{$instance->banned}}"
					>Actions</button>
					@if($instance->unlisted)
					<i class="fas fa-minus-circle text-danger" data-toggle="tooltip" title="Unlisted from timelines"></i>
					@endif
					@if($instance->auto_cw)
					<i class="fas fa-eye-slash text-danger" data-toggle="tooltip" title="CW applied to all media"></i>
					@endif
					@if($instance->banned)
					<i class="fas fa-shield-alt text-danger" data-toggle="tooltip" title="Instance is banned"></i>
					@endif
				</p>
			</div>
			<div>
				<div class="d-inline-block pr-4">
					<p class="h4 font-weight-light text-center">{{$instance->profiles()->count()}}</p>
					<p class="mb-0 small font-weight-normal text-muted">Profiles</p>
				</div>
				<div class="d-inline-block pr-4">
					<p class="h4 font-weight-light text-center">{{$instance->statuses()->count()}}</p>
					<p class="mb-0 small font-weight-normal text-muted">Statuses</p>
				</div>
				<div class="d-inline-block pr-4">
					<p class="h4 font-weight-light text-center text-muted">{{$instance->reported()->count()}}</p>
					<p class="mb-0 small font-weight-normal text-muted">Reports</p>
				</div>
				<div class="d-inline-block">
					<p class="h4 font-weight-light text-center text-muted filesize" data-size="{{$instance->media()->sum('size')}}">0</p>
					<p class="mb-0 small font-weight-normal text-muted">Storage Used</p>
				</div>
			</div>
		</div>
	</li>
	@endforeach
</ul>
<div class="d-flex justify-content-center mt-5 small">
	{{$instances->links()}}
</div>
@endif
@endsection

@push('scripts')
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
