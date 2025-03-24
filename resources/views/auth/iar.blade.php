@extends('layouts.blank')

@section('content')
    <div class="container">
        <div class="row min-vh-100 align-items-center justify-content-center">
            <div class="col-12 col-md-6 col-lg-5">
                <div class="text-center mb-5">
                    <img src="/img/pixelfed-icon-white.svg" width="90">
                </div>

                <div class="card shadow-sm">
                    <div class="card-body p-4">
                        <h2 class="text-center">Join Pixelfed</h2>
                        <p class="lead text-center mb-4">Enter Your Email</p>

                        <form method="POST">
                            @csrf

                            <div class="form-group">
                                <label for="email">Email address</label>
                                <input type="email"
                                       class="form-control @error('email') is-invalid @enderror"
                                       id="email"
                                       name="email"
                                       placeholder="Enter your email address here"
                                       required
                                       autocomplete="email">
                                @error('email')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            @if((bool) config_cache('captcha.enabled') && (bool) config_cache('captcha.active.register'))
                            <div class="form-group text-center">
                                {!! Captcha::display() !!}
                            </div>
                            @endif

                            <button type="submit" class="btn btn-primary btn-block">
                                Send Verification Code
                            </button>
                        </form>

                        @if ($errors->any())
                        <div class="mt-4">
                            <p class="text-center">If you need to resend the email verification, click <a href="/i/app-email-resend">here</a>.</p>
                        </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('styles')
    <style>
        :root {
            --bg-color: #111827;
            --card-bg: #1f2937;
            --text-color: #f3f4f6;
            --text-muted: #9ca3af;
            --input-bg: #374151;
            --input-border: #4b5563;
            --input-focus: #3b82f6;
            --card-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.3);
        }

        body {
            background-color: var(--bg-color);
            color: var(--text-color);
            transition: background-color 0.3s, color 0.3s;
            min-height: 100vh;
            display: flex;
            align-items: center;
        }

        .card {
            background-color: var(--card-bg);
            border: none;
            border-radius: 1rem;
            box-shadow: var(--card-shadow);
        }

        .benefits-list {
            color: var(--text-muted);
        }

        .benefits-list i {
            color: #3b82f6;
            margin-right: 0.5rem;
        }

        .form-control {
            background-color: var(--input-bg);
            border-color: var(--input-border);
            color: var(--text-color);
            border-radius: 0.5rem;
            padding: 0.75rem 1rem;
            transition: all 0.2s;
        }

        .form-control:focus {
            background-color: var(--input-bg);
            border-color: var(--input-focus);
            color: var(--text-color);
            box-shadow: 0 0 0 0.2rem rgba(59, 130, 246, 0.25);
        }

        .btn-primary {
            padding: 0.75rem 1.5rem;
            border-radius: 0.5rem;
            font-weight: 500;
            transition: transform 0.2s;
            background-color: #3b82f6;
            border-color: #3b82f6;
        }

        .btn-primary:hover {
            transform: translateY(-1px);
            background-color: #2563eb;
            border-color: #2563eb;
        }

        .form-group label {
            font-weight: 500;
            margin-bottom: 0.5rem;
        }

        @media (prefers-color-scheme: dark) {
            a {
                color: #60a5fa;
            }
            a:hover {
                color: #93c5fd;
            }
            .card {
                border: 1px solid rgba(255, 255, 255, 0.1);
            }
        }
    </style>
@endpush
