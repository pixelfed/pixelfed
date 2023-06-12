@extends('site.help.partial.template', ['breadcrumb'=>'Import'])

@section('section')

    <div class="title">
        <h3 class="font-weight-bold">Import</h3>
    </div>
    <hr>
    <p class="lead py-3">With the Import from Instagram feature, you can seamlessly transfer your photos, captions, and even hashtags from your Instagram account to Pixelfed, ensuring a smooth transition without losing your cherished memories or creative expressions.</p>

    <hr class="mb-4" />

    <p class="text-center font-weight-bold">How to get your export data from Instagram:</p>
    <ol class="pb-4">
        <li class="mb-2">
            <span>Follow the Instagram instructions on <strong>Downloading a copy of your data on Instagram</strong> on <a href="https://help.instagram.com/181231772500920" class="font-weight-bold">this page</a>. <strong class="text-danger small font-weight-bold">Make sure you select the JSON format</strong></span>
        </li>
        <li class="mb-2">
            <span>Wait for the email from Instagram with your download link</span>
        </li>
        <li class="mb-2">
            <span>Download your .zip export from Instagram</span>
        </li>
        <li class="mb-2">
            <span>Navigate to the <a href="/settings/import" class="font-weight-bold">Import</a> settings page</span>
        </li>
        <li class="">
            <span>Follow the instructions and import your posts 🥳</span>
        </li>
    </ol>
    <hr class="mb-4" />

    <p class="text-center font-weight-bold">Import Limits</p>

    <div class="list-group pb-4">
        <div class="list-group-item d-flex justify-content-between align-items-center">
            <div>
                <p class="font-weight-bold mb-0">Max Posts</p>
                <p class="small mb-0">The maximum imported posts allowed</p>
            </div>
            <div class="font-weight-bold">{{ config('import.instagram.limits.max_posts') == -1 ? 'Unlimited' : config('import.instagram.limits.max_posts') }}</div>
        </div>
        <div class="list-group-item d-flex justify-content-between align-items-center">
            <div>
                <p class="font-weight-bold mb-0">Max Attempts</p>
                <p class="small mb-0">The maximum import attempts allowed<br />(counted as total imports grouped by day)</p>
            </div>
            <div class="font-weight-bold">{{ config('import.instagram.limits.max_attempts') == -1 ? 'Unlimited' : config('import.instagram.limits.max_attempts') }}</div>
        </div>
        <div class="list-group-item d-flex justify-content-between align-items-center">
            <div>
                <p class="font-weight-bold mb-0">Video Imports</p>
                <p class="small mb-0">The server supports importing video posts</p>
            </div>
            <div class="font-weight-bold">{{ config('import.instagram.allow_video_posts') ? '✅' : '❌' }}</div>
        </div>
    </div>

    <hr class="mb-4" />

    <p class="text-center font-weight-bold mb-0">Import Permissions</p>
    <p class="text-center small">Who is allowed to use the Import feature</p>

    <div class="list-group">
        <div class="list-group-item d-flex justify-content-between align-items-center">
            <div>
                <p class="font-weight-bold mb-0">Only Admins</p>
                <p class="small mb-0">Only admin accounts can import</p>
            </div>
            <div class="font-weight-bold">{{ config('import.instagram.permissions.admins_only') ? '✅' : '❌' }}</div>
        </div>
        <div class="list-group-item d-flex justify-content-between align-items-center">
            <div>
                <p class="font-weight-bold mb-0">Only Admins + Following</p>
                <p class="small mb-0">Only admin accounts, or accounts they follow, can import</p>
            </div>
            <div class="font-weight-bold">{{ config('import.instagram.permissions.admin_follows_only') ? '✅' : '❌' }}</div>
        </div>
        <div class="list-group-item d-flex justify-content-between align-items-center">
            <div>
                <p class="font-weight-bold mb-0">Minimum Account Age</p>
                <p class="small mb-0">Only accounts with a minimum age in days can import</p>
            </div>
            <div class="font-weight-bold">{{ config('import.instagram.permissions.min_account_age')}}</div>
        </div>
        <div class="list-group-item d-flex justify-content-between align-items-center">
            <div>
                <p class="font-weight-bold mb-0">Minimum Follower Count</p>
                <p class="small mb-0">Only accounts with a minimum follower count can import</p>
            </div>
            <div class="font-weight-bold">{{ config('import.instagram.permissions.min_follower_count')}}</div>
        </div>
    </div>
@endsection
