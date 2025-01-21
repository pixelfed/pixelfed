@extends('settings.template')

@section('section')
  <account-import />
@endsection

@push('scripts')
<script type="text/javascript" src="{{ mix('js/account-import.js') }}"></script>
@endpush

