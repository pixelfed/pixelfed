<template>
    <div class="portfolio-settings px-3">
        <div v-if="loading" class="d-flex justify-content-center align-items-center py-5">
            <b-spinner variant="primary" />
        </div>
        <div v-else class="row justify-content-center mb-5 pb-5">
            <div class="col-12 col-md-8 bg-dark py-2 rounded">
                <ul class="nav nav-pills nav-fill">
                    <li v-for="(tab, index) in tabs" class="nav-item" :class="{ disabled: index !== 0 && !settings.active}">
                        <span v-if="index !== 0 && !settings.active" class="nav-link">{{ tab }}</span>
                        <a v-else class="nav-link" :class="{ active: tab === tabIndex }" href="#" @click.prevent="toggleTab(tab)">{{ tab }}</a>
                    </li>
                </ul>
            </div>

            <transition name="slide-fade">
                <div v-if="tabIndex === 'Configure'" class="col-12 col-md-8 bg-dark mt-3 py-2 rounded" key="0">
                    <div v-if="!user.statuses_count" class="alert alert-danger">
                        <p class="mb-0 small font-weight-bold">You don't have any public posts, once you share public posts you can enable your portfolio.</p>
                    </div>

                    <div class="d-flex justify-content-between align-items-center py-2">
                        <div class="setting-label">
                            <p class="lead mb-0">Portfolio Enabled</p>
                            <p class="small mb-0 text-muted">You must enable your portfolio before you or anyone can view it.</p>
                        </div>

                        <div class="setting-switch mt-n1">
                            <b-form-checkbox v-model="settings.active" name="check-button" size="lg" switch :disabled="!user.statuses_count" />
                        </div>
                    </div>

                    <hr>

                    <div class="d-flex justify-content-between align-items-center py-2">
                        <div class="setting-label" style="max-width: 50%;">
                            <p class="mb-0">Portfolio Source</p>
                            <p class="small mb-0 text-muted">Choose how you want to populate your portfolio, select Most Recent posts to automatically update your portfolio with recent posts or Curated Posts to select specific posts for your portfolio.</p>
                        </div>
                        <div class="ml-3">
                            <b-form-select v-model="settings.profile_source" :options="profileSourceOptions" :disabled="!user.statuses_count" />
                        </div>
                    </div>
                </div>

                <div v-else-if="tabIndex === 'Curate'" class="col-12 col-md-8 mt-3 py-2 px-0" key="1">
                    <div v-if="!recentPostsLoaded" class="d-flex align-items-center justify-content-center py-5 my-5">
                        <div class="text-center">
                            <div class="spinner-border" role="status">
                                <span class="sr-only">Loading...</span>
                            </div>
                            <p class="text-muted">Loading recent posts...</p>
                        </div>
                    </div>

                    <template v-else>
                        <div class="mt-n2 mb-4">
                            <p class="text-muted small">Select up to 24 photos from your 100 most recent posts. You can only select public photo posts, videos are not supported at this time.</p>

                            <div class="d-flex align-items-center justify-content-between">
                                <p class="font-weight-bold mb-0">Selected {{ selectedRecentPosts.length }}/24</p>
                                <div>
                                    <button
                                        class="btn btn-link font-weight-bold mr-3 text-decoration-none"
                                        :disabled="!selectedRecentPosts.length"
                                        @click="clearSelected">
                                        Clear selected
                                    </button>

                                    <button
                                        class="btn btn-primary py-0 font-weight-bold"
                                        style="width: 150px;"
                                        :disabled="!canSaveCurated"
                                        @click="saveCurated()">
                                        <template v-if="!isSavingCurated">Save</template>
                                        <b-spinner v-else small />
                                    </button>
                                </div>
                            </div>
                        </div>

                        <div class="d-flex justify-content-between align-items-center">
                            <span @click="recentPostsPrev">
                                <i :class="prevClass" />
                            </span>

                            <div class="row flex-grow-1 mx-2">
                                <div v-for="(post, index) in recentPosts.slice(rpStart, rpStart + 9)" class="col-12 col-md-4 mb-1 p-1">
                                        <div class="square user-select-none" @click.prevent="toggleRecentPost(post.id)">
                                            <transition name="fade">
                                                <img
                                                    :key="post.id"
                                                    :src="post.media_attachments[0].url"
                                                    width="100%"
                                                    height="300"
                                                    style="overflow: hidden;object-fit: cover;"
                                                    :draggable="false"
                                                    class="square-content pr-1">
                                            </transition>

                                            <div v-if="selectedRecentPosts.indexOf(post.id) !== -1" style="position: absolute;right: -5px;bottom:-5px;">
                                                <div class="selected-badge">{{ selectedRecentPosts.indexOf(post.id) + 1 }}</div>
                                            </div>
                                        </div>
                                </div>
                            </div>

                            <span @click="recentPostsNext()">
                                <i :class="nextClass" />
                            </span>
                        </div>
                    </template>
                </div>

                <div v-else-if="tabIndex === 'Customize'" class="col-12 col-md-8 mt-3 py-2" key="2">
                    <div v-for="setting in customizeSettings" class="card bg-dark mb-5">
                        <div class="card-header">{{ setting.title }}</div>
                        <div class="list-group bg-dark">
                            <div v-for="item in setting.items" class="list-group-item">
                                <div class="d-flex justify-content-between align-items-center py-2">
                                    <div class="setting-label">
                                        <p class="mb-0">{{ item.label }}</p>
                                        <p v-if="item.description" class="small text-muted mb-0">{{ item.description }}</p>
                                    </div>

                                    <div class="setting-switch mt-n1">
                                        <b-form-checkbox
                                            v-model="settings[item.model]"
                                            name="check-button"
                                            size="lg"
                                            switch
                                            :disabled="item.requiredWithTrue && !settings[item.requiredWithTrue]" />
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="card bg-dark mb-5">
                        <div class="card-header">Portfolio</div>
                        <div class="list-group bg-dark">
                            <div class="list-group-item">
                                <div class="d-flex justify-content-between align-items-center py-2">
                                    <div class="setting-label">
                                        <p class="mb-0">Layout</p>
                                    </div>

                                    <div>
                                        <b-form-select v-model="settings.profile_layout" :options="profileLayoutOptions" />
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div v-else-if="tabIndex === 'Share'" class="col-12 col-md-8 bg-dark mt-3 py-2 rounded" key="0">
                    <div class="py-2">
                        <p class="text-muted">Portfolio URL</p>
                        <p class="lead mb-0"><a :href="settings.url">{{ settings.url }}</a></p>
                    </div>
                </div>
            </transition>
        </div>
    </div>
</template>

<script type="text/javascript">
    export default {
        data() {
            return {
                loading: true,
                tabIndex: "Configure",
                tabs: [
                    "Configure",
                    "Customize",
                    "View Portfolio"
                ],
                user: undefined,
                settings: undefined,
                recentPostsLoaded: false,
                rpStart: 0,
                recentPosts: [],
                recentPostsPage: undefined,
                selectedRecentPosts: [],
                isSavingCurated: false,
                canSaveCurated: false,
                customizeSettings: [],
                profileSourceOptions: [
                    { value: null, text: 'Please select an option', disabled: true },
                    { value: 'recent', text: 'Most recent posts' },
                ],
                profileLayoutOptions: [
                    { value: null, text: 'Please select an option', disabled: true },
                    { value: 'grid', text: 'Grid' },
                    { value: 'masonry', text: 'Masonry' },
                    { value: 'album', text: 'Album' },
                ]
            }
        },

        computed: {
            prevClass() {
                return this.rpStart === 0 ?
                    "fa fa-arrow-circle-left fa-3x text-dark" :
                    "fa fa-arrow-circle-left fa-3x text-muted cursor-pointer";
            },

            nextClass() {
                return this.rpStart > (this.recentPosts.length - 9) ?
                    "fa fa-arrow-circle-right fa-3x text-dark" :
                    "fa fa-arrow-circle-right fa-3x text-muted cursor-pointer";
            },
        },

        watch: {
            settings: {
                deep: true,
                immediate: true,
                handler: function(o, n) {
                    if(this.loading) {
                        return;
                    }
                    if(!n.show_timestamp) {
                        this.settings.show_link = false;
                    }
                    this.updateSettings();
                }
            }
        },

        mounted() {
            this.fetchUser();
        },

        methods: {
            fetchUser() {
                axios.get('/api/v1/accounts/verify_credentials')
                .then(res => {
                    this.user = res.data;

                    if(res.data.statuses_count > 0) {
                        this.profileSourceOptions = [
                            { value: null, text: 'Please select an option', disabled: true },
                            { value: 'recent', text: 'Most recent posts' },
                            { value: 'custom', text: 'Curated posts' },
                        ];
                    } else {
                        setTimeout(() => {
                            this.settings.active = false;
                            this.settings.profile_source = 'recent';
                            this.tabIndex = 'Configure';
                        }, 1000);
                    }
                })

                axios.post(this.apiPath('/api/portfolio/self/settings.json'))
                .then(res => {
                    this.settings = res.data;
                    this.updateTabs();
                    if(res.data.metadata && res.data.metadata.posts) {
                        this.selectedRecentPosts = res.data.metadata.posts;
                    }
                })
                .then(() => {
                    this.initCustomizeSettings();
                })
                .then(() => {
                    const url = new URL(window.location);
                    if(url.searchParams.has('tab')) {
                        let tab = url.searchParams.get('tab');
                        let tabs = this.settings.profile_source === 'custom' ?
                        ['curate', 'customize', 'share'] :
                        ['customize', 'share'];
                        if(tabs.indexOf(tab) !== -1) {
                            this.toggleTab(tab.slice(0, 1).toUpperCase() + tab.slice(1));
                        }
                    }
                })
                .then(() => {
                    setTimeout(() => {
                        this.loading = false;
                    }, 500);
                })
            },

            apiPath(path) {
                return path;
            },

            toggleTab(idx) {
                if(idx === 'Curate' && !this.recentPostsLoaded) {
                    this.loadRecentPosts();
                }
                this.tabIndex = idx;
                this.rpStart = 0;
                if(idx == 'Configure') {
                    const url = new URL(window.location);
                    url.searchParams.delete('tab');
                    window.history.pushState({}, '', url);
                } else if (idx == 'View Portfolio') {
                    this.tabIndex = 'Configure';
                    window.location.href = `https://${window._portfolio.domain}${window._portfolio.path}/${this.user.username}`;
                    return;
                } else {
                    const url = new URL(window.location);
                    url.searchParams.set('tab', idx.toLowerCase());
                    window.history.pushState({}, '', url);
                }
            },

            updateTabs() {
                if(this.settings.profile_source === 'custom') {
                    this.tabs = [
                        "Configure",
                        "Curate",
                        "Customize",
                        "View Portfolio"
                    ];
                } else {
                    this.tabs = [
                        "Configure",
                        "Customize",
                        "View Portfolio"
                    ];
                }
            },

            updateSettings() {
                axios.post(this.apiPath('/api/portfolio/self/update-settings.json'), this.settings)
                .then(res => {
                    this.updateTabs();
                    this.$bvToast.toast(`Your settings have been successfully updated!`, {
                        variant: 'dark',
                        title: 'Settings Updated',
                        autoHideDelay: 2000,
                        appendToast: false
                    })
                })
            },

            loadRecentPosts() {
                axios.get('/api/v1/accounts/' + this.user.id + '/statuses?only_media=1&media_types=photo&limit=100')
                .then(res => {
                    if(res.data.length) {
                        this.recentPosts = res.data.filter(p => p.visibility === "public");
                    }
                })
                .then(() => {
                    setTimeout(() => {
                        this.recentPostsLoaded = true;
                    }, 500);
                })
            },

            toggleRecentPost(id) {
                if(this.selectedRecentPosts.indexOf(id) == -1) {
                    if(this.selectedRecentPosts.length === 24) {
                        return;
                    }
                    this.selectedRecentPosts.push(id);
                } else {
                    this.selectedRecentPosts = this.selectedRecentPosts.filter(i => i !== id);
                }
                this.canSaveCurated = true;
            },

            recentPostsPrev() {
                if(this.rpStart === 0) {
                    return;
                }
                this.rpStart = this.rpStart - 9;
            },

            recentPostsNext() {
                if(this.rpStart > (this.recentPosts.length - 9)) {
                    return;
                }
                this.rpStart = this.rpStart + 9;
            },

            clearSelected() {
                this.selectedRecentPosts = [];
            },

            saveCurated() {
                this.isSavingCurated = true;
                event.currentTarget?.blur();

                axios.post('/api/portfolio/self/curated.json', {
                    ids: this.selectedRecentPosts
                })
                .then(res => {
                    this.isSavingCurated = false;
                    this.$bvToast.toast(`Your curated posts have been updated!`, {
                        variant: 'dark',
                        title: 'Portfolio Updated',
                        autoHideDelay: 2000,
                        appendToast: false
                    })
                })
                .catch(err => {
                    this.isSavingCurated = false;
                    this.$bvToast.toast(`An error occured while attempting to update your portfolio, please try again later and contact an admin if this problem persists.`, {
                        variant: 'dark',
                        title: 'Error',
                        autoHideDelay: 2000,
                        appendToast: false
                    })
                })
            },

            initCustomizeSettings() {
                this.customizeSettings = [
                    {
                        title: "Post Settings",
                        items: [
                            {
                                label: "Show Captions",
                                model: "show_captions"
                            },
                            {
                                label: "Show License",
                                model: "show_license"
                            },
                            {
                                label: "Show Location",
                                model: "show_location"
                            },
                            {
                                label: "Show Timestamp",
                                model: "show_timestamp"
                            },
                            {
                                label: "Link to Post",
                                description: "Add link to timestamp to view the original post url, requires show timestamp to be enabled",
                                model: "show_link",
                                requiredWithTrue: "show_timestamp"
                            }
                        ]
                    },

                    {
                        title: "Profile Settings",
                        items: [
                            {
                                label: "Show Avatar",
                                model: "show_avatar"
                            },
                            {
                                label: "Show Bio",
                                model: "show_bio"
                            }
                        ]
                    },
                ]
            }
        }
    }
</script>
