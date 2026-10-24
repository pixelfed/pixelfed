@extends('settings.template')

@section('section')
<div class="d-flex justify-content-between align-items-center">
    <div class="title d-flex align-items-center" style="gap: 1rem;">
        <p class="mb-0"><a href="/settings/privacy"><i class="far fa-chevron-left fa-lg"></i></a></p>
        <h3 class="font-weight-bold mb-0">Quotes of your posts</h3>
    </div>
</div>
<hr />
@if (session('status'))
<div class="alert alert-success">{{ session('status') }}</div>
@endif
<p class="text-muted small">Posts on other servers that quote yours. Revoking tells the other server to stop showing your post inside that quote, and that quote cannot be approved again.</p>
@if($quotes->count() > 0)
<div class="list-group">
    @foreach($quotes as $quote)
    <div class="list-group-item">
        <div class="d-flex justify-content-between align-items-center">
            <div class="text-truncate pr-3">
                <a href="{{ $quote->quote_url }}" class="text-decoration-none text-dark font-weight-bold" target="_blank" rel="noopener nofollow">
                    @if($quote->actor)
                    Quote by {{ $quote->actor->username }}
                    @else
                    Quote post
                    @endif
                    <i class="far fa-external-link ml-1 text-muted" style="opacity: 0.5"></i>
                </a>
                <div class="small text-muted">
                    @if($quote->status)
                    of <a href="{{ $quote->status->url() }}" class="text-muted">your post</a>
                    &middot;
                    @endif
                    approved {{ $quote->created_at->diffForHumans() }}
                </div>
            </div>
            <span class="btn-group">
                <form method="post" onsubmit="return confirm('Revoke approval for this quote?');">
                    @csrf
                    <input type="hidden" name="id" value="{{ $quote->id }}">
                    <button type="submit" class="btn btn-link btn-sm px-3 font-weight-bold text-danger">Revoke</button>
                </form>
            </span>
        </div>
    </div>
    @endforeach
</div>
<div class="d-flex justify-content-center mt-3 font-weight-bold">
    {{ $quotes->links() }}
</div>
@else
<p class="lead text-center font-weight-bold">Nobody has quoted your posts yet.</p>
@endif

@endsection
