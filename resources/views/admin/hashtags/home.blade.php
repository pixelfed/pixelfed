@extends('admin.partial.template-full')

@section('section')
</div>
<hashtag-component />
@endsection

@push('scripts')
<script type="text/javascript">
    new Vue({ el: '#panel'});
</script>
@endpush

