@extends('layouts.app')

@section('content')
<gs-permalink gid="{{$group->id}}" sid="{{$gp['status_id']}}" />
@endsection

@push('scripts')
<script type="text/javascript" src="{{ mix('js/group-status.js') }}"></script>
<script type="text/javascript">App.boot();</script>
@endpush
