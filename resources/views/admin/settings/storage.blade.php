@extends('admin.partial.template')

@include('admin.settings.sidebar')

@section('section')
<div class="title">
	<h3 class="font-weight-bold">Storage</h3>
	<p class="lead">Filesystem storage stats</p>
</div>
<hr>
<p class="alert alert-warning">
	<strong>Feature Unavailable:</strong> This feature will be released in a future version.
</p>
@endsection

@push('scripts')
<script type="text/javascript">
	$('.human-size').each(function(d,a) {
		let el = $(a);
		let size = el.data('bytes');
		el.text(filesize(size, {round: 0}));
	});
</script>
@endpush
