@extends('layouts.app')

@section('content')
<div class="container mt-5">
  <div class="col-12">
    <p class="font-weight-bold text-lighter text-uppercase">{{ $page->title ?? 'Legal Notice' }}</p>
    <div class="card border shadow-none">
      <div class="card-body p-md-5 text-justify mx-md-3" style="white-space: pre-line">
        @if($page && $page->content)
        {!! $page->content !!}
        @endif
      </div>
    </div>
  </div>
</div>
@endsection
@push('meta')
<meta property="og:description" content="{{ $page->title ?? 'Legal Notice' }}">
@endpush
