<template>
<div class="container remote-auth-start">
    <div class="row mt-5 justify-content-center">
        <div class="col-12 col-md-5">
            <div class="card shadow-none border" style="border-radius: 20px;">
                <div v-if="!loaded" class="card-body d-flex justify-content-center flex-column" style="min-height: 662px;">
                    <p class="lead text-center font-weight-bold mb-0">Sign-in with Mastodon</p>
                    <div class="w-100">
                        <hr>
                    </div>
                    <div class="d-flex justify-content-center align-items-center flex-grow-1">
                        <b-spinner />
                    </div>
                </div>

                <div v-else class="card-body" style="min-height: 662px;">
                    <p class="lead text-center font-weight-bold">Sign-in with Mastodon</p>
                    <hr>
                    <p class="small text-center mb-3">Select your Mastodon server:</p>
                    <button
                        v-for="domain in domains"
                        type="button"
                        class="server-btn"
                        @click="handleRedirect(domain)">
                        <span class="font-weight-bold">{{ domain }}</span>
                    </button>
                    <hr v-if="!config.default_only && !config.custom_only">
                    <p v-if="!config.default_only && !config.custom_only" class="text-center">
                        <button type="button" class="other-server-btn" @click="handleOther()">Sign-in with a different server</button>
                    </p>
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
    export default {
        props: {
            config: {
                type: Object
            }
        },

        data() {
            return {
                loaded: false,
                domains: []
            }
        },

        mounted() {
            this.fetchDomains();
        },

        methods: {
            fetchDomains() {
                axios.post('/auth/raw/mastodon/domains')
                .then(res => {
                    this.domains = res.data;
                })
                .finally(() => {
                    setTimeout(() => {
                        this.loaded = true;
                    }, 500);
                })
            },

            handleRedirect(domain) {
                axios.post('/auth/raw/mastodon/redirect', { domain: domain })
                .then(res => {
                    if(!res || !res.data.hasOwnProperty('ready')) {
                        return;
                    }

                    if(res.data.hasOwnProperty('action') && res.data.action === 'incompatible_domain') {
                        swal('Oops!', 'This server is not compatible, please choose another or try again later!', 'error');
                        return;
                    }

                    if(res.data.hasOwnProperty('action') && res.data.action === 'blocked_domain') {
                        swal('Server Blocked', 'This server is blocked by admins and cannot be used, please try another server!', 'error');
                        return;
                    }

                    if(res.data.ready) {
                        window.location.href = '/auth/raw/mastodon/preflight?d=' + domain + '&dsh=' + res.data.dsh;
                    }
                })
            },

            handleOther() {
                swal({
                  text: 'Enter your mastodon domain (without https://)',
                  content: "input",
                  button: {
                    text: "Next",
                    closeModal: false,
                  },
                })
                .then(domain => {
                  if (!domain || domain.length < 2 || domain.indexOf('.') == -1) {
                    swal('Oops!', "Please enter a valid domain!", 'error');
                    return;
                  };

                  if(domain.startsWith('http')) {
                    swal('Oops!', "The domain you enter should not start with http(s://)\nUse the domain format, like mastodon.social", 'error');
                    return;
                  }

                  return this.handleRedirect(domain);
                })
            }
        }
    }
</script>

<style lang="scss">
    @use '../../../../node_modules/bootstrap/scss/bootstrap';

    .remote-auth-start {
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
    }
</style>
