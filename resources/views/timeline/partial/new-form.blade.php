    <div class="card">
      <div class="card-header font-weight-bold">New Post</div>
      <div class="card-body" id="statusForm">
        <form method="post" action="/timeline" enctype="multipart/form-data">
          @csrf
          <div class="form-group">
            <label class="font-weight-bold text-muted small">Upload Image</label>
            <input type="file" class="form-control-file" name="photo" accept="image/*">
            <small class="form-text text-muted">
              Max Size: @maxFileSize(). Supported formats: jpeg, png, gif, bmp.
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
            <label class="font-weight-bold text-muted small">CW/NSFW</label>
            <div class="switch switch-sm">
              <input type="checkbox" class="switch" id="cw-switch" name="cw">
              <label for="cw-switch" class="small font-weight-bold">(Default off)</label>
            </div>
            <small class="form-text text-muted">
              Please mark all NSFW and controversial content, as per our content policy.
            </small>
          </div>  
          <button type="submit" class="btn btn-outline-primary btn-block">Post</button>
        </form>
      </div>  
    </div>