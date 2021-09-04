@extends('settings.template')

@section('section')

  <div class="title">
    <h3 class="font-weight-bold">{{__('settings.updatePassword')}}</h3>
  </div>
  <hr>
  <form method="post">
    @csrf
    <div class="form-group row">
      <label for="existing" class="col-sm-3 col-form-label font-weight-bold">{{__('settings.current')}}</label>
      <div class="col-sm-9">
        <input type="password" class="form-control" name="current" placeholder="{{__('settings.addCurrent')}}">
      </div>
    </div>
    <hr>
    <div class="form-group row">
      <label for="new" class="col-sm-3 col-form-label font-weight-bold">{{__('settings.new')}}</label>
      <div class="col-sm-9">
        <input type="password" class="form-control" name="password" placeholder="{{__('settings.addNew')}}">
      </div>
    </div>
    <div class="form-group row">
      <label for="confirm" class="col-sm-3 col-form-label font-weight-bold">{{__('settings.confirm')}}</label>
      <div class="col-sm-9">
        <input type="password" class="form-control" name="password_confirmation" placeholder="{{__('settings.addConfirm')}}">
      </div>
    </div>
    <hr>
    <div class="form-group row">
      <div class="col-12 text-right">
        <button type="submit" class="btn btn-primary font-weight-bold py-0 px-5">{{__('settings.submit')}}</button>
      </div>
    </div>
  </form>

@endsection