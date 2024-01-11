@extends('settings.template-vue')

@section('section')
<div class="d-flex h-100 flex-column">
    <div class="d-flex justify-content-between align-items-center">
        <div class="title d-flex align-items-center" style="gap: 1rem;">
            <p class="mb-0"><a href="/settings/home"><i class="far fa-chevron-left fa-lg"></i></a></p>
            <h3 class="font-weight-bold mb-0">Parental Controls</h3>
        </div>
    </div>

    <hr />

    @if($children->count())
    <div class="d-flex flex-column flex-grow-1 w-100">
        <div class="list-group w-100">
            @foreach($children as $child)
            <a class="list-group-item d-flex align-items-center text-decoration-none text-dark" href="{{ $child->manageUrl() }}" style="gap: 1rem;">
                <img src="/storage/avatars/default.png" width="40" height="40" class="rounded-circle" />

                <div class="flex-grow-1">
                    @if($child->child_id && $child->email_verified_at)
                    <p class="font-weight-bold mb-0" style="line-height: 1.5;">&commat;{{ $child->childAccount()['username'] }}</p>
                    <p class="small text-muted mb-0" style="line-height: 1;">{{ $child->childAccount()['display_name'] }}</p>
                    @else
                    <p class="font-weight-light mb-0 text-danger" style="line-height: 1.5;">Invite Pending</p>
                    <p class="mb-0 small" style="line-height: 1;">{{ $child->email }}</p>
                    @endif
                </div>

                <div class="font-weight-bold small text-lighter" style="line-height:1;">
                    <i class="far fa-clock mr-1"></i>
                    {{ $child->updated_at->diffForHumans() }}
                </div>
            </a>
            @endforeach
        </div>

        <div class="mt-3">
            {{ $children->links() }}
        </div>
    </div>
    @else
    <div class="d-flex flex-grow-1 bg-light mb-3 rounded p-4">
        <p>You are not managing any children accounts.</p>
    </div>
    @endif

    <div class="d-flex justify-content-between align-items-center">
        <a class="btn btn-outline-dark font-weight-bold py-2 px-4" href="{{ route('settings.pc.add') }}">
            <i class="far fa-plus mr-2"></i> Add Child
        </a>

        <div class="font-weight-bold">
            <span>{{ $children->total() }}/{{ config('instance.parental_controls.limits.max_children') }}</span>
            <span>children added</span>
        </div>
    </div>

</div>
@endsection

