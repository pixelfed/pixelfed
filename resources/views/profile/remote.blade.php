@extends('layouts.app')

@section('content')
<remote-profile profile-id="{{$profile->id}}"></remote-profile>	
@endsection

@push('meta')
<meta name="robots" content="noindex, noimageindex, nofollow, nosnippet, noarchive">
@endpush

@push('scripts')
<script type="text/javascript" src="{{mix('js/rempro.js')}}"></script>
<script type="text/javascript">
	App.boot();
</script>
@endpush
