<form method="post">
    @csrf
    <div class="form-group row">
      <label for="app_url" class="col-sm-3 col-form-label font-weight-bold text-right">Registration</label>
      <div class="col-sm-9">
        <div class="form-check pb-2">
          <input class="form-check-input" type="checkbox" id="open_registration" name="open_registration" {{config('pixelfed.open_registration') === true ? 'checked=""' : '' }}>
          <label class="form-check-label font-weight-bold" for="open_registration">
            {{config('pixelfed.open_registration') === true ? 'Open' : 'Closed' }}
          </label>
          <p class="text-muted small help-text font-weight-bold">When this option is enabled, new user registration is open.</p>
        </div>
      </div>
    </div>
    <div class="form-group row">
      <label for="app_url" class="col-sm-3 col-form-label font-weight-bold text-right">Email Validation</label>
      <div class="col-sm-9">
        <div class="form-check pb-2">
          <input class="form-check-input" type="checkbox" id="enforce_email_verification" name="enforce_email_verification" {{config('pixelfed.enforce_email_verification') === true ? 'checked=""' : '' }}>
          <label class="form-check-label font-weight-bold" for="open_registration">
            {{config('pixelfed.enforce_email_verification') == true ? 'Enabled' : 'Disabled' }}
          </label>
          <p class="text-muted small help-text font-weight-bold">Enforce email validation for new user registration.</p>
        </div>
      </div>
    </div>
    <div class="form-group row">
      <label for="app_url" class="col-sm-3 col-form-label font-weight-bold text-right">ActivityPub</label>
      <div class="col-sm-9">
        <div class="form-check pb-2">
          <input class="form-check-input" type="checkbox" id="activitypub_enabled" name="activitypub_enabled" {{config('federation.activitypub.enabled') === true ? 'checked=""' : '' }}>
          <label class="form-check-label font-weight-bold" for="activitypub_enabled">
            {{config('federation.activitypub.enabled') === true ? 'Enabled' : 'Disabled' }}
          </label>
          <p class="text-muted small help-text font-weight-bold">Enable for federation support.</p>
        </div>
      </div>
    </div>
    <hr>
    <div class="form-group row">
      <label class="col-sm-3 col-form-label font-weight-bold text-right">Account Size</label>
      <div class="col-sm-9">
        <input type="text" class="form-control" placeholder="1000000" name="max_account_size" value="{{config('pixelfed.max_account_size')}}">
        <span class="help-text font-weight-bold text-muted small">
          Max account size for users, in KB.
        </span>
      </div>
    </div>
    <div class="form-group row">
      <label class="col-sm-3 col-form-label font-weight-bold text-right">Max Upload Size</label>
      <div class="col-sm-9">
        <input type="text" class="form-control" placeholder="15000" name="max_photo_size" value="{{config('pixelfed.max_photo_size')}}">
        <span class="help-text font-weight-bold text-muted small">
          Max file size for uploads, in KB.
        </span>
      </div>
    </div>
    <div class="form-group row">
      <label class="col-sm-3 col-form-label font-weight-bold text-right">Caption Length</label>
      <div class="col-sm-9">
        <input type="text" class="form-control" placeholder="500" name="caption_limit" value="{{config('pixelfed.max_caption_length')}}">
        <span class="help-text font-weight-bold text-muted small">
          Character limit for captions and comments.
        </span>
      </div>
    </div>
    <div class="form-group row">
      <label class="col-sm-3 col-form-label font-weight-bold text-right">Max Album Size</label>
      <div class="col-sm-9">
        <input type="text" class="form-control" placeholder="3" name="album_limit" value="{{config('pixelfed.max_album_length')}}">
        <span class="help-text font-weight-bold text-muted small">
          Limit # of media per post.
        </span>
      </div>
    </div>
    <div class="form-group row">
      <label class="col-sm-3 col-form-label font-weight-bold text-right">Image Quality</label>
      <div class="col-sm-9">
        <input type="text" class="form-control" placeholder="80" name="image_quality" value="{{config('pixelfed.image_quality')}}">
        <span class="help-text font-weight-bold text-muted small">
          Image quality. Must be a value between 1 (worst) - 100 (best).
        </span>
      </div>
    </div>
    <hr>
    <div class="form-group row mb-0">
      <div class="col-12 text-right">
        <button type="submit" class="btn btn-primary font-weight-bold">Submit</button>
      </div>
    </div>
  </form>
