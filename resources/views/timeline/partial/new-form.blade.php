    <div class="card">
      <div class="card-header font-weight-bold">New Post</div>
      <div class="card-body" id="statusForm">
        @if (session('error'))
            <div class="alert alert-danger">
                {{ session('error') }}
            </div>
        @endif
        <form method="post" action="/timeline" enctype="multipart/form-data">
          @csrf
          <input type="hidden" name="filter_name" value="">
          <input type="hidden" name="filter_class" value="">
          <div class="form-group">
            <label class="font-weight-bold text-muted small">Upload Image</label>
            <input type="file" class="form-control-file" id="fileInput" name="photo[]" accept="image/*" multiple="">
            <small class="form-text text-muted">
              Max Size: @maxFileSize(). Supported formats: jpeg, png, gif, bmp. Limited to {{config('pixelfed.max_album_length')}} photos per post.
            </small>
          </div>
          <div class="form-group">
            <label class="font-weight-bold text-muted small">Caption</label>
            <input type="text" class="form-control" name="caption" placeholder="Add a caption here" autocomplete="off">
            <small class="form-text text-muted">
              Max length: {{config('pixelfed.max_caption_length')}} characters.
            </small>
          </div>
          <div class="form-group">
            <button class="btn btn-primary btn-sm px-3 py-1 font-weight-bold" type="button" data-toggle="collapse" data-target="#collapsePreview" aria-expanded="false" aria-controls="collapsePreview">
              Options
            </button>
            <div class="collapse" id="collapsePreview">

              <div class="form-group pt-3">
                <label class="font-weight-bold text-muted small">CW/NSFW</label>
                <div class="switch switch-sm">
                  <input type="checkbox" class="switch" id="cw-switch" name="cw">
                  <label for="cw-switch" class="small font-weight-bold">(Default off)</label>
                </div>
                <small class="form-text text-muted">
                  Please mark all NSFW and controversial content, as per our content policy.
                </small>
              </div>

{{--               <div class="form-group">
                <label class="font-weight-bold text-muted small">Visibility</label>
                <div class="switch switch-sm">
                  <input type="checkbox" class="switch" id="visibility-switch" name="visibility">
                  <label for="visibility-switch" class="small font-weight-bold">Public | Followers-only</label>
                </div>
                <small class="form-text text-muted">
                  Toggle this to limit this post to your followers only.
                </small>
              </div> --}}

              <div class="form-group d-none form-preview">
                <label class="font-weight-bold text-muted small">Photo Preview</label>
                <figure class="filterContainer">
                    <img class="filterPreview img-fluid">
                </figure>
                <small class="form-text text-muted font-weight-bold">
                  No filter selected.
                </small>
              </div>
              <div class="form-group d-none form-filters">
                <label for="filterSelectDropdown" class="font-weight-bold text-muted small">Select Filter</label>
                <select class="form-control" id="filterSelectDropdown">
                  <option value="none" selected="">No Filter</option>
                </select>
              </div>  
            </div>
          </div>  
          <button type="submit" class="btn btn-outline-primary btn-block">Post</button>
        </form>
      </div>  
    </div>
