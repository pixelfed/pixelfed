@if($status->is_nsfw)
<details class="details-animated">
  <summary>
    <p class="mb-0 lead font-weight-bold">CW / NSFW / Hidden Media</p>
    <p class="font-weight-light">(click to show)</p>
  </summary>
	<div class="embed-responsive embed-responsive-16by9">
		<video class="video" preload="none" controls loop>
			<source src="{{$status->firstMedia()->url()}}" type="{{$status->firstMedia()->mime}}">
		</video>
	</div>
 </details>
@else
<div class="embed-responsive embed-responsive-16by9">
	<video class="video" preload="none" controls loop>
		<source src="{{$status->firstMedia()->url()}}" type="{{$status->firstMedia()->mime}}">
	</video>
</div>
@endif