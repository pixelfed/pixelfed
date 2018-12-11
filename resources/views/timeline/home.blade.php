@extends('layouts.app')

@section('content')

<timeline></timeline>

@endsection

@push('scripts')
<script type="text/javascript">
	new Vue({
		el: '#content'
	});
</script>
@endpush