@extends('layouts.app')

@section('content')

<timeline scope="network"></timeline>

@endsection

@push('scripts')
<script type="text/javascript" src="{{ mix('js/timeline.js') }}"></script>
<script type="text/javascript" src="{{ mix('js/compose.js') }}"></script>
<script type="text/javascript">
	new Vue({
		el: '#content'
	});
</script>
@endpush