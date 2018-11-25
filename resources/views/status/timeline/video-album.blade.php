@if($status->is_nsfw)
  <div id="video-carousel-wrapper-{{$status->id}}" class="carousel slide carousel-fade" data-ride="false" data-interval="false">
    <ol class="carousel-indicators">
      @for($i = 0; $i < $status->media_count; $i++)
      <li data-target="#video-carousel-wrapper-{{$status->id}}" data-slide-to="{{$i}}" class="{{$i == 0 ? 'active' : ''}}"></li>
      @endfor
    </ol>
    <div class="carousel-inner">
      @foreach($status->media()->orderBy('order')->get() as $media)
      <div class="carousel-item {{$loop->iteration == 1 ? 'active' : ''}}">
        <span class="float-right mr-3 badge badge-dark" style="position:fixed;top:8px;right:0;margin-bottom:-20px;z-index: 999;">{{$loop->iteration}}/{{$loop->count}}</span>
        <div class="embed-responsive embed-responsive-4by3">
          <video class=" embed-responsive-item" controls loop>
            <source src="{{$media->url()}}" type="{{$media->mime}}">
          </video>
        </div>
      </div>
      @endforeach
    </div>
    <a class="carousel-control-prev" href="#video-carousel-wrapper-{{$status->id}}" role="button" data-slide="prev">
      <span class="carousel-control-prev-icon" aria-hidden="true"></span>
      <span class="sr-only">Previous</span>
    </a>
    <a class="carousel-control-next" href="#video-carousel-wrapper-{{$status->id}}" role="button" data-slide="next">
      <span class="carousel-control-next-icon" aria-hidden="true"></span>
      <span class="sr-only">Next</span>
    </a>
  </div>
@else
  <div id="video-carousel-wrapper-{{$status->id}}" class="carousel slide carousel-fade" data-ride="false" data-interval="false">
    <ol class="carousel-indicators">
      @for($i = 0; $i < $status->media_count; $i++)
      <li data-target="#video-carousel-wrapper-{{$status->id}}" data-slide-to="{{$i}}" class="{{$i == 0 ? 'active' : ''}}"></li>
      @endfor
    </ol>
    <div class="carousel-inner">
      @foreach($status->media()->orderBy('order')->get() as $media)
      <div class="carousel-item {{$loop->iteration == 1 ? 'active' : ''}}">
        <span class="float-right mr-3 badge badge-dark" style="position:fixed;top:8px;right:0;margin-bottom:-20px;z-index: 999;">{{$loop->iteration}}/{{$loop->count}}</span>
        <div class="embed-responsive embed-responsive-4by3">
          <video class=" embed-responsive-item" controls loop>
            <source src="{{$media->url()}}" type="{{$media->mime}}">
          </video>
        </div>
      </div>
      @endforeach
    </div>
    <a class="carousel-control-prev" href="#video-carousel-wrapper-{{$status->id}}" role="button" data-slide="prev">
      <span class="carousel-control-prev-icon" aria-hidden="true"></span>
      <span class="sr-only">Previous</span>
    </a>
    <a class="carousel-control-next" href="#video-carousel-wrapper-{{$status->id}}" role="button" data-slide="next">
      <span class="carousel-control-next-icon" aria-hidden="true"></span>
      <span class="sr-only">Next</span>
    </a>
  </div>
@endif