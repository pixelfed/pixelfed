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
        {{__('Opt-out of search engine indexing')}}
      </label>
      <p class="text-muted small help-text">When your account is visible to search engines, your information can be crawled and stored by search engines.</p>
    </div>

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

    <div class="form-group row mt-5 pt-5">
      <div class="col-12 text-right">
        <hr>
        <button type="submit" class="btn btn-primary font-weight-bold">Submit</button>
      </div>
    </div>
  </form>

@endsection