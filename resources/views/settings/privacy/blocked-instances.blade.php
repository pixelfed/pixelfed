@extends('settings.template')

@section('section')

  <div class="title">
    <h3 class="font-weight-bold">Blocked Instances</h3>
  </div>
  <hr>
  <div class="form-group pb-1">
    <p>
      <a class="btn btn-outline-secondary py-0 font-weight-bold" href="{{route('settings.privacy.muted-users')}}">Muted Users</a>
      <a class="btn btn-outline-secondary py-0 font-weight-bold" href="{{route('settings.privacy.blocked-users')}}">Blocked Users</a>
      <a class="btn btn-outline-secondary py-0 font-weight-bold" href="{{route('settings.privacy.blocked-keywords')}}">Blocked keywords</a>
      <a class="btn btn-outline-primary py-0 font-weight-bold" href="{{route('settings.privacy.blocked-instances')}}">Blocked instances</a>
    </p>
  </div>
  @if($filters->count() > 0)
  <ul class="list-group list-group-flush">
    @foreach($filters as $filter)
    <li class="list-group-item">
      <div class="d-flex justify-content-between align-items-center font-weight-bold">
        <span>
          <span class="pr-5">{{$filter->id}}</span>
          <span>{{$filter->instance->domain}}</span>
        </span>
        <span class="btn-group">
          <form action="{{route('settings.privacy.blocked-instances.unblock')}}" method="POST">
            @csrf
            <input type="hidden" name="id" value="{{$filter->id}}">
            <button type="submit" class="btn btn-outline-secondary btn-sm px-3 font-weight-bold">Unblock</button>
          </form>
        </span>
      </div> 
    </li>
    @endforeach
  </ul>
  <div class="d-flex justify-content-center mt-3 font-weight-bold">
    {{$filters->links()}}
  </div>
    <p>
      <button type="button" class="btn btn-primary font-weight-bold px-3 blockInstance">Block Instance</button>
    </p>
  @else
    <p>You can block entire instances, this prevents users on that instance from interacting with your content. To understand how blocking works, <a href="#">read more</a> in the Help Center.</p>
    <p class="lead mb-4">You are not blocking any instances. For a list of instances banned by the admin, click <a href="#">here</a>.</p>
    <p>
      <button type="button" class="btn btn-primary font-weight-bold px-3 blockInstance">Block Instance</button>
    </p>
  @endif

@endsection

@push('scripts')

<script type="text/javascript">
  $(document).ready(function() {
      $('.blockInstance').on('click', function() {
      swal({
        text: 'Add domain to block.',
        content: "input",
        button: {
          text: "Block",
          closeModal: false,
        },
      })
      .then(val => {
        if (!val) {
          swal.stopLoading();
          swal.close();
          return;
        };
        let msg = 'The URL you have entered is not valid, please try again.'
        try {
          let validator = new URL(val);
          if(!validator.hostname || validator.protocol != 'https:') {
            swal.stopLoading();
            swal.close();
            swal('Invalid URL', msg, 'error');
            return;
          };
          axios.post(window.location.href, {
            domain: validator.href
          }).then(res => {
            window.location.href = window.location.href;
          }).catch(err => {
            swal.stopLoading();
            swal.close();
            swal('Invalid URL', msg, 'error');
            return;
          });
        } catch(e) {
          swal.stopLoading();
          swal.close();
          swal('Invalid URL', msg, 'error');
        }
      })
    });
  });
</script>

@endpush