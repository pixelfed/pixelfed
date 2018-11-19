@if($status->is_nsfw)

@else
  <div id="photo-carousel-wrapper-{{$status->id}}" class="carousel slide carousel-fade" data-ride="carousel">
    <ol class="carousel-indicators">
      @for($i = 0; $i < $status->media_count; $i++)
      <li data-target="#photo-carousel-wrapper-{{$status->id}}" data-slide-to="{{$i}}" class="{{$i == 0 ? 'active' : ''}}"></li>
      @endfor
    </ol>
    <div class="carousel-inner">
      @foreach($status->media()->orderBy('order')->get() as $media)
      <div class="carousel-item {{$loop->iteration == 1 ? 'active' : ''}}">
        <figure class="{{$media->filter_class}}">
          <span class="float-right mr-3 badge badge-dark" style="position:fixed;top:8px;right:0;margin-bottom:-20px;">{{$loop->iteration}}/{{$loop->count}}</span>
          <img class="d-block w-100" src="{{$media->url()}}" alt="{{$status->caption}}">
        </figure>
      </div>
      @endforeach
    </div>
    <a class="carousel-control-prev" href="#photo-carousel-wrapper-{{$status->id}}" role="button" data-slide="prev">
      <span class="carousel-control-prev-icon" aria-hidden="true"></span>
      <span class="sr-only">Previous</span>
    </a>
    <a class="carousel-control-next" href="#photo-carousel-wrapper-{{$status->id}}" role="button" data-slide="next">
      <span class="carousel-control-next-icon" aria-hidden="true"></span>
      <span class="sr-only">Next</span>
    </a>
  </div>
@endif