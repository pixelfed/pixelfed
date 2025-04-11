<template>
<div v-if="loaded">
    <div class="header bg-primary pb-2 mt-n4">
        <div class="container-fluid">
            <div class="header-body">
                <div class="row align-items-center py-4">
                    <div class="col-lg-6 col-7">
                        <p class="display-1 text-white d-inline-block mb-0">Settings</p>
                        <p class="h3 text-white font-weight-light">Manage your server settings</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="container">
        <div class="row">
            <div class="col-12 col-md-3">
                <div class="nav-wrapper">
                    <div class="nav flex-column nav-pills" id="tabs-icons-text" role="tablist" aria-orientation="vertical">
                        <div v-for="tab in tabs" class="nav-item">
                            <a class="nav-link mb-sm-3" :class="{ active: tabIndex === tab.id }" href="#" @click.prevent="toggleTab(tab.id)">
                                <i :class="tab.icon"></i>
                                <span class="ml-2">{{ tab.title }}</span>
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-12 col-md-9">
                <div class="card shadow mt-3">
                    <div class="card-body">
                        <div class="tab-content">

                            <div v-if="tabIndex === 1" class="tab-pane fade show active">
                                <tab-header title="Settings" :saving="isSubmitting" :saved="isSubmittingTimeout" @save="handleSave('overview')" />

                                <div class="row">
                                    <div class="col-12 col-md-6">
                                        <div class="card shadow-none border card-body" style="padding: 1.1rem 1.6rem">
                                            <div class="form-group mb-0">
                                                <label for="form-summary" class="font-weight-bold">Registration Status</label>
                                                <select v-model="features.registration_status" class="form-control form-control-muted">
                                                    <option value="open" >Open - Anyone can register</option>
                                                    <option value="filtered">Filtered - Anyone can apply (Curated Onboarding)</option>
                                                    <option value="closed">Closed - Nobody can register</option>
                                                </select>
                                            </div>
                                        </div>

                                        <checkbox
                                            name="Cloud Storage"
                                            :value="features.cloud_storage"
                                            description="Store photos and videos on S3 compatible object storage providers."
                                            @change="handleChange($event, 'features', 'cloud_storage')"
                                        />

                                        <checkbox
                                            name="ActivityPub"
                                            :value="features.activitypub_enabled"
                                            description="ActivityPub federation, compatible with Pixelfed, Mastodon and other projects."
                                            @change="handleChange($event, 'features', 'activitypub_enabled')"
                                        />

                                        <checkbox
                                            name="Authorized Fetch Mode"
                                            :value="features.authorized_fetch"
                                            description="Strictly enforce domain restrictions by enabling Authorized Fetch mode."
                                            @change="handleChange($event, 'features', 'authorized_fetch')"
                                        />

                                        <checkbox
                                            name="Account Migration"
                                            :value="features.account_migration"
                                            description="Allow local accounts to migrate to other local or remote accounts."
                                            @change="handleChange($event, 'features', 'account_migration')"
                                        />
                                    </div>

                                    <div class="col-12 col-md-6">
                                        <checkbox
                                            name="Mobile APIs"
                                            :value="features.mobile_apis"
                                            description="Enable apis required for official mobile app support and 3rd party apps."
                                            @change="handleChange($event, 'features', 'mobile_apis')"
                                        />

                                        <checkbox
                                            name="Stories"
                                            :value="features.stories"
                                            description="Allow users to share federated ephemeral Stories that disappear after 24 hours."
                                            @change="handleChange($event, 'features', 'stories')"
                                        />

                                        <checkbox
                                            name="Instagram Import"
                                            :value="features.instagram_import"
                                            description="Enable users to use the <span class='font-weight-bold'>experimental</span> Instagram Import support."
                                            @change="handleChange($event, 'features', 'instagram_import')"
                                        />

                                        <!-- <checkbox
                                            name="Allowlist Mode"
                                            :value="features.activitypub_enabled"
                                            description="Permit interactions only with instances you specifically authorize, both for sending and receiving."
                                            @change="handleChange($event, 'features', 'activitypub_enabled')"
                                        /> -->

                                        <checkbox
                                            name="Spam detection"
                                            :value="features.autospam_enabled"
                                            description="Detect and remove spam from timelines using the automated Autospam detection."
                                            @change="handleChange($event, 'features', 'autospam_enabled')"
                                        />
                                    </div>
                                </div>
                            </div>

                            <div v-else-if="tabIndex === 'landing'" class="tab-pane fade show active" role="tabpanel">
                                <tab-header title="Landing" :saving="isSubmitting" :saved="isSubmittingTimeout" @save="handleSave('landing')" />

                                <div class="row">
                                   <div class="col-12 col-md-6">
                                        <div class="card shadow-none border card-body" style="padding: 1.1rem 1.6rem">
                                            <div class="form-group mb-0">
                                                <label for="form-summary" class="font-weight-bold">Admin Account</label>
                                                <select v-model="landing.current_admin" class="form-control form-control-muted">
                                                    <option disabled="" value="0">Select a designated admin</option>
                                                    <option v-for="(acct, index) in landing.admins" :key="'pfc-' + acct + index" :value="acct.profile_id">{{ acct.username }}</option>
                                                </select>
                                            </div>
                                        </div>

                                        <checkbox
                                            name="Show Directory"
                                            :value="landing.show_directory"
                                            description="Show the account directory on the landing page for guest users."
                                            @change="handleChange($event, 'landing', 'show_directory')"
                                        />
                                    </div>

                                    <div class="col-12 col-md-6">
                                        <checkbox
                                            name="Show Explore Feed"
                                            :value="landing.show_explore"
                                            description="Show the explore feed of popular posts on the landing page for guest users."
                                            @change="handleChange($event, 'landing', 'show_explore')"
                                        />
                                    </div>
                                </div>
                            </div>

                            <div v-else-if="tabIndex === 'branding'" class="tab-pane fade show active" role="tabpanel">
                                <tab-header title="Branding" :saving="isSubmitting" :saved="isSubmittingTimeout" @save="handleSave('branding')" />

                                <div class="row">
                                    <div class="col-12 col-md-8">
                                        <div class="card shadow-none border card-body" style="padding: 1.1rem 1.6rem">
                                            <div class="form-group mb-1">
                                                <label for="form-summary" class="font-weight-bold">Server Name</label>
                                                <input
                                                    class="form-control form-control-muted"
                                                    placeholder="Pixelfed"
                                                    v-model="branding.name" />
                                            </div>
                                            <p class="help-text small text-muted mb-0">
                                                The instance name used in titles, metadata and apis.
                                            </p>
                                        </div>

                                        <div class="card shadow-none border card-body">
                                            <div class="form-group mb-1">
                                                <label for="form-summary" class="font-weight-bold">Short Description</label>
                                                <textarea
                                                    class="form-control form-control-muted"
                                                    placeholder="Pixelfed"
                                                    rows="4"
                                                    v-model="branding.short_description"></textarea>
                                            </div>
                                            <p class="help-text small text-muted mb-0">
                                                Short description of instance used on various pages and apis.
                                            </p>
                                        </div>

                                        <div class="card shadow-none border card-body">
                                            <div class="form-group mb-1">
                                                <label for="form-summary" class="font-weight-bold">Long Description</label>
                                                <textarea
                                                    class="form-control form-control-muted"
                                                    placeholder="Pixelfed"
                                                    rows="8"
                                                    v-model="branding.long_description"></textarea>
                                            </div>
                                            <p class="help-text small text-muted mb-0">
                                                Longer description of instance used on about page.
                                            </p>
                                        </div>
                                    </div>
                                    <div class="col-12 col-md-4">
                                        <p>
                                            <a class="btn btn-dark btn-block" href="/i/admin/settings/custom-css">Edit Custom CSS</a>
                                        </p>
                                    </div>
                                </div>
                            </div>

                            <div v-else-if="tabIndex === 'media'" class="tab-pane fade show active" role="tabpanel">
                                <tab-header title="Media" :saving="isSubmitting" :saved="isSubmittingTimeout" @save="handleSave('media')" />

                                <div class="row">
                                    <div class="col-12 col-md-6">
                                        <div class="card shadow-none border card-body">
                                            <div class="form-group mb-1">
                                                <label class="font-weight-bold text-muted">Max Media Size</label>
                                                <div class="input-group mb-0">
                                                    <input
                                                        type="text"
                                                        class="form-control"
                                                        placeholder="15000"
                                                        aria-label="Max media size"
                                                        aria-describedby="maxMediaSize"
                                                        v-model="media.max_photo_size">
                                                    <div class="input-group-append">
                                                        <span class="input-group-text" id="maxMediaSize">= {{ maxMediaSizeToMb }}</span>
                                                    </div>
                                                </div>
                                            </div>
                                            <p class="help-text small text-muted mb-0">
                                                Maximum file upload size in KB
                                            </p>
                                        </div>

                                        <checkbox
                                            name="Optimize Images"
                                            :value="media.optimize_image"
                                            description="Enable to optimize images and generate thumbnails for local image media uploads."
                                            @change="handleChange($event, 'media', 'optimize_image')"
                                        />

                                        <checkbox
                                            name="Optimize Video"
                                            :value="media.optimize_video"
                                            description="Enable to generate video thumbnails for local video media uploads."
                                            @change="handleChange($event, 'media', 'optimize_video')"
                                        />

                                        <div class="card shadow-none border card-body">
                                            <div class="form-group mb-1">
                                                <label class="font-weight-bold text-muted">Media Types</label>

                                                <div class="list-group">
                                                    <div v-for="(mediaType, key) in mediaTypes" class="list-group-item py-2">
                                                        <div class="custom-control custom-checkbox">
                                                            <input
                                                                type="checkbox"
                                                                class="custom-control-input"
                                                                :name="key"
                                                                :id="key"
                                                                v-model="mediaTypes[key]">
                                                            <label class="custom-control-label font-weight-bold" :for="key">{{ key }}</label>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                            <p class="help-text small text-muted mb-0">
                                                Supported mime types for media uploads
                                            </p>
                                        </div>
                                    </div>
                                    <div class="col-12 col-md-6">
                                        <div class="card shadow-none border card-body">
                                            <div class="form-group mb-1">
                                                <label class="font-weight-bold text-muted">Photo Album Limit</label>
                                                <input
                                                    type="number"
                                                    min="1"
                                                    max="20"
                                                    class="form-control"
                                                    name="max_album_length"
                                                    v-model="media.max_album_length">
                                            </div>
                                            <p class="help-text small text-muted mb-0">
                                                The maximum number of photos or videos per album
                                            </p>
                                        </div>

                                        <transition name="fade">
                                            <div v-if="media.optimize_image" class="card shadow-none border card-body">
                                                <div class="form-group mb-1">
                                                    <label class="font-weight-bold text-muted">Image Quality</label>
                                                    <input
                                                        type="number"
                                                        min="20"
                                                        max="100"
                                                        class="form-control"
                                                        name="image_quality"
                                                        v-model="media.image_quality">
                                                </div>
                                                <p class="help-text small text-muted mb-0">
                                                    Image optimization quality from 0-100%.
                                                </p>
                                            </div>
                                        </transition>
                                    </div>
                                </div>
                            </div>

                            <div v-else-if="tabIndex === 'platform'" class="tab-pane fade show active" role="tabpanel">
                                <tab-header title="Platform" :saving="isSubmitting" :saved="isSubmittingTimeout" @save="handleSave('platform')" />

                                <div class="row">
                                    <div class="col-12 col-md-6">
                                        <checkbox
                                            name="Allow Profile Embeds"
                                            :value="platform.allow_profile_embeds"
                                            description="Allow anyone to embed public profiles on other websites."
                                            @change="handleChange($event, 'platform', 'allow_profile_embeds')"
                                        />

                                        <div class="card shadow-none border card-body">
                                            <div class="form-group mb-0">
                                                <div class="custom-control custom-checkbox">
                                                    <input
                                                        type="checkbox"
                                                        name="allow_app_registrations"
                                                        class="custom-control-input"
                                                        id="platform1"
                                                        :disabled="features.registration_status !== 'open'"
                                                        v-model="platform.allow_app_registration">
                                                    <label class="custom-control-label font-weight-bold" for="platform1">Allow App Registrations</label>
                                                </div>
                                                <p v-if="features.registration_status !== 'open'" class="mb-0 small text-muted">Requires open registration to be enabled.</p>
                                                <p v-else class="mb-0 small">Allow users to register via the official Pixelfed mobile application.</p>
                                            </div>
                                        </div>

                                        <checkbox
                                            name="Custom Emoji"
                                            :value="platform.custom_emoji_enabled"
                                            description="Enable federated custom emoji that is compatible with Mastodon, Pleroma and others."
                                            @change="handleChange($event, 'platform', 'custom_emoji_enabled')"
                                        />

                                        <template v-if="features.registration_status === 'open' && features.allow_app_registration">
                                            <div class="card shadow-none border card-body">
                                                <div class="form-group mb-1">
                                                    <label class="font-weight-bold text-muted">app_registration_rate_limit_attempts</label>
                                                    <input
                                                        type="number"
                                                        class="form-control"
                                                        name="app_registration_rate_limit_attempts"
                                                        v-model="platform.app_registration_rate_limit_attempts">
                                                </div>
                                                <p class="help-text small text-muted mb-0">
                                                    app_registration_rate_limit_attempts.
                                                </p>
                                            </div>
                                            <div class="card shadow-none border card-body">
                                                <div class="form-group mb-1">
                                                    <label class="font-weight-bold text-muted">app_registration_rate_limit_decay</label>
                                                    <input
                                                        type="number"
                                                        class="form-control"
                                                        name="app_registration_rate_limit_decay"
                                                        v-model="platform.app_registration_rate_limit_decay">
                                                </div>
                                                <p class="help-text small text-muted mb-0">
                                                    app_registration_rate_limit_decay
                                                </p>
                                            </div>
                                        </template>
                                    </div>

                                    <div class="col-12 col-md-6">
                                        <checkbox
                                            name="Allow Post Embeds"
                                            :value="platform.allow_post_embeds"
                                            description="Allow anyone to embed public posts on other websites."
                                            @change="handleChange($event, 'platform', 'allow_post_embeds')"
                                        />

                                        <div class="card shadow-none border card-body">
                                            <div class="form-group mb-1">
                                                <div class="custom-control custom-checkbox">
                                                    <input
                                                        type="checkbox"
                                                        name="hcaps"
                                                        class="custom-control-input"
                                                        id="hcp"
                                                        v-model="platform.captcha_enabled">
                                                    <label class="custom-control-label font-weight-bold" for="hcp">Enable hCaptcha</label>
                                                </div>
                                            </div>
                                            <template v-if="platform.captcha_enabled">
                                                <hr class="my-2">
                                                <div class="row">
                                                    <div class="col-12 col-md-6">
                                                        <div class="form-group my-1">
                                                            <label class="text-muted small">hCaptcha Secret</label>
                                                            <input
                                                                type="text"
                                                                class="form-control"
                                                                name="captcha_secret"
                                                                v-model="platform.captcha_secret">
                                                        </div>
                                                    </div>
                                                    <div class="col-12 col-md-6">
                                                        <div class="form-group my-1">
                                                            <label class="text-muted small">hCaptcha Sitekey</label>
                                                            <input
                                                                type="text"
                                                                class="form-control"
                                                                name="captcha_sitekey"
                                                                v-model="platform.captcha_sitekey">
                                                        </div>
                                                    </div>
                                                </div>
                                                <hr class="mt-2 mb-4">
                                                <div class="row">
                                                    <div class="col-12 col-lg-6">
                                                        <div class="custom-control custom-checkbox">
                                                            <input
                                                                type="checkbox"
                                                                name="captcha_on_login"
                                                                class="custom-control-input"
                                                                id="captcha_on_login"
                                                                v-model="platform.captcha_on_login">
                                                            <label class="custom-control-label font-weight-bold" for="captcha_on_login">Login Captcha</label>
                                                        </div>
                                                    </div>
                                                    <div class="col-12 col-lg-6">
                                                        <div class="custom-control custom-checkbox">
                                                            <input
                                                                type="checkbox"
                                                                name="captcha_on_register"
                                                                class="custom-control-input"
                                                                id="captcha_on_register"
                                                                v-model="platform.captcha_on_register">
                                                            <label class="custom-control-label font-weight-bold" for="captcha_on_register">Register Captcha</label>
                                                        </div>
                                                    </div>
                                                </div>
                                                <hr class="mt-4 mb-2">
                                            </template>
                                            <p class="help-text small text-muted mb-0">
                                                Enable hCaptcha on login and register pages
                                            </p>
                                        </div>

                                        <template v-if="features.registration_status === 'open' && features.allow_app_registration">
                                            <div class="card shadow-none border card-body">
                                                <div class="form-group mb-1">
                                                    <label class="font-weight-bold text-muted">app_registration_confirm_rate_limit_attempts</label>
                                                    <input
                                                        type="number"
                                                        class="form-control"
                                                        name="app_registration_confirm_rate_limit_attempts"
                                                        v-model="platform.app_registration_confirm_rate_limit_attempts">
                                                </div>
                                                <p class="help-text small text-muted mb-0">
                                                    app_registration_confirm_rate_limit_attempts.
                                                </p>
                                            </div>
                                            <div class="card shadow-none border card-body">
                                                <div class="form-group mb-1">
                                                    <label class="font-weight-bold text-muted">app_registration_confirm_rate_limit_decay</label>
                                                    <input
                                                        type="number"
                                                        class="form-control"
                                                        name="app_registration_confirm_rate_limit_decay"
                                                        v-model="platform.app_registration_confirm_rate_limit_decay">
                                                </div>
                                                <p class="help-text small text-muted mb-0">
                                                    app_registration_confirm_rate_limit_decay.
                                                </p>
                                            </div>
                                        </template>
                                    </div>
                                </div>
                            </div>

                            <div v-else-if="tabIndex === 'posts'" class="tab-pane fade show active" role="tabpanel">
                                <tab-header title="Posts" :saving="isSubmitting" :saved="isSubmittingTimeout" @save="handleSave('posts')" />

                                <div class="row">
                                    <div class="col-12 col-md-6">
                                        <div class="card shadow-none border card-body">
                                            <div class="form-group mb-1">
                                                <label class="font-weight-bold text-muted">Max Caption Length</label>
                                                <input
                                                    type="number"
                                                    min="1"
                                                    max="10000"
                                                    class="form-control"
                                                    name="max_caption_limit"
                                                    v-model="posts.max_caption_length">
                                            </div>
                                            <p class="help-text small text-muted mb-0">
                                                The maximum character count of post captions. We recommend a limit between 500-2000.
                                            </p>
                                        </div>
                                    </div>

                                    <div class="col-12 col-md-6">
                                        <div class="card shadow-none border card-body">
                                            <div class="form-group mb-1">
                                                <label class="font-weight-bold text-muted">Max Alttext Length</label>
                                                <input
                                                    type="number"
                                                    min="1"
                                                    max="10000"
                                                    class="form-control"
                                                    name="max_altext_length"
                                                    v-model="posts.max_altext_length">
                                            </div>
                                            <p class="help-text small text-muted mb-0">
                                                The maximum character count of post media alttext captions. We recommend a limit between 2000-10000.
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div v-else-if="tabIndex === 'rules'" class="tab-pane fade show active" role="tabpanel">
                                <tab-header title="Rules" :saving="isSubmitting" :saved="isSubmittingTimeout" @save="handleSave('rules')" />

                                <div class="row">
                                    <div class="col-12 mb-3">
                                        <div v-if="hasDuplicateRulesComputed" class="alert alert-danger">
                                            <p class="font-weight-bold mb-0">Duplicate rules detected, you should fix this!</p>
                                        </div>
                                        <div class="position-relative">
                                            <div class="card shadow-none border">
                                                <div class="card-header py-2 bg-primary text-white font-weight-bold text-center">Active Rules</div>
                                                <div class="list-group list-group-flush">
                                                    <div
                                                        v-for="(rule, idx) in rulesComputed"
                                                        class="list-group-item">
                                                        <div class="d-flex justify-content-between align-items-start">
                                                            <div class="d-flex gap-1 align-items-start">
                                                                <div class="rule-badge">
                                                                    <div class="rule-badge-inner">{{ idx + 1 }}</div>
                                                                </div>
                                                                <admin-read-more
                                                                    :key="rule"
                                                                    class="text-dark rule-text"
                                                                    :content="rule"
                                                                    :maxLength="140"
                                                                    :initialLimit="30"
                                                                    fontSize="13" />
                                                            </div>

                                                            <button
                                                                class="btn btn-link btn-sm"
                                                                :disabled="isDeletingRule"
                                                                @click.prevent="handleDeleteRule(rule, idx, $event)">
                                                                <i class="fas fa-trash-alt text-danger"></i>
                                                            </button>
                                                        </div>
                                                    </div>


                                                    <div v-if="!rules || !rules.length" class="list-group-item">
                                                        <p class="text-center mb-0">No rules set!</p>
                                                    </div>
                                                </div>
                                            </div>
                                            <div v-if="!showAllRules && rules.length > 2" class="d-flex justify-content-center" style="position:absolute;width: 100%;padding-top: 10rem;bottom:0;background: linear-gradient(to bottom, rgba(255,255,255,0), rgba(255,255,255, 1));">
                                                <button class="btn btn-dark font-weight-bold rounded-pill btn-block" @click.prevent="showAllRules = true">Show all rules</button>
                                        </div>
                                        </div>
                                    </div>

                                    <div class="col-12 col-md-6">
                                        <div class="card shadow-none border card-body">
                                            <div class="form-group mb-1">
                                                <label class="font-weight-bold text-muted">Add New Rule</label>
                                                <textarea
                                                    type="text"
                                                    class="form-control"
                                                    name="new_rule"
                                                    rows="5"
                                                    minlength="5"
                                                    maxlength="1000"
                                                    placeholder="Add your new rule here..."
                                                    :disabled="isSubmittingNewRule || isDeletingRule"
                                                    v-model="newRule"></textarea>
                                            </div>
                                            <div class="d-flex justify-content-between align-items-center">
                                                <p class="help-text small text-muted mb-0">
                                                    Add a new rule
                                                </p>
                                                <p class="help-text small text-muted mb-0">
                                                    {{ newRule && newRule.length ? newRule.length : 0 }}/1000
                                                </p>
                                            </div>
                                            <hr class="my-2">
                                            <p class="mb-0">
                                                <button
                                                    class="btn btn-primary btn-sm btn-block font-weight-bold rounded-pill"
                                                    :disabled="!newRule || !newRule.length || isSubmittingNewRule || isDeletingRule"
                                                    @click.prevent="handleAddRule">Add Rule</button>
                                            </p>
                                        </div>

                                        <button v-if="rules && rules.length" class="btn btn-outline-danger rounded-pill btn-block btn-sm" @click.prevent="handleDeleteAllRules">Delete all rules</button>
                                    </div>

                                    <div v-if="suggestedRulesComputed && suggestedRulesComputed.length" class="col-12 col-md-6">
                                        <div class="border-bottom pb-2 mb-3 d-flex justify-content-between align-items-center">
                                            <p class="font-weight-bold mb-0">Suggested Rules</p>
                                            <a v-if="!rules.length" class="font-weight-bold small" href="#" @click.prevent="importAllDefaultRules">Import All</a>
                                        </div>

                                        <div class="list-group">
                                            <a
                                                v-for="rule in suggestedRulesComputed"
                                                class="list-group-item small"
                                                href="#"
                                                @click.prevent="addSuggestedRule(rule, $event)">{{ rule }}</a>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div v-else-if="tabIndex === 'storage'" class="tab-pane fade show active" role="tabpanel">
                                <tab-header title="Storage" :saving="isSubmitting" :saved="isSubmittingTimeout" @save="handleSave('storage')" />

                                <div class="row">
                                    <div class="col-12 col-md-6">
                                        <div class="card shadow-none border card-body" style="padding: 1.1rem 1.6rem">
                                            <div class="form-group mb-0">
                                                <label for="form-summary" class="font-weight-bold">Primary Storage Disk</label>
                                                <select v-model="storage.primary_disk" class="form-control form-control-muted">
                                                    <option value="local" >Local</option>
                                                    <option value="cloud">Cloud/S3</option>
                                                </select>
                                            </div>
                                            <p class="help-text small text-muted mt-2 mb-0">
                                                The storage disk where avatars and media uploads are stored.
                                            </p>
                                        </div>
                                    </div>

                                    <div class="col-12 col-md-6">
                                        <div class="card border">
                                            <div class="card-header bg-gradient-primary">
                                                <p class="text-center mb-0 text-white font-weight-bold">Cloud Disk Config</p>
                                            </div>

                                            <div v-if="!showDiskConfig" class="card-body">
                                                <p class="text-center mb-0">
                                                    <a
                                                        class="btn btn-primary bg-gradient-primary shadow-lg rounded-pill"
                                                        href="#"
                                                        @click.prevent="showDiskConfig = true">
                                                        View/Edit
                                                    </a>
                                                </p>
                                            </div>
                                            <div v-else class="card-body">
                                                <div class="form-group mb-4 d-flex align-items-center gap-1">
                                                    <label for="form-summary" class="font-weight-bold mb-0">Disk</label>
                                                    <select v-model="storage.disk_config.driver" class="form-control form-control-muted mb-0">
                                                        <option value="s3" >S3</option>
                                                        <option value="spaces">DigitalOcean Spaces</option>
                                                    </select>
                                                </div>

                                                <form-input
                                                    name="Key"
                                                    :value="storage.disk_config.key"
                                                    description=""
                                                    :isCard="false"
                                                    :isInline="true"
                                                    @change="handleSubChange($event, 'storage', 'disk_config', 'key')"
                                                />
                                                <form-input
                                                    name="Secret"
                                                    :value="storage.disk_config.secret"
                                                    description=""
                                                    :isCard="false"
                                                    :isInline="true"
                                                    @change="handleSubChange($event, 'storage', 'disk_config', 'secret')"
                                                />
                                                <form-input
                                                    name="Region"
                                                    :value="storage.disk_config.region"
                                                    description=""
                                                    :isCard="false"
                                                    :isInline="true"
                                                    @change="handleSubChange($event, 'storage', 'disk_config', 'region')"
                                                />
                                                <form-input
                                                    name="Bucket"
                                                    :value="storage.disk_config.bucket"
                                                    description=""
                                                    :isCard="false"
                                                    :isInline="true"
                                                    @change="handleSubChange($event, 'storage', 'disk_config', 'bucket')"
                                                />
                                                <form-input
                                                    name="Endpoint"
                                                    :value="storage.disk_config.endpoint"
                                                    description=""
                                                    :isCard="false"
                                                    :isInline="true"
                                                    @change="handleSubChange($event, 'storage', 'disk_config', 'endpoint')"
                                                />
                                                <form-input
                                                    name="Visibility"
                                                    :value="storage.disk_config.visibility"
                                                    description=""
                                                    :isCard="false"
                                                    :isInline="true"
                                                    :isDisabled="true"
                                                    @change="handleSubChange($event, 'storage', 'disk_config', 'visibility')"
                                                />
                                                <form-input
                                                    name="Url"
                                                    :value="storage.disk_config.url"
                                                    description=""
                                                    :isCard="false"
                                                    :isInline="true"
                                                    @change="handleSubChange($event, 'storage', 'disk_config', 'url')"
                                                />
                                            </div>
                                        </div>

                                    </div>
                                </div>
                            </div>

                            <div v-else-if="tabIndex === 'users'" class="tab-pane fade show active" role="tabpanel">
                                <tab-header title="Users" :saving="isSubmitting" :saved="isSubmittingTimeout" @save="handleSave('users')" />

                                <div class="row">
                                    <div class="col-12 col-md-6">
                                        <checkbox
                                            name="Require Email Verifications"
                                            :value="users.require_email_verification"
                                            description="Require users to verify their email address is valid before they can use the account."
                                            @change="handleChange($event, 'users', 'require_email_verification')"
                                        />

                                        <form-input
                                            name="Max User Blocks"
                                            :value="users.max_user_blocks.toString()"
                                            description="The max number of account blocks per user."
                                            @change="handleChange($event, 'users', 'max_user_blocks')"
                                        />

                                        <form-input
                                            name="Max User Mutes"
                                            :value="users.max_user_mutes.toString()"
                                            description="The max number of account mutes per user."
                                            @change="handleChange($event, 'users', 'max_user_mutes')"
                                        />

                                        <form-input
                                            name="Max User Domain Blocks"
                                            :value="users.max_domain_blocks.toString()"
                                            description="The max number of domain blocks per user."
                                            @change="handleChange($event, 'users', 'max_domain_blocks')"
                                        />
                                    </div>

                                    <div class="col-12 col-md-6">
                                        <div class="card shadow-none border card-body">
                                            <div class="form-group mb-0">
                                                <div class="custom-control custom-checkbox">
                                                    <input type="checkbox" name="enforce_account_limit" class="custom-control-input" id="users2" v-model="users.enforce_account_limit">
                                                    <label class="custom-control-label font-weight-bold" for="users2">Enforce Account Limit</label>
                                                </div>
                                                <p class="mb-0 small">Set a storage limit per user account for all uploaded media (photo + video).</p>
                                            </div>
                                            <transition name="fade">
                                            <div v-if="users.enforce_account_limit">
                                                <hr class="my-2">
                                                <div class="form-group mb-1">
                                                    <div class="input-group mb-0">
                                                        <input
                                                            type="text"
                                                            class="form-control"
                                                            placeholder="15000"
                                                            aria-label="Max account size"
                                                            aria-describedby="maxMediaSize"
                                                            v-model="users.max_account_size">
                                                        <div class="input-group-append">
                                                            <span class="input-group-text">= {{maxAccountSizeToMb }}</span>
                                                        </div>
                                                    </div>
                                                </div>
                                                <p class="help-text small text-muted mb-0">
                                                    Maximum file storage limit per user account.
                                                </p>
                                            </div>
                                            </transition>
                                        </div>

                                        <div class="card shadow-none border">
                                            <div class="card-body">
                                                <div class="form-group mb-0">
                                                    <div class="custom-control custom-checkbox">
                                                        <input type="checkbox" name="admin_autofollow" class="custom-control-input" id="users4" v-model="users.admin_autofollow">
                                                        <label class="custom-control-label font-weight-bold" for="users4">Autofollow Accounts</label>
                                                    </div>
                                                    <p class="mb-0 small">Force new accounts to follow accounts you specify below</p>
                                                </div>
                                            </div>
                                            <transition name="fade">
                                                <div v-if="users.admin_autofollow" class="list-group list-group-flush">
                                                    <div v-if="users.admin_autofollow_accounts?.length">
                                                        <div v-for="user in users.admin_autofollow_accounts" class="list-group-item">
                                                            <div class="d-flex justify-content-between align-items-center">
                                                                <p class="font-weight-bold mb-0">&commat;{{ user }}</p>
                                                                <button class="btn btn-link p-0" @click.prevent="removeAutofollow(user, $event)"><i class="fas fa-trash-alt text-danger"></i></button>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div v-else class="list-group-item">
                                                        <p class="text-center mb-0">No autofollow accounts active.</p>
                                                    </div>
                                                </div>
                                            </transition>
                                            <transition name="fade">
                                                <div v-if="users.admin_autofollow && (users.admin_autofollow_accounts && users.admin_autofollow_accounts.length < 5)" class="card-footer">
                                                    <button
                                                        class="btn btn-primary btn-block rounded-pill"
                                                        @click.prevent="addAutofollow">Add Autofollow Account</button>
                                                </div>
                                            </transition>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<div v-else>
    <div class="container my-5 py-5 text-center">
        <div class="spinner-border text-primary" role="status">
            <span class="sr-only">Loading...</span>
        </div>
    </div>
</div>
</template>

<script type="text/javascript">
    import AdminReadMore from "./partial/AdminReadMore.vue";
    import AdminSettingsTabHeader from "./partial/AdminSettingsTabHeader.vue";
    import Checkbox from "./partial/AdminSettingsCheckbox.vue";
    import FormInput from "./partial/AdminSettingsInput.vue";

    export default {
        components: {
            "admin-read-more": AdminReadMore,
            "tab-header": AdminSettingsTabHeader,
            "checkbox": Checkbox,
            "form-input": FormInput
        },

        data() {
            return {
                loaded: false,
                initialData: {},
                tabIndex: 1,
                tabbies: [
                    'landing',
                    'branding',
                    'media',
                    'posts',
                    'platform',
                    'rules',
                    'users',
                    'storage'
                ],
                tabs: [
                    { id: 1, title: "Overview", icon: "far fa-home" },
                    // { id: 2, title: "Status", icon: "far fa-asterisk" },
                    { id: 'landing', title: "Landing", icon: "far fa-info-circle" },
                    { id: 'branding', title: "Branding", icon: "far fa-user-crown" },
                    { id: 'media', title: "Media", icon: "far fa-image" },
                    { id: 'platform', title: "Platform", icon: "far fa-database" },
                    { id: 'posts', title: "Posts", icon: "far fa-heart" },
                    { id: 'rules', title: "Rules", icon: "far fa-eye-slash" },
                    { id: 'storage', title: "Storage", icon: "far fa-hdd" },
                    { id: 'users', title: "Users", icon: "far fa-users" },
                ],

                isSubmitting: false,
                isSubmittingTimeout: false,
                isSubmittingTimeoutHandler: undefined,

                features: [],
                landing: {
                    current_admin: 0,
                },
                branding: [],
                media: [],
                mediaTypes: {
                    jpeg: false,
                    png: false,
                    gif: false,
                    webp: false,
                    avif: false,
                    heic: false,
                    mp4: false,
                    mov: false,
                },
                rules: [],
                users: [],
                posts: [],
                platform: [],
                storage: [],
                newRule: undefined,
                isSubmittingNewRule: false,
                isDeletingRule: false,
                suggestedRules: [],
                hasDuplicateRules: false,
                showAllRules: false,
                showDiskConfig: false,
            }
        },

        computed: {
            maxMediaSizeToMb: {
                get() {
                    if(!this.media || !this.media.max_photo_size) {
                        return '0.00 MB';
                    }

                    return (this.media.max_photo_size / 1000).toFixed(2) + ' MB';
                }
            },

            maxAccountSizeToMb: {
                get() {
                    if(!this.users || !this.users.max_account_size) {
                        return '0.00 MB';
                    }

                    const mb = (this.users.max_account_size / 1024);

                    if(mb > 1000000) {
                        return (mb / 1000000).toFixed(1) + 'TB';
                    }

                    if(mb > 1000) {
                        return (mb / 1024).toFixed(2) + 'GB';
                    }
                    return (this.users.max_account_size / 1024).toFixed(2) + ' MB';
                }
            },

            rulesComputed: {
                get() {
                    if(!this.rules || !this.rules.length) {
                        return [];
                    }

                    if(this.rules.length > 2) {
                        if(!this.showAllRules) {
                            return this.rules.slice(0, 2);
                        }
                    }
                    return this.rules;
                }
            },

            suggestedRulesComputed: {
                get() {
                    if(!this.rules || !this.rules.length) {
                        return this.suggestedRules;
                    }

                    return this.suggestedRules.filter(rule => {
                        if(this.rules.includes(rule)) {
                            return false;
                        }

                        return true;
                    });
                }
            },

            hasDuplicateRulesComputed: {
                get() {
                    if(!this.rules || !this.rules.length) {
                        return false;
                    }
                    const array = this.rules;
                    const duplicates = array.filter((item, index) => array.indexOf(item) !== index);

                    return duplicates.length;
                }
            },

            activeMediaTypes: {
                get() {
                    let res = '';

                    if(this.mediaTypes.jpeg) {
                        res += 'image/jpeg,'
                    }

                    if(this.mediaTypes.png) {
                        res += 'image/png,'
                    }

                    if(this.mediaTypes.gif) {
                        res += 'image/gif,'
                    }

                    if(this.mediaTypes.webp) {
                        res += 'image/webp,'
                    }

                    if(this.mediaTypes.avif) {
                        res += 'image/avif,'
                    }

                    if(this.mediaTypes.heic) {
                        res += 'image/heic,'
                    }

                    if(this.mediaTypes.mp4) {
                        res += 'video/mp4,'
                    }

                    if(this.mediaTypes.mov) {
                        res += 'video/mov,'
                    }

                    if(res.endsWith(',')) {
                        res = res.slice(0, -1);
                    }
                    return res;
                }
            }
        },

        mounted() {
            this.fetchInitialData();

            const params = new URL(window.location.href);

            if(params.searchParams.has('t')) {
                const tab = params.searchParams.get('t');
                if(this.tabbies.includes(tab)) {
                    this.tabIndex = tab;
                } else {
                    window.history.pushState(null, null, '/i/admin/settings')
                }
            }
        },

        methods: {
            toggleTab(idx) {
                clearTimeout(this.isSubmittingTimeoutHandler)
                this.isSubmittingTimeout = false;
                this.tabIndex = idx;
                this.showAllRules = false;
                if(this.tabbies.includes(idx)) {
                    window.history.pushState(null, null, '/i/admin/settings?t=' + idx);
                } else {
                    window.history.pushState(null, null, '/i/admin/settings');
                }
            },

            fetchInitialData() {
                axios.get('/i/admin/api/settings/fetch')
                .then(res => {
                    this.initialData = res.data;

                    this.features = res.data.features;
                    this.landing = res.data.landing;
                    this.branding = res.data.branding;
                    this.media = res.data.media;
                    this.setMediaTypes();
                    this.rules = res.data.rules;
                    this.users = res.data.users;
                    this.suggestedRules = res.data['suggested_rules'];
                    this.posts = res.data.posts;
                    this.platform = res.data.platform;
                    this.storage = res.data.storage;
                })
                .then(() => {
                    this.loaded = true;
                })
            },

            setMediaTypes() {
                const types = this.media.media_types.split(',');
                if(types && types.length) {
                    types.forEach((type) => {
                        let mime = type.split('/')[1];
                        if(['jpeg', 'png', 'gif', 'webp', 'avif', 'heic', 'mp4', 'mov'].includes(mime)) {
                            this.mediaTypes[mime] = true;
                        }
                    })
                }
            },

            formatCount(c) {
                return window.App.util.format.count(c);
            },

            formatDateTime(ts) {
                let date = new Date(ts);
                return new Intl.DateTimeFormat('en-US', {dateStyle: 'medium', timeStyle: 'short'}).format(date);
            },

            formatDate(ts) {
                let date = new Date(ts);
                return new Intl.DateTimeFormat('en-US', {month: 'short', year: 'numeric'}).format(date);
            },

            formatTimestamp(ts) {
                return window.App.util.format.timeAgo(ts);
            },

            handleSave(type) {
                this.isSubmitting = true;
                switch(type) {
                    case 'overview':
                        return this.saveHome();
                    break;
                    case 'landing':
                        return this.saveLanding();
                    break;
                    case 'branding':
                        return this.saveBranding();
                    break;
                    case 'posts':
                        return this.savePosts();
                    break;
                    case 'media':
                        return this.saveMedia();
                    break;
                    case 'platform':
                        return this.savePlatform();
                    break;
                    case 'users':
                        return this.saveUsers();
                    break;
                    case 'storage':
                        return this.saveStorage();
                    break;
                }
            },

            handleAddRule($event) {
                $event.currentTarget?.blur();
                this.isSubmittingNewRule = true;

                axios.post('/i/admin/api/settings/rules/add', {
                    rule: this.newRule
                }).then(res => {
                    this.rules.push(this.newRule);
                    this.newRule = undefined;
                    this.isSubmittingNewRule = false;
                    this.showAllRules = true;
                })
                .catch(err => {
                    if(err.response.data && err.response.data?.message) {
                        swal('Error', err.response.data.message, 'error');
                    }
                    this.isSubmittingNewRule = false;
                })
            },

            addSuggestedRule(rule, $event) {
                $event.currentTarget?.blur();

                this.newRule = rule;
            },

            importAllDefaultRules($event) {
                $event.currentTarget?.blur();
                this.isSubmittingNewRule = true;
                this.showAllRules = true;
                for (var i = this.suggestedRules.length - 1; i >= 0; i--) {
                    const rule = this.suggestedRules[i]
                    setTimeout(() => {
                        axios.post('/i/admin/api/settings/rules/add', {
                            rule: rule
                        }).then(res => {
                            this.rules.push(rule);
                        })
                    }, (i * 300))
                }
                this.isSubmittingNewRule = false;
            },

            handleDeleteRule(rule, idx, $event) {
                $event.currentTarget?.blur();
                this.isDeletingRule = true;

                axios.post('/i/admin/api/settings/rules/delete', {
                    rule: rule,
                }).then(res => {
                    this.isDeletingRule = false;
                    this.rules = res.data;
                })
                .catch(err => {

                })
            },

            handleDeleteAllRules($event) {
                $event.currentTarget?.blur();
                this.isDeletingRule = true;

                swal({
                    title: 'Confirm',
                    text: 'Are you sure you want to delete all rules?',
                    buttons: true,
                    dangerMode: true,
                }).then(res => {
                    if(res === true) {
                        axios.post('/i/admin/api/settings/rules/delete/all')
                        .then(res => {
                            this.isDeletingRule = false;
                            this.rules = []
                        })
                        .catch(err => {

                        })
                    } else {
                        this.isDeletingRule = false;
                    }
                })
            },

            removeAutofollow(username, $event) {
                $event.currentTarget?.blur();

                axios.post('/i/admin/api/settings/autofollow/delete', {
                    username: username
                }).then(res => {
                    this.users.admin_autofollow_accounts = res.data.accounts;
                }).catch(err => {
                    swal("Oops!", "An error occurred, please try again later!", "error");
                });
            },

            addAutofollow($event) {
                $event.currentTarget?.blur();

                swal({
                    text: 'Enter account username',
                    content: "input",
                    button: {
                        text: "Add Autofollow",
                        closeModal: false,
                    },
                }).then(username => {
                    if (!username) throw null;

                    axios.post('/i/admin/api/settings/autofollow/add', {
                        username: username
                    })
                    .then(res => {
                        if(!res.data.accounts.map(acc => acc.toLowerCase()).includes(username.toLowerCase())) {
                            swal("Oops!", "The account you attempted to add does not exist or cannot be added!", "error");
                        }
                        this.users.admin_autofollow_accounts = res.data.accounts;
                        swal.stopLoading();
                        swal.close();
                    })
                    .catch(err => {
                        if(err.response.data && err.response.data.message) {
                            swal('Error', err.response.data.message, 'error');
                        } else {
                            swal("Oops!", "The account you attempted to add does not exist or cannot be added!", "error");
                        }
                        swal.stopLoading();
                        swal.close();
                    });
                })

            },

            saveHome() {
                axios.post('/i/admin/api/settings/update/home', {
                    registration_status: this.features.registration_status,
                    cloud_storage: this.features.cloud_storage,
                    activitypub_enabled: this.features.activitypub_enabled,
                    account_migration: this.features.account_migration,
                    mobile_apis: this.features.mobile_apis,
                    stories: this.features.stories,
                    instagram_import: this.features.instagram_import,
                    autospam_enabled: this.features.autospam_enabled,
                    authorized_fetch: this.features.authorized_fetch,
                }).then(res => {
                    this.isSubmitting = false;
                    this.isSubmittingTimeout = true;
                    this.isSubmittingTimeoutHandler = setTimeout(() => {
                        this.isSubmittingTimeout = false;
                    }, 4000);
                })
            },

            saveLanding() {
                axios.post('/i/admin/api/settings/update/landing', {
                    current_admin: this.landing.current_admin,
                    show_directory: this.landing.show_directory,
                    show_explore: this.landing.show_explore
                }).then(res => {
                    this.isSubmitting = false;
                    this.isSubmittingTimeout = true;
                    this.isSubmittingTimeoutHandler = setTimeout(() => {
                        this.isSubmittingTimeout = false;
                    }, 4000);
                })
            },

            saveBranding() {
                axios.post('/i/admin/api/settings/update/branding', {
                    name: this.branding.name,
                    short_description: this.branding.short_description,
                    long_description: this.branding.long_description
                }).then(res => {
                    this.isSubmitting = false;
                    this.isSubmittingTimeout = true;
                    this.isSubmittingTimeoutHandler = setTimeout(() => {
                        this.isSubmittingTimeout = false;
                    }, 4000);
                })
            },

            savePosts() {
                axios.post('/i/admin/api/settings/update/posts', {
                    max_caption_length: this.posts.max_caption_length,
                    max_altext_length: this.posts.max_altext_length,
                }).then(res => {
                    this.posts = res.data;
                    this.isSubmitting = false;
                    this.isSubmittingTimeout = true;
                    this.isSubmittingTimeoutHandler = setTimeout(() => {
                        this.isSubmittingTimeout = false;
                    }, 4000);
                })
                .catch(err => {
                    this.isSubmitting = false;
                    if(err.response.data && err.response.data.message) {
                        swal('Error', err.response.data.message, 'error');
                    } else {
                        swal('Oops!', 'An error occured', 'error');
                    }
                })
            },

            saveMedia() {
                axios.post('/i/admin/api/settings/update/media', {
                    image_quality: this.media.image_quality,
                    max_album_length: this.media.max_album_length,
                    max_photo_size: this.media.max_photo_size,
                    media_types: this.activeMediaTypes,
                    optimize_image: this.media.optimize_image,
                    optimize_video: this.media.optimize_video,
                }).then(res => {
                    this.isSubmitting = false;
                    this.isSubmittingTimeout = true;
                    this.isSubmittingTimeoutHandler = setTimeout(() => {
                        this.isSubmittingTimeout = false;
                    }, 4000);
                }).catch(err => {
                    this.isSubmitting = false;
                    if(err.response.data && err.response.data.message) {
                        swal('Error', err.response.data.message, 'error');
                    } else {
                        swal('Oops!', 'An error occured', 'error');
                    }
                })
            },

            savePlatform() {
                axios.post('/i/admin/api/settings/update/platform', {
                    allow_app_registration: this.platform.allow_app_registration,
                    app_registration_rate_limit_attempts: this.platform.app_registration_rate_limit_attempts,
                    app_registration_rate_limit_decay: this.platform.app_registration_rate_limit_decay,
                    app_registration_confirm_rate_limit_attempts: this.platform.app_registration_confirm_rate_limit_attempts,
                    app_registration_confirm_rate_limit_decay: this.platform.app_registration_confirm_rate_limit_decay,
                    allow_post_embeds: this.platform.allow_post_embeds,
                    allow_profile_embeds: this.platform.allow_profile_embeds,
                    captcha_enabled: this.platform.captcha_enabled,
                    captcha_secret: this.platform.captcha_secret,
                    captcha_sitekey: this.platform.captcha_sitekey,
                    captcha_on_login: this.platform.captcha_on_login,
                    captcha_on_register: this.platform.captcha_on_register,
                    custom_emoji_enabled: this.platform.custom_emoji_enabled,
                }).then(res => {
                    this.platform = res.data;
                    this.isSubmitting = false;
                    this.isSubmittingTimeout = true;
                    this.isSubmittingTimeoutHandler = setTimeout(() => {
                        this.isSubmittingTimeout = false;
                    }, 4000);
                })
                .catch(err => {
                    this.isSubmitting = false;
                    if(err.response.data && err.response.data.message) {
                        swal('Error', err.response.data.message, 'error');
                    } else {
                        swal('Oops!', 'An error occured', 'error');
                    }
                })
            },

            saveUsers() {
                axios.post('/i/admin/api/settings/update/users', {
                    require_email_verification: this.users.require_email_verification,
                    enforce_account_limit: this.users.enforce_account_limit,
                    max_account_size: this.users.max_account_size,
                    admin_autofollow: this.users.admin_autofollow,
                    admin_autofollow_accounts: this.users.admin_autofollow_accounts,
                    max_user_blocks: this.users.max_user_blocks,
                    max_user_mutes: this.users.max_user_mutes,
                    max_domain_blocks: this.users.max_domain_blocks,
                }).then(res => {
                    this.isSubmitting = false;
                    this.isSubmittingTimeout = true;
                    this.isSubmittingTimeoutHandler = setTimeout(() => {
                        this.isSubmittingTimeout = false;
                    }, 4000);
                }).catch(err => {
                    if(err.response.data.message) {
                        swal('Error', err.response.data.message, 'error');
                    } else {
                        swal('Error', 'An unexpected error occurred, please try again!', 'error');
                    }
                    this.isSubmitting = false;
                })
            },

            saveStorage() {
                let data = this.showDiskConfig ?
                    {
                        primary_disk: this.storage.primary_disk,
                        update_disk: true,
                        disk_config: this.storage.disk_config,
                    } : {
                        primary_disk: this.storage.primary_disk,
                    }
                axios.post('/i/admin/api/settings/update/storage', data)
                .then(res => {
                    this.features.cloud_storage = res.data.primary_disk === 'cloud';
                    this.isSubmitting = false;
                    this.isSubmittingTimeout = true;
                    this.isSubmittingTimeoutHandler = setTimeout(() => {
                        this.isSubmittingTimeout = false;
                    }, 4000);
                }).catch(err => {
                    if(err.response.data.error) {
                        if(err.response.data.s3_vce) {
                            let el = document.createElement('div');
                            el.classList.add('text-left');
                            el.innerHTML = err.response.data.message;
                            let wrapper = document.createElement('div');
                            wrapper.appendChild(el);
                            swal({
                                title: 'Invalid S3 Credentials',
                                content: wrapper,
                                icon: 'error'
                            });
                        } else {
                            swal('Error', err.response.data.message, 'error');
                        }
                    }
                    this.isSubmitting = false;
                })
            },

            handleChange($event, cat, type) {
                switch(cat) {
                    case 'features':
                        this.features[type] = $event;
                    break;

                    case 'landing':
                        this.landing[type] = $event;
                    break;

                    case 'platform':
                        this.platform[type] = $event;
                    break;

                    case 'media':
                        this.media[type] = $event;
                    break;

                    case 'users':
                        this.users[type] = $event;
                    break;

                    case 'storage':
                        this.storage[type] = $event;
                    break;
                }
                console.log($event)
                console.log(type)
            },

            handleSubChange($event, cat, type, sub) {
                switch(cat) {
                    case 'features':
                        this.features[type][sub] = $event;
                    break;

                    case 'landing':
                        this.landing[type][sub] = $event;
                    break;

                    case 'platform':
                        this.platform[type][sub] = $event;
                    break;

                    case 'media':
                        this.media[type][sub] = $event;
                    break;

                    case 'users':
                        this.users[type][sub] = $event;
                    break;

                    case 'storage':
                        this.storage[type][sub] = $event;
                    break;
                }
                console.log($event)
                console.log(type)
            },
        },

        watch: {

        }
    }
</script>

<style lang="scss" scoped>
    .rule-badge {
        display: flex;
        width: 34px;
        height: 34px;
        justify-content: center;
        align-items: center;
        background-color: #fff;
        border-radius: 34px;
        border: 2px solid var(--primary);

        &-inner {
            display: flex;
            justify-content: center;
            align-items: center;
            width: 26px;
            height: 26px;
            border-radius: 26px;
            background-color: var(--primary);
            color: #fff;
            font-weight: bold;
            font-size: 13px;
        }
    }
    .rule-text {
        max-width: 90%;
        margin-bottom: 0px;
        font-size: 14px;
    }
    .gap-1 {
        gap: 1rem;
    }
</style>
