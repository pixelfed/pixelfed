@extends('admin.partial.template-full')

@section('section')
  <div class="title">
    <h3 class="font-weight-bold">Report #<span class="reportid" data-id="{{$report->id}}">{{$report->id}}</span> - <span class="badge badge-danger">{{ucfirst($report->type)}}</span></h3>
  </div>

  <div class="card">
    <div class="card-body">
      <h5 class="card-title">Reported: <a href="{{$report->reported()->url()}}">{{$report->reported()->url()}}</a></h5>
      <h6 class="card-subtitle mb-2 text-muted">Reported by: <a href="{{$report->reporter->url()}}">{{$report->reporter->username}}</a></h6>
      <p class="card-text text-muted">
        <span class="font-weight-bold text-dark">Message: </span>
        {{$report->message ?? 'No message provided.'}}
      </p>

      @if(!$report->admin_seen)
      <a href="#" class="card-link report-action-btn font-weight-bold" data-action="ignore">Ignore</a>
      {{-- <a href="#" class="card-link font-weight-bold">Request Mod Feedback</a> --}}
      <a href="#" class="card-link report-action-btn font-weight-bold" data-action="cw">Add CW</a>
      <a href="#" class="card-link report-action-btn font-weight-bold" data-action="unlist">Unlist/Hide</a>
{{--       <a href="#" class="card-link report-action-btn font-weight-bold text-danger" data-action="delete">Delete</a>
      <a href="#" class="card-link report-action-btn font-weight-bold text-danger" data-action="shadowban">Shadowban User</a>
      <a href="#" class="card-link report-action-btn font-weight-bold text-danger" data-action="ban">Ban User</a> --}}
      @else
      <p class="font-weight-bold mb-0">Resolved {{$report->admin_seen->diffForHumans()}}</p>
      @endif
    </div>
  </div>

  <div class="accordion mt-3" id="accordianBackground">
    <div class="card">
      <div class="card-header bg-white" id="headingOne">
        <h5 class="mb-0">
          <button class="btn btn-link font-weight-bold text-dark" type="button" data-toggle="collapse" data-target="#background" aria-expanded="true" aria-controls="background">
            Background
          </button>
        </h5>
      </div>
      <div id="background" class="collapse show" aria-labelledby="headingOne">
        <div class="card-body">
          <div class="row">
            <div class="col-12 col-md-6">
              <div class="card">
                <div class="card-header bg-white font-weight-bold">
                  Reporter
                </div>
                <ul class="list-group list-group-flush">
                  <li class="list-group-item">Joined <span class="font-weight-bold">{{$report->reporter->created_at->diffForHumans()}}</span></li>
                  <li class="list-group-item">Total Reports: <span class="font-weight-bold">{{App\Report::whereProfileId($report->reporter->id)->count()}}</span></li>
                  <li class="list-group-item">Total Reported: <span class="font-weight-bold">{{App\Report::whereReportedProfileId($report->reporter->id)->count()}}</span></li>
                </ul>
              </div>
            </div>
            <div class="col-12 col-md-6">
              <div class="card">
                <div class="card-header bg-white font-weight-bold">
                  Reported
                </div>
                <ul class="list-group list-group-flush">
                  <li class="list-group-item">Joined <span class="font-weight-bold">{{$report->reportedUser->created_at->diffForHumans()}}</span></li>
                  <li class="list-group-item">Total Reports: <span class="font-weight-bold">{{App\Report::whereProfileId($report->reportedUser->id)->count()}}</span></li>
                  <li class="list-group-item">Total Reported: <span class="font-weight-bold">{{App\Report::whereReportedProfileId($report->reportedUser->id)->count()}}</span></li>
                </ul>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>

{{--   <div class="accordion mt-3" id="accordianLog">
    <div class="card">
      <div class="card-header bg-white" id="headingTwo">
        <h5 class="mb-0">
          <button class="btn btn-link font-weight-bold text-dark" type="button" data-toggle="collapse" data-target="#log" aria-expanded="true" aria-controls="log">
            Activity Log
          </button>
        </h5>
      </div>
      <div id="log" class="collapse show" aria-labelledby="headingTwo">
        <div class="card-body" style="max-height: 200px;overflow-y: scroll;">
            <div class="my-3 border-left-primary">
              <p class="pl-2"><a href="#">admin</a> ignored this report. <span class="float-right pl-2 small font-weight-bold">2m</span></p>
            </div>
            <div class="my-3 border-left-primary">
              <p class="pl-2"><a href="#">admin</a> ignored this report. <span class="float-right pl-2 small font-weight-bold">2m</span></p>
            </div>
            <div class="my-3 border-left-primary">
              <p class="pl-2"><a href="#">admin</a> ignored this report. <span class="float-right pl-2 small font-weight-bold">2m</span></p>
            </div>
        </div>
      </div>
    </div>
  </div> --}}


{{--   <div class="accordion mt-3" id="accordianComments">
    <div class="card">
      <div class="card-header bg-white" id="headingThree">
        <h5 class="mb-0">
          <button class="btn btn-link font-weight-bold text-dark" type="button" data-toggle="collapse" data-target="#comments" aria-expanded="true" aria-controls="comments">
            Comments
          </button>
        </h5>
      </div>
      <div id="comments" class="collapse show" aria-labelledby="headingThree">
        <div class="card-body"  style="max-height: 400px; overflow-y: scroll;">
          <div class="report-comment-wrapper">
            <div class="my-3 report-comment">
              <div class="card bg-primary text-white">
                <div class="card-body">
                  <a href="#" class="text-white font-weight-bold">[username]</a>: {{str_limit('Anim pariatur cliche reprehenderit, enim eiusmod high life accusamus terry richardson ad squid. 3 wolf moon officia aute, non cupidatat skateboard dolor brunch. Food truck quinoa nesciunt laborum eiusmod.', 150)}} <span class="float-right small p-2">2m</span>
                </div>
              </div>
            </div>
            <div class="my-3 report-comment">
              <div class="card bg-light">
                <div class="card-body">
                  <a href="#" class="font-weight-bold">me</a>: {{str_limit('Anim pariatur cliche reprehenderit, enim eiusmod high life accusamus terry richardson ad squid. 3 wolf moon officia aute, non cupidatat skateboard dolor brunch. Food truck quinoa nesciunt laborum eiusmod.', 150)}} <span class="float-right small p-2">2m</span>
                </div>
              </div>
            </div>
            <div class="my-3 report-comment">
              <div class="card bg-light">
                <div class="card-body">
                  <a href="#" class="font-weight-bold">me</a>: {{str_limit('Anim pariatur cliche reprehenderit, enim eiusmod high life accusamus terry richardson ad squid. 3 wolf moon officia aute, non cupidatat skateboard dolor brunch. Food truck quinoa nesciunt laborum eiusmod.', 150)}} <span class="float-right small p-2">2m</span>
                </div>
              </div>
            </div>
          </div>
        </div>
        <div class="card-footer">
          <form>
             @csrf
             <input type="hidden" name="report_id" value="{{$report->id}}">
             <input type="text" class="form-control" name="comment" placeholder="Add a comment here" autocomplete="off">
          </form>
        </div>
      </div>
    </div>
  </div> --}}
@endsection
  
@push('scripts')
<script type="text/javascript">
  
  $(document).on('click', '.report-action-btn', function(e) {
    e.preventDefault();
    let el = $(this);
    let action = el.data('action');
    console.log(action);

    axios.post(window.location.href, {
      'action': action
    })
    .then(function(res) {
      swal('Success', 'Issue updated successfully!', 'success');
      window.location.href = window.location.href;
    }).catch(function(res) {
      swal('Error', res.data.msg, 'error');
    });
  })

</script>
@endpush