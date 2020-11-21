@extends('layouts.app')

@section('content')
<story-compose></story-compose>
@endsection

@push('scripts')
<script type="text/javascript">
	new Vue({
		el: '#content'
	});
</script>
@endpush