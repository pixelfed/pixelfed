@extends('layouts.app')

@section('content')
<div>
  <direct-component></direct-component>
</div>
@endsection

@push('scripts')
<script type="text/javascript" src="{{ mix('js/direct.js') }}"></script>
<script type="text/javascript">App.boot();</script>
@endpush
