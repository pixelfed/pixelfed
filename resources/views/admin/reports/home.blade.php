@extends('admin.partial.template')

@section('section')
  <div class="title">
    <h3 class="font-weight-bold">Reports</h3>
  </div>

  <hr>

  <table class="table">
    <thead class="thead-dark">
      <tr>
        <th scope="col">#</th>
        <th scope="col">Reporter</th>
        <th scope="col">Type</th>
        <th scope="col">Reported</th>
        <th scope="col">Status</th>
        <th scope="col">Created</th>
      </tr>
    </thead>
    <tbody>
      @foreach($reports as $report)
      <tr>
        <th scope="row">
          <a href="{{$report->url()}}">
            {{$report->id}}
          </a>
        </th>
        <td class="font-weight-bold"><a href="{{$report->reporter->url()}}">{{$report->reporter->username}}</a></td>
        <td class="font-weight-bold">{{$report->type}}</td>
        <td class="font-weight-bold"><a href="{{$report->reported()->url()}}">{{str_limit($report->reported()->url(), 25)}}</a></td>
        @if(!$report->admin_seen)
        <td><span class="text-danger font-weight-bold">Unresolved</span></td>
        @else
        <td><span class="text-success font-weight-bold">Resolved</span></td>
        @endif
        <td class="font-weight-bold">{{$report->created_at->diffForHumans(null, true, true, true)}}</td>
      </tr>
      @endforeach
    </tbody>
  </table>
  <div class="d-flex justify-content-center mt-5 small">
    {{$reports->links()}}
  </div>
@endsection

@push('scripts')
  <script type="text/javascript">
    $(document).ready(function() {
      $('.human-size').each(function(d,a) {
        let el = $(a);
        let size = el.data('bytes');
        el.text(filesize(size, {round: 0}));
      });
    });
  </script>
@endpush