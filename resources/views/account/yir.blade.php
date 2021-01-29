@extends('layouts.blank')

@section('content')
<my-yearreview></my-yearreview>
@endsection

@push('scripts')
<script type="text/javascript" src="{{mix('js/my2020.js')}}"></script>
	<script type="text/javascript">App.boot();</script>
@endpush