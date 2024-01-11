@extends('settings.template-vue')

@section('section')
<form class="d-flex h-100 flex-column" method="post">
    @csrf
<div class="d-flex h-100 flex-column">
    <div class="d-flex justify-content-between align-items-center">
        <div class="title d-flex align-items-center" style="gap: 1rem;">
            <p class="mb-0"><a href="/settings/parental-controls"><i class="far fa-chevron-left fa-lg"></i></a></p>
            <h3 class="font-weight-bold mb-0">Add child</h3>
        </div>
    </div>

    <hr />

    <div class="d-flex flex-column flex-grow-1">
        <h4>Choose your child's policies</h4>

        <div class="mb-4">
            <p class="font-weight-bold mb-1">Allowed Actions</p>

            @include('settings.parental-controls.checkbox', ['name' => 'post', 'title' => 'Post', 'checked' => true])
            @include('settings.parental-controls.checkbox', ['name' => 'comment', 'title' => 'Comment', 'checked' => true])
            @include('settings.parental-controls.checkbox', ['name' => 'like', 'title' => 'Like', 'checked' => true])
            @include('settings.parental-controls.checkbox', ['name' => 'share', 'title' => 'Share', 'checked' => true])
            @include('settings.parental-controls.checkbox', ['name' => 'follow', 'title' => 'Follow'])
            @include('settings.parental-controls.checkbox', ['name' => 'bookmark', 'title' => 'Bookmark'])
            @include('settings.parental-controls.checkbox', ['name' => 'story', 'title' => 'Add to story'])
            @include('settings.parental-controls.checkbox', ['name' => 'collection', 'title' => 'Add to collection'])
        </div>
        <div class="mb-4">
            <p class="font-weight-bold mb-1">Enabled features</p>

            @include('settings.parental-controls.checkbox', ['name' => 'discovery_feeds', 'title' => 'Discovery Feeds'])
            @include('settings.parental-controls.checkbox', ['name' => 'dms', 'title' => 'Direct Messages'])
            @include('settings.parental-controls.checkbox', ['name' => 'federation', 'title' => 'Federation'])
        </div>
        <div class="mb-4">
            <p class="font-weight-bold mb-1">Preferences</p>

            @include('settings.parental-controls.checkbox', ['name' => 'hide_network', 'title' => 'Hide my child\'s connections'])
            @include('settings.parental-controls.checkbox', ['name' => 'private', 'title' => 'Make my child\'s account private'])
            @include('settings.parental-controls.checkbox', ['name' => 'hide_cw', 'title' => 'Hide sensitive media'])
        </div>
    </div>

    <div>
        <div class="form-group">
            <label class="font-weight-bold mb-0">Email address</label>
            <p class="help-text lh-1 small">Where should we send this invite?</p>
            <input class="form-control" placeholder="Enter your childs email address" name="email" required>
        </div>

        <button class="btn btn-dark btn-block font-weight-bold">Add Child</button>
    </div>
</div>
</form>
@endsection

