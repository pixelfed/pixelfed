@extends('admin.partial.template-full')

@section('section')
</div>
<admin-autospam />
@endsection

@push('scripts')
<script type="text/javascript">
    new Vue({ el: '#panel'});
</script>
@endpush
