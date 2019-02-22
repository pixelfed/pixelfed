@extends('layouts.app')

@section('content')
	<search-results></search-results>
@endsection

@push('scripts')
<script type="text/javascript">
	new Vue({
		el: '#content'
	})
</script>
@endpush