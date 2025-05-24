@extends('settings.template')

@section('section')

  <div class="title">
    <h3 class="font-weight-bold">{{__('settings.privacy.privacy_settings')}}</h3>
  </div>
  <hr>
  <div class="form-group pb-1">
    <p>
      <a class="btn btn-link py-0 font-weight-bold" href="{{route('settings.privacy.muted-users')}}">{{ __('profile.mutedAccounts') }}</a>
      <a class="btn btn-link py-0 font-weight-bold" href="{{route('settings.privacy.blocked-users')}}">{{ __('profile.blockedAccounts') }}</a>
      <a class="btn btn-link py-0 font-weight-bold" href="{{route('settings.privacy.domain-blocks')}}">{{ __('profile.blockedDomains') }}</a>
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
        Manually Review Follow Requests
      </label>
      <p class="text-muted small help-text">When you get a follow request, Pixelfed will not automatically approve it. You can instead manually confirm or deny the follow request. Your existing followers won't be affected.</p>
    </div>

    <div class="form-check pb-3">
      <input class="form-check-input" type="checkbox" name="crawlable" id="crawlable" {{!$settings->crawlable ? 'checked=""':''}} {{$settings->is_private ? 'disabled=""':''}}>
      <label class="form-check-label font-weight-bold" for="crawlable">
        {{__('settings.privacy.disable_search_engine_indexing')}}
      </label>
      <p class="text-muted small help-text">{{__('settings.privacy.when_your_account_is_visible_to_search_engines_etc')}} {!! $settings->is_private ? '<strong>'.__('settings.privacy.not_available_when_your_account_is_private').'</strong>' : ''!!}</p>
    </div>

    <div class="form-check pb-3">
      <input class="form-check-input" type="checkbox" name="indexable" id="indexable" {{$profile->indexable ? 'checked=""':''}} {{$settings->is_private ? 'disabled=""':''}}>
      <label class="form-check-label font-weight-bold" for="indexable">
        {{__('settings.privacy.include_public_posts_in_search_results')}}
      </label>
        <p class="text-muted small help-text">{{__('settings.privacy.your_public_posts_may_appear_in_search_results_etc')}} {!! $settings->is_private ? '<strong>'.__('settings.privacy.not_available_when_your_account_is_private').'</strong>' : ''!!}</p>
    </div>


    <div class="form-check pb-3">
      <input class="form-check-input" type="checkbox" name="is_suggestable" id="is_suggestable" {{$settings->is_private ? 'disabled=""':''}} {{auth()->user()->profile->is_suggestable ? 'checked=""':''}}>
      <label class="form-check-label font-weight-bold" for="is_suggestable">
        {{__('settings.privacy.show_on_directory')}}
      </label>
      <p class="text-muted small help-text">{{__('settings.privacy.when_this_option_is_enabled_your_profile_is_etc')}} {!! $settings->is_private ? '<strong>'.__('settings.privacy.not_available_when_your_account_is_private').'</strong>' : ''!!}</p>
    </div>

    <div class="form-check pb-3">
      <input class="form-check-input" type="checkbox" id="public_dm" {{$settings->public_dm ? 'checked=""':''}} name="public_dm">
      <label class="form-check-label font-weight-bold" for="public_dm">
        {{__('settings.privacy.receive_direct_messages_from_anyone')}}
      </label>
      <p class="text-muted small help-text">{{__('settings.privacy.if_selected_you_will_be_able_to_receive_messages_etc')}}</p>
    </div>
    {{-- <div class="form-check pb-3">
      <input class="form-check-input" type="checkbox" value="" id="srs" checked="">
      <label class="form-check-label font-weight-bold" for="srs">
        {{__('Hide sensitive content from search results')}}
      </label>
      <p class="text-muted small help-text">{{__('settings.privacy.this_prevents_posts_with_potentially_sensitive_etc')}}</p>
    </div> --}}
    {{-- <div class="form-check pb-3">
      <input class="form-check-input" type="checkbox" value="" id="rbma" checked="">
      <label class="form-check-label font-weight-bold" for="rbma">
        {{__('Remove blocked and muted accounts')}}
      </label>
      <p class="text-muted small help-text">{{__('settings.privacy.use_this_to_eliminate_search_results_from_accounts_etc')}}</p>
    </div>
    <div class="form-check pb-3">
      <input class="form-check-input" type="checkbox" value="" id="ssp">
      <label class="form-check-label font-weight-bold" for="ssp">
        {{__('Display media that may contain sensitive content')}}
      </label>
      <p class="text-muted small help-text">{{__('settings.privacy.show_all_media_including_potentially_sensitive_content')}}</p>
    </div> --}}

    <div class="form-check pb-3">
      <input class="form-check-input" type="checkbox" name="show_profile_follower_count" id="show_profile_follower_count" {{$settings->show_profile_follower_count ? 'checked=""':''}}>
      <label class="form-check-label font-weight-bold" for="show_profile_follower_count">
        {{__('settings.privacy.show_follower_count')}}
      </label>
      <p class="text-muted small help-text">{{__('settings.privacy.display_follower_count_on_profile')}}</p>
    </div>


    <div class="form-check pb-3">
      <input class="form-check-input" type="checkbox" name="show_profile_following_count" id="show_profile_following_count" {{$settings->show_profile_following_count ? 'checked=""':''}}>
      <label class="form-check-label font-weight-bold" for="show_profile_following_count">
        {{__('settings.privacy.show_following_count')}}
      </label>
      <p class="text-muted small help-text">{{__('settings.privacy.display_following_count_on_profile')}}</p>
    </div>

    <div class="form-check pb-3">
      <input class="form-check-input" type="checkbox" name="disable_embeds" id="disable_embeds" {{$settings->disable_embeds ? 'checked=""':''}}>
      <label class="form-check-label font-weight-bold" for="disable_embeds">
        {{__('settings.privacy.disable_embeds')}}
      </label>
      <p class="text-muted small help-text">{{__('settings.privacy.disable_post_and_profile_embeds')}}</p>
    </div>

    @if(!$settings->is_private)
    <div class="form-check pb-3">
      <input class="form-check-input" type="checkbox" name="show_atom" id="show_atom" {{$settings->show_atom ? 'checked=""':''}}>
      <label class="form-check-label font-weight-bold" for="show_atom">
        {{__('settings.privacy.enable_atom_feed')}}
      </label>
      <p class="text-muted small help-text mb-0">{{__('settings.privacy.enable_your_profile_atom_feed_only_public_profiles_etc')}}</p>
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
        <button type="submit" class="btn btn-primary font-weight-bold py-0 px-5">{{__('settings.submit')}}</button>
      </div>
    </div>
  </form>
<div class="modal" tabindex="-1" role="dialog" id="pac_modal">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">{{__('settings.privacy.confirm_this_action')}}</h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <div class="modal-body p-3">
        <p class="font-weight-bold">{{__('settings.privacy.please_select_the_type_of_private_account_you_etc')}}</p>
        <div>
          <div class="form-check">
            <input class="form-check-input" type="radio" id="fm-1" name="pfType" value="keep-all" checked>
            <label class="form-check-label pb-2 font-weight-bold" for="fm-1">
              {{__('settings.privacy.keep_existing_followers')}}
            </label>
          </div>
          <div class="form-check">
            <input class="form-check-input" type="radio" id="fm-2" name="pfType" value="mutual-only">
            <label class="form-check-label pb-2 font-weight-bold" for="fm-2">
              {{__('settings.privacy.only_keep_mutual_followers')}}
            </label>
          </div>
          <div class="form-check">
            <input class="form-check-input" type="radio" id="fm-3" name="pfType" value="only-followers">
            <label class="form-check-label pb-2 font-weight-bold" for="fm-3">
              {{__('settings.privacy.only_followers_that_have_followed_you_for_atleast')}} <select name="pfDuration">
                  <option value="60">1 {{__('settings.privacy.hour')}}</option>
                  <option value="1440">1 {{__('settings.privacy.day')}}</option>
                  <option value="20160">2 {{__('settings.privacy.weeks')}}</option>
                  <option value="43200">1 {{__('settings.privacy.month')}}</option>
                  <option value="259200">6 {{__('settings.privacy.months')}}</option>
                  <option value="525600">1 {{__('settings.privacy.year')}}</option>
                </select>
            </label>
          </div>
          <div class="form-check">
            <input class="form-check-input" type="radio" id="fm-4" name="pfType" value="remove-all">
            <label class="form-check-label font-weight-bold text-danger" for="fm-4">
              {{__('settings.privacy.remove_existing_followers')}}
            </label>
          </div>
          {{-- <hr>
          <div class="form-check pt-3">
            <input class="form-check-input" type="checkbox" id="allowFollowRequest">
            <label class="form-check-label" for="allowFollowRequest">
              {{__('settings.privacy.allow_new_follow_requests')}}
            </label>
          </div>
          <div class="form-check">
            <input class="form-check-input" type="checkbox" name="blockNotifications" id="chk4">
            <label class="form-check-label" for="chk4">
              {{__('settings.privacy.block_notifications_from_accounts_i_dont_follow')}}
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
        swal('{{__('settings.error')}}', '{{__('settings.privacy.an_error_occured_please_try_again')}}', 'error');
      });
    });
  });

</script>
@endpush
