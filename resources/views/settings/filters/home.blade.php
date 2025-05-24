@extends('settings.template')

@section('section')

<filters-list />

@endsection

@push('scripts')
<script type="text/javascript" src="{{mix('js/custom_filters.js')}}"></script>
@endpush
