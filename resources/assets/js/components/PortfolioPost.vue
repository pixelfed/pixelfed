<template>
    <div>
        <div v-if="loading" class="container">
            <div class="d-flex justify-content-center align-items-center" style="height: 100vh;">
                <b-spinner />
            </div>
        </div>

        <div v-else>
            <div class="container mb-5">
                <div class="row mt-3">
                    <div class="col-12 mb-4">

                        <div class="d-flex justify-content-center">
                            <img :src="post.media_attachments[0].url" class="img-fluid mb-4" style="max-height: 80vh;object-fit: contain;">
                        </div>

                    </div>
                    <div class="col-12 mb-4">
                        <p v-if="settings.show_captions && post.content_text">{{ post.content_text }}</p>
                        <div class="d-md-flex justify-content-between align-items-center">
                            <p class="small text-lighter">by <a :href="profileUrl()" class="text-lighter font-weight-bold">&commat;{{profile.username}}</a></p>
                            <p v-if="settings.show_license && post.media_attachments[0].license" class="small text-muted">Licensed under {{ post.media_attachments[0].license.title }}</p>
                            <p v-if="settings.show_location && post.place" class="small text-muted">{{ post.place.name }}, {{ post.place.country }}</p>
                            <p v-if="settings.show_timestamp" class="small text-muted">
                                <a v-if="settings.show_link" :href="post.url" class="text-lighter font-weight-bold" style="z-index: 2">
                                    {{ formatDate(post.created_at) }}
                                </a>
                                <span v-else class="user-select-none">
                                    {{ formatDate(post.created_at) }}
                                </span>
                            </p>
                        </div>
                    </div>
                </div>
            </div>
            <div class="container">
                <div class="row">
                    <div class="col-12">
                        <div class="d-flex fixed-bottom p-3 justify-content-between align-items-center">
                            <a v-if="user" class="logo-mark logo-mark-sm mb-0 p-1" href="/">
                                <span class="text-gradient-primary">portfolio</span>
                            </a>
                            <span v-else class="logo-mark logo-mark-sm mb-0 p-1">
                                <span class="text-gradient-primary">portfolio</span>
                            </span>
                            <p v-if="user && user.id === profile.id" class="text-center mb-0">
                                <a :href="settingsUrl" class="text-muted"><i class="far fa-cog fa-lg"></i></a>
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
        props: [ 'initialData' ],

        data() {
            return {
                loading: true,
                isAuthed: undefined,
                user: undefined,
                settings: undefined,
                post: undefined,
                profile: undefined,
                settingsUrl: window._portfolio.path + '/settings'
            }
        },

        mounted() {
            const initialData = JSON.parse(this.initialData);
            this.post = initialData.post;
            this.profile = initialData.profile;
            this.isAuthed = initialData.authed;
            this.fetchUser();
        },

        methods: {
            async fetchUser() {
                if(this.isAuthed) {
                    await axios.get('/api/v1/accounts/verify_credentials')
                    .then(res => {
                        this.user = res.data;
                    })
                    .catch(err => {
                    });
                }
                await axios.get('/api/portfolio/account/settings.json', {
                    params: {
                        id: this.profile.id
                    }
                })
                .then(res => {
                    this.settings = res.data;
                })
                .then(() => {
                    setTimeout(() => {
                        this.loading = false;
                    }, 500);
                })

            },

            profileUrl() {
                return `https://${window._portfolio.domain}${window._portfolio.path}/${this.profile.username}`;
            },

            postUrl(res) {
                return `/${this.profile.username}/${res.id}`;
            },

            formatDate(ts) {
                const dts = new Date(ts);
                return dts.toLocaleDateString(undefined, { weekday: 'short', year: 'numeric', month: 'long', day: 'numeric' });
            }
        }
    }
</script>
