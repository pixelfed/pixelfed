@extends('settings.template')

@section('section')

  <div class="title">
    <h3 class="font-weight-bold">{{__('settings.importFromInstagram')}}</h3>
    <p class="lead">{{__('settings.step1')}}</p>
  </div>
  <hr>
  <section>
    <p class="lead">{!!__('settings.step1Discription',['url'=>'https://www.instagram.com/download/request/'])!!}</p>
  </section>
  <section class="mt-5 col-md-8 offset-md-2">
    <div class="card mb-3 step-one">
      <div class="card-body text-center">
        <p class="h5 font-weight-bold">{!!__('settings.importPhotosDirectory')!!}</p>
        <p class="text-muted">{{__('settings.step1LimitNotice')}}</p>
        <hr>
        <form enctype="multipart/form-data" class="" method="post">
          @csrf
          <input type="file" name="media[]" multiple="" directory="" webkitdirectory="" mozdirectory="" accept="image/*">
          <button type="submit" class="mt-4 btn btn-primary btn-block font-weight-bold">{{__('settings.step1Upload')}}</button>
        </form>
      </div>
    </div>
  </section>

@endsection