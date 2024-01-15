@extends('settings.template-vue')

@section('section')
<form class="d-flex h-100 flex-column" method="post">
    @csrf
<div class="d-flex h-100 flex-column">
    <div class="d-flex justify-content-between align-items-center">
        <div class="title d-flex align-items-center" style="gap: 1rem;">
            <p class="mb-0"><a href="/settings/parental-controls"><i class="far fa-chevron-left fa-lg"></i></a></p>
            <div>
                <h3 class="font-weight-bold mb-0">Manage child</h3>
                <p class="small mb-0">Last updated: {{ $pc->updated_at->diffForHumans() }}</p>
            </div>
        </div>

        <button class="btn btn-dark font-weight-bold">Update</button>
    </div>

    <hr />

    <div class="d-flex flex-column flex-grow-1">
        <ul class="nav nav-pills mb-0" id="pills-tab" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link active font-weight-bold" id="pills-status-tab" data-toggle="pill" data-target="#pills-status" type="button" role="tab" aria-controls="pills-status" aria-selected="true">Status</button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link font-weight-bold" id="pills-permissions-tab" data-toggle="pill" data-target="#pills-permissions" type="button" role="tab" aria-controls="pills-permissions" aria-selected="false">Permissions</button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link font-weight-bold" id="pills-details-tab" data-toggle="pill" data-target="#pills-details" type="button" role="tab" aria-controls="pills-details" aria-selected="false">Account Details</button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link font-weight-bold" id="pills-actions-tab" data-toggle="pill" data-target="#pills-actions" type="button" role="tab" aria-controls="pills-actions" aria-selected="false">Actions</button>
            </li>
        </ul>
        <div>
            <hr>
        </div>
        <div class="tab-content" id="pills-tabContent">
            <div class="tab-pane fade show active" id="pills-status" role="tabpanel" aria-labelledby="pills-status-tab">
                @if(!$pc->child_id && !$pc->email_verified_at)
                @include('settings.parental-controls.child-status', ['state' => 'sent_invite'])
                @elseif($pc->child_id && !$pc->email_verified_at)
                @include('settings.parental-controls.child-status', ['state' => 'awaiting_email_confirmation'])
                @elseif($pc->child_id && $pc->email_verified_at)
                @include('settings.parental-controls.child-status', ['state' => 'active'])
                @else
                @include('settings.parental-controls.child-status', ['state' => 'sent_invite'])
                @endif
            </div>
            <div class="tab-pane fade" id="pills-permissions" role="tabpanel" aria-labelledby="pills-permissions-tab">
                <div class="mb-4">
                    <p class="font-weight-bold mb-1">Allowed Actions</p>

                    @include('settings.parental-controls.checkbox', ['name' => 'post', 'title' => 'Post', 'checked' => $pc->permissions['post']])
                    @include('settings.parental-controls.checkbox', ['name' => 'comment', 'title' => 'Comment', 'checked' => $pc->permissions['comment']])
                    @include('settings.parental-controls.checkbox', ['name' => 'like', 'title' => 'Like', 'checked' => $pc->permissions['like']])
                    @include('settings.parental-controls.checkbox', ['name' => 'share', 'title' => 'Share', 'checked' => $pc->permissions['share']])
                    @include('settings.parental-controls.checkbox', ['name' => 'follow', 'title' => 'Follow', 'checked' => $pc->permissions['follow']])
                    @include('settings.parental-controls.checkbox', ['name' => 'bookmark', 'title' => 'Bookmark', 'checked' => $pc->permissions['bookmark']])
                    @include('settings.parental-controls.checkbox', ['name' => 'story', 'title' => 'Add to story', 'checked' => $pc->permissions['story']])
                    @include('settings.parental-controls.checkbox', ['name' => 'collection', 'title' => 'Add to collection', 'checked' => $pc->permissions['collection']])
                </div>
                <div class="mb-4">
                    <p class="font-weight-bold mb-1">Enabled features</p>

                    @include('settings.parental-controls.checkbox', ['name' => 'discovery_feeds', 'title' => 'Discovery Feeds', 'checked' => $pc->permissions['discovery_feeds']])
                    @include('settings.parental-controls.checkbox', ['name' => 'dms', 'title' => 'Direct Messages', 'checked' => $pc->permissions['dms']])
                    @include('settings.parental-controls.checkbox', ['name' => 'federation', 'title' => 'Federation', 'checked' => $pc->permissions['federation']])
                </div>
                <div class="mb-4">
                    <p class="font-weight-bold mb-1">Preferences</p>

                    @include('settings.parental-controls.checkbox', ['name' => 'hide_network', 'title' => 'Hide my child\'s connections', 'checked' => $pc->permissions['hide_network']])
                    @include('settings.parental-controls.checkbox', ['name' => 'private', 'title' => 'Make my child\'s account private', 'checked' => $pc->permissions['private']])
                    @include('settings.parental-controls.checkbox', ['name' => 'hide_cw', 'title' => 'Hide sensitive media', 'checked' => $pc->permissions['hide_cw']])
                </div>
            </div>
            <div class="tab-pane fade" id="pills-details" role="tabpanel" aria-labelledby="pills-details-tab">
                <div>
                    <div class="form-group">
                        <label class="font-weight-bold mb-0">Email address</label>
                        <input class="form-control" name="email" value="{{ $pc->email }}" disabled>
                    </div>
                </div>
            </div>
            <div class="tab-pane fade" id="pills-actions" role="tabpanel" aria-labelledby="pills-actions-tab">
                <div class="d-flex flex-column" style="gap: 2rem;">
                    @if(!$pc->child_id && !$pc->email_verified_at)
                    <div>
                        <p class="lead font-weight-bold mb-0">Cancel Invite</p>
                        <p class="small text-muted">Cancel the child invite and prevent it from being used.</p>
                        <a class="btn btn-outline-dark px-5" href="{{ route('settings.pc.cancel-invite', ['id' => $pc->id]) }}"><i class="fas fa-user-minus mr-1"></i> Cancel Invite</a>
                    </div>
                    @else
                    <div>
                        <p class="lead font-weight-bold mb-0">Stop Managing</p>
                        <p class="small text-muted">Transition account to a regular account without parental controls.</p>
                        <a class="btn btn-outline-dark px-5" href="{{ route('settings.pc.stop-managing', ['id' => $pc->id]) }}"><i class="fas fa-user-minus mr-1"></i> Stop Managing Child</a>
                    </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

</div>
</form>
@endsection

@push('scripts')
<script type="text/javascript">
    @if(request()->has('permissions'))
    $('#pills-tab button[data-target="#pills-permissions"]').tab('show')
    @elseif(request()->has('actions'))
    $('#pills-tab button[data-target="#pills-actions"]').tab('show')
    @endif
</script>
@endpush
