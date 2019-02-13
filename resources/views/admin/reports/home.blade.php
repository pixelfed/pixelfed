@extends('admin.partial.template-full')

@section('section')
<div class="title">
  <h3 class="font-weight-bold d-inline-block">Reports</h3>
  <span class="float-right">
    <a class="btn btn-{{request()->input('layout')!=='list'?'primary':'light'}} btn-sm" href="{{route('admin.reports')}}">
      <i class="fas fa-th"></i>
    </a>
    <a class="btn btn-{{request()->input('layout')=='list'?'primary':'light'}} btn-sm mr-3" href="{{route('admin.reports',['layout'=>'list', 'page' => request()->input('page') ?? 1])}}">
      <i class="fas fa-list"></i>
    </a>
    <div class="dropdown d-inline-block">
      <button class="btn btn-light btn-sm dropdown-toggle font-weight-bold" type="button" id="filterDropdown" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
        <i class="fas fa-filter"></i>
      </button>
      <div class="dropdown-menu dropdown-menu-right" aria-labelledby="filterDropdown" style="width: 300px;">
        {{-- <div class="dropdown-item">
          <form>
            <input type="hidden" name="layout" value="{{request()->input('layout')}}"></input>
            <input type="hidden" name="page" value="{{request()->input('page')}}"></input>
            <div class="input-group input-group-sm">
              <input class="form-control" name="search" placeholder="Filter by username" autocomplete="off"></input>
              <div class="input-group-append">
                <button class="btn btn-outline-primary" type="submit">Filter</button>
              </div>
            </div>
          </form>
        </div>
        <div class="dropdown-divider"></div>
        <div class="dropdown-divider"></div> --}}
        <a class="dropdown-item font-weight-light {{request()->filter=='open'?'active':''}}" href="?filter=open&layout={{request()->input('layout')}}">Open Reports Only</a>
        <a class="dropdown-item font-weight-light {{request()->filter=='closed'?'active':''}}" href="?filter=closed&layout={{request()->layout}}">Closed Reports Only</a>
        <div class="dropdown-divider"></div>
        <a class="dropdown-item font-weight-light" href="?layout={{request()->input('layout')}}">Show all</a>
      </div>
    </div>
  </span>
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
@if(request()->input('layout') == 'list')
  <table class="table w-100">
    <thead class="bg-light">
      <tr>
        <th scope="col">
            <div class="custom-control custom-checkbox table-check">
              <input type="checkbox" class="custom-control-input row-check-item" id="row-check-all">
              <label class="custom-control-label" for="row-check-all"></label>
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
        <td scope="row">
          <div class="custom-control custom-checkbox">
            <input type="checkbox" class="custom-control-input row-check-item" id="row-check-{{$report->id}}" data-resolved="{{$report->admin_seen?'true':'false'}}" data-id="{{$report->id}}">
            <label class="custom-control-label" for="row-check-{{$report->id}}"></label>
          </div>
        </td>
        <td>
          <a href="{{$report->url()}}" class="btn btn-sm btn-outline-primary my-0 py-0">
            {{$report->id}}
          </a>
          
        </td>
        <td class="font-weight-bold"><a href="{{$report->reporter->url()}}">{{$report->reporter->username}}</a></td>
        <td class="font-weight-bold">{{$report->type}}</td>
        <td class="font-weight-bold"><a href="{{$report->reported()->url()}}" title="{{$report->reported()->url()}}">{{str_limit($report->reported()->url(), 25)}}</a></td>
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
@else
  <div class="row">
    @foreach($reports as $report)
      <div class="col-md-4 col-12 mb-3">
        <div class="card bg-light">
          <div class="card-body py-3">
            <p class="font-weight-lighter h2">{{$report->type}} <a href="{{$report->url()}}" class="h6 float-right text-primary"># {{$report->id}}</a></p>
            <p class="small text-truncate mb-0"><a href="{{$report->reported()->url()}}" title="{{$report->reported()->url()}}">{{$report->reported()->url()}}</a></p>
          </div>
          <div class="card-footer py-1 d-flex align-items-center justify-content-between">
              <div class="badge badge-light">local report</div>
              @if($report->admin_seen)
              <div class="badge badge-light">closed</div>
              @else
              <div class="badge badge-danger">open</div>
              @endif
          </div>
        </div>
      </div>
    @endforeach
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

        let len = $('.row-check-item:checked').length;
        $('.bulk-count').text(len).attr('data-count', len);
      });

      $(document).on('click', '.row-check-item', function(e) {
        var el = $(this)[0];
        let len = $('.bulk-count').attr('data-count');
        console.log(el.checked);
        if(el.checked == true) {
          $('.bulk-actions').removeClass('d-none');
          len++;
          $('.bulk-count').text(len).attr('data-count', len);
        } else {
          if(len == 0) {
            $('.bulk-actions').addClass('d-none');
          } else {
            len--;
            $('.bulk-count').text(len).attr('data-count', len);   
          }
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