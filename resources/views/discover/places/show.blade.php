@extends('layouts.app')

@section('content')
<div class="container">	
    <div class="profile-header row mt-4">
        <div class="col-12 col-md-2">
            <div class="profile-avatar">
                <div class="bg-pixelfed mb-3 d-flex align-items-center justify-content-center display-4 font-weight-bold text-white" style="width: 132px; height: 132px; border-radius: 100%"><i class="fal fa-map-pin"></i></div>
            </div>
        </div>
        <div class="col-12 col-md-9 d-flex align-items-center">
            <div class="profile-details">
                <div class="username-bar pb-2 d-flex align-items-center">
                    <div class="ml-4">
                        <p class="h3 font-weight-lighter">{{$place->name}}, {{$place->country}}</p>
                        <p class="small text-muted">({{$place->lat}}, {{$place->long}})</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="tag-timeline">
        <div class="row">
            @if($posts->count() > 0)
                @foreach($posts as $status)
                <div class="col-4 p-1 p-lg-2">
                    <a class="card info-overlay card-md-border-0" href="{{$status['url']}}">
                        <picture class="square bg-muted">
                            @if(!Str::endsWith($status['media_attachments'][0]['preview_url'], 'no-preview.png'))
                            <source class="square-content" srcset="{{ $status['media_attachments'][0]['preview_url'] }}" />
                            @endif
                            <img class="square-content" src="{{ $status['media_attachments'][0]['url'] }}" alt="{{ $status['media_attachments'][0]['description'] ?? 'Photo was not tagged with any alt text' }}" onerror="this.src='/storage/no-preview.png';this.onerror=null;" />
                        </picture>
                    </a>
                </div>
                @endforeach
            @else
             <div class="col-12">
                    <div class="text-center border rounded py-5">
                        <div class="">
                            <i class="far fa-exclamation-triangle fa-4x text-lighter mb-3"></i>
                            <h4>No Posts Yet</h4>
                            <p class="text-muted">There are no posts tagged at this location yet.</p>
                            <a href="/discover/places" class="btn btn-outline-primary font-weight-bold rounded-pill mt-2">
                                Explore Other Places
                            </a>
                        </div>
                    </div>
                </div>
            @endif
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script type="text/javascript" src="{{ mix('js/compose.js') }}"></script>
<script type="text/javascript">App.boot();</script>
@endpush
