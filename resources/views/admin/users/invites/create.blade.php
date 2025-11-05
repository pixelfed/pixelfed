@extends('admin.partial.template-full')

@section('section')
    <div class="title d-flex justify-content-between align-items-center">
        <span><a href="{{ route('admin.users') }}" class="btn btn-outline-secondary btn-sm font-weight-bold">Back</a></span>
        <span class="text-center">
            <h3 class="font-weight-bold mb-0">Create Invite</h3>
        </span>
        <span>&nbsp;</span>
    </div>
    <hr>
    <div class="col-12 col-md-8 offset-md-2">
        <div class="row">
            <div class="col-12">
                <form method="post">
                    @csrf
                    <div class="form-group">
                        <label class="font-weight-bold text-muted">Invite Name (only visible to admins)</label>
                        <input
                            type="text"
                            class="form-control{{ $errors->has('name') ? ' is-invalid' : '' }}"
                            name="name"
                            placeholder="Untitled Invite"
                            value="{{ old('name') }}"
                        />
                        @error('name')
                        <span class="invalid-feedback">{{ $message }}</span>
                        @enderror
                    </div>
                    <div class="form-group">
                        <label class="font-weight-bold text-muted">Invite Description (only visible to admins)</label>
                        <textarea
                            class="form-control{{ $errors->has('description') ? ' is-invalid' : '' }}"
                            rows="2"
                            name="description"
                            maxlength="1000">{{ old('description') }}</textarea>
                        @error('description')
                        <span class="invalid-feedback">{{ $message }}</span>
                        @enderror
                    </div>
                    <div class="form-group">
                        <label class="font-weight-bold text-muted">Message (shown to invitees)</label>
                        <textarea
                            class="form-control{{ $errors->has('message') ? ' is-invalid' : '' }}"
                            rows="2"
                            name="message"
                            maxlength="1000">{{ old('message', "You've been invited to join " . config('app.name')) }}</textarea>
                        @error('message')
                        <span class="invalid-feedback">{{ $message }}</span>
                        @enderror
                    </div>
                    <div class="form-group">
                        <label class="font-weight-bold text-muted">Email</label>
                        <input
                            type="email"
                            class="form-control{{ $errors->has('email') ? ' is-invalid' : '' }}"
                            name="email"
                            value="{{ old('email') }}"
                        />
                        <p class="help-text small text-muted font-weight-bold">If provided, an invitation email will be sent to this address</p>
                        @error('email')
                        <span class="invalid-feedback">{{ $message }}</span>
                        @enderror
                    </div>
                    <div class="form-group">
                        <label class="font-weight-bold text-muted">Maximum number of uses</label>
                        <input
                            type="number"
                            min="0"
                            class="form-control{{ $errors->has('max_uses') ? ' is-invalid' : '' }}"
                            name="max_uses"
                            value="{{ old('max_uses', 1) }}"
                            required
                        />
                        <p class="help-text small text-muted font-weight-bold">Use 0 for unlimited</p>
                        @error('max_uses')
                        <span class="invalid-feedback">{{ $message }}</span>
                        @enderror
                    </div>
                    <div class="form-group">
                        <label class="font-weight-bold text-muted">Expiry date in days</label>
                        <input
                            type="number"
                            min="0"
                            class="form-control{{ $errors->has('expires_in') ? ' is-invalid' : '' }}"
                            name="expires_in"
                            value="{{ old('expires_in', 0) }}"
                            required
                        />
                        <p class="help-text small text-muted font-weight-bold">Use 0 for invite to never expire</p>
                        @error('expires_in')
                        <span class="invalid-feedback">{{ $message }}</span>
                        @enderror
                    </div>
                    <div class="form-group">
                        <div class="custom-control custom-switch">
                            <input type="checkbox" class="custom-control-input" id="skip_email_verification" name="skip_email_verification" value="1" @checked(old('skip_email_verification'))}}>
                            <label class="custom-control-label" for="skip_email_verification">Skip email verification</label>
                        </div>
                        @error('skip_email_verification')
                        <span class="invalid-feedback" style="display: block;">{{ $message }}</span>
                        @enderror
                    </div>
                    <hr>
                    <p class="float-right">
                        <button type="submit" class="btn btn-primary font-weight-bold py-1">CREATE</button>
                    </p>
                </form>
            </div>
        </div>
    </div>

@endsection
