<div id="modal-text" class="d-none">
  <div class="d-flex justify-content-between align-items-center">
    <h4>Timeline</h4>
    <button type="button" class="btn btn-link text-dark" data-toggle="modal" data-target="#modal-post">
      <span class="icon-plus"></span>
    </button>
  </div>
</div>
<div tabindex="-1" role="dialog" id="modal-post" aria-hidden="true">
  <div role="document" id="modal-dialog">
    <div class="card">
      <div class="card-header font-weight-bold">New Post</div>
      <div class="card-body" id="statusForm">
        <form method="post" action="/timeline" enctype="multipart/form-data">
          @csrf
          <div class="custom-file">
            <label class="custom-file-label">
              <span class="icon-picture mr-1"></span>
              Upload Image
            </label>
            <input type="file" class="custom-file-input" name="photo" accept="image/*">
            <small class="form-text text-muted">
              Max Size: @maxFileSize(). Supported formats: jpeg, png, gif, bmp.
            </small>
          </div>
          <div class="form-group">
            <label class="font-weight-bold text-muted small">Caption</label>
            <input type="text" class="form-control" name="caption" placeholder="Add a caption here">
            <small class="form-text text-muted">
              Max length: {{config('pixelfed.max_caption_length')}} characters.
            </small>
          </div>
          <button type="submit" class="btn btn-outline-primary btn-block">Post</button>
        </form>
      </div>
    </div>
  </div>
</div>
