@extends('admin.partial.template-full')

@section('section')
</div>
<instances-component />
@endsection

@push('scripts')
<script type="text/javascript">
    new Vue({ el: '#panel'});
</script>
@endpush
