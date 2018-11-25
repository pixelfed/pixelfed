@if($status->is_nsfw)
<details class="details-animated">
  <summary>
    <p class="mb-0 lead font-weight-bold">CW / NSFW / Hidden Media</p>
    <p class="font-weight-light">(click to show)</p>
  </summary>
  <a class="max-hide-overflow {{$status->firstMedia()->filter_class}}" href="{{$status->url()}}">
    <img class="card-img-top" src="{{$status->mediaUrl()}}">
  </a>
</details>
@else
<div class="{{$status->firstMedia()->filter_class}}">
  <img src="{{$status->mediaUrl()}}" width="100%">
</div>
@endif