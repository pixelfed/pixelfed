@extends('layouts.app')

@section('content')
<discover-component></discover-component>
@endsection

@push('scripts')
<script type="text/javascript" src="{{ mix('js/discover.js') }}"></script>
<script type="text/javascript" src="{{ mix('js/compose.js') }}"></script>
<script type="text/javascript">
$(document).ready(function(){new Vue({el: '#content'});});
</script>
@endpush