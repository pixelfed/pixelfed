@extends('settings.template')

@section('section')

<div class="title">
	<h3 class="font-weight-bold">{{__('settings.relationships')}}</h3>
</div>
<hr>
<ul class="nav nav-pills">
	<li class="nav-item">
		<a class="nav-link font-weight-bold {{!request()->has('mode') || $mode == 'followers' ? 'active' : ''}}" href="?mode=followers&page=1">{{__('settings.followers')}}</a>
	</li>
	<li class="nav-item">
		<a class="nav-link font-weight-bold {{$mode == 'following' ? 'active' : ''}}" href="?mode=following&page=1">{{__('settings.following')}}</a>
	</li>
	<li class="nav-item">
		<a class="nav-link font-weight-bold {{$mode == 'hashtags' ? 'active' : ''}}" href="?mode=hashtags&page=1">{{__('settings.hashtags')}}</a>
	</li>
</ul>
<hr>
@if(empty($data))
<p class="text-center lead pt-5 mt-5">You are not {{$mode == 'hashtags' ? 'following any hashtags.' : ($mode == 'following' ? 'following anyone.' : 'followed by anyone.')}}</p>
@else
<div class="table-responsive">
<table class="table table-bordered table-hover">
		@if($mode == 'hashtags')
	<thead>
		<tr>
			{{-- <th scope="col" class="pt-0 pb-1 mt-0">
				<input type="checkbox" name="check" class="form-control check-all">
			</th> --}}
			<th scope="col">{{__('settings.hashtag')}}</th>
			<th scope="col">{{__('settings.action')}}</th>
		</tr>
	</thead>
	<tbody>
		@foreach($data as $hashtag)
		<tr>
			<td class="font-weight-bold">
				<a href="{{$hashtag->hashtag->url('?src=relset')}}" class="text-decoration-none text-dark">
					<p class="mb-0 pb-0">#{{$hashtag->hashtag->name}}</p>
				</a>
			</td>
			<td class="text-center">
				<a class="btn btn-outline-danger btn-sm py-0 action-btn" href="#" data-id="{{$hashtag->hashtag->name}}" data-action="unfollowhashtag">Unfollow</a>
			</td>
		</tr>
		@endforeach
		@else
	<thead>
		<tr>
			{{-- <th scope="col" class="pt-0 pb-1 mt-0">
				<input type="checkbox" name="check" class="form-control check-all">
			</th> --}}
			<th scope="col">{{__('settings.username')}}</th>
			<th scope="col">{{__('settings.action')}}</th>
		</tr>
	</thead>
	<tbody>
		@foreach($data as $follower)
		<tr>
			{{-- <th scope="row" class="pb-0 pt-1 my-0">
				<input type="checkbox" class="form-control mr-1 check-row">
			</th> --}}
			<td class="font-weight-bold">
				<a href="{{$follower->url()}}" class="text-decoration-none text-dark">
					<p class="mb-0 pb-0 text-truncate" title="{{$follower->username}}">{{$follower->username}}</p>
				</a>
			</td>
			@if($mode == 'following')
			<td class="text-center">
				<a class="btn btn-outline-danger btn-sm py-0 action-btn" href="#" data-id="{{$follower->id}}" data-action="unfollow">{{__('settings.unfollow')}}</a>
			</td>
			@else
			<td class="text-center">
				<a class="btn btn-outline-primary btn-sm py-0 action-btn" href="#" data-id="{{$follower->id}}" data-action="mute">{{__('settings.mute')}}</a>
				<a class="btn btn-outline-danger btn-sm py-0 action-btn" href="#" data-id="{{$follower->id}}" data-action="block">{{__('settings.block')}}</a>
			</td>
			@endif
		</tr>
		@endforeach
		@endif
	</tbody>
</table>
</div>
<div class="d-flex justify-content-center">{{$data->appends(['mode' => $mode])->links()}}</div>
@endif
@endsection

@push('styles')
<style type="text/css">
.table-hover tbody tr:hover {
    color: #718096;
    background-color: #F7FAFC;
}
</style>
@endpush 
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

				case 'unfollowhashtag':
				axios.post('/api/local/discover/tag/subscribe', {
					name: id
				}).then(res => {
					swal(
						'Unfollow Successful',
						'You have successfully unfollowed that hashtag',
						'success'
						);
				});
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