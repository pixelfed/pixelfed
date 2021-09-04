@extends('settings.template')

@section('section')

  <div class="title">
    <h3 class="font-weight-bold">{{__('settings.privacySettings')}}</h3>
  </div>
  <hr>
  <div class="form-group pb-1">
    <p>
      <a class="btn btn-outline-secondary py-0 font-weight-bold" href="{{route('settings.privacy.muted-users')}}">{{__('settings.mutedUsers')}}</a>
      <a class="btn btn-outline-secondary py-0 font-weight-bold" href="{{route('settings.privacy.blocked-users')}}">{{__('settings.blockedUsers')}}</a>
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
        {{__('settings.privateAccount')}}
      </label>
      <p class="text-muted small help-text">{{__('settings.privateAccountDiscription')}}</p>
    </div>
    <div class="form-check pb-3">
      <input class="form-check-input" type="checkbox" name="crawlable" id="crawlable" {{!$settings->crawlable ? 'checked=""':''}} {{$settings->is_private ? 'disabled=""':''}}>
      <label class="form-check-label font-weight-bold" for="crawlable">
        {{__('settings.optOutSerchEngine')}}
      </label>
      <p class="text-muted small help-text">{{__('settings.optOutSerchEngineDiscription')}}</p>
    </div>


    {{-- <div class="form-check pb-3">
      <input class="form-check-input" type="checkbox" name="show_discover" id="show_discover" {{$settings->is_private ? 'disabled=""':''}} {{$settings->show_discover ? 'checked=""':''}}>
      <label class="form-check-label font-weight-bold" for="show_discover">
        {{__('settings.visibleDiscover')}}
      </label>
      <p class="text-muted small help-text">{{__('settings.visibleDiscoverDiscription')}}</p>
    </div> --}}
    <div class="form-check pb-3">
      <input class="form-check-input" type="checkbox" id="public_dm" {{$settings->public_dm ? 'checked=""':''}} name="public_dm">
      <label class="form-check-label font-weight-bold" for="public_dm">
        {{__('settings.receiveDirectAnyone')}}
      </label>
      <p class="text-muted small help-text">{{__('settings.receiveDirectAnyoneDiscription')}}</p>
    </div>
    {{-- <div class="form-check pb-3">
      <input class="form-check-input" type="checkbox" value="" id="srs" checked="">
      <label class="form-check-label font-weight-bold" for="srs">
        {{__('settings.hideSensitiveSearch')}}
      </label>
      <p class="text-muted small help-text">{{__('settings.hideSensitiveSearchDiscription')}}</p>
    </div> --}}
    {{-- <div class="form-check pb-3">
      <input class="form-check-input" type="checkbox" value="" id="rbma" checked="">
      <label class="form-check-label font-weight-bold" for="rbma">
        {{__('settings.removeBlockdMuteRemove')}}
      </label>
      <p class="text-muted small help-text">{{__('settings.removeBlockdMuteRemoveDiscription')}}</p>
    </div>
    <div class="form-check pb-3">
      <input class="form-check-input" type="checkbox" value="" id="ssp">
      <label class="form-check-label font-weight-bold" for="ssp">
        {{__('settings.displayMediaSensitive')}}
      </label>
      <p class="text-muted small help-text">{{__('settings.displayMediaSensitiveDiscription')}}</p>
    </div> --}}

    <div class="form-check pb-3">
      <input class="form-check-input" type="checkbox" name="show_profile_follower_count" id="show_profile_follower_count" {{$settings->show_profile_follower_count ? 'checked=""':''}}>
      <label class="form-check-label font-weight-bold" for="show_profile_follower_count">
        {{__('settings.showFollowerCount')}}
      </label>
      <p class="text-muted small help-text">{{__('settings.showFollowerCountDiscription')}}</p>
    </div>


    <div class="form-check pb-3">
      <input class="form-check-input" type="checkbox" name="show_profile_following_count" id="show_profile_following_count" {{$settings->show_profile_following_count ? 'checked=""':''}}>
      <label class="form-check-label font-weight-bold" for="show_profile_following_count">
        {{__('settings.showFollowingCount')}}
      </label>
      <p class="text-muted small help-text">{{__('settings.showFollowingCountDiscription')}}</p>
    </div>

    <div class="form-group row mt-5 pt-5">
      <div class="col-12 text-right">
        <hr>
        <button type="submit" class="btn btn-primary font-weight-bold py-0 px-5">{{__('settings.submit')}}</button>
      </div>
    </div>
  </form>
<div class="modal" tabindex="-1" role="dialog" id="pac_modal">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">{{__('settings.confirmThisAction')}}</h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <div class="modal-body p-3">
        <p class="font-weight-bold">{{__('settings.confirmThisActionDiscription')}}</p>
        <div>
          <div class="form-check">
            <input class="form-check-input" type="radio" id="fm-1" name="pfType" value="keep-all" checked>
            <label class="form-check-label pb-2 font-weight-bold" for="fm-1">
              {{__('settings.keepExistingFollowers')}}
            </label>
          </div>
          <div class="form-check">
            <input class="form-check-input" type="radio" id="fm-2" name="pfType" value="mutual-only">
            <label class="form-check-label pb-2 font-weight-bold" for="fm-2">
              {{__('settings.keepMutalFollowers')}}
            </label>
          </div>
          <div class="form-check">
            <input class="form-check-input" type="radio" id="fm-3" name="pfType" value="only-followers">
            <label class="form-check-label pb-2 font-weight-bold" for="fm-3">
              {{__('settings.keepAtleastFollowers')}} <select name="pfDuration">
                  <option value="60">{{__('settings.1hour')}}</option>
                  <option value="1440">{{__('settings.1day')}}</option>
                  <option value="20160">{{__('settings.2weeks')}}</option>
                  <option value="43200">{{__('settings.1month')}}</option>
                  <option value="259200">{{__('settings.6months')}}</option>
                  <option value="525600">{{__('settings.1year')}}</option>
                </select>
            </label>
          </div>
          <div class="form-check">
            <input class="form-check-input" type="radio" id="fm-4" name="pfType" value="remove-all">
            <label class="form-check-label font-weight-bold text-danger" for="fm-4">
              {{__('settings.removeExistingFollowers')}}
            </label>
          </div>
          {{-- <hr>
          <div class="form-check pt-3">
            <input class="form-check-input" type="checkbox" id="allowFollowRequest">
            <label class="form-check-label" for="allowFollowRequest">
              {{__('settings.allowNewFollowRequest')}}
            </label>
          </div>
          <div class="form-check">
            <input class="form-check-input" type="checkbox" name="blockNotifications" id="chk4">
            <label class="form-check-label" for="chk4">
              {{__('settings.blockNotificationsDontFollow')}}
            </label>
          </div> --}}
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-outline-secondary font-weight-bold py-0" data-dismiss="modal">{{__('settings.cancel')}}</button>
        <button type="button" class="btn btn-primary font-weight-bold py-0" id="modal_confirm">{{__('settings.save')}}</button>
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