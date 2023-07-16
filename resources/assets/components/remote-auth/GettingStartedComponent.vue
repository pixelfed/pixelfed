<template>
<div class="container remote-auth-getting-started">
    <div class="row mt-5 justify-content-center">
        <div class="col-12 col-xl-5 col-md-7">
            <div v-if="!error" class="card shadow-none border" style="border-radius: 20px;">
                <div v-if="!loaded && !existing && !maxUsesReached" class="card-body d-flex align-items-center flex-column" style="min-height: 400px;">
                    <div class="w-100">
                        <p class="lead text-center font-weight-bold">Sign-in with Mastodon</p>
                        <hr />
                    </div>
                    <div class="w-100 d-flex align-items-center justify-content-center flex-grow-1 flex-column gap-1">
                        <div class="position-relative w-100">
                            <p class="pa-center">Please wait...</p>
                            <instagram-loader></instagram-loader>
                        </div>
                        <div class="w-100">
                            <hr>
                            <p class="text-center mb-0">
                                <a class="font-weight-bold" href="/login">Go back to login</a>
                            </p>
                        </div>
                    </div>
                </div>

                <div v-else-if="!loaded && !existing && maxUsesReached" class="card-body d-flex align-items-center flex-column" style="min-height: 660px;">
                    <div class="w-100">
                        <p class="lead text-center font-weight-bold">Sign-in with Mastodon</p>
                        <hr />
                    </div>
                    <div class="w-100 d-flex align-items-center justify-content-center flex-grow-1 flex-column gap-1">

                        <p class="lead text-center font-weight-bold mt-3">Oops!</p>

                        <p class="mb-2 text-center">We cannot complete your request at this time</p>
                        <p class="mb-3 text-center text-xs">It appears that you've signed-in on other Pixelfed instances and reached the max limit that we accept.</p>
                    </div>

                    <div class="w-100">
                        <p class="text-center mb-0">
                            <a class="font-weight-bold" href="/site/contact">Contact Support</a>
                        </p>
                        <hr>
                        <p class="text-center mb-0">
                            <a class="font-weight-bold" href="/login">Go back to login</a>
                        </p>
                    </div>
                </div>

                <div v-else-if="!loaded && existing" class="card-body d-flex align-items-center flex-column" style="min-height: 660px;">
                    <div class="w-100">
                        <p class="lead text-center font-weight-bold">Sign-in with Mastodon</p>
                        <hr />
                    </div>
                    <div class="w-100 d-flex align-items-center justify-content-center flex-grow-1 flex-column gap-1">
                        <b-spinner />
                        <div class="text-center">
                            <p class="lead mb-0">Welcome back!</p>
                            <p class="text-xs text-muted">One moment please, we're logging you in...</p>
                        </div>
                    </div>
                </div>

                <register-form v-else :initialData="prefill" v-on:setCanReload="setCanReload" />
            </div>
            <div v-else class="card shadow-none border">
                <div class="card-body d-flex align-items-center flex-column" style="min-height: 660px;">
                    <div class="w-100">
                        <p class="lead text-center font-weight-bold">Sign-in with Mastodon</p>
                        <hr />
                    </div>
                    <div class="w-100 d-flex align-items-center justify-content-center flex-grow-1 flex-column gap-1">

                        <p class="lead text-center font-weight-bold mt-3">Oops, something went wrong!</p>

                        <p class="mb-3">We cannot complete your request at this time, please try again later.</p>

                        <p class="text-xs text-muted mb-1">This can happen for a few different reasons:</p>

                        <ul class="text-xs text-muted">
                            <li>The remote instance cannot be reached</li>
                            <li>The remote instance is not supported yet</li>
                            <li>The remote instance has been disabled by admins</li>
                            <li>The remote instance does not allow remote logins</li>
                        </ul>
                    </div>

                    <div class="w-100">
                        <hr>
                        <p class="text-center mb-0">
                            <a class="font-weight-bold" href="/login">Go back to login</a>
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
</template>

<script type="text/javascript">
    import { InstagramLoader } from 'vue-content-loader';
    import RegisterForm from './partials/RegisterForm.vue';

    export default {
        components: {
            InstagramLoader,
            RegisterForm
        },

        data() {
            return {
                loaded: false,
                error: false,
                prefill: false,
                existing: undefined,
                maxUsesReached: undefined,
                tab: 'loading',
                canReload: false,
            }
        },

        mounted() {
            this.validateSession();

            window.onbeforeunload = function () {
                if(!this.canReload) {
                    alert('You are trying to leave.');
                    return false;
                }
            }
        },

        methods: {
            validateSession() {
                axios.post('/auth/raw/mastodon/s/check')
                .then(res => {
                    if(!res && !res.hasOwnProperty('action')) {
                        swal('Oops!', 'An unexpected error occured, please try again later', 'error');
                        return;
                    }

                    switch(res.data.action) {
                        case 'onboard':
                            this.getPrefillData();
                            return;
                        break;

                        case 'redirect_existing_user':
                            this.existing = true;
                            this.canReload = true;
                            window.onbeforeunload = undefined;
                            this.redirectExistingUser();
                            return;
                        break;

                        case 'max_uses_reached':
                            this.maxUsesReached = true;
                            this.canReload = true;
                            window.onbeforeunload = undefined;
                            return;
                        break;

                        default:
                            this.error = true;
                            return;
                        break;
                    }
                })
                .catch(error => {
                    this.canReload = true;
                    window.onbeforeunload = undefined;
                    this.error = true;
                })
            },

            setCanReload() {
                this.canReload = true;
                window.onbeforeunload = undefined;
            },

            redirectExistingUser() {
                this.canReload = true;
                setTimeout(() => {
                    this.handleLogin();
                }, 1500);
            },

            handleLogin() {
                axios.post('/auth/raw/mastodon/s/login')
                .then(res => {
                    setTimeout(() => {
                        window.location.reload();
                    }, 1500);
                })
                .catch(err => {
                    this.canReload = false;
                    this.error = true;
                })
            },

            getPrefillData() {
                axios.post('/auth/raw/mastodon/s/prefill')
                .then(res => {
                    this.prefill = res.data;
                })
                .catch(error => {
                    this.error = true;
                })
                .finally(() => {
                    setTimeout(() => {
                        this.loaded = true;
                    }, 1000);
                })
            }
        }
    }
</script>

<style lang="scss">
    @use '../../../../node_modules/bootstrap/scss/bootstrap';

    .remote-auth-getting-started {
        .text-xs {
            font-size: 12px;
        }

        .gap-1 {
            gap: 1rem;
        }

        .opacity-50 {
            opacity: .3;
        }

        .server-btn {
            @extend .btn;
            @extend .btn-primary;
            @extend .btn-block;
            @extend .rounded-pill;
            @extend .font-weight-light;

            background: linear-gradient(#6364FF, #563ACC);
        }

        .other-server-btn {
            @extend .btn;
            @extend .btn-dark;
            @extend .btn-block;
            @extend .rounded-pill;
            @extend .font-weight-light;
        }

        .pa-center {
            position: absolute;
            left: 50%;
            top: 50%;
            transform: translate(-50%);
            font-weight: 600;
            font-size: 16px;
        }
    }
</style>
