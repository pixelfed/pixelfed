<template>
    <div class="admin-invite-component">
        <div class="admin-invite-component-inner">
            <div class="card bg-dark">
                <div v-if="tabIndex === 0" class="card-body d-flex align-items-center justify-content-center">
                    <div class="text-center">
                        <b-spinner variant="muted" />
                        <p class="text-muted mb-0">Loading...</p>
                    </div>
                </div>

                <div v-else-if="tabIndex === 1" class="card-body">
                    <div class="d-flex justify-content-center my-3">
                        <img src="/img/pixelfed-icon-color.png" width="60" alt="Pixelfed logo" />
                    </div>
                    <div class="d-flex flex-column align-items-center justify-content-center">
                        <p class="lead mb-1 text-muted">You've been invited to join</p>
                        <p class="h3 mb-2">{{ instance.uri }}</p>
                        <p class="mb-0 text-muted">
                            <span>{{ instance.stats.user_count.toLocaleString('en-CA', { compactDisplay: "short", notation: "compact"}) }} users</span>
                            <span>·</span>
                            <span>{{ instance.stats.status_count.toLocaleString('en-CA', { compactDisplay: "short", notation: "compact"}) }} posts</span>
                        </p>

                        <div v-if="inviteConfig.message != 'You\'ve been invited to join'">
                            <div class="admin-message">
                                <p class="small text-light mb-0">Message from admin(s):</p>
                                {{ inviteConfig.message }}
                            </div>
                        </div>
                    </div>

                    <div class="mt-5">
                        <div class="form-group">
                            <label for="username">Username</label>
                            <input
                                type="text"
                                class="form-control form-control-lg"
                                placeholder="What should everyone call you?"
                                minlength="2"
                                maxlength="15"
                                v-model="form.username" />

                            <p v-if="errors.username" class="form-text text-danger">
                                <i class="far fa-exclamation-triangle mr-1"></i>
                                {{ errors.username }}
                            </p>
                        </div>

                        <button
                            class="btn btn-primary btn-block font-weight-bold"
                            @click="proceed(tabIndex)"
                            :disabled="isProceeding || !form.username || form.username.length < 2">
                            <template v-if="isProceeding">
                                <b-spinner small />
                            </template>
                            <template v-else>
                                Continue
                            </template>
                        </button>

                        <p class="login-link">
                            <a href="/login">Already have an account?</a>
                        </p>

                        <p class="register-terms">
                            By registering, you agree to our <a href="/site/terms">Terms of Service</a> and <a href="/site/privacy">Privacy Policy</a>.
                        </p>
                    </div>
                </div>

                <div v-else-if="tabIndex === 2" class="card-body">
                    <div class="d-flex justify-content-center my-3">
                        <img src="/img/pixelfed-icon-color.png" width="60" alt="Pixelfed logo" />
                    </div>
                    <div class="d-flex flex-column align-items-center justify-content-center">
                        <p class="lead mb-1 text-muted">You've been invited to join</p>
                        <p class="h3 mb-2">{{ instance.uri }}</p>
                    </div>
                    <div class="mt-5">
                        <div class="form-group">
                            <label for="username">Email Address</label>
                            <input
                                type="email"
                                class="form-control form-control-lg"
                                placeholder="Your email address"
                                v-model="form.email" />

                            <p v-if="errors.email" class="form-text text-danger">
                                <i class="far fa-exclamation-triangle mr-1"></i>
                                {{ errors.email }}
                            </p>
                        </div>

                        <button
                            class="btn btn-primary btn-block font-weight-bold"
                            @click="proceed(tabIndex)"
                            :disabled="isProceeding || !form.email || !validateEmail()">
                            <template v-if="isProceeding">
                                <b-spinner small />
                            </template>
                            <template v-else>
                                Continue
                            </template>
                        </button>
                    </div>
                </div>

                <div v-else-if="tabIndex === 3" class="card-body">
                    <div class="d-flex justify-content-center my-3">
                        <img src="/img/pixelfed-icon-color.png" width="60" alt="Pixelfed logo" />
                    </div>
                    <div class="d-flex flex-column align-items-center justify-content-center">
                        <p class="lead mb-1 text-muted">You've been invited to join</p>
                        <p class="h3 mb-2">{{ instance.uri }}</p>
                    </div>
                    <div class="mt-5">
                        <div class="form-group">
                            <label for="username">Password</label>
                            <input
                                type="password"
                                class="form-control form-control-lg"
                                placeholder="Use a secure password"
                                minlength="8"
                                v-model="form.password" />

                            <p v-if="errors.password" class="form-text text-danger">
                                <i class="far fa-exclamation-triangle mr-1"></i>
                                {{ errors.password }}
                            </p>
                        </div>

                        <button
                            class="btn btn-primary btn-block font-weight-bold"
                            @click="proceed(tabIndex)"
                            :disabled="isProceeding || !form.password || form.password.length < 8">
                            <template v-if="isProceeding">
                                <b-spinner small />
                            </template>
                            <template v-else>
                                Continue
                            </template>
                        </button>
                    </div>
                </div>

                <div v-else-if="tabIndex === 4" class="card-body">
                    <div class="d-flex justify-content-center my-3">
                        <img src="/img/pixelfed-icon-color.png" width="60" alt="Pixelfed logo" />
                    </div>
                    <div class="d-flex flex-column align-items-center justify-content-center">
                        <p class="lead mb-1 text-muted">You've been invited to join</p>
                        <p class="h3 mb-2">{{ instance.uri }}</p>
                    </div>
                    <div class="mt-5">
                        <div class="form-group">
                            <label for="username">Confirm Password</label>
                            <input
                                type="password"
                                class="form-control form-control-lg"
                                placeholder="Use a secure password"
                                minlength="8"
                                v-model="form.password_confirm" />

                            <p v-if="errors.password_confirm" class="form-text text-danger">
                                <i class="far fa-exclamation-triangle mr-1"></i>
                                {{ errors.password_confirm }}
                            </p>
                        </div>

                        <button
                            class="btn btn-primary btn-block font-weight-bold"
                            @click="proceed(tabIndex)"
                            :disabled="isProceeding || !form.password_confirm || form.password !== form.password_confirm">
                            <template v-if="isProceeding">
                                <b-spinner small />
                            </template>
                            <template v-else>
                                Continue
                            </template>
                        </button>
                    </div>
                </div>

                <div v-else-if="tabIndex === 5" class="card-body">
                    <div class="d-flex justify-content-center my-3">
                        <img src="/img/pixelfed-icon-color.png" width="60" alt="Pixelfed logo" />
                    </div>
                    <div class="d-flex flex-column align-items-center justify-content-center">
                        <p class="lead mb-1 text-muted">You've been invited to join</p>
                        <p class="h3 mb-2">{{ instance.uri }}</p>
                    </div>
                    <div class="mt-5">
                        <div class="form-group">
                            <label for="username">Display Name</label>
                            <input
                                type="text"
                                class="form-control form-control-lg"
                                placeholder="Add an optional display name"
                                minlength="8"
                                v-model="form.display_name" />

                            <p v-if="errors.display_name" class="form-text text-danger">
                                <i class="far fa-exclamation-triangle mr-1"></i>
                                {{ errors.display_name }}
                            </p>
                        </div>

                        <button
                            class="btn btn-primary btn-block font-weight-bold"
                            @click="proceed(tabIndex)"
                            :disabled="isProceeding">
                            <template v-if="isProceeding">
                                <b-spinner small />
                            </template>
                            <template v-else>
                                Continue
                            </template>
                        </button>
                    </div>
                </div>

                <div v-else-if="tabIndex === 6" class="card-body d-flex flex-column">
                    <div class="d-flex justify-content-center my-3">
                        <img src="/img/pixelfed-icon-color.png" width="60" alt="Pixelfed logo" />
                    </div>
                    <div class="d-flex flex-column align-items-center justify-content-center">
                        <p class="lead mb-1 text-muted">You've been invited to join</p>
                        <p class="h3 mb-2">{{ instance.uri }}</p>
                    </div>
                    <div class="mt-5 d-flex align-items-center justify-content-center flex-column flex-grow-1">
                        <b-spinner variant="muted" />
                        <p class="text-muted">Registering...</p>
                    </div>
                </div>

                <div v-else-if="tabIndex === 'invalid-code'" class="card-body d-flex align-items-center justify-content-center">
                    <div>
                        <h1 class="text-center">Invalid Invite Code</h1>
                        <hr>
                        <p class="text-muted mb-1">The invite code you were provided is not valid, this can happen when:</p>
                        <ul class="text-muted">
                            <li>Invite code has typos</li>
                            <li>Invite code was already used</li>
                            <li>Invite code has reached max uses</li>
                            <li>Invite code has expired</li>
                            <li>You have been rate limited</li>
                        </ul>
                        <hr>
                        <a href="/" class="btn btn-primary btn-block rounded-pill font-weight-bold">Go back home</a>
                    </div>
                </div>

                <div v-else class="card-body">
                    <p>An error occured.</p>
                </div>
            </div>
        </div>
    </div>
</template>

<script type="text/javascript">
    export default {
        props: ['code'],

        data() {
            return {
                instance: {},
                inviteConfig: {},
                tabIndex: 0,
                isProceeding: false,
                errors: {
                    username: undefined,
                    email: undefined,
                    password: undefined,
                    password_confirm: undefined
                },

                form: {
                    username: undefined,
                    email: undefined,
                    password: undefined,
                    password_confirm: undefined,
                    display_name: undefined,
                }
            }
        },

        mounted() {
            this.fetchInstanceData();
        },

        methods: {
            fetchInstanceData() {
                axios.get('/api/v1/instance')
                .then(res => {
                    this.instance = res.data;
                })
                .then(res => {
                    this.verifyToken();
                })
                .catch(err => {
                    console.log(err);
                })
            },

            verifyToken() {
                axios.post('/api/v1.1/auth/invite/admin/verify', {
                    token: this.code,
                })
                .then(res => {
                    this.tabIndex = 1;
                    this.inviteConfig = res.data;
                })
                .catch(err => {
                    this.tabIndex = 'invalid-code';
                })
            },

            checkUsernameAvailability() {
                axios.post('/api/v1.1/auth/invite/admin/uc', {
                    token: this.code,
                    username: this.form.username
                })
                .then(res => {
                    if(res && res.data) {
                        this.isProceeding = false;
                        this.tabIndex = 2;
                    } else {
                        this.tabIndex = 'invalid-code';
                        this.isProceeding = false;
                    }
                })
                .catch(err => {
                    if(err.response.data && err.response.data.username) {
                        this.errors.username = err.response.data.username[0];
                        this.isProceeding = false;
                    } else {
                        this.tabIndex = 'invalid-code';
                        this.isProceeding = false;
                    }
                })
            },

            checkEmailAvailability() {
                axios.post('/api/v1.1/auth/invite/admin/ec', {
                    token: this.code,
                    email: this.form.email
                })
                .then(res => {
                    if(res && res.data) {
                        this.isProceeding = false;
                        this.tabIndex = 3;
                    } else {
                        this.tabIndex = 'invalid-code';
                        this.isProceeding = false;
                    }
                })
                .catch(err => {
                    if(err.response.data && err.response.data.email) {
                        this.errors.email = err.response.data.email[0];
                        this.isProceeding = false;
                    } else {
                        this.tabIndex = 'invalid-code';
                        this.isProceeding = false;
                    }
                })
            },

            validateEmail() {
                if(!this.form.email || !this.form.email.length) {
                    return false;
                }

                return /^[a-zA-Z]+[a-zA-Z0-9_.-]+@[a-zA-Z0-9_.-]+[a-zA-Z]$/i.test(this.form.email);
            },

            handleRegistration() {
                var $form = $('<form>', {
                    action: '/api/v1.1/auth/invite/admin/re',
                    method: 'post'
                });
                let fields = {
                    '_token': document.head.querySelector('meta[name="csrf-token"]').content,
                    token: this.code,
                    username: this.form.username,
                    name: this.form.display_name,
                    email: this.form.email,
                    password: this.form.password,
                    password_confirm: this.form.password_confirm
                };

                $.each(fields, function(key, val) {
                     $('<input>').attr({
                         type: "hidden",
                         name: key,
                         value: val
                     }).appendTo($form);
                });
                $form.appendTo('body').submit();
            },

            proceed(cur) {
                this.isProceeding = true;
                event.currentTarget.blur();

                switch(cur) {
                    case 1:
                        this.checkUsernameAvailability();
                    break;

                    case 2:
                        this.checkEmailAvailability();
                    break;

                    case 3:
                        this.isProceeding = false;
                        this.tabIndex = 4;
                    break;

                    case 4:
                        this.isProceeding = false;
                        this.tabIndex = 5;
                    break;

                    case 5:
                        this.tabIndex = 6;
                        this.handleRegistration();
                    break;
                }
            }
        }
    }
</script>

<style lang="scss">
    .admin-invite-component {
        font-family: var(--font-family-sans-serif);

        &-inner {
            display: flex;
            width: 100wv;
            height: 100vh;
            justify-content: center;
            align-items: center;

            .card {
                width: 100%;
                color: #fff;
                padding: 1.25rem 2.5rem;
                border-radius: 10px;
                min-height: 530px;

                @media(min-width: 768px) {
                    width: 30%;
                }

                label {
                    color: var(--muted);
                    font-weight: bold;
                    text-transform: uppercase;
                }

                .login-link {
                    margin-top: 10px;
                    font-weight: 600;
                }

                .register-terms {
                    font-size: 12px;
                    color: var(--muted);
                }

                .form-control {
                    color: #fff;
                }

                .admin-message {
                    margin-top: 20px;
                    border: 1px solid var(--dropdown-item-hover-color);
                    color: var(--text-lighter);
                    padding: 1rem;
                    border-radius: 5px;
                }
            }
        }
    }
</style>
