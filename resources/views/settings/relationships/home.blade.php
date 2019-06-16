@extends('settings.template')

@section('section')

<div class="title">
	<h3 class="font-weight-bold">Followers & Following</h3>
</div>
<hr>
@if(empty($data))
<p class="text-center lead pt-5 mt-5">You are not {{$mode == 'following' ? 'following anyone.' : 'followed by anyone.'}}</p>
@else
<ul class="nav nav-pills">
	<li class="nav-item">
		<a class="nav-link font-weight-bold {{$mode == 'followers' ? 'active' : ''}}" href="?mode=followers&page=1">Followers</a>
	</li>
	<li class="nav-item">
		<a class="nav-link font-weight-bold {{$mode == 'following' ? 'active' : ''}}" href="?mode=following&page=1">Following</a>
	</li>
</ul>
<hr>
<table class="table table-bordered">
	<thead>
		<tr>
			<th scope="col" class="pt-0 pb-1 mt-0">
				<input type="checkbox" name="check" class="form-control check-all">
			</th>
			<th scope="col">Username</th>
			<th scope="col">Relationship</th>
			<th scope="col">Action</th>
		</tr>
	</thead>
	<tbody>
		@foreach($data as $follower)
		<tr>
			<th scope="row" class="pb-0 pt-1 my-0">
				{{-- <input type="checkbox" class="form-control mr-1 check-row"> --}}
			</th>
			<td class="font-weight-bold">
				<img src="{{$follower->avatarUrl()}}" width="20px" height="20px" class="rounded-circle border mr-2">
				<span class="d-inline-block text-truncate" style="max-width: 160px;" title="{{$follower->username}}">{{$follower->username}}</span>
			</td>
			@if($mode == 'following')
			<td class="text-center">Following</td>
			<td class="text-center">
				<a class="btn btn-outline-danger btn-sm py-0 action-btn" href="#" data-id="{{$follower->id}}" data-action="unfollow">Unfollow</a>
			</td>
			@else
			<td class="text-center">Follower</td>
			<td class="text-center">
				<a class="btn btn-outline-primary btn-sm py-0 action-btn" href="#" data-id="{{$follower->id}}" data-action="mute">Mute</a>
				<a class="btn btn-outline-danger btn-sm py-0 action-btn" href="#" data-id="{{$follower->id}}" data-action="block">Block</a>
			</td>
			@endif
		</tr>
		@endforeach
	</tbody>
</table>
<div class="d-flex justify-content-center">{{$data->appends(['mode' => $mode])->links()}}</div>
@endif
@endsection

@push('scripts')
<script type="text/javascript">
	$(document).ready(() => {
		$('.action-btn').on('click', e => {
			e.preventDefault();
			let action = e.target.getAttribute('data-action');
			let id = e.target.getAttribute('data-id');

			switch(action) {
				case 'mute':
				axios.post('/i/mute', {
					type: 'user',
					item: id
				}).then(res => {
					swal(
						'Mute Successful',
						'You have successfully muted that user',
						'success'
						);
				});
				break;

				case 'block':
				axios.post('/i/block', {
					type: 'user',
					item: id
				}).then(res => {
					swal(
						'Block Successful',
						'You have successfully blocked that user',
						'success'
						);
				});
				break;

				case 'unfollow':
				axios.post('/i/follow', {
					item: id
				}).then(res => {
					swal(
						'Unfollow Successful',
						'You have successfully unfollowed that user',
						'success'
						);
				});
				break;
			}
			setTimeout(function() {
				window.location.href = window.location.href;
			}, 3000);
		});

		$('.check-all').on('click', e => {

		})
	});
</script>
@endpush