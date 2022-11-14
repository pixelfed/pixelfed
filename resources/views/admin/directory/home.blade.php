@extends('admin.partial.template-full')

@section('section')
</div>
<admin-directory />
@endsection

@push('scripts')
<script type="text/javascript">
    new Vue({ el: '#panel'});
</script>
@endpush
