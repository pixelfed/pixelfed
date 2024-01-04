@extends('settings.template')

@section('section')

<div class="d-flex justify-content-between align-items-center">
    <div class="title d-flex align-items-center" style="gap: 1rem;">
        <p class="mb-0"><a href="/settings/privacy"><i class="far fa-chevron-left fa-lg"></i></a></p>
        <h3 class="font-weight-bold mb-0">Blocked Accounts</h3>
    </div>
</div>
<hr />

@if($users->count() > 0)
<div class="list-group">
    @foreach($users as $user)
    <div class="list-group-item">
        <div class="d-flex justify-content-between align-items-center font-weight-bold">
            <span><a href="{{$user->url()}}" class="text-decoration-none text-dark"><img class="rounded-circle mr-3" src="{{$user->avatarUrl()}}" width="32px" onerror="this.onerror=null;this.src='/storage/avatars/default.jpg?v=0';">{{$user->username}}</a></span>
            <span class="btn-group">
                <form method="post">
                    @csrf
                    <input type="hidden" name="profile_id" value="{{$user->id}}">
                    <button type="submit" class="btn btn-link btn-sm px-3 font-weight-bold">Unblock</button>
                </form>
            </span>
        </div>
    </div>
    @endforeach
</div>
<div class="d-flex justify-content-center mt-3 font-weight-bold">
    {{$users->links()}}
</div>
@else
<p class="lead text-center font-weight-bold">You are not blocking any accounts.</p>
@endif

@endsection
