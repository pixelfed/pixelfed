@extends('settings.template')

@section('section')

  <div class="title">
    <h3 class="font-weight-bold">{{__('settings.importFromInstagram')}}</h3>
    <p class="lead">{{__('settings.step2')}}</p>
  </div>
  <hr>
  <section class="mt-5 col-md-8 offset-md-2">
    <div class="card mb-3 step-two">
      <div class="card-body text-center">
        <p class="h5 font-weight-bold">{!!__('settings.importMediaJson')!!}</p>
        <p class="text-muted">{{__('settings.step2LimitNotice')}}</p>
        <hr>
        <form enctype="multipart/form-data" class="" method="post">
          @csrf
          <input type="file" name="media">
          <button type="submit" class="mt-4 btn btn-primary btn-block font-weight-bold">{{__('settings.step2Upload')}}</button>
        </form>
      </div>
      </div>
    </div>
  </section>

@endsection