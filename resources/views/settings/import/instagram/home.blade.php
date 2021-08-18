@extends('settings.template')

@section('section')

  <div class="title">
    <h3 class="font-weight-bold">{{__('settings.importFromInstagram')}}</h3>
  </div>
  <hr>
  <section>
    <div class="alert alert-info">
      <p class="mb-0 font-weight-bold">{!!__('settings.downloadInstagramBackup',['url' => 'https://www.instagram.com/download/request/'])!!}</p>
    </div>
    <p class="lead font-weight-bold mb-1">{{__('settings.requirements')}}</p>
    <ul class="lead mb-4">
      <li>{{__('settings.mediaJsonFile')}}</li>      
      <li>{{__('settings.photosDirectory')}}</li>      
    </ul>
    <p class="lead font-weight-bold mb-1">{{__('settings.process')}}</p>
    <ol class="lead mb-4">
      <li>{{__('settings.uploadMediaJson')}}</li>      
      <li>{{__('settings.uploadphotosDirectory')}}</li>      
      {{-- <li>{{__('settings.confirmEachPost')}}</li> --}}
      <li>{{__('settings.importData')}}</li>      
    </ol>
    <form method="post">
      @csrf
      <p>
        <button type="submit" class="btn btn-outline-primary btn-block font-weight-bold py-1">{{__('settings.startImport')}}</button>
      </p>
    </form>
  </section>


@endsection