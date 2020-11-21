<div class="alert alert-info">Filesystems information is read only, to make changes please edit the .env</div>

	<div class="form-group row">
	  <label for="app_name" class="col-sm-3 col-form-label font-weight-bold text-right">Driver</label>
	  <div class="col-sm-9">
	    <select class="form-control" disabled>
	      <option {{config('filesystems.default') == 'local' ? 'selected=""':''}}>Local</option>
	      <option {{config('filesystems.default') == 's3' ? 'selected=""':''}}>S3</option>
	      <option {{config('filesystems.default') == 'spaces' ? 'selected=""':''}}>Digital Ocean Spaces</option>
	    </select>
	  </div>
	</div>
