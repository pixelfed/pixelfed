@extends('settings.template')

@section('section')

  <div class="title">
    <h3 class="font-weight-bold">Account Settings</h3>
  </div>
  <hr>
  <form method="post">
    @csrf
    <div class="form-group row">
      <div class="col-sm-3">
        <img src="{{Auth::user()->profile->avatarUrl()}}" width="38px" height="38px" class="rounded-circle float-right">
      </div>
      <div class="col-sm-9">
        <p class="lead font-weight-bold mb-0">{{Auth::user()->username}}</p>
        <p class="mb-0"><a href="#" class="font-weight-bold change-profile-photo">Change Profile Photo</a></p>
        <p><span class="small font-weight-bold">Max avatar size: <span id="maxAvatarSize"></span></span></p>
      </div>
    </div>
    <div class="form-group row">
      <label for="name" class="col-sm-3 col-form-label font-weight-bold text-right">Name</label>
      <div class="col-sm-9">
        <input type="text" class="form-control" id="name" name="name" placeholder="Your Name" value="{{Auth::user()->profile->name}}">
      </div>
    </div>
    <div class="form-group row">
      <label for="username" class="col-sm-3 col-form-label font-weight-bold text-right">Username</label>
      <div class="col-sm-9">
        <input type="text" class="form-control" id="username" name="username" placeholder="Username" value="{{Auth::user()->profile->username}}" readonly>
      </div>
    </div>
    <div class="form-group row">
      <label for="website" class="col-sm-3 col-form-label font-weight-bold text-right">Website</label>
      <div class="col-sm-9">
        <input type="text" class="form-control" id="website" name="website" placeholder="Website" value="{{Auth::user()->profile->website}}">
      </div>
    </div>
    <div class="form-group row">
      <label for="bio" class="col-sm-3 col-form-label font-weight-bold text-right">Bio</label>
      <div class="col-sm-9">
        <textarea class="form-control" id="bio" name="bio" placeholder="Add a bio here" rows="2" data-max-length="{{config('pixelfed.max_bio_length')}}">{{Auth::user()->profile->bio}}</textarea>
        <p class="form-text">
          <span class="bio-counter float-right small text-muted">0/{{config('pixelfed.max_bio_length')}}</span>
        </p>
      </div>
    </div>
    <div class="pt-5">
      <p class="font-weight-bold text-muted text-center">Private Information</p>
    </div>
    <div class="form-group row">
      <label for="email" class="col-sm-3 col-form-label font-weight-bold text-right">Email</label>
      <div class="col-sm-9">
        <input type="email" class="form-control" id="email" name="email" placeholder="Email Address" value="{{Auth::user()->email}}">
        <p class="help-text small text-muted font-weight-bold">
          @if(Auth::user()->email_verified_at)
          <span class="text-success">Verified</span> {{Auth::user()->email_verified_at->diffForHumans()}}
          @else
          <span class="text-danger">Unverified</span> You need to <a href="/i/verify-email">verify your email</a>.
          @endif
        </p>
      </div>
    </div>
    <div class="pt-5">
      <p class="font-weight-bold text-muted text-center">Storage Usage</p>
    </div>
    <div class="form-group row">
      <label for="email" class="col-sm-3 col-form-label font-weight-bold text-right">Storage Used</label>
      <div class="col-sm-9">
        <div class="progress mt-2">
          <div class="progress-bar" role="progressbar" style="width: {{$storage['percentUsed']}}%"  aria-valuenow="{{$storage['percentUsed']}}" aria-valuemin="0" aria-valuemax="100"></div>
        </div>
        <div class="help-text">
          <span class="small text-muted">
            {{$storage['percentUsed']}}% used
          </span>
          <span class="small text-muted float-right">
            {{$storage['usedPretty']}} / {{$storage['limitPretty']}}
          </span>
        </div>
      </div>
    </div>
    <hr>
    <div class="form-group row">
      <div class="col-12 d-flex align-items-center justify-content-between">
        <a class="font-weight-bold" href="{{route('settings.remove.temporary')}}">Temporarily Disable Account</a>
        <button type="submit" class="btn btn-primary font-weight-bold float-right">Submit</button>
      </div>
    </div>
  </form>

@endsection

@push('scripts')
<script type="text/javascript">

  $(document).on('click', '.modal-close', function(e) {
    swal.close();
  });

  $('#bio').on('change keyup paste', function(e) {
    let el = $(this);
    let len = el.val().length;
    let limit = el.data('max-length');

    if(len > 100) {
      el.attr('rows', '4');
    }

    let val = len + ' / ' + limit;

    if(len > limit) {
      let diff = len - limit;
      val = '<span class="text-danger">-' + diff + '</span> / ' + limit;
    }

    $('.bio-counter').html(val);
  });

  $('#maxAvatarSize').text(filesize({{config('pixelfed.max_avatar_size') * 1024}}, {round: 0}));

  $(document).on('click', '.change-profile-photo', function(e) {
    e.preventDefault();
    swal({
      title: 'Upload Photo',
      content: {
        element: 'input',
        attributes: {
          placeholder: 'Upload your photo.',
          type: 'file',
          name: 'photoUpload',
          id: 'photoUploadInput'
        }
      },
      buttons: {
        confirm: {
          text: 'Upload'
        }
      }
    }).then((res) => {
      if(!res) {
        return;
      }
      const input = $('#photoUploadInput')[0];
      const photo = input.files[0];
      const form = new FormData();
      form.append("upload", photo);

      axios.post('/api/v1/avatar/update', form, {
          headers: {
            'Content-Type': 'multipart/form-data'
          }
      }).then((res) => {
        swal('Success', 'Your photo has been successfully updated! It may take a few minutes to update across the site.', 'success');
      }).catch((res) => {
        let msg = res.response.data.errors.upload[0];
        swal('Something went wrong', msg, 'error');
      });
    });
  });
</script>
@endpush
