@extends('admin.partial.template')

@section('section')
  <div class="title font-weight-bold">
    <h3 class="">Reports</h3>
    <p>
      <span class="pr-3">
        <span>Open:</span>
        <span class="text-danger">{{App\Report::whereNull('admin_seen')->count()}}</span>
      </span>
      <span class="">
        <span>Closed:</span>
        <span class="text-success">{{App\Report::whereNotNull('admin_seen')->count()}}</span>
      </span>
    </p>
  </div>

  <hr>

  <div class="mb-3 bulk-actions d-none">
    <div class="d-flex justify-content-between">
      <span>
        <span class="bulk-count font-weight-bold" data-count="0">
          0
        </span>
        <span class="bulk-desc"> items selected</span>
      </span>
      <span class="d-inline-flex">
        <select class="custom-select custom-select-sm font-weight-bold bulk-action">
          <option selected disabled="">Select Bulk Action</option>
          <option value="1">Ignore</option>
          <option value="2">Add C/W</option>
          <option value="3">Unlist from timelines</option>
        </select>
        <a class="btn btn-outline-primary btn-sm ml-3 font-weight-bold apply-bulk" href="#">
          Apply
        </a>
      </span>
    </div>
  </div>

  <table class="table table-responsive">
    <thead class="bg-light">
      <tr>
        <th scope="col">
          <div class="">
            <div class="custom-control custom-checkbox table-check">
              <input type="checkbox" class="custom-control-input" id="row-check-all">
              <label class="custom-control-label" for="row-check-all"></label>
            </div>
          </div>
        </th>
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
        <td class="py-0">
          <div class="custom-control custom-checkbox">
            <input type="checkbox" class="custom-control-input row-check-item" id="row-check-{{$report->id}}" data-resolved="{{$report->admin_seen?'true':'false'}}" data-id="{{$report->id}}">
            <label class="custom-control-label" for="row-check-{{$report->id}}"></label>
          </div>
        </td>
        <td>
          <a href="{{$report->url()}}" class="btn btn-sm btn-outline-primary">
            {{$report->id}}
          </a>
          
        </td>
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

@push('styles')
<style type="text/css">
  .custom-control-label:after, .custom-control-label:before {
    top: auto;
    bottom: auto;
  }
  .table-check .custom-control-label {
    top: -11px;
  }
</style>
@endpush

@push('scripts')
  <script type="text/javascript">
    $(document).ready(function() {

      $(document).on('click', '#row-check-all', function(e) {
        let el = $(this);
        let attr = el.attr('checked');

        if (typeof attr !== typeof undefined && attr !== false) {
          $('.bulk-actions').addClass('d-none');
          $('.row-check-item[data-resolved=false]').removeAttr('checked').prop('checked', false);
          el.removeAttr('checked').prop('checked', false);
        } else {
          $('.bulk-actions').removeClass('d-none');
          el.attr('checked', '').prop('checked', true);
          $('.row-check-item[data-resolved=false]').attr('checked', '').prop('checked', true);
        }

        let len = $('.row-check-item[checked]').length;
        $('.bulk-count').text(len).attr('data-count', len);
      });

      $(document).on('click', '.row-check-item', function(e) {
        var el = $(this)[0];
        let len = $('.bulk-count').attr('data-count');
        if(el.checked == true) {
          len++;
          $('.bulk-count').text(len).attr('data-count', len);
        } else {
          len--;
          $('.bulk-count').text(len).attr('data-count', len);   
        }
        if(len == 0) {
          $('.bulk-actions').addClass('d-none');
          $('#row-check-all').prop('checked', false);
        } else {
          $('.bulk-actions').removeClass('d-none');
        }
      });

      $(document).on('click', '.apply-bulk', function(e) {
        e.preventDefault();
        let ids = $('.row-check-item:checked').map(function(i,k) {
          return $(this).attr('data-id');
        }).get();
        let action = $('.bulk-action option:selected').val();
        if(action == 'Select Bulk Action') {
          swal('Error', 'You need to select a bulk action first.', 'error');
          $('.bulk-action').focus();
          return;
        }
        axios.post('/i/admin/reports/bulk',{
          'action': action,
          'ids': ids
        }).then(function(res) {
          swal('Success', 'Bulk Update was successful!', 'success');
          window.location.href = window.location.href;
        }).catch(function(res) {
          swal('Ooops!', 'Something went wrong', 'error');
        });
      });

      $('.human-size').each(function(d,a) {
        let el = $(a);
        let size = el.data('bytes');
        el.text(filesize(size, {round: 0}));
      });
    });
  </script>
@endpush