@extends('admin.partial.template-full')

@section('section')
<div class="title d-flex justify-content-between align-items-center">
	<h3 class="font-weight-bold">Users</h3>
	<form method="get">
		<input type="hidden" name="a" value="search">
		<div class="input-group">
			<input class="form-control" name="q" placeholder="Search usernames" value="{{request()->input('q')}}">
			<div class="input-group-append">
				<button type="submit" class="btn btn-primary">
					<i class="fas fa-search"></i>
				</button>
			</div>
		</div>
	</form>
</div>
<hr>
<div class="table-responsive">
	<table class="table">
		<thead class="bg-light">
			<tr class="text-center">
				<th scope="col" class="border-0" width="10%">
					<span>ID</span> 
				</th>
				<th scope="col" class="border-0" width="30%">
					<span>Username</span>
				</th>
				<th scope="col" class="border-0" width="30%">
					<span>Actions</span>
				</th>
			</tr>
		</thead>
		<tbody>
			@foreach($users as $user)
			@if($user->status == 'deleted')
			<tr class="font-weight-bold text-center user-row">
				<th scope="row">
					<span class="text-danger" class="text-monospace">{{$user->id}}</span>
				</th>
				<td class="text-left">
					<span title="{{$user->username}}" data-toggle="tooltip" data-placement="bottom">
						<span class="text-danger">{{$user->username}}</span>
					</span>
				</td>
				<td>
					<span class="font-weight-bold small">
						<span class="text-danger">Account Deleted</span>
					</span>
				</td>
			</tr>
			@else 
			<tr class="font-weight-bold text-center user-row">
				<th scope="row">
					<span class="text-monospace">{{$user->id}}</span>
				</th>
				<td class="text-left">
					<span title="{{$user->username}}" data-toggle="tooltip" data-placement="bottom">
						<span>{{$user->username}}</span>
						@if($user->is_admin)
						<i class="text-danger fas fa-certificate" title="Admin"></i>
						@endif
					</span>
				</td>
				<td>
					<span class="action-row font-weight-lighter">
						<a href="/{{$user->username}}" class="pr-2 text-muted small font-weight-bold" title="View Profile" data-toggle="tooltip" data-placement="bottom">
							Profile
						</a>

						<a href="/i/admin/users/show/{{$user->id}}" class="pr-2 text-muted small font-weight-bold" title="Profile Review" data-toggle="tooltip" data-placement="bottom">
							Review
						</a>

						<a href="/i/admin/users/modtools/{{$user->id}}" class="pr-2 text-muted small font-weight-bold" title="Moderation Logs" data-toggle="tooltip" data-placement="bottom">
							Mod Tools
						</a>
					</span>
				</td>
			</tr>
			@endif
			@endforeach
		</tbody>
	</table>
</div>
<div class="d-flex justify-content-center mt-5 small">
	<ul class="pagination">
		@if($pagination['prev'] !== null || $pagination['prev'] == 1)
		<li class="page-item"><a class="page-link pagination__prev" href="?page={{$pagination['prev']}}{{$pagination['query']}}" rel="prev">« Previous</a></li>
		@else
		<li class="page-item disabled"><span class="page-link" >« Previous</span></li>
		@endif
		<li class="page-item"><a class="page-link pagination__next" href="?page={{$pagination['next']}}{{$pagination['query']}}" rel="next">Next »</a></li>
	</ul>
</div>
@endsection

@push('styles')
<style type="text/css">
	.user-row:hover {
		background-color: #eff8ff;
	}
	.user-row:hover .action-row {
		display: block;
	}
	.user-row:hover .last-active {
		display: none;
	}
</style>
@endpush
@push('scripts')
<script type="text/javascript">
	$(document).ready(function() {
		$('.human-size').each(function(d,a) {
			let el = $(a);
			let size = el.data('bytes');
			el.text(filesize(size, {round: 0}));
		});
	});
</script>
@endpush
