@extends('portfolio.layout', ['title' => "@{$user['username']}'s Portfolio"])

@section('content')
<portfolio-profile initial-data="{{json_encode(['profile' => $user])}}" />
@endsection

@push('scripts')
<script type="text/javascript" src="{{ mix('js/portfolio.js') }}"></script>
<script type="text/javascript">
    App.boot();
</script>
@endpush
