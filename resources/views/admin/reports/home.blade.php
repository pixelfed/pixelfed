@extends('admin.partial.template-full')

@section('section')
<div class="title mb-3">
  <h3 class="font-weight-bold d-inline-block">Reports</h3>
  <span class="float-right">
    <a class="btn btn-{{request()->input('filter')=='all'||request()->input('filter')==null?'primary':'light'}} btn-sm font-weight-bold" href="{{route('admin.reports')}}">
      ALL
    </a>
    <a class="btn btn-{{request()->input('filter')=='open'?'primary':'light'}} btn-sm font-weight-bold" href="{{route('admin.reports',['filter'=>'open', 'page' => request()->input('page') ?? 1])}}">
      OPEN
    </a>
    <a class="btn btn-{{request()->input('filter')=='closed'?'primary':'light'}} btn-sm mr-3 font-weight-bold" href="{{route('admin.reports',['filter'=>'closed', 'page' => request()->input('page') ?? 1])}}">
      CLOSED
    </a>
  </span>
</div>
@php($ai = App\AccountInterstitial::whereNotNull('appeal_requested_at')->whereNull('appeal_handled_at')->count())
@php($spam = App\AccountInterstitial::whereType('post.autospam')->whereNull('appeal_handled_at')->count())
@if($ai || $spam)
<div class="mb-4">
  <a class="btn btn-outline-primary px-5 py-3 mr-3" href="/i/admin/reports/appeals">
    <p class="font-weight-bold h4 mb-0">{{$ai}}</p>
    Appeal {{$ai == 1 ? 'Request' : 'Requests'}}
  </a>
  <a class="btn btn-outline-primary px-5 py-3" href="/i/admin/reports/autospam">
    <p class="font-weight-bold h4 mb-0">{{$spam}}</p>
    Flagged {{$ai == 1 ? 'Post' : 'Posts'}}
  </a>
</div>
@endif
  @if($reports->count())
  <div class="card shadow-none border">
    <div class="list-group list-group-flush">
      @foreach($reports as $report)
      <div class="list-group-item {{$report->admin_seen ? 'bg-light' : 'bg-white'}}">
        <div class="p-4">
          <div class="media d-flex align-items-center">
            <div class="mr-3 border rounded d-flex justify-content-center align-items-center media-avatar">
              <span class="text-lighter lead"><i class="fas fa-camera"></i></span>
            </div>
            <div class="media-body">
              <p class="mb-1 small"><span class="font-weight-bold text-uppercase">{{$report->type}}</span></p>
              @if($report->reporter && $report->status)
              <p class="mb-0 lead"><a class="font-weight-bold text-dark" href="{{$report->reporter->url()}}">{{$report->reporter->username}}</a> reported this <a href="{{$report->status->url()}}" class="font-weight-bold text-dark">post</a></p>
              @else
              <p class="mb-0 lead">
                @if(!$report->reporter)
                <span class="font-weight-bold text-dark">Deleted user</span>
                @else
                <a class="font-weight-bold text-dark" href="{{$report->reporter->url()}}">{{$report->reporter->username}}</a> 
                @endif
                 reported this 
                 @if(!$report->status)
                 <span class="font-weight-bold text-muted">deleted post</span>
                 @else
                 <a href="{{$report->status->url()}}" class="font-weight-bold text-dark">post</a> 
                 @endif

               </p>

              @endif
            </div>
            <div class="float-right">
              {{-- @if($report->admin_seen == null)
              <a class="btn btn-outline-primary btn-sm font-weight-bold py-1 px-2 mr-2" href="{{$report->url()}}/action"><i class="fas fa-check"></i></a>
              @endif
              <a class="btn btn-outline-primary btn-sm font-weight-bold py-1 px-2 mr-2" href="{{$report->url()}}/action"><i class="fas fa-cog"></i></a> --}}
              @if($report->status)
              <a class="btn btn-primary btn-sm font-weight-bold py-1 px-3" href="{{$report->url()}}">VIEW</a>
              @endif
            </div>
          </div>
        </div>
      </div>
      @endforeach
    </div>
  </div>
  @else
  <div class="card shadow-none border">
    <div class="card-body">
      <p class="mb-0 p-5 text-center font-weight-bold lead">No reports found</p>
    </div>
  </div>
  @endif

  <div class="d-flex justify-content-center mt-5 small">
    {{$reports->appends(['layout'=>request()->layout, 'filter' => request()->filter])->links()}}
  </div>
@endsection

@push('styles')
<style type="text/css">
  .custom-control-label:after, .custom-control-label:before {
    top: auto;
    bottom: auto;
  }
  .media-avatar {
    width:64px;
    height:64px;
    background:#e9ecef;
  }
</style>
@endpush

@push('scripts')

@endpush