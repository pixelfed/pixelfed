@extends('layouts.app',['title' => 'Welcome to ' . config_cache('app.name')])

@section('content')
<div class="container mt-4">
    <div class="row justify-content-center" style="min-height: 60vh">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">Dashboard</div>

                <div class="card-body">
                    @if (session('status'))
                        <div class="alert alert-success">
                            {{ session('status') }}
                        </div>
                    @endif

                    <p class="lead mb-0">Welcome to {{config_cache('app.name')}}!</p>
                </div>
            </div>

            <a class="card mt-3 text-primary font-weight-bold text-center" href="{{route('timeline.personal')}}">
                <div class="card-body">
                    Take me to my timeline
                </div>
            </a>
        </div>
    </div>
</div>
@endsection

@push('meta')
<meta property="og:description" content="">
@endpush


@push('styles')
@endpush
