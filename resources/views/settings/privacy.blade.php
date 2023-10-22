@extends('settings.template')

@section('section')

  <div class="title">
    <h3 class="font-weight-bold">Privacy Settings</h3>
  </div>
  <hr>
  <div class="form-group pb-1">
    <p>
      <a class="btn btn-outline-secondary py-0 font-weight-bold" href="{{route('settings.privacy.muted-users')}}">Muted Users</a>
      <a class="btn btn-outline-secondary py-0 font-weight-bold" href="{{route('settings.privacy.blocked-users')}}">Blocked Users</a>
    </p>
  </div>
  <form method="post">
    @csrf
    <input type="hidden" name="pa_mode" value="">
    <input type="hidden" name="pa_duration" value="">
    <input type="hidden" name="pa_newrequests" value="">
    <div class="form-check pb-3">
      <input class="form-check-input" type="checkbox" name="is_private" id="is_private" {{$settings->is_private ? 'checked=""':''}}>
      <label class="form-check-label font-weight-bold" for="is_private">
        {{__('Private Account')}}
      </label>
      <p class="text-muted small help-text">When your account is private, only people you approve can see your photos and videos on pixelfed. Your existing followers won't be affected.</p>
    </div>

    <div class="form-check pb-3">
      <input class="form-check-input" type="checkbox" name="crawlable" id="crawlable" {{!$settings->crawlable ? 'checked=""':''}} {{$settings->is_private ? 'disabled=""':''}}>
      <label class="form-check-label font-weight-bold" for="crawlable">
        {{__('Disable Search Engine indexing')}}
      </label>
      <p class="text-muted small help-text">When your account is visible to search engines, your information can be crawled and stored by search engines. {!! $settings->is_private ? '<strong>Not available when your account is private</strong>' : ''!!}</p>
    </div>

    <div class="form-check pb-3">
      <input class="form-check-input" type="checkbox" name="indexable" id="indexable" {{$profile->indexable ? 'checked=""':''}} {{$settings->is_private ? 'disabled=""':''}}>
      <label class="form-check-label font-weight-bold" for="indexable">
        {{__('Include public posts in search results')}}
      </label>
        <p class="text-muted small help-text">Your public posts may appear in search results on Pixelfed and Mastodon. People who have interacted with your posts may be able to search them regardless. {!! $settings->is_private ? '<strong>Not available when your account is private</strong>' : ''!!}</p>
    </div>


    <div class="form-check pb-3">
      <input class="form-check-input" type="checkbox" name="is_suggestable" id="is_suggestable" {{$settings->is_private ? 'disabled=""':''}} {{auth()->user()->profile->is_suggestable ? 'checked=""':''}}>
      <label class="form-check-label font-weight-bold" for="is_suggestable">
        {{__('Show on Directory')}}
      </label>
      <p class="text-muted small help-text">When this option is enabled, your profile is included in the Directory. Only public profiles are eligible. {!! $settings->is_private ? '<strong>Not available when your account is private</strong>' : ''!!}</p>
    </div>

    <div class="form-check pb-3">
      <input class="form-check-input" type="checkbox" id="public_dm" {{$settings->public_dm ? 'checked=""':''}} name="public_dm">
      <label class="form-check-label font-weight-bold" for="public_dm">
        {{__('Receive Direct Messages from anyone')}}
      </label>
      <p class="text-muted small help-text">If selected, you will be able to receive messages and notifications from any user even if you do not follow them.</p>
    </div>
    {{-- <div class="form-check pb-3">
      <input class="form-check-input" type="checkbox" value="" id="srs" checked="">
      <label class="form-check-label font-weight-bold" for="srs">
        {{__('Hide sensitive content from search results')}}
      </label>
      <p class="text-muted small help-text">This prevents posts with potentially sensitive content from displaying in your search results.</p>
    </div> --}}
    {{-- <div class="form-check pb-3">
      <input class="form-check-input" type="checkbox" value="" id="rbma" checked="">
      <label class="form-check-label font-weight-bold" for="rbma">
        {{__('Remove blocked and muted accounts')}}
      </label>
      <p class="text-muted small help-text">Use this to eliminate search results from accounts you've blocked or muted.</p>
    </div>
    <div class="form-check pb-3">
      <input class="form-check-input" type="checkbox" value="" id="ssp">
      <label class="form-check-label font-weight-bold" for="ssp">
        {{__('Display media that may contain sensitive content')}}
      </label>
      <p class="text-muted small help-text">Show all media, including potentially sensitive content.</p>
    </div> --}}

    <div class="form-check pb-3">
      <input class="form-check-input" type="checkbox" name="show_profile_follower_count" id="show_profile_follower_count" {{$settings->show_profile_follower_count ? 'checked=""':''}}>
      <label class="form-check-label font-weight-bold" for="show_profile_follower_count">
        {{__('Show Follower Count')}}
      </label>
      <p class="text-muted small help-text">Display follower count on profile</p>
    </div>


    <div class="form-check pb-3">
      <input class="form-check-input" type="checkbox" name="show_profile_following_count" id="show_profile_following_count" {{$settings->show_profile_following_count ? 'checked=""':''}}>
      <label class="form-check-label font-weight-bold" for="show_profile_following_count">
        {{__('Show Following Count')}}
      </label>
      <p class="text-muted small help-text">Display following count on profile</p>
    </div>

    @if(!$settings->is_private)
    <div class="form-check pb-3">
      <input class="form-check-input" type="checkbox" name="show_atom" id="show_atom" {{$settings->show_atom ? 'checked=""':''}}>
      <label class="form-check-label font-weight-bold" for="show_atom">
        {{__('Enable Atom Feed')}}
      </label>
      <p class="text-muted small help-text mb-0">Enable your profile atom feed. Only public profiles are eligible.</p>
      @if($settings->show_atom)
      <p class="small">
         <a href="{{$profile->permalink('.atom')}}" class="text-success font-weight-bold small" target="_blank">
            {{ $profile->permalink('.atom') }}
            <i class="far fa-external-link ml-1 text-muted" style="opacity: 0.5"></i>
         </a>
      </p>
      @endif
    </div>
    @endif

    <div class="form-group row mt-5 pt-5">
      <div class="col-12 text-right">
        <hr>
        <button type="submit" class="btn btn-primary font-weight-bold py-0 px-5">Submit</button>
      </div>
    </div>
  </form>
<div class="modal" tabindex="-1" role="dialog" id="pac_modal">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Confirm this action</h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <div class="modal-body p-3">
        <p class="font-weight-bold">Please select the type of private account you would like:</p>
        <div>
          <div class="form-check">
            <input class="form-check-input" type="radio" id="fm-1" name="pfType" value="keep-all" checked>
            <label class="form-check-label pb-2 font-weight-bold" for="fm-1">
              Keep existing followers
            </label>
          </div>
          <div class="form-check">
            <input class="form-check-input" type="radio" id="fm-2" name="pfType" value="mutual-only">
            <label class="form-check-label pb-2 font-weight-bold" for="fm-2">
              Only keep mutual followers
            </label>
          </div>
          <div class="form-check">
            <input class="form-check-input" type="radio" id="fm-3" name="pfType" value="only-followers">
            <label class="form-check-label pb-2 font-weight-bold" for="fm-3">
              Only followers that have followed you for atleast <select name="pfDuration">
                  <option value="60">1 hour</option>
                  <option value="1440">1 day</option>
                  <option value="20160">2 weeks</option>
                  <option value="43200">1 month</option>
                  <option value="259200">6 months</option>
                  <option value="525600">1 year</option>
                </select>
            </label>
          </div>
          <div class="form-check">
            <input class="form-check-input" type="radio" id="fm-4" name="pfType" value="remove-all">
            <label class="form-check-label font-weight-bold text-danger" for="fm-4">
              Remove existing followers
            </label>
          </div>
          {{-- <hr>
          <div class="form-check pt-3">
            <input class="form-check-input" type="checkbox" id="allowFollowRequest">
            <label class="form-check-label" for="allowFollowRequest">
              Allow new follow requests
            </label>
          </div>
          <div class="form-check">
            <input class="form-check-input" type="checkbox" name="blockNotifications" id="chk4">
            <label class="form-check-label" for="chk4">
              Block notifications from accounts I don't follow
            </label>
          </div> --}}
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-outline-secondary font-weight-bold py-0" data-dismiss="modal">Cancel</button>
        <button type="button" class="btn btn-primary font-weight-bold py-0" id="modal_confirm">Save</button>
      </div>
    </div>
  </div>
</div>
@endsection

@push('scripts')
<script type="text/javascript">
  $(document).ready(function() {

    $('#is_private').on('click', function(e) {
      let el = $(this);
      if(el[0].checked) {
        $('#pac_modal').modal('show');
      }
    });

    $('#modal_confirm').on('click', function(e) {
      $('#pac_modal').modal('hide')
      let mode = $('input[name="pfType"]:checked').val();
      let duration = $('select[name="pfDuration"]').val();
      // let newrequests = $('#allowFollowRequest')[0].checked;
      axios.post("{{route('settings.privacy.account')}}", {
        'mode': mode,
        'duration': duration,
        // 'newrequests': newrequests
      }).then(res => {
        window.location.href = window.location.href;
      }).catch(err => {
        swal('Error', 'An error occured. Please try again.', 'error');
      });
    });
  });

</script>
@endpush
