@extends('layouts.app')

@section('content')

<div class="container">
	<div class="col-12 col-md-8 offset-md-2 pt-4">

		<div class="card">
			<div class="card-header bg-white font-weight-bold d-flex justify-content-between align-items-center">
				<span>Edit Status</span>
				<a class="btn btn-outline-primary btn-sm font-weight-bold" href="{{$status->url()}}">Back to post</a>
			</div>
			<div class="card-body">
				@csrf
				<div class="form-group mb-0">
					<label class="font-weight-bold text-muted small">CW/NSFW</label>
					<div class="switch switch-sm">
						<input type="checkbox" class="switch" id="cw-switch" name="cw" {{$status->is_nsfw==true?'checked=""':''}} disabled="">
						<label for="cw-switch" class="small font-weight-bold">(Default off)</label>
					</div>
				</div>
			</div>
		</div>
		<div class="accordion" id="accordionWrapper">
			@foreach($status->media()->orderBy('order')->get() as $media)
			<div class="card mt-4 media-card">
				<div class="card-header bg-white font-weight-bold" data-toggle="collapse" href="#collapseMedia{{$loop->iteration}}">
					Media #{{$media->order + 1}}
					<span class="float-right">
						<span class="badge badge-primary">
							{{$media->mime}}
						</span>
					</span>
				</div>
				<div class="collapse {{$loop->iteration==1?'show':''}}" id="collapseMedia{{$loop->iteration}}" data-parent="#accordionWrapper">
					<div class="card-body p-0">
						<form method="post" enctype="multipart/form-data" class="media-form">
							@csrf
							<input type="hidden" name="media_id" value="{{$media->id}}">
							<div class="filter-wrapper {{$media->filter_class}}" data-filter="{{$media->filter_class}}">
								<img class="img-fluid" src="{{$media->url()}}" width="100%">
							</div>
							<div class="p-3">
								<div class="form-group">
									<label class="font-weight-bold text-muted small">Description</label>
									<input class="form-control" name="media_caption" value="{{$media->caption}}" placeholder="Add a descriptive caption for screenreaders" autocomplete="off">
								</div>
							@if($media->activityVerb() == 'Image')
								<div class="form-group form-filters" data-filter="{{$media->filter_class}}">
									<label for="filterSelectDropdown" class="font-weight-bold text-muted small">Select Filter</label>
									<select class="form-control filter-dropdown" name="media_filter"><option value="" selected="">No Filter</option><option value="filter-1977">1977</option><option value="filter-aden">Aden</option><option value="filter-amaro">Amaro</option><option value="filter-ashby">Ashby</option><option value="filter-brannan">Brannan</option><option value="filter-brooklyn">Brooklyn</option><option value="filter-charmes">Charmes</option><option value="filter-clarendon">Clarendon</option><option value="filter-crema">Crema</option><option value="filter-dogpatch">Dogpatch</option><option value="filter-earlybird">Earlybird</option><option value="filter-gingham">Gingham</option><option value="filter-ginza">Ginza</option><option value="filter-hefe">Hefe</option><option value="filter-helena">Helena</option><option value="filter-hudson">Hudson</option><option value="filter-inkwell">Inkwell</option><option value="filter-kelvin">Kelvin</option><option value="filter-juno">Kuno</option><option value="filter-lark">Lark</option><option value="filter-lofi">Lo-Fi</option><option value="filter-ludwig">Ludwig</option><option value="filter-maven">Maven</option><option value="filter-mayfair">Mayfair</option><option value="filter-moon">Moon</option><option value="filter-nashville">Nashville</option><option value="filter-perpetua">Perpetua</option><option value="filter-poprocket">Poprocket</option><option value="filter-reyes">Reyes</option><option value="filter-rise">Rise</option><option value="filter-sierra">Sierra</option><option value="filter-skyline">Skyline</option><option value="filter-slumber">Slumber</option><option value="filter-stinson">Stinson</option><option value="filter-sutro">Sutro</option><option value="filter-toaster">Toaster</option><option value="filter-valencia">Valencia</option><option value="filter-vesper">Vesper</option><option value="filter-walden">Walden</option><option value="filter-willow">Willow</option><option value="filter-xpro-ii">X-Pro II</option></select>
								</div>
							@endif
								<hr>
								<div class="form-group d-flex justify-content-between align-items-center mb-0">
									<p class="text-muted font-weight-bold mb-0 small">Last Updated: {{$media->updated_at->diffForHumans()}}</p>
									<button type="submit" class="btn btn-primary btn-sm font-weight-bold px-4">Update</button>
								</div>  
							</div>
						</form>
					</div>
				</div>
			</div>
			@endforeach
		</div>

	</div>
</div>
@endsection

@push('scripts')
<script type="text/javascript">
	$(document).ready(function() {
		App.boot();
		$('.form-filters').each(function(i,d) {
			let el = $(d);
			let filter = el.data('filter');
			if(filter) {
				var opt = el.find('option[value='+filter+']')[0];
				$(opt).attr('selected','');
			}
		});

		$('.media-form').on('submit', function(e){
			e.preventDefault();
			let el = $(this);
			let id = el.find('input[name=media_id]').val();
			let caption = el.find('input[name=media_caption]').val();
			let filter = el.find('.filter-dropdown option:selected').val();
			axios.post(window.location.href, {
				'id': id,
				'caption': caption,
				'filter': filter
			}).then((res) => {
				window.location.href = '{{$status->url()}}';
			}).catch((err) => {
				swal('Something went wrong', 'An error occurred, please try again later', 'error');
			});
		});
	});

</script>
@endpush