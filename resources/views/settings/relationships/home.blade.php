@extends('settings.template')

@section('section')

<div class="title">
	<h3 class="font-weight-bold">{{__('settings.relationships')}}</h3>
</div>
<hr>
<div class="form-group pb-1">
    <p>
        <a class="btn py-0 btn-link {{!request()->has('mode') || $mode == 'followers' ? 'font-weight-bold' : 'text-muted'}}" href="?mode=followers&page=1">{{__('settings.relationships.followers')}}</a>
        <a class="btn btn-link py-0  {{$mode == 'following' ? 'font-weight-bold' : 'text-muted'}}" href="?mode=following&page=1">{{__('settings.relationships.following')}}</a>
        <a class="btn btn-link py-0 {{$mode == 'hashtags' ? 'font-weight-bold' : 'text-muted'}}" href="?mode=hashtags&page=1">{{__('settings.relationships.hashtags')}}</a>
    </p>
</div>

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
			<th scope="col">{{__('settings.relationships.hashtag')}}</th>
			<th scope="col">{{__('settings.relationships.action')}}</th>
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
			<th scope="col">{{__('settings.relationships.username')}}</th>
			<th scope="col">{{__('settings.relationships.action')}}</th>
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
				<a class="btn btn-outline-danger btn-sm py-0 action-btn" href="#" data-id="{{$follower->id}}" data-action="unfollow">Unfollow</a>
			</td>
			@else
			<td class="text-center">
				<a class="btn btn-outline-primary btn-sm py-0 action-btn" href="#" data-id="{{$follower->id}}" data-action="mute">{{__('settings.relationships.mute')}}</a>
				<a class="btn btn-outline-danger btn-sm py-0 action-btn" href="#" data-id="{{$follower->id}}" data-action="block">{{__('settings.relationships.block')}}</a>
                <a class="btn btn-outline-secondary btn-sm py-0 action-btn" href="#" data-id="{{$follower->id}}" data-action="removeFollow">{{__('settings.relationships.removeFollow')}}</a>
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
						'{{__('settings.relationships.mute_successful')}}',
						'{{__('settings.relationships.you_have_successfully_muted_that_user')}}',
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
						'{{__('settings.relationships.block_successful')}}',
						'{{__('settings.relationships.you_have_successfully_blocked_that_user')}}',
						'success'
						);
				});
				break;

				case 'unfollow':
				axios.post('/api/v1/accounts/' + id + '/unfollow')
				.then(res => {
					swal(
						'{{__('settings.relationships.unfollow_successful')}}',
						'{{__('settings.relationships.you_have_successfully_unfollowed_that_user')}}',
						'success'
						);
				})
				.catch(err => {
					swal(
						'{{__('settings.error')}}',
						'{{__('settings.relationships.an_error_occured_when_attempting_to_unfollow_this_user')}}',
						'error'
						);
				});
				break;

				case 'unfollowhashtag':
				axios.post('/api/v1/tags/' + id + '/unfollow').then(res => {
					swal(
						'{{__('settings.relationships.unfollow_successful')}}',
						'{{__('settings.relationships.you_have_successfully_unfollowed_that_hashtag')}}',
						'success'
						);
				});
                break;
                case 'removeFollow':
                axios.post('/api/pixelfed/v1/accounts/' + id + '/remove_from_followers').then(res => {
                    swal(
                        '{{__('settings.relationships.unfollow_successful')}}',
                        '{{__('settings.relationships.you_have_successfully_unfollowed_that_user')}}',
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
