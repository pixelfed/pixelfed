@extends('settings.template')

@push('styles')
<style>
    .privacy-settings {
        max-width: 760px;
    }

    .privacy-settings-header {
        margin-bottom: 1.75rem;
    }

    .privacy-settings-header h3 {
        margin-bottom: .35rem;
    }

    .privacy-settings-header p {
        margin-bottom: 0;
        color: #6c757d;
    }

    .privacy-links {
        display: flex;
        flex-wrap: wrap;
        gap: .5rem;
        margin-bottom: 2rem;
    }

    .privacy-links .btn {
        padding: .4rem .8rem;
        border: 1px solid #dee2e6;
        border-radius: .5rem;
        font-size: .875rem;
        font-weight: 600;
        color: #495057;
        background: #fff;
        text-decoration: none;
    }

    .privacy-links .btn:hover,
    .privacy-links .btn:focus {
        color: #212529;
        background: #f8f9fa;
        border-color: #ced4da;
        text-decoration: none;
    }

    .privacy-section {
        margin-bottom: 1.25rem;
        border: 1px solid #e9ecef;
        border-radius: .65rem;
        background: #fff;
        overflow: hidden;
    }

    .privacy-section-header {
        padding: 1rem 1.25rem;
        border-bottom: 1px solid #e9ecef;
        background: #fafafa;
    }

    .privacy-section-header h5 {
        margin-bottom: .2rem;
        font-size: .95rem;
        font-weight: 700;
    }

    .privacy-section-header p {
        margin: 0;
        color: #6c757d;
        font-size: .8rem;
        line-height: 1.45;
    }

    .privacy-option {
        padding: 1.1rem 1.25rem;
        border-bottom: 1px solid #f0f0f0;
    }

    .privacy-option:last-child {
        border-bottom: 0;
    }

    .privacy-option .custom-control-label {
        cursor: pointer;
        font-size: .925rem;
        font-weight: 600;
        line-height: 1.4;
    }

    .privacy-option .custom-control-input:disabled ~ .custom-control-label {
        cursor: not-allowed;
    }

    .privacy-option-disabled {
        background: #fafafa;
    }

    .privacy-option-disabled .custom-control-label,
    .privacy-option-disabled .privacy-description {
        opacity: .65;
    }

    .privacy-description {
        margin: .3rem 0 0;
        color: #6c757d;
        font-size: .8rem;
        line-height: 1.55;
    }

    .privacy-description strong {
        color: #495057;
    }

    .privacy-select {
        padding: 1.1rem 1.25rem;
    }

    .privacy-select label {
        margin-bottom: .25rem;
        font-size: .925rem;
        font-weight: 600;
    }

    .privacy-select .form-control {
        max-width: 340px;
        height: calc(2.25rem + 2px);
        margin-top: .65rem;
        border-color: #ced4da;
        border-radius: .45rem;
    }

    .privacy-select .form-control:focus {
        border-color: #80bdff;
        box-shadow: 0 0 0 .2rem rgba(0,123,255,.1);
    }

    .atom-link {
        display: inline-flex;
        align-items: center;
        margin-top: .5rem;
        font-size: .75rem;
        font-weight: 600;
        word-break: break-all;
    }

    .privacy-save {
        display: flex;
        align-items: center;
        justify-content: flex-end;
        margin-top: 1.75rem;
        padding-top: 1.25rem;
        border-top: 1px solid #e9ecef;
    }

    .privacy-save .btn {
        min-width: 130px;
        border-radius: .45rem;
    }

    #pac_modal .modal-content {
        border: 0;
        border-radius: .65rem;
        box-shadow: 0 1rem 3rem rgba(0, 0, 0, .15);
    }

    #pac_modal .modal-header {
        padding: 1.1rem 1.25rem;
        border-bottom-color: #e9ecef;
    }

    #pac_modal .modal-title {
        font-size: 1rem;
        font-weight: 700;
    }

    #pac_modal .modal-body {
        padding: 1.25rem !important;
    }

    #pac_modal .modal-footer {
        padding: 1rem 1.25rem;
        border-top-color: #e9ecef;
    }

    #pac_modal .private-mode-option {
        padding: .65rem .75rem;
        margin-bottom: .35rem;
        border: 1px solid transparent;
        border-radius: .45rem;
    }

    #pac_modal .private-mode-option:hover {
        background: #f8f9fa;
    }

    #pac_modal select {
        margin-left: .25rem;
        padding: .1rem .25rem;
        border: 1px solid #ced4da;
        border-radius: .25rem;
        background: #fff;
    }

    @media (max-width: 575.98px) {
        .privacy-links {
            gap: .4rem;
        }

        .privacy-links .btn {
            font-size: .8rem;
        }

        .privacy-section-header,
        .privacy-option,
        .privacy-select {
            padding-left: 1rem;
            padding-right: 1rem;
        }

        .privacy-select .form-control {
            max-width: none;
        }

        .privacy-save .btn {
            width: 100%;
        }
    }
</style>
@endpush

@section('section')


<div class="privacy-settings">

    <div class="privacy-settings-header">
        <h3 class="font-weight-bold">
            {{ __('settings.privacy.privacy_settings') }}
        </h3>
        <p>Control who can interact with you and how your profile appears across Pixelfed.</p>
    </div>

    <div class="privacy-links">
        <a
            class="btn"
            href="{{ route('settings.privacy.muted-users') }}">
            {{ __('profile.mutedAccounts') }}
        </a>

        <a
            class="btn"
            href="{{ route('settings.privacy.blocked-users') }}">
            {{ __('profile.blockedAccounts') }}
        </a>

        <a
            class="btn"
            href="{{ route('settings.privacy.domain-blocks') }}">
            {{ __('profile.blockedDomains') }}
        </a>

        <a
            class="btn"
            href="{{ route('settings.privacy.featured-collections') }}">
            Featured Collections
        </a>
    </div>

    <form method="post">
        @csrf

        <input type="hidden" name="pa_mode" value="">
        <input type="hidden" name="pa_duration" value="">
        <input type="hidden" name="pa_newrequests" value="">

        {{-- Account privacy --}}
        <div class="privacy-section">
            <div class="privacy-section-header">
                <h5>Account privacy</h5>
                <p>Control who can follow your account and see your posts.</p>
            </div>

            <div class="privacy-option">
                <div class="custom-control custom-checkbox">
                    <input
                        class="custom-control-input"
                        type="checkbox"
                        name="is_private"
                        id="is_private"
                        {{ $settings->is_private ? 'checked' : '' }}>

                    <label
                        class="custom-control-label"
                        for="is_private">
                        Manually Review Follow Requests
                    </label>

                    <p class="privacy-description">
                        When you get a follow request, Pixelfed will not automatically
                        approve it. You can manually confirm or deny each request.
                        Your existing followers won't be affected unless you choose
                        otherwise.
                    </p>
                </div>
            </div>
        </div>

        {{-- Discoverability --}}
        <div class="privacy-section">
            <div class="privacy-section-header">
                <h5>Discoverability</h5>
                <p>Choose where your profile and posts can be discovered.</p>
            </div>

            <div class="privacy-option {{ $settings->is_private ? 'privacy-option-disabled' : '' }}">
                <div class="custom-control custom-checkbox">
                    <input
                        class="custom-control-input"
                        type="checkbox"
                        name="crawlable"
                        id="crawlable"
                        {{ !$settings->crawlable ? 'checked' : '' }}
                        {{ $settings->is_private ? 'disabled' : '' }}>

                    <label
                        class="custom-control-label"
                        for="crawlable">
                        {{ __('settings.privacy.disable_search_engine_indexing') }}
                    </label>

                    <p class="privacy-description">
                        {{ __('settings.privacy.when_your_account_is_visible_to_search_engines_etc') }}

                        @if($settings->is_private)
                            <strong>
                                {{ __('settings.privacy.not_available_when_your_account_is_private') }}
                            </strong>
                        @endif
                    </p>
                </div>
            </div>

            <div class="privacy-option {{ $settings->is_private ? 'privacy-option-disabled' : '' }}">
                <div class="custom-control custom-checkbox">
                    <input
                        class="custom-control-input"
                        type="checkbox"
                        name="indexable"
                        id="indexable"
                        {{ $profile->indexable ? 'checked' : '' }}
                        {{ $settings->is_private ? 'disabled' : '' }}>

                    <label
                        class="custom-control-label"
                        for="indexable">
                        {{ __('settings.privacy.include_public_posts_in_search_results') }}
                    </label>

                    <p class="privacy-description">
                        {{ __('settings.privacy.your_public_posts_may_appear_in_search_results_etc') }}

                        @if($settings->is_private)
                            <strong>
                                {{ __('settings.privacy.not_available_when_your_account_is_private') }}
                            </strong>
                        @endif
                    </p>
                </div>
            </div>

            <div class="privacy-option {{ $settings->is_private ? 'privacy-option-disabled' : '' }}">
                <div class="custom-control custom-checkbox">
                    <input
                        class="custom-control-input"
                        type="checkbox"
                        name="is_suggestable"
                        id="is_suggestable"
                        {{ auth()->user()->profile->is_suggestable ? 'checked' : '' }}
                        {{ $settings->is_private ? 'disabled' : '' }}>

                    <label
                        class="custom-control-label"
                        for="is_suggestable">
                        {{ __('settings.privacy.show_on_directory') }}
                    </label>

                    <p class="privacy-description">
                        {{ __('settings.privacy.when_this_option_is_enabled_your_profile_is_etc') }}

                        @if($settings->is_private)
                            <strong>
                                {{ __('settings.privacy.not_available_when_your_account_is_private') }}
                            </strong>
                        @endif
                    </p>
                </div>
            </div>
        </div>

        {{-- Messages --}}
        <div class="privacy-section">
            <div class="privacy-section-header">
                <h5>Messages</h5>
                <p>Control who is allowed to contact you.</p>
            </div>

            <div class="privacy-option">
                <div class="custom-control custom-checkbox">
                    <input
                        class="custom-control-input"
                        type="checkbox"
                        name="public_dm"
                        id="public_dm"
                        {{ $settings->public_dm ? 'checked' : '' }}>

                    <label
                        class="custom-control-label"
                        for="public_dm">
                        {{ __('settings.privacy.receive_direct_messages_from_anyone') }}
                    </label>

                    <p class="privacy-description">
                        {{ __('settings.privacy.if_selected_you_will_be_able_to_receive_messages_etc') }}
                    </p>
                </div>
            </div>
        </div>

        {{-- Profile --}}
        <div class="privacy-section">
            <div class="privacy-section-header">
                <h5>Profile</h5>
                <p>Choose what information is displayed publicly on your profile.</p>
            </div>

            <div class="privacy-option">
                <div class="custom-control custom-checkbox">
                    <input
                        class="custom-control-input"
                        type="checkbox"
                        name="show_profile_follower_count"
                        id="show_profile_follower_count"
                        {{ $settings->show_profile_follower_count ? 'checked' : '' }}>

                    <label
                        class="custom-control-label"
                        for="show_profile_follower_count">
                        {{ __('settings.privacy.show_follower_count') }}
                    </label>

                    <p class="privacy-description">
                        {{ __('settings.privacy.display_follower_count_on_profile') }}
                    </p>
                </div>
            </div>

            <div class="privacy-option">
                <div class="custom-control custom-checkbox">
                    <input
                        class="custom-control-input"
                        type="checkbox"
                        name="show_profile_following_count"
                        id="show_profile_following_count"
                        {{ $settings->show_profile_following_count ? 'checked' : '' }}>

                    <label
                        class="custom-control-label"
                        for="show_profile_following_count">
                        {{ __('settings.privacy.show_following_count') }}
                    </label>

                    <p class="privacy-description">
                        {{ __('settings.privacy.display_following_count_on_profile') }}
                    </p>
                </div>
            </div>
        </div>

        {{-- Sharing --}}
        <div class="privacy-section">
            <div class="privacy-section-header">
                <h5>Sharing</h5>
                <p>Manage how your profile and posts can be shared outside Pixelfed.</p>
            </div>

            <div class="privacy-option">
                <div class="custom-control custom-checkbox">
                    <input
                        class="custom-control-input"
                        type="checkbox"
                        name="disable_embeds"
                        id="disable_embeds"
                        {{ $settings->disable_embeds ? 'checked' : '' }}>

                    <label
                        class="custom-control-label"
                        for="disable_embeds">
                        {{ __('settings.privacy.disable_embeds') }}
                    </label>

                    <p class="privacy-description">
                        {{ __('settings.privacy.disable_post_and_profile_embeds') }}
                    </p>
                </div>
            </div>

            @if(!$settings->is_private)
                <div class="privacy-option">
                    <div class="custom-control custom-checkbox">
                        <input
                            class="custom-control-input"
                            type="checkbox"
                            name="show_atom"
                            id="show_atom"
                            {{ $settings->show_atom ? 'checked' : '' }}>

                        <label
                            class="custom-control-label"
                            for="show_atom">
                            {{ __('settings.privacy.enable_atom_feed') }}
                        </label>

                        <p class="privacy-description">
                            {{ __('settings.privacy.enable_your_profile_atom_feed_only_public_profiles_etc') }}
                        </p>

                        @if($settings->show_atom)
                            <a
                                href="{{ $profile->permalink('.atom') }}"
                                class="atom-link text-success"
                                target="_blank"
                                rel="noopener noreferrer">
                                {{ $profile->permalink('.atom') }}
                                <i class="fas fa-external-link-alt ml-2 text-muted"></i>
                            </a>
                        @endif
                    </div>
                </div>
            @endif
        </div>

        {{-- Featured collections --}}
        <div class="privacy-section">
            <div class="privacy-section-header">
                <h5>Featured Collections</h5>
                <p>Control whether other people can recommend your account in collections.</p>
            </div>

            <div class="privacy-select">
                <label for="can_feature">
                    Who can feature you
                </label>

                <p class="privacy-description mb-0">
                    People on other servers can add you to featured collections,
                    sometimes called starter packs, that help new users find accounts
                    to follow.
                </p>

                <select
                    class="form-control"
                    name="can_feature"
                    id="can_feature">

                    <option
                        value="everyone"
                        {{ ($settings->can_feature ?? 'everyone') === 'everyone' ? 'selected' : '' }}>
                        Everyone
                    </option>

                    <option
                        value="followers"
                        {{ ($settings->can_feature ?? 'everyone') === 'followers' ? 'selected' : '' }}>
                        People who follow you
                    </option>

                    <option
                        value="nobody"
                        {{ ($settings->can_feature ?? 'everyone') === 'nobody' ? 'selected' : '' }}>
                        Nobody
                    </option>
                </select>

                <p class="privacy-description mt-2 mb-0">
                    You can remove yourself from a collection at any time from
                    <a
                        href="{{ route('settings.privacy.featured-collections') }}"
                        class="font-weight-bold">
                        Featured Collections
                    </a>.
                </p>
            </div>
        </div>

        {{-- Quote posts --}}
        <div class="privacy-section">
            <div class="privacy-section-header">
                <h5>Quote Posts</h5>
                <p>Control whether people on other servers can quote your posts.</p>
            </div>

            <div class="privacy-select">
                <label for="can_quote">
                    Who can quote your posts
                </label>

                <p class="privacy-description mb-0">
                    This is the default for your public and unlisted posts. Apps can
                    set a different choice on individual posts. Followers-only posts
                    can never be quoted by anyone else.
                </p>

                <select
                    class="form-control"
                    name="can_quote"
                    id="can_quote">

                    <option
                        value="everyone"
                        {{ ($settings->can_quote ?? 'everyone') === 'everyone' ? 'selected' : '' }}>
                        Everyone
                    </option>

                    <option
                        value="followers"
                        {{ ($settings->can_quote ?? 'everyone') === 'followers' ? 'selected' : '' }}>
                        People who follow you
                    </option>

                    <option
                        value="nobody"
                        {{ ($settings->can_quote ?? 'everyone') === 'nobody' ? 'selected' : '' }}>
                        Nobody
                    </option>
                </select>

                <p class="privacy-description mt-2 mb-0">
                    You can review quotes of your posts and revoke any of them from
                    <a
                        href="{{ route('settings.privacy.quotes') }}"
                        class="font-weight-bold">
                        Quotes of your posts
                    </a>.
                </p>
            </div>
        </div>

        <div class="privacy-save">
            <button
                type="submit"
                class="btn btn-primary font-weight-bold px-4 py-2">
                {{ __('settings.submit') }}
            </button>
        </div>
    </form>
</div>

{{-- Private account confirmation --}}
<div
    class="modal fade"
    tabindex="-1"
    role="dialog"
    id="pac_modal"
    aria-labelledby="pac_modal_title"
    aria-hidden="true">

    <div
        class="modal-dialog modal-dialog-centered"
        role="document">

        <div class="modal-content">

            <div class="modal-header">
                <h5
                    class="modal-title"
                    id="pac_modal_title">
                    {{ __('settings.privacy.confirm_this_action') }}
                </h5>

                <button
                    type="button"
                    class="close"
                    data-dismiss="modal"
                    aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>

            <div class="modal-body">
                <p class="font-weight-bold mb-3">
                    {{ __('settings.privacy.please_select_the_type_of_private_account_you_etc') }}
                </p>

                <div class="private-mode-option">
                    <div class="custom-control custom-radio">
                        <input
                            class="custom-control-input"
                            type="radio"
                            id="fm-1"
                            name="pfType"
                            value="keep-all"
                            checked>

                        <label
                            class="custom-control-label font-weight-bold"
                            for="fm-1">
                            {{ __('settings.privacy.keep_existing_followers') }}
                        </label>
                    </div>
                </div>

                <div class="private-mode-option">
                    <div class="custom-control custom-radio">
                        <input
                            class="custom-control-input"
                            type="radio"
                            id="fm-2"
                            name="pfType"
                            value="mutual-only">

                        <label
                            class="custom-control-label font-weight-bold"
                            for="fm-2">
                            {{ __('settings.privacy.only_keep_mutual_followers') }}
                        </label>
                    </div>
                </div>

                <div class="private-mode-option">
                    <div class="custom-control custom-radio">
                        <input
                            class="custom-control-input"
                            type="radio"
                            id="fm-3"
                            name="pfType"
                            value="only-followers">

                        <label
                            class="custom-control-label font-weight-bold"
                            for="fm-3">
                            {{ __('settings.privacy.only_followers_that_have_followed_you_for_atleast') }}

                            <select
                                name="pfDuration"
                                aria-label="Follower duration">
                                <option value="60">
                                    1 {{ __('settings.privacy.hour') }}
                                </option>

                                <option value="1440">
                                    1 {{ __('settings.privacy.day') }}
                                </option>

                                <option value="20160">
                                    2 {{ __('settings.privacy.weeks') }}
                                </option>

                                <option value="43200">
                                    1 {{ __('settings.privacy.month') }}
                                </option>

                                <option value="259200">
                                    6 {{ __('settings.privacy.months') }}
                                </option>

                                <option value="525600">
                                    1 {{ __('settings.privacy.year') }}
                                </option>
                            </select>
                        </label>
                    </div>
                </div>

                <div class="private-mode-option">
                    <div class="custom-control custom-radio">
                        <input
                            class="custom-control-input"
                            type="radio"
                            id="fm-4"
                            name="pfType"
                            value="remove-all">

                        <label
                            class="custom-control-label font-weight-bold text-danger"
                            for="fm-4">
                            {{ __('settings.privacy.remove_existing_followers') }}
                        </label>
                    </div>
                </div>
            </div>

            <div class="modal-footer">
                <button
                    type="button"
                    class="btn btn-outline-secondary font-weight-bold px-3"
                    data-dismiss="modal">
                    {{ __('settings.cancel') }}
                </button>

                <button
                    type="button"
                    class="btn btn-primary font-weight-bold px-4"
                    id="modal_confirm">
                    {{ __('settings.save') }}
                </button>
            </div>

        </div>
    </div>
</div>

@endsection

@push('scripts')
<script type="text/javascript">
    $(document).ready(function() {
        let privateModeConfirmed = false;

        $('#is_private').on('change', function() {
            if(this.checked) {
                privateModeConfirmed = false;
                $('#pac_modal').modal('show');
            }
        });

        $('#pac_modal').on('hidden.bs.modal', function() {
            if(!privateModeConfirmed) {
                $('#is_private').prop('checked', false);
            }
        });

        $('#modal_confirm').on('click', function() {
            const button = $(this);
            const mode = $('input[name="pfType"]:checked').val();
            const duration = $('select[name="pfDuration"]').val();

            privateModeConfirmed = true;

            button
                .prop('disabled', true)
                .text('{{ __('settings.save') }}...');

            axios.post("{{ route('settings.privacy.account') }}", {
                mode: mode,
                duration: duration
            })
            .then(() => {
                window.location.reload();
            })
            .catch(() => {
                privateModeConfirmed = false;

                $('#is_private').prop('checked', false);
                $('#pac_modal').modal('hide');

                button
                    .prop('disabled', false)
                    .text('{{ __('settings.save') }}');

                swal(
                    '{{ __('settings.error') }}',
                    '{{ __('settings.privacy.an_error_occured_please_try_again') }}',
                    'error'
                );
            });
        });
    });
</script>
@endpush
