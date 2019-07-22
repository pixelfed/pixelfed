@extends('admin.partial.template-full')

@section('section')
  <div class="title">
    <h3 class="font-weight-bold">Dashboard</h3>
  </div>

  <hr>

  <div class="row">
    <div class="col-md-4">
      <div class="card shadow-none border" style="min-height:125px">
        <div class="card-body">
          <p class="small text-uppercase font-weight-bold text-muted">New Messages</p>
          <p class="h2 mb-0">{{$data['contact']['count']}}</p>
        </div>
        <canvas width="100" height="10" class="sparkline mb-1" data-chart_values="{{$data['contact']['graph']}}"></canvas>
      </div>
    </div>

    <div class="col-md-4">
      <div class="card shadow-none border" style="min-height:125px">
        <div class="card-body">
          <p class="small text-uppercase font-weight-bold text-muted">Failed Jobs (24h)</p>
          <p class="h2 mb-0">{{$data['failedjobs']['count']}}</p>
        </div>
        <canvas width="100" height="10" class="sparkline mb-1" data-chart_values="{{$data['failedjobs']['graph']}}"></canvas>
      </div>
    </div>

    <div class="col-md-4">
      <div class="card shadow-none border" style="min-height:125px">
        <div class="card-body">
          <p class="small text-uppercase font-weight-bold text-muted">Reports</p>
          <p class="h2 mb-0" title="{{$data['reports']['count']}}" data-toggle="tooltip">{{$data['reports']['count']}}</p>
        </div>
        <canvas width="100" height="10" class="sparkline mb-1" data-chart_values="{{$data['reports']['graph']}}"></canvas>
      </div>
    </div>
  </div>

  <div class="row mt-4">
    <div class="col-md-4">
      <div class="card shadow-none border" style="min-height:125px">
        <div class="card-body">
          <p class="small text-uppercase font-weight-bold text-muted">Statuses</p>
          <p class="h2 mb-0" title="{{$data['statuses']['count']}}" data-toggle="tooltip">{{$data['statuses']['count']}}</p>
        </div>
        <canvas width="100" height="10" class="sparkline mb-1" data-chart_values="{{$data['statuses']['graph']}}"></canvas>
      </div>
    </div>

    <div class="col-md-4">
      <div class="card shadow-none border" style="min-height:125px">
        <div class="card-body">
          <p class="small text-uppercase font-weight-bold text-muted">Replies</p>
          <p class="h2 mb-0" title="{{$data['replies']['count']}}" data-toggle="tooltip">{{$data['replies']['count']}}</p>
        </div>
        <canvas width="100" height="10" class="sparkline mb-1" data-chart_values="{{$data['replies']['graph']}}""></canvas>
      </div>
    </div>
    <div class="col-md-4">
      <div class="card shadow-none border" style="min-height:125px">
        <div class="card-body">
          <p class="small text-uppercase font-weight-bold text-muted">Shares (Reblogs)</p>
          <p class="h2 mb-0">{{$data['shares']['count']}}</p>
        </div>
        <canvas width="100" height="10" class="sparkline mb-1" data-chart_values="{{$data['shares']['graph']}}"></canvas>
      </div>
    </div>
  </div>

  <div class="row mt-4">
    <div class="col-md-4">
      <div class="card shadow-none border" style="min-height:125px">
        <div class="card-body">
          <p class="small text-uppercase font-weight-bold text-muted">Likes</p>
          <p class="h2 mb-0">{{$data['likes']['count']}}</p>
        </div>
        <canvas width="100" height="10" class="sparkline mb-1" data-chart_values="{{$data['likes']['graph']}}"></canvas>
      </div>
    </div>

    <div class="col-md-4">
      <div class="card shadow-none border" style="min-height:125px">
        <div class="card-body">
          <p class="small text-uppercase font-weight-bold text-muted">Profiles</p>
          <p class="h2 mb-0">{{$data['profiles']['count']}}</p>
        </div>
        <canvas width="100" height="10" class="sparkline mb-1" data-chart_values="{{$data['profiles']['graph']}}"></canvas>
      </div>
    </div>

    <div class="col-md-4">
      <div class="card shadow-none border" style="min-height:125px">
        <div class="card-body">
          <p class="small text-uppercase font-weight-bold text-muted">Users</p>
          <p class="h2 mb-0">{{$data['users']['count']}}</p>
        </div>
        <canvas width="100" height="10" class="sparkline mb-1" data-chart_values="{{$data['users']['graph']}}"></canvas>
      </div>
    </div>
  </div>

  <div class="row mt-4">
    <div class="col-md-4">
      <div class="card shadow-none border" style="min-height:125px">
        <div class="card-body">
          <p class="small text-uppercase font-weight-bold text-muted">Remote Instances</p>
          <p class="h2 mb-0">{{$data['instances']['count']}}</p>
        </div>
        <canvas width="100" height="10" class="sparkline mb-1" data-chart_values="{{$data['instances']['graph']}}"></canvas>
      </div>
    </div>

    <div class="col-md-4">
      <div class="card shadow-none border" style="min-height:125px">
        <div class="card-body">
          <p class="small text-uppercase font-weight-bold text-muted">Photos Uploaded</p>
          <p class="h2 mb-0">{{$data['media']['count']}}</p>
        </div>
        <canvas width="100" height="10" class="sparkline mb-1" data-chart_values="{{$data['media']['graph']}}"></canvas>
      </div>
    </div>
    <div class="col-md-4">
      <div class="card shadow-none border" style="min-height:125px">
        <div class="card-body">
          <p class="small text-uppercase font-weight-bold text-muted">Storage Used</p>
          <p class="human-size mb-0" data-bytes="{{$data['storage']['count']}}">{{$data['storage']['count']}} bytes</p>
        </div>
        <canvas width="100" height="10" class="sparkline mb-1" data-chart_values="{{$data['storage']['graph']}}"></canvas>
      </div>
    </div>
  </div>
@endsection

@push('scripts')
<script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/2.7.3/Chart.min.js" integrity="sha256-oSgtFCCmHWRPQ/JmR4OoZ3Xke1Pw4v50uh6pLcu+fIc=" crossorigin="anonymous"></script>
  <script type="text/javascript">
    $(document).ready(function() {
      $('.human-size').each(function(d,a) {
        let el = $(a);
        let size = el.data('bytes');
        el.addClass('h2');
        el.text(filesize(size, {round: 0}));
      });
      $('.sparkline').each(function() {
          var ctx = $(this).get(0).getContext("2d");
          var myNewChart = new Chart(ctx);
          var chartData = JSON.parse($(this).attr('data-chart_values'));
          var data = {};
          var labels = [];
          var datasets = {};
          for (var i = 0; i < chartData.length; i++) {
            labels.push('');
          }
          datasets['data'] = chartData;
          datasets['backgroundColor'] = '#ffffff';
          data['labels'] = labels;
          data['datasets'] = [datasets];
          new Chart(ctx, {
            type: 'line',
            data: data,
            options: {
              responsive: true,
              legend: {
                display: false
              },
              elements: {
                line: {
                  borderColor: '#08d',
                  borderWidth: 1
                },
                point: {
                  radius: 0
                }
              },
              tooltips: {
                enabled: false
              },
              scales: {
                yAxes: [
                  {
                    display: false
                  }
                ],
                xAxes: [
                  {
                    display: false
                  }
                ]
              }
            }
          });
      })
    });
  </script>
@endpush