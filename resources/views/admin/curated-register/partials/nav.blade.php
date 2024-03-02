@if(request()->filled('a'))
@if(request()->input('a') === 'rj')
<div class="alert alert-danger">
    <p class="font-weight-bold mb-0"><i class="far fa-info-circle mr-2"></i>Successfully rejected application!</p>
</div>
@endif
@if(request()->input('a') === 'aj')
<div class="alert alert-success">
    <p class="font-weight-bold mb-0"><i class="far fa-info-circle mr-2"></i>Successfully accepted application!</p>
</div>
@endif
@endif

<div class="row mb-3 justify-content-between">
    <div class="col-12">
        <ul class="nav nav-pills">
            <li class="nav-item">
                <a class="nav-link {{!request()->is('*home') || request()->has('filter') ? '':'active'}}" href="/i/admin/curated-onboarding/home">Open Applications</a>
            </li>
            <li class="nav-item">
                <a class="nav-link {{request()->has('filter') && request()->filter == 'responses' ? 'active':''}}" href="/i/admin/curated-onboarding/home?filter=responses">User Response Replies</a>
            </li>
            <li class="nav-item">
                <a class="nav-link {{request()->has('filter') && request()->filter == 'awaiting' ? 'active':''}}" href="/i/admin/curated-onboarding/home?filter=awaiting">Awaiting User Response</a>
            </li>
            <li class="nav-item">
                <a class="nav-link {{request()->has('filter') && request()->filter == 'approved' ? 'active':''}}" href="/i/admin/curated-onboarding/home?filter=approved&sort=desc">Approved Applications</a>
            </li>
            <li class="nav-item">
                <a class="nav-link {{request()->has('filter') && request()->filter == 'rejected' ? 'active':''}}" href="/i/admin/curated-onboarding/home?filter=rejected&sort=desc">Rejected Applications</a>
            </li>
            <li class="nav-item">
                <a class="nav-link {{request()->has('filter') && request()->filter == 'all' ? 'active':''}}" href="/i/admin/curated-onboarding/home?filter=all&sort=desc">All Applications</a>
            </li>
            <li class="nav-item">
                <a class="nav-link {{ request()->is('*templates*') ? 'active' : ''}}" href="/i/admin/curated-onboarding/templates">Templates</a>
            </li>
        </ul>
    </div>
</div>


@push('scripts')

@endpush
