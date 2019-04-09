<div class="alert alert-info">Cache information is read only, to make changes please edit the .env</div>

<div class="form-group row">
  <label for="app_name" class="col-sm-3 col-form-label font-weight-bold text-right">Driver</label>
  <div class="col-sm-9">
    <select class="form-control" disabled>
      <option {{config('cache.default') == 'apc' ? 'selected=""':''}}>APC</option>
      <option {{config('cache.default') == 'array' ? 'selected=""':''}}>Array</option>
      <option {{config('cache.default') == 'database' ? 'selected=""':''}}>Database</option>
      <option {{config('cache.default') == 'file' ? 'selected=""':''}}>File</option>
      <option {{config('cache.default') == 'memcached' ? 'selected=""':''}}>Memcached</option>
      <option {{config('cache.default') == 'redis' ? 'selected=""':''}}>Redis</option>
    </select>
  </div>
</div>

<div class="form-group row">
  <label for="db_host" class="col-sm-3 col-form-label font-weight-bold text-right">Cache Prefix</label>
  <div class="col-sm-9">
    <input type="text" class="form-control" disabled value="{{config('cache.prefix')}}">
  </div>
</div>