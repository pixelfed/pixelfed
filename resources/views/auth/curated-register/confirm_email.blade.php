@extends('layouts.blank')

@section('content')
<div class="container">
    <div class="row justify-content-center align-items-center">
        <div class="col-12 col-md-7">
            <div class="logo">
                <img src="/img/pixelfed-icon-color.svg" width="40" height="40" alt="Pixelfed Logo">
                <p class="font-weight-bold mb-0">Pixelfed</p>
            </div>

            @include('auth.curated-register.partials.progress-bar', ['step' => 3])

            @if ($errors->any())
                @foreach ($errors->all() as $error)
                <div class="alert alert-danger bg-danger border-danger text-white">
                    <p class="lead font-weight-bold mb-0"><i class="far fa-exclamation-triangle mr-2"></i> {{ $error }}</p>
                </div>
                @endforeach
                <div class="mb-5"></div>
            @endif

            <h1 class="text-center">Confirm Email</h1>
            <p class="lead text-center">Please confirm your email address so we can continue processing your registration application.</p>

            <form method="post">
                @csrf
                <input type="hidden" name="sid" value={{request()->input('sid')}}>
                <input type="hidden" name="code" value={{request()->input('code')}}>
                @if(config('instance.curated_registration.captcha_enabled'))
                <div class="d-flex justify-content-center my-3">
                    {!! Captcha::display() !!}
                </div>
                @endif
                <div class="mt-3 pt-4">
                    <button class="btn btn-primary rounded-pill font-weight-bold btn-block">Confirm Email Address</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('styles')
    <link href="{{ mix('css/landing.css') }}" rel="stylesheet">
    <style type="text/css">
        .gap-1 {
            gap: 1rem;
        }

        .opacity-5 {
            opacity: .5;
        }

        .logo {
            display: flex;
            margin-top: 50px;
            align-items: center;
            justify-content: center;
            gap: 1rem;

            p {
                font-size: 30px;
            }
        }

        .action-btns {
            display: flex;
            margin: 3rem auto 1rem auto;
            flex-direction: row;
            gap: 1rem;

            form {
                flex-grow: 1;
            }
        }

        .small-links {
            display: flex;
            justify-content: center;
            flex-direction: row;
            margin-bottom: 5rem;
            gap: 1rem;

            a {
                color: rgba(255, 255, 255, 0.6);
                font-size: 12px;
            }
        }
    </style>
@endpush
