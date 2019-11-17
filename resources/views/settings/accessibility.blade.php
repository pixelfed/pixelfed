@extends('settings.template')

@section('section')

  <div class="title">
    <h3 class="font-weight-bold">Accessibility</h3>
  </div>
  <hr>
  <form method="post">
    @csrf
    {{--<div class="form-check pb-3">
      <input class="form-check-input" type="checkbox" name="compose_media_descriptions" id="media_descriptions" {{$settings->compose_media_descriptions ? 'checked=""':''}} disabled>
      <label class="form-check-label font-weight-bold" for="compose_media_descriptions">
        {{__('Require media descriptions')}}
      </label>
      <p class="text-muted small help-text">Requires you to describe images for the visually impaired. <a href="#">Learn more</a>.</p>
    </div>
    <div class="form-check pb-3">
      <input class="form-check-input" type="checkbox" name="compose_media_descriptions" id="media_descriptions">
      <label class="form-check-label font-weight-bold" for="compose_media_descriptions">
        {{__('LiteUI')}}
      </label>
      <p class="text-muted small help-text">LiteUI is a lightweight, non-js design for low bandwidth devices. <a href="#">Learn more</a>.</p>
    </div> --}}
    <div class="form-check pb-3">
      <input class="form-check-input" type="checkbox" name="reduce_motion" id="reduce_motion" {{$settings->reduce_motion ? 'checked=""':''}}>
      <label class="form-check-label font-weight-bold" for="reduce_motion">
        {{__('Reduce Motion')}}
      </label>
      <p class="text-muted small help-text">Prevent animation effects.</p>
    </div>
    {{-- <div class="form-check pb-3">
      <input class="form-check-input" type="checkbox" name="optimize_screen_reader" id="optimize_screen_reader" {{$settings->optimize_screen_reader ? 'checked=""':''}}>
      <label class="form-check-label font-weight-bold" for="optimize_screen_reader">
        {{__('Enhanced Screen Reader Mode')}}
      </label>
      <p class="text-muted small help-text">Optimizes the experience for screen readers.</p>
    </div> --}}
    <div class="form-check pb-3">
      <input class="form-check-input" type="checkbox" name="high_contrast_mode" id="high_contrast_mode" {{$settings->high_contrast_mode ? 'checked=""':''}}>
      <label class="form-check-label font-weight-bold" for="high_contrast_mode">
        {{__('High Contrast Mode')}}
      </label>
      <p class="text-muted small help-text">High contrast mode for the visually impaired.</p>
    </div>
    <div class="form-check pb-3">
      <input class="form-check-input" type="checkbox" name="video_autoplay" id="video_autoplay" {{$settings->video_autoplay ? 'checked=""':''}}>
      <label class="form-check-label font-weight-bold" for="video_autoplay">
        {{__('Disable video autoplay')}}
      </label>
      <p class="text-muted small help-text">Prevent videos from autoplaying.</p>
    </div>
    <div class="form-group row mt-5 pt-5">
      <div class="col-12 text-right">
        <hr>
        <button type="submit" class="btn btn-primary font-weight-bold py-0 px-5">Submit</button>
      </div>
    </div>
  </form>

@endsection