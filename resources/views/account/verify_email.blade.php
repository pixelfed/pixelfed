  @extends('layouts.app')

@section('content')
<div class="container mt-4">
  <div class="col-12 col-md-8 offset-md-2">
    @if (session('status'))
        <div class="alert alert-success">
            <p class="font-weight-bold mb-0">{{ session('status') }}</p>
        </div>
    @endif
    @if (session('error'))
        <div class="alert alert-danger">
            <p class="font-weight-bold mb-0">{{ session('error') }}</p>
        </div>
    @endif
    <div class="card shadow-none border">
      <div class="card-header font-weight-bold bg-white">{{ __('account.confirmEmail') }}</div>
      <div class="card-body">
        <p class="lead">{{ __('account.confirmEmailMessage') }}</p>
        <p class="lead">{{ __('account.yourEmail') }}<span class="font-weight-bold">{{Auth::user()->email}}</span></p>
        <p class="lead">{!! __('account.changeEmail') !!}</p>
        <p class="small">{!! __('account.contact') !!}</p>
        <hr>
        <form method="post">
          @csrf
          <button type="submit" class="btn btn-primary btn-block py-1 font-weight-bold">{{ __('account.sendConfirmEmail') }}</button>
        </form>
      </div>
    </div>
  </div>
</div>
@endsection
