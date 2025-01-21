<template>
    <div class="card-body">
        <p class="lead text-center font-weight-bold">Sign-in with Mastodon</p>
        <hr>

        <template v-if="step === 1">
            <div class="wrapper-mh">
                <div class="flex-grow-1">
                    <p class="text-dark">Hello {{ initialData['_webfinger'] }},</p>
                    <p class="lead font-weight-bold">Welcome to Pixelfed!</p>

                    <p>You are moments away from joining our vibrant photo and video focused community with members from around the world.</p>
                </div>

                <p class="text-xs text-lighter">Your Mastodon account <strong>avatar</strong>, <strong>bio</strong>, <strong>display name</strong>, <strong>followed accounts</strong> and <strong>username</strong> will be imported to speed up the sign-up process. We will never post on your behalf, we only access your public profile data (avatar, bio, display name, followed accounts and username).</p>
            </div>
        </template>

        <template v-else-if="step === 2">
            <div class="wrapper-mh">
                <div class="pt-3">
                    <div class="form-group has-float-label">
                        <input class="form-control form-control-lg" id="f_username" aria-describedby="f_username_help" v-model="username"  autofocus/>
                        <label for="f_username">Username</label>
                        <p v-if="validUsername && !usernameError" id="f_username_help" class="text-xs text-success font-weight-bold mt-1 mb-0">Available</p>
                        <p v-else-if="!validUsername && !usernameError" id="f_username_help" class="text-xs text-danger font-weight-bold mt-1 mb-0">Username taken</p>
                        <p v-else-if="usernameError" id="f_username_help" class="text-xs text-danger font-weight-bold mt-1 mb-0">{{ usernameError }}</p>
                    </div>
                </div>

                <div class="pt-3">
                    <p class="text-sm font-weight-bold mb-1">Avatar</p>

                    <div class="border rounded-lg p-3 d-flex align-items-center justify-content-between gap-1">
                        <img v-if="form.importAvatar" :src="initialData.avatar" width="40" height="40" class="rounded-circle" />
                        <img v-else src="/storage/avatars/default.jpg" width="40" height="40" class="rounded-circle" />

                        <div class="custom-control custom-checkbox">
                            <input type="checkbox" class="custom-control-input" id="customCheck1" v-model="form.importAvatar">
                            <label class="custom-control-label text-xs font-weight-bold" style="line-height: 24px;" for="customCheck1">Import my Mastodon avatar</label>
                        </div>
                    </div>
                </div>
            </div>
        </template>

        <template v-else-if="step === 3">
            <div class="wrapper-mh">
                <div class="pt-3">
                    <div class="form-group has-float-label">
                        <input class="form-control form-control-lg" id="f_name" aria-describedby="f_name_help" v-model="form.display_name" />
                        <label for="f_name">Display Name</label>
                        <div id="f_name_help" class="text-xs text-muted mt-1">Your display name, shown on your profile. You can change this later.</div>
                    </div>
                </div>
                <div class="pt-3">
                    <div class="form-group has-float-label">
                        <textarea class="form-control" id="f_bio" aria-describedby="f_bio_help" rows="5" v-model="form.bio"></textarea>
                        <label for="f_bio">Bio</label>
                        <div id="f_bio_help" class="text-xs text-muted mt-1 d-flex justify-content-between align-items-center">
                            <div>Describe yourself, you can change this later.</div>
                            <div>{{ form.bio ? form.bio.length : 0 }}/500</div>
                        </div>
                    </div>
                </div>
            </div>
        </template>

        <template v-else-if="step === 4">
            <div class="wrapper-mh">
                <div class="pt-3">
                    <div class="d-flex align-items-center justify-content-between mb-3">
                        <div>
                            <p class="font-weight-bold mb-0">Import accounts you follow</p>
                            <p class="text-muted text-xs mb-0">You can skip this step and follow accounts later</p>
                        </div>
                        <div style="min-width: 100px;text-align:right;">
                            <p v-if="following && selectedFollowing && following.length == selectedFollowing.length" class="mb-0"><a class="font-weight-bold text-xs text-danger" href="#" @click.prevent="handleFollowerUnselectAll()">Unselect All</a></p>
                            <p v-else class="mb-0"><a class="font-weight-bold text-xs" href="#" @click.prevent="handleFollowerSelectAll()">Select All</a></p>
                        </div>
                    </div>
                    <div v-if="!followingFetched" class="d-flex align-items-center justify-content-center limit-h">
                        <div class="w-100">
                            <instagram-loader></instagram-loader>
                        </div>
                    </div>
                    <div v-else class="list-group limit-h">
                        <div v-for="(account, idx) in following" class="list-group-item">
                            <div class="d-flex align-items-center" style="gap:8px;">
                                <div class="d-flex align-items-center" style="gap:5px;">
                                    <div class="custom-control custom-checkbox">
                                        <input
                                            type="checkbox"
                                            class="custom-control-input"
                                            :value="account.url"
                                            :id="'fac' + idx"
                                            v-model="selectedFollowing"
                                            @change="handleFollower($event, account)">
                                        <label class="custom-control-label" :for="'fac' + idx"></label>
                                    </div>
                                    <img v-if="account.avatar" :src="account.avatar" width="34" height="34" class="rounded-circle" />
                                    <img v-else src="/storage/avatars/default.jpg" width="34" height="34" class="rounded-circle" />
                                </div>

                                <div style="max-width: 70%">
                                    <p class="font-weight-bold mb-0 text-truncate">&commat;{{account.username}}</p>
                                    <p class="text-xs text-lighter mb-0 text-truncate">{{account.url.replace('https://', '')}}</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </template>

        <template v-else-if="step === 5">
            <div class="wrapper-mh">
                <div class="pt-3">
                    <div class="pb-3">
                        <p class="font-weight-bold mb-0">We need a bit more info</p>
                        <p class="text-xs text-muted">Enter your email so you recover access to your account in the future</p>
                    </div>

                    <div class="form-group has-float-label">
                        <input class="form-control" id="f_email" aria-describedby="f_email_help" v-model="email" autofocus="autofocus" />
                        <label for="f_email">Email address</label>
                        <p v-if="email && validEmail && !emailError" id="f_email_help" class="text-xs text-success font-weight-bold mt-1 mb-0">Available</p>
                        <p v-else-if="email && !validEmail && !emailError" id="f_email_help" class="text-xs text-danger font-weight-bold mt-1 mb-0">Email already in use</p>
                        <p v-else-if="email && emailError" id="f_email_help" class="text-xs text-danger font-weight-bold mt-1 mb-0">{{ emailError }}</p>
                        <p v-else id="f_email_help" class="text-xs text-muted mt-1 mb-0">We'll never share your email with anyone else.</p>
                    </div>
                </div>

                <div v-if="email && email.length && validEmail" class="pt-3">
                    <div class="form-group has-float-label">
                        <input type="password" class="form-control" id="f_password" aria-describedby="f_password_help" autocomplete="new-password" v-model="password" autofocus="autofocus" />
                        <label for="f_password">Password</label>
                        <div id="f_password_help" class="text-xs text-muted">Use a memorable password that you don't use on other sites.</div>
                    </div>
                </div>

                <div v-if="password && password.length >= 8" class="pt-3">
                    <div class="form-group has-float-label">
                        <input type="password" class="form-control" id="f_passwordConfirm" aria-describedby="f_passwordConfirm_help" autocomplete="new-password" v-model="passwordConfirm" autofocus="autofocus" />
                        <label for="f_passwordConfirm">Confirm Password</label>
                        <div id="f_passwordConfirm_help" class="text-xs text-muted">Re-enter your password.</div>
                    </div>
                </div>
            </div>
        </template>

        <template v-else-if="step === 6">
            <div class="wrapper-mh">
                <div class="my-5">
                    <p class="lead text-center font-weight-bold mb-0">You're almost ready!</p>
                    <p class="text-center text-lighter text-xs">Confirm your email and other info</p>
                </div>

                <div class="card shadow-none border" style="border-radius: 1rem;">
                    <div class="card-body">
                        <div class="d-flex gap-1">
                            <img :src="initialData.avatar" width="90" height="90" class="rounded-circle">

                            <div>
                                <p class="lead font-weight-bold mb-n1">@{{username}}</p>
                                <p class="small font-weight-light text-muted mb-1">{{username}}@pixelfed.test</p>
                                <p class="text-xs mb-0 text-lighter">{{ form.bio.slice(0, 80) + '...' }}</p>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="list-group mt-3" style="border-radius: 1rem;">
                    <div class="list-group-item d-flex align-items-center justify-content-between">
                        <div class="text-xs">Email</div>
                        <div class="font-weight-bold">{{ email }}</div>
                    </div>

                    <div class="list-group-item d-flex align-items-center justify-content-between">
                        <div class="text-xs">Following Imports</div>
                        <div class="font-weight-bold">{{ selectedFollowing ? selectedFollowing.length : 0 }}</div>
                    </div>
                </div>
            </div>
        </template>

        <template v-else-if="step === 7">
            <div class="wrapper-mh">
                <div class="w-100 d-flex flex-column gap-1">
                    <b-progress :value="submitProgress" :max="100" height="1rem" animated></b-progress>

                    <p class="text-center text-xs text-lighter">{{ submitMessage }}</p>
                </div>
            </div>
        </template>

        <hr>
        <template v-if="step === 7">
            <div class="d-flex align-items-center justify-content-center gap-1 mb-2">
                <button class="btn btn-outline-primary font-weight-bold btn-block my-0" @click="handleBack()">Back</button>
                <button class="btn btn-primary font-weight-bold btn-block my-0" @click="handleJoin()" disabled>Continue</button>
            </div>
        </template>
        <template v-else>
            <div class="d-flex align-items-center justify-content-center gap-1 mb-2">
                <button v-if="step > 1" class="btn btn-outline-primary font-weight-bold btn-block my-0" :disabled="isSubmitting" @click="handleBack()">Back</button>
                <button
                    v-if="step === 6"
                    class="btn btn-primary font-weight-bold btn-block my-0"
                    :disabled="isSubmitting"
                    @click="handleJoin()">
                    <b-spinner v-if="isSubmitting" small />
                    <span v-else>Continue</span>
                </button>
                <button v-else class="btn btn-primary font-weight-bold btn-block my-0" :disabled="canProceed()" @click="handleProceed()">Next</button>
            </div>
            <template v-if="isSubmitting ? false : step <= 6">
                <hr>
                <p class="text-center mb-0">
                    <a class="font-weight-bold" href="/login">Go back to login</a>
                </p>
            </template>
        </template>
    </div>
</template>

<script type="text/javascript">
    import {debounce} from './../../../js/util/debounce.js';
    import { InstagramLoader } from 'vue-content-loader';

    export default {
        props: {
            initialData: {
                type: Object
            }
        },

        components: {
            InstagramLoader,
        },

        data() {
            return {
                step: 1,
                validUsername: false,
                usernameError: undefined,
                username: this.initialData.username,
                email: undefined,
                emailError: undefined,
                validEmail: false,
                password: undefined,
                passwordConfirm: undefined,
                passwordValid: false,
                following: [],
                followingFetched: false,
                selectedFollowing: [],
                form: {
                    importAvatar: true,
                    bio: this.stripTagsPreserveNewlines(this.initialData.note),
                    display_name: this.initialData.display_name,
                },
                isSubmitting: false,
                submitProgress: 0,
                submitMessage: 'Please wait...',
                isImportingFollowing: false,
                accountToId: [],
                followingIds: [],
                accessToken: undefined,
            }
        },

        mounted() {
            this.checkUsernameAvailability();
        },

        watch: {
            username: debounce(function(username) {
                this.checkUsernameAvailability();
            }, 500),

            email: debounce(function(email) {
                this.checkEmailAvailability();
            }, 500),

            passwordConfirm: function(confirm) {
                this.checkPasswordConfirm(confirm);
            },

            selectedFollowing: function(account) {
                this.lookupSelected(account);
            }
        },

        methods: {
            checkPasswordConfirm(password) {
                if(!this.password || !password) {
                    return;
                }

                this.passwordValid = password.trim() === this.password.trim();
            },

            handleBack() {
                event.currentTarget.blur();
                this.step--;
            },

            handleProceed() {
                event.currentTarget.blur();
                this.step++;

                if(!this.followingFetched) {
                    this.fetchFollowing();
                }
            },

            checkUsernameAvailability() {
                axios.post('/auth/raw/mastodon/s/username-check', {
                    username: this.username
                })
                .then(res => {
                    if(res.data && res.data.hasOwnProperty('exists')) {
                        this.usernameError = undefined;
                        this.validUsername = res.data.exists == false;
                    }
                })
                .catch(err => {
                    this.usernameError = err.response.data.message;
                })
            },

            checkEmailAvailability() {
                axios.post('/auth/raw/mastodon/s/email-check', {
                    email: this.email
                })
                .then(res => {
                    if(!res.data) {
                        this.emailError = undefined;
                        this.validEmail = false;
                        return;
                    }
                    if(res.data && res.data.hasOwnProperty('banned') && res.data.banned) {
                        this.emailError = 'This email provider is not supported, please use a different email address.';
                        this.validEmail = false;
                        return;
                    }
                    if(res.data && res.data.hasOwnProperty('exists')) {
                        this.emailError = undefined;
                        this.validEmail = res.data.exists == false;
                    }
                })
                .catch(err => {
                    this.emailError = err.response.data.message;
                })
            },

            canProceed() {
                switch(this.step) {
                    case 1:
                        return false;
                    break;

                    case 2:
                         return (this.usernameError || !this.validUsername);
                    break;

                    case 3:
                         return false;
                    break;

                    case 4:
                        return false;
                    break;

                    case 5:
                         return (
                            !this.email ||
                            !this.validEmail ||
                            !this.password ||
                            !this.password.length ||
                            this.password.length < 8 ||
                            !this.passwordConfirm ||
                            !this.passwordConfirm.length ||
                            this.passwordConfirm.length < 8 ||
                            !this.passwordValid
                        );
                    break;

                    case 6:

                    break;
                }
            },

            handleFollower(event, account) {
                let state = event.target.checked;
                if(state) {
                    if(this.selectedFollowing.indexOf(account.url) == -1) {
                        this.selectedFollowing.push(account.url)
                    }
                } else {
                    this.selectedFollowing = this.selectedFollowing.filter(s => s !== account.url);
                }
            },

            handleFollowerSelectAll() {
                this.selectedFollowing = this.following.map(f => f.url);
            },

            handleFollowerUnselectAll() {
                this.selectedFollowing = [];
            },

            lookupSelected(accounts) {
                if(!accounts || !accounts.length) {
                    return;
                }

                for (var i = accounts.length - 1; i >= 0; i--) {
                    let acct = accounts[i];
                    if(!this.accountToId.map(a => a.url).includes(acct)) {
                        axios.post('/auth/raw/mastodon/s/account-to-id', {
                            account: acct
                        })
                        .then(res => {
                            this.accountToId.push({
                                id: res.data.id,
                                url: acct
                            })

                        })
                    }
                }
            },

            fetchFollowing() {
                axios.post('/auth/raw/mastodon/s/following')
                .then(res => {
                    this.following = res.data.following;
                    this.followingFetched = true;
                })
                .finally(() => {
                    setTimeout(() => {
                        this.followingFetched = true;
                    }, 1000)
                })
            },

            stripTagsPreserveNewlines(htmlString) {
                const parser = new DOMParser();
                const document = parser.parseFromString(htmlString, 'text/html');
                const body = document.body;

                let strippedString = '';

                function traverse(element) {
                    const nodeName = element.nodeName.toLowerCase();

                    if (nodeName === 'p') {
                        strippedString += '\n';
                    } else if (nodeName === '#text') {
                        strippedString += element.textContent;
                    }

                    const childNodes = element.childNodes;

                    for (let i = 0; i < childNodes.length; i++) {
                        traverse(childNodes[i]);
                    }
                }

                traverse(body);

                strippedString = strippedString.trim();
                return strippedString;
            },

            handleJoin() {
                this.isSubmitting = true;
                this.step = 7;
                this.submitProgress = 10;

                axios.post('/auth/raw/mastodon/s/submit', {
                    email: this.email,
                    name: this.form.display_name,
                    password: this.password,
                    password_confirmation: this.passwordConfirm,
                    username: this.username,
                })
                .then(res => {
                    if(res.data.hasOwnProperty('token') && res.data.token) {
                        this.accessToken = res.data.token;
                        setTimeout(() => {
                            this.submitProgress = 20;
                            this.submitMessage = 'Claiming your username...';
                            this.storeBio();
                        }, 2000);
                    } else {
                        swal('Something went wrong', 'An unexpected error occured, please try again later');
                    }
                })
            },

            storeBio() {
                axios.post('/auth/raw/mastodon/s/store-bio', {
                    bio: this.form.bio
                })
                .then(res => {
                    this.submitProgress = 30;
                    this.submitMessage = 'Importing your bio...';
                })
                .finally(() => {
                    this.storeFollowing();
                })
            },

            storeFollowing() {
                this.submitProgress = 40;
                this.submitMessage = 'Importing following accounts...';

                if(!this.selectedFollowing || !this.selectedFollowing.length) {
                    setTimeout(() => {
                        this.iterateFollowing();
                    }, 500);

                    return;
                }
                let ids = this.selectedFollowing
                    .map(id => {
                        return this.accountToId.filter(ai => ai.url == id).map(ai => ai.id);
                    })
                    .flat()
                    .filter(r => r && r.length && typeof r === 'string')
                this.followingIds = ids;
                setTimeout(() => {
                    this.iterateFollowing();
                }, 500);
                // axios.post('/auth/raw/mastodon/s/store-following', {
                //     accounts: this.selectedFollowing
                // })
                // .then(res => {
                //     this.followingIds = res.data;
                //     this.submitProgress = 40;
                //     this.submitMessage = 'Importing following accounts...';
                //     setTimeout(() => {
                //         this.iterateFollowing();
                //     }, 1000);
                // })
            },

            iterateFollowing() {
                if(!this.followingIds || !this.followingIds.length) {
                    this.storeAvatar();
                    return;
                }

                let id = this.followingIds.pop();
                return this.handleFollow(id);
            },

            handleFollow(id) {
                const config = {
                    headers: { Authorization: `Bearer ${this.accessToken}` }
                };
                axios.post(`/api/v1/accounts/${id}/follow`, {}, config)
                .then(res => {
                })
                .finally(() => {
                    this.iterateFollowing();
                })
            },

            storeAvatar() {
                this.submitProgress = 70;
                this.submitMessage = 'Importing your avatar...';
                if(this.form.importAvatar == false) {
                    this.submitProgress = 90;
                    this.submitMessage = 'Preparing your account...';
                    this.finishUp();
                    return;
                }
                axios.post('/auth/raw/mastodon/s/store-avatar', {
                    avatar_url: this.initialData.avatar
                })
                .then(res => {
                    this.submitProgress = 90;
                    this.submitMessage = 'Preparing your account...';
                    this.finishUp();
                })
            },

            finishUp() {
                this.submitProgress = 92;
                this.submitMessage = 'Finishing up...';
                axios.post('/auth/raw/mastodon/s/finish-up')
                .then(() => {
                    this.$emit('setCanReload');
                    this.submitProgress = 95;
                    this.submitMessage = 'Logging you in...';
                    setTimeout(() => {
                        this.submitProgress = 100;
                        window.location.reload();
                    }, 5000)
                })
            }
        }
    }
</script>

<style lang="scss">
.wrapper-mh {
    min-height: 429px;
    display: flex;
    justify-content: center;
    flex-direction: column;
}
.limit-h {
    height: 300px;
    overflow-x: hidden;
    overflow-y: auto;
}
.has-float-label {
  display: block;
  position: relative;
}
.has-float-label label, .has-float-label > span {
  position: absolute;
  left: 0;
  top: 0;
  cursor: text;
  font-size: 75%;
  font-weight: bold;
  opacity: 1;
  -webkit-transition: all .2s;
          transition: all .2s;
  top: -.5em;
  left: 0.75rem;
  z-index: 3;
  line-height: 1;
  padding: 0 4px;
  background: #fff;
}
.has-float-label label::after, .has-float-label > span::after {
  content: " ";
  display: block;
  position: absolute;
  background: #fff;
  height: 2px;
  top: 50%;
  left: -.2em;
  right: -.2em;
  z-index: -1;
}
.has-float-label .form-control::-webkit-input-placeholder {
  opacity: 1;
  -webkit-transition: all .2s;
          transition: all .2s;
}
.has-float-label .form-control::-moz-placeholder {
  opacity: 1;
  transition: all .2s;
}
.has-float-label .form-control:-ms-input-placeholder {
  opacity: 1;
  transition: all .2s;
}
.has-float-label .form-control::placeholder {
  opacity: 1;
  -webkit-transition: all .2s;
          transition: all .2s;
}
.has-float-label .form-control:placeholder-shown:not(:focus)::-webkit-input-placeholder {
  opacity: 0;
}
.has-float-label .form-control:placeholder-shown:not(:focus)::-moz-placeholder {
  opacity: 0;
}
.has-float-label .form-control:placeholder-shown:not(:focus):-ms-input-placeholder {
  opacity: 0;
}
.has-float-label .form-control:placeholder-shown:not(:focus)::placeholder {
  opacity: 0;
}
.has-float-label .form-control:placeholder-shown:not(:focus) + * {
  font-size: 150%;
  opacity: .5;
  top: .3em;
}

.input-group .has-float-label {
  -webkit-box-flex: 1;
  -webkit-flex-grow: 1;
      -ms-flex-positive: 1;
          flex-grow: 1;
  margin-bottom: 0;
  display: -webkit-box;
  display: -webkit-flex;
  display: -ms-flexbox;
  display: flex;
  -webkit-box-orient: vertical;
  -webkit-box-direction: normal;
  -webkit-flex-direction: column;
      -ms-flex-direction: column;
          flex-direction: column;
  -webkit-box-pack: center;
  -webkit-justify-content: center;
      -ms-flex-pack: center;
          justify-content: center;
}
.input-group .has-float-label .form-control {
  width: 100%;
  border-radius: 0.25rem;
}
.input-group .has-float-label:not(:last-child), .input-group .has-float-label:not(:last-child) .form-control {
  border-bottom-right-radius: 0;
  border-top-right-radius: 0;
  border-right: 0;
}
.input-group .has-float-label:not(:first-child), .input-group .has-float-label:not(:first-child) .form-control {
  border-bottom-left-radius: 0;
  border-top-left-radius: 0;
}

.opacity-0 {
    opacity: 0;
    transition: opacity 0.5s;
}

.sl {

.progress {
    background-color: #fff;
}
#tick {
  stroke: #63bc01;
  stroke-width: 6;
  transition: all 1s;
}

#circle {
  stroke: #63bc01;
  stroke-width: 6;
  transform-origin: 50px 50px 0;
  transition: all 1s;
}

.progress #tick {
  opacity: 0;
}

.ready #tick {
  stroke-dasharray: 1000;
  stroke-dashoffset: 1000;
  animation: draw 8s ease-out forwards;
}

.progress #circle {
  stroke: #4c4c4c;
  stroke-dasharray: 314;
  stroke-dashoffset: 1000;
  animation: spin 3s linear infinite;
}

.ready #circle {
  stroke-dashoffset: 66;
  stroke: #63bc01;
}

#circle {
  stroke-dasharray: 500;
}

@keyframes spin {
  0% {
    transform: rotate(0deg);
    stroke-dashoffset: 66;
  }
  50% {
    transform: rotate(540deg);
    stroke-dashoffset: 314;
  }
  100% {
    transform: rotate(1080deg);
    stroke-dashoffset: 66;
  }
}

@keyframes draw {
  to {
    stroke-dashoffset: 0;
  }
}

#scheck {
  width: 300px;
  height: 300px;
}
}

</style>
