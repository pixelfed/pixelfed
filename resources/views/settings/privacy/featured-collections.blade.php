@extends('settings.template')

@section('section')
<div class="d-flex justify-content-between align-items-center">
    <div class="title d-flex align-items-center" style="gap: 1rem;">
        <p class="mb-0"><a href="/settings/privacy"><i class="far fa-chevron-left fa-lg"></i></a></p>
        <h3 class="font-weight-bold mb-0">Featured Collections</h3>
    </div>
</div>
<hr />
@if (session('status'))
<div class="alert alert-success">{{ session('status') }}</div>
@endif
<p class="text-muted small">Collections on other servers that feature your account. Removing yourself tells the collection owner to drop you, and that collection cannot add you again until you change your privacy setting.</p>
@if($collections->count() > 0)
<div class="list-group">
    @foreach($collections as $collection)
    <div class="list-group-item">
        <div class="d-flex justify-content-between align-items-center">
            <div class="text-truncate pr-3">
                <a href="{{ $collection->collection_url }}" class="text-decoration-none text-dark font-weight-bold" target="_blank" rel="noopener nofollow">
                    {{ $collection->collection_name ?? 'Untitled collection' }}
                    <i class="far fa-external-link ml-1 text-muted" style="opacity: 0.5"></i>
                </a>
                <div class="small text-muted">
                    @if($collection->actor)
                    by <a href="{{ $collection->actor->url() }}" class="text-muted" target="_blank" rel="noopener">{{ $collection->actor->username }}</a>
                    &middot;
                    @endif
                    added {{ $collection->created_at->diffForHumans() }}
                </div>
            </div>
            <span class="btn-group">
                <form method="post" onsubmit="return confirm('Remove yourself from this collection?');">
                    @csrf
                    <input type="hidden" name="id" value="{{ $collection->id }}">
                    <button type="submit" class="btn btn-link btn-sm px-3 font-weight-bold text-danger">Remove</button>
                </form>
            </span>
        </div>
    </div>
    @endforeach
</div>
<div class="d-flex justify-content-center mt-3 font-weight-bold">
    {{ $collections->links() }}
</div>
@else
<p class="lead text-center font-weight-bold">You are not featured in any collections.</p>
@endif

@endsection
