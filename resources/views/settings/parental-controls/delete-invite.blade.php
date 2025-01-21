@extends('settings.template-vue')

@section('section')
<form class="d-flex h-100 flex-column" method="post">
    @csrf
    <div class="d-flex h-100 flex-column" style="gap: 1rem;">
        <div class="d-flex justify-content-between align-items-center">
            <div class="title d-flex align-items-center" style="gap: 1rem;">
                <p class="mb-0"><a href="{{ $pc->manageUrl() }}?actions"><i class="far fa-chevron-left fa-lg"></i></a></p>
                <div>
                    <h3 class="font-weight-bold mb-0">Cancel child invite</h3>
                    <p class="small mb-0">Last updated: {{ $pc->updated_at->diffForHumans() }}</p>
                </div>
            </div>
        </div>
        <div>
            <hr />
        </div>

        <div class="d-flex bg-light align-items-center justify-content-center flex-grow-1 flex-column">
            <p>
                <i class="far fa-exclamation-triangle fa-3x"></i>
            </p>
            <h4>Are you sure you want to cancel this invite?</h4>
            <p>The child you invited will not be able to join if you cancel the invite.</p>
        </div>

        <button type="submit" class="btn btn-danger btn-block font-weight-bold">Cancel invite</button>
    </div>
</form>

@endsection
