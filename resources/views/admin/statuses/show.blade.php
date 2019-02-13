@extends('admin.partial.template-full')

@section('section')
  <div class="title">
    <h3 class="font-weight-bold">Status #{{$status->id}}</h3>
  </div>

  <hr>

  <div>
    <div class="btn-group" role="group">
      <button type="button" class="btn btn-outline-secondary">View Details</button>
      <button type="button" class="btn btn-outline-secondary">View User Stats</button>
      <button type="button" class="btn btn-outline-danger">Delete</button>
    </div>
  </div>

  @php($item = $status)
  @include('status.template')
@endsection

@push('scripts')
  <script type="text/javascript">
    $(document).ready(function() {
      $('.human-size').each(function(d,a) {
        let el = $(a);
        let size = el.data('bytes');
        el.text(filesize(size, {round: 0}));
      });

      $('.status-card .card-footer').hide();
      $('.status-card .reactions').hide();
      $('.status-card .comments').hide();
    });
  </script>
@endpush