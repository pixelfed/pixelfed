    <div class="card card-md-rounded-0">
      <div class="card-header bg-white font-weight-bold d-inline-flex justify-content-between">
        <div>{{__('Create New Post')}}</div>
        <div>
          <span class="badge badge-success mr-1">NEW</span>
          <a href="/i/compose">Experimental UI</a>
        </div>
      </div>
      <div class="card-body" id="statusForm">

        <form method="post" action="{{route('timeline.personal')}}" enctype="multipart/form-data">
          @csrf
          <input type="hidden" name="filter_name" value="">
          <input type="hidden" name="filter_class" value="">
          <div class="form-group">
            <div class="custom-file">
              <input type="file" class="custom-file-input" id="fileInput" name="photo[]" accept="{{config('pixelfed.media_types')}}" multiple="">
              <label class="custom-file-label" for="fileInput">Upload Image(s)</label>
            </div>
            <small class="form-text text-muted">
              Max Size: @maxFileSize(). Supported formats: jpeg, png, gif, bmp. Limited to {{config('pixelfed.max_album_length')}} photos per post.
            </small>
          </div>
          <div class="form-group">
            <textarea class="form-control" name="caption" placeholder="Add optional caption here" autocomplete="off" data-limit="{{config('pixelfed.max_caption_length')}}" rows="1"></textarea>
            <p class="form-text text-muted small text-right">
              <span class="caption-counter">0</span>
              <span>/</span>
              <span>{{config('pixelfed.max_caption_length')}}</span>
            </p>
          </div>
          <div class="form-group">
            <button class="btn btn-outline-primary btn-sm px-3 py-1 font-weight-bold" type="button" data-toggle="collapse" data-target="#collapsePreview" aria-expanded="false" aria-controls="collapsePreview">
              Options &nbsp; <i class="fas fa-chevron-down"></i>
            </button>
            <div class="collapse" id="collapsePreview">
              <div class="form-group pt-3">
                <label class="font-weight-bold text-muted small">Visibility</label>
                <div class="switch switch-sm">
                  <select class="form-control" name="visibility">
                    <option value="public" selected="">Public</option>
                    <option value="unlisted">Unlisted (hidden from public timelines)</option>
                    <option value="private">Followers Only</option>
                  </select>
                </div>
                <small class="form-text text-muted">
                  Set the visibility of this post.
                </small>
              </div>
              <div class="form-group">
                <label class="font-weight-bold text-muted small">CW/NSFW</label>
                <div class="switch switch-sm">
                  <input type="checkbox" class="switch" id="cw-switch" name="cw">
                  <label for="cw-switch" class="small font-weight-bold">(Default off)</label>
                </div>
                <small class="form-text text-muted">
                  Please mark all NSFW and controversial content, as per our content policy.
                </small>
              </div>
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
          <button type="submit" class="btn btn-outline-primary btn-block font-weight-bold">Create Post</button>
        </form>
      </div>  
    </div>
