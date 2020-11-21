@extends('layouts.app')

@section('content')

<div class="container px-0 mt-0 mt-md-4 mb-md-5 pb-md-5">
  <div class="col-12 px-0 col-md-8 offset-md-2">
    <div class="card">
      <div class="card-header lead font-weight-bold bg-white">
        Report Sensitive Comment
      </div>
      <div class="card-body">
        <div class="row">
          <div class="col-12 col-md-10 offset-md-1 my-3">
            <form method="post" action="{{route('report.form')}}">
              @csrf
              <input type="hidden" name="report" value="sensitive"></input>
              <input type="hidden" name="type" value="{{request()->query('type')}}"></input>
              <input type="hidden" name="id" value="{{request()->query('id')}}"></input>
              <div class="form-group row">
                <label class="col-sm-3 col-form-label font-weight-bold text-right">Message</label>
                <div class="col-sm-9">
                  <textarea class="form-control" name="msg" placeholder="Add an optional message for mods/admins" rows="4"></textarea>
                </div>
              </div>
              <hr>
              <div class="form-group row">
                <div class="col-12">
                  <button type="submit" class="btn btn-primary btn-block font-weight-bold">Submit</button>
                </div>
              </div>
            </form>
          </div>

          <div class="col-12 col-md-8 offset-md-2">
            <p><a class="font-weight-bold" href="#">
              Learn more
            </a> about our reporting guidelines and policy.</p>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

@endsection