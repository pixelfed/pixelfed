<template>
    <div class="web-wrapper notification-metro-component">
        <div v-if="isLoaded" class="container-fluid mt-3">
            <div class="row">
                <div class="col-md-3 d-md-block">
                    <sidebar :user="profile" />
                </div>

                <div class="col-md-9 col-lg-9 col-xl-5 offset-xl-1">
                    <template v-if="tabIndex === 0">
                        <h1 class="font-weight-bold">
                            {{ $t("notifications.title")}}
                        </h1>
                        <p class="small mt-n2">&nbsp;</p>
                    </template>
                    <template v-else-if="tabIndex === 10">
                        <div class="d-flex align-items-center mb-3">
                            <a class="text-muted" href="#" @click.prevent="tabIndex = 0" style="opacity:0.3">
                                <i class="far fa-chevron-circle-left fa-2x mr-3" title="Go back to notifications"></i>
                            </a>
                            <h1 class="font-weight-bold">
                            {{  $t("notifications.followRequest") }}
                            </h1>
                        </div>
                    </template>
                    <template v-else>
                        <h1 class="font-weight-bold">
                            {{ tabs[tabIndex].name }}
                        </h1>
                        <p class="small text-lighter mt-n2">{{ tabs[tabIndex].description }}</p>
                    </template>

                    <div v-if="!notificationsLoaded">
                        <placeholder />
                    </div>

                    <template v-else>
                        <ul v-if="tabIndex != 10 && (hasNotifications || hasFollowRequests)" class="notification-filters nav nav-tabs nav-fill mb-3">
                            <li v-for="(item, idx) in tabs" class="nav-item">
                                <a
                                    class="nav-link"
                                    :class="{ active: tabIndex === idx }"
                                    href="#"
                                    @click.prevent="toggleTab(idx)">
                                    <i
                                        class="mr-1 nav-link-icon"
                                        :class="[ item.icon ]"
                                        >
                                    </i>
                                    <span class="d-none d-xl-inline-block">
                                        {{ item.name }}
                                    </span>
                                </a>
                            </li>
                        </ul>

                        <div v-if="shouldShowEmptyState">
                            <div class="row justify-content-center">
                                <div class="col-12 col-md-10 text-center">
                                    <img src="/img/illustrations/dk-nature-man-monochrome.svg" class="img-fluid" style="opacity: 0.6;">
                                    <p class="lead text-muted font-weight-bold">{{ $t('notifications.noneFound') }}</p>
                                </div>
                            </div>
                        </div>

                        <div v-else-if="tabSwitching">
                            <placeholder />
                        </div>

                        <div v-else>
                            <div v-if="tabIndex === 0">
                                <div
                                    v-if="hasFollowRequests"
                                    class="card card-body shadow-none border border-warning rounded-pill mb-3 py-2">
                                    <div class="media align-items-center">
                                        <i class="far fa-exclamation-circle mr-3 text-warning"></i>
                                        <div class="media-body">
                                            <p class="mb-0">
                                                <strong>{{ followRequests.count }} follow {{ followRequests.count > 1 ? 'requests' : 'request' }}</strong>
                                            </p>
                                        </div>
                                        <a
                                            class="ml-2 small d-flex font-weight-bold primary text-uppercase mb-0"
                                            href="#"
                                            @click.prevent="showFollowRequests()">
                                            View<span class="d-none d-md-block">&nbsp;Follow Requests</span>
                                        </a>
                                    </div>
                                </div>

                                <div v-if="hasNotifications">
                                    <notification
                                        v-for="(n, index) in notifications"
                                        :key="`notification:${index}:${n.id}`"
                                        :n="n" />

                                    <div v-if="canLoadMore">
                                        <intersect @enter="enterIntersect">
                                            <placeholder />
                                        </intersect>
                                    </div>
                                </div>
                            </div>

                            <div v-else-if="tabIndex === 10">
                                <div v-if="hasFollowRequests" class="list-group">
                                    <div v-for="(acct, index) in followRequests.accounts" class="list-group-item">
                                        <div class="media align-items-center">
                                            <router-link :to="`/i/web/profile/${acct.account.id}`" class="primary">
                                                <img :src="acct.avatar" width="80" height="80" class="rounded-lg shadow mr-3" onerror="this.onerror=null;this.src='/storage/avatars/default.jpg?v=0';">
                                            </router-link>
                                            <div class="media-body mr-3">
                                                <p class="font-weight-bold mb-0 text-break" style="font-size:17px">
                                                    <router-link :to="`/i/web/profile/${acct.account.id}`" class="primary">
                                                        {{ acct.username }}
                                                    </router-link>
                                                </p>
                                                <p class="mb-1 text-muted text-break" style="font-size:11px">{{ truncate(acct.account.note_text, 100) }}</p>
                                                <div class="d-flex text-lighter" style="font-size:11px">
                                                    <span class="mr-3">
                                                        <span class="font-weight-bold">{{ acct.account.statuses_count }}</span>
                                                        <span>Posts</span>
                                                    </span>
                                                    <span>
                                                        <span class="font-weight-bold">{{ acct.account.followers_count }}</span>
                                                        <span>Followers</span>
                                                    </span>
                                                </div>
                                            </div>
                                            <div class="d-flex flex-column d-md-block">
                                                <button
                                                    class="btn btn-outline-success py-1 btn-sm font-weight-bold rounded-pill mr-2 mb-1"
                                                    @click.prevent="handleFollowRequest('accept', index)"
                                                    >
                                                    {{ $t('notifications.accept') }}
                                                </button>

                                                <button class="btn btn-outline-lighter py-1 btn-sm font-weight-bold rounded-pill mb-1"
                                                    @click.prevent="handleFollowRequest('reject', index)"
                                                    >
                                                    {{ $t("notifications.reject") }}
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div v-else>
                                <div v-if="filteredLoaded">
                                    <div class="card card-body bg-transparent shadow-none border p-2 mb-3 rounded-pill text-lighter">
                                        <div class="media align-items-center small">
                                            <i class="far fa-exclamation-triangle mx-2"></i>
                                            <div class="media-body">
                                                <p class="mb-0 font-weight-bold">{{ $t("notifications.filteringResults") }}</p>
                                            </div>
                                        </div>
                                    </div>

                                    <div v-if="filteredFeed.length">
                                        <notification
                                            v-for="(n, index) in filteredFeed"
                                            :key="`notification:filtered:${index}:${n.id}`"
                                            :n="n" />
                                    </div>

                                    <div v-else>
                                        <div v-if="filteredEmpty">
                                            <div class="card card-body shadow-sm border-0 d-flex flex-row align-items-center" style="border-radius: 20px;gap:1rem;">
                                                <i class="far fa-inbox fa-2x text-muted"></i>
                                                <div class="font-weight-bold">No recent {{ tabs[tabIndex].name }}!</div>
                                            </div>
                                        </div>

                                        <placeholder v-else />
                                    </div>

                                    <div v-if="canLoadMoreFiltered">
                                        <intersect @enter="enterFilteredIntersect">
                                            <placeholder />
                                        </intersect>
                                    </div>
                                </div>

                                <div v-else>
                                    <placeholder />
                                </div>
                            </div>
                        </div>
                    </template>
                </div>
            </div>
            <drawer />
        </div>
    </div>
</template>

<script type="text/javascript">
    import Drawer from './partials/drawer.vue';
    import Sidebar from './partials/sidebar.vue';
    import Notification from './partials/timeline/Notification.vue';
    import Placeholder from './partials/placeholders/NotificationPlaceholder.vue';
    import Intersect from 'vue-intersect';

    export default {
         components: {
            "drawer": Drawer,
            "sidebar": Sidebar,
            "intersect": Intersect,
            "notification": Notification,
            "placeholder": Placeholder,
        },

        data() {
            return {
                isLoaded: false,
                profile: undefined,
                ids: [],
                notifications: undefined,
                notificationsLoaded: false,
                notificationRetries: 0,
                notificationsEmpty: true,
                notificationRetryTimeout: undefined,
                max_id: undefined,
                canLoadMore: false,
                isIntersecting: false,
                tabIndex: 0,
                tabs: [
                    {
                        id: 'all',
                        name: 'All',
                        icon: 'far fa-bell',
                        types: []
                    },

                    {
                        id: 'mentions',
                        name: this.$t("notifications.mentions"),
                        description: this.$t("notifications.mentionsDescription"),
                        icon: 'far fa-at',
                        types: ['comment', 'mention']
                    },

                    {
                        id: 'likes',
                        name: this.$t("notifications.likes"),
                        description: this.$t("notifications.likesDescription"),
                        icon: 'far fa-heart',
                        types: ['favourite']
                    },

                    {
                        id: 'followers',
                        name: this.$t("notifications.followers"),
                        description: this.$t("notifications.followersDescription"),
                        icon: 'far fa-user-plus',
                        types: ['follow']
                    },

                    {
                        id: 'reblogs',
                        name: this.$t("notifications.reblogs"),
                        description:this.$t("notifications.reblogsDescription"),
                        icon: 'far fa-retweet',
                        types: ['share']
                    },

                    {
                        id: 'direct',
                        name: this.$t("notifications.dms"),
                        description: this.$t("notifications.dmsDescription"),
                        icon: 'far fa-envelope',
                        types: ['direct']
                    },
                ],
                tabSwitching: false,
                filteredFeed: [],
                filteredLoaded: false,
                filteredIsIntersecting: false,
                filteredMaxId: undefined,
                canLoadMoreFiltered: true,
                filterPaginationTimeout: undefined,
                filteredIterations: 0,
                filteredEmpty: false,
                followRequests: [],
                followRequestsChecked: false,
                followRequestsPage: 1
            }
        },

        computed: {
            // Simplified computed properties for better readability
            hasNotifications() {
                return this.notifications && this.notifications.length > 0;
            },

            hasFollowRequests() {
                return this.followRequests &&
                       this.followRequests.accounts &&
                       this.followRequests.accounts.length > 0;
            },

            shouldShowEmptyState() {
                // Show empty state when:
                // 1. Notifications are loaded
                // 2. We're on the main tab (tabIndex 0)
                // 3. There are no notifications AND no follow requests
                // 4. We've finished checking follow requests (if user has locked profile)
                return this.notificationsLoaded &&
                       this.tabIndex === 0 &&
                       !this.hasNotifications &&
                       !this.hasFollowRequests &&
                       (this.followRequestsChecked || !this.profile.locked);
            }
        },

        mounted() {
            this.profile = window._sharedData.user;
            this.isLoaded = true;
            if(this.profile.locked) {
                this.fetchFollowRequests();
            } else {
                this.followRequestsChecked = true;
            }
            this.fetchNotifications();
        },

        beforeDestroy() {
            clearTimeout(this.notificationRetryTimeout);
        },

        methods: {
            fetchNotifications() {
                this.notificationRetries++;
                axios.get('/api/pixelfed/v1/notifications?pg=true')
                .then(res => {
                    if(!res || !res.data || !res.data.length) {
                        if(this.notificationRetries >= 2) {
                            clearTimeout(this.notificationRetryTimeout);
                            this.canLoadMore = false;
                            this.notificationsLoaded = true;
                            this.notificationsEmpty = true;
                            return;
                        }
                        this.notificationRetryTimeout = setTimeout(() => {
                            this.fetchNotifications();
                        }, 1000);
                        return;
                    }

                    let data = res.data.filter(n => {
                        if(n.type == 'share' && !n.status) {
                            return false;
                        }
                        if(n.type == 'comment' && !n.status) {
                            return false;
                        }
                        if(n.type == 'mention' && !n.status) {
                            return false;
                        }
                        if(n.type == 'favourite' && !n.status) {
                            return false;
                        }
                        if(n.type == 'follow' && !n.account) {
                            return false;
                        }
                        return true;
                    });

                    let ids = res.data.map(n => n.id);
                    this.max_id = Math.min(...ids);
                    this.ids.push(...ids);
                    this.notifications = data;
                    this.notificationsLoaded = true;
                    this.notificationsEmpty = data.length === 0;
                    this.canLoadMore = data.length > 0;
                })
                .catch(err => {
                    console.error('Error fetching notifications:', err);
                    this.notificationsLoaded = true;
                    this.notificationsEmpty = true;
                    this.canLoadMore = false;
                });
            },

            enterIntersect() {
                if(this.isIntersecting) {
                    return;
                }

                if(!isFinite(this.max_id)) {
                    return;
                }

                this.isIntersecting = true;

                axios.get('/api/pixelfed/v1/notifications', {
                    params: {
                        max_id: this.max_id
                    }
                }).then(res => {
                    if(!res.data.length) {
                        this.canLoadMore = false;
                    }
                    let ids = res.data.map(n => n.id);
                    this.max_id = Math.min(...ids);
                    this.notifications.push(...res.data);
                    this.isIntersecting = false;
                })
            },

            toggleTab(idx) {
                this.tabSwitching = true;
                this.canLoadMoreFiltered = true;
                this.filteredEmpty = false; // Reset empty state
                this.filteredIterations = 0;
                this.filteredLoaded = false; // Reset loaded state
                this.filterFeed(this.tabs[idx].id);
            },

            filterFeed(type) {
                switch(type) {
                    case 'all':
                        this.tabIndex = 0;
                        this.filteredFeed = [];
                        this.filteredLoaded = false;
                        this.filteredIsIntersecting = false;
                        this.filteredMaxId = undefined;
                        this.canLoadMoreFiltered = false;
                        this.tabSwitching = false;
                    break;

                    case 'mentions':
                        this.tabIndex = 1;
                        this.filteredMaxId = this.max_id;
                        this.filteredFeed = this.notifications.filter(n => this.tabs[this.tabIndex].types.includes(n.type));
                        this.filteredIsIntersecting = false;
                        this.tabSwitching = false;
                        this.filteredLoaded = true;
                        // Set empty state if no results found
                        this.filteredEmpty = this.filteredFeed.length === 0;
                        this.canLoadMoreFiltered = this.filteredFeed.length > 0;
                    break;

                    case 'likes':
                        this.tabIndex = 2;
                        this.filteredMaxId = this.max_id;
                        this.filteredFeed = this.notifications.filter(n => n.type === 'favourite');
                        this.filteredIsIntersecting = false;
                        this.tabSwitching = false;
                        this.filteredLoaded = true;
                        // Set empty state if no results found
                        this.filteredEmpty = this.filteredFeed.length === 0;
                        this.canLoadMoreFiltered = this.filteredFeed.length > 0;
                    break;

                    case 'followers':
                        this.tabIndex = 3;
                        this.filteredMaxId = this.max_id;
                        this.filteredFeed = this.notifications.filter(n => n.type === 'follow');
                        this.filteredIsIntersecting = false;
                        this.tabSwitching = false;
                        this.filteredLoaded = true;
                        // Set empty state if no results found
                        this.filteredEmpty = this.filteredFeed.length === 0;
                        this.canLoadMoreFiltered = this.filteredFeed.length > 0;
                    break;

                    case 'reblogs':
                        this.tabIndex = 4;
                        this.filteredMaxId = this.max_id;
                        this.filteredFeed = this.notifications.filter(n => n.type === 'share');
                        this.filteredIsIntersecting = false;
                        this.tabSwitching = false;
                        this.filteredLoaded = true;
                        // Set empty state if no results found
                        this.filteredEmpty = this.filteredFeed.length === 0;
                        this.canLoadMoreFiltered = this.filteredFeed.length > 0;
                    break;

                    case 'direct':
                        this.tabIndex = 5;
                        this.filteredMaxId = this.max_id;
                        this.filteredFeed = this.notifications.filter(n => n.type === 'direct');
                        this.filteredIsIntersecting = false;
                        this.tabSwitching = false;
                        this.filteredLoaded = true;
                        // Set empty state if no results found
                        this.filteredEmpty = this.filteredFeed.length === 0;
                        this.canLoadMoreFiltered = this.filteredFeed.length > 0;
                    break;
                }
            },

            enterFilteredIntersect() {
                if( !this.canLoadMoreFiltered ||
                    this.filteredIsIntersecting ||
                    this.filteredIterations > 10
                ) {
                    if(this.filteredFeed.length == 0) {
                        this.filteredEmpty = true;
                        this.canLoadMoreFiltered = false;
                    }
                    return;
                }

                if(!isFinite(this.max_id) || !isFinite(this.filteredMaxId)) {
                    this.canLoadMoreFiltered = false;
                    return;
                }

                this.filteredIsIntersecting = true;

                axios.get('/api/pixelfed/v1/notifications', {
                    params: {
                        max_id: this.filteredMaxId,
                        limit: 40
                    }
                })
                .then(res => {
                    let mids = res.data.map(n => n.id);
                    let max_id = Math.min(...mids);
                    if(max_id < this.max_id) {
                        this.max_id = max_id;
                        res.data.forEach(n => {
                            if(this.ids.indexOf(n.id) == -1) {
                                this.ids.push(n.id);
                                this.notifications.push(n);
                            }
                        });
                    }
                    this.filteredIterations++;
                    if(this.filterPaginationTimeout && this.filterPaginationTimeout < 500) {
                        clearTimeout(this.filterPaginationTimeout);
                    }
                    if(!res.data || !res.data.length) {
                        this.canLoadMoreFiltered = false;
                    }
                    if(!res.data.length) {
                        this.canLoadMoreFiltered = false;
                    }
                    let ids = res.data.map(n => n.id);
                    this.filteredMaxId = Math.min(...ids);
                    let types = this.tabs[this.tabIndex].types;
                    let data = res.data.filter(n => types.includes(n.type));
                    this.filteredFeed.push(...data);
                    this.filteredIsIntersecting = false;
                    if(this.filteredFeed.length < 10) {
                        setTimeout(() => this.enterFilteredIntersect(), 500);
                    }
                    this.filterPaginationTimeout = setTimeout(() => {
                        this.canLoadMoreFiltered = false;
                    }, 2000);
                })
                .catch(err => {
                    this.canLoadMoreFiltered = false;
                })
            },

            fetchFollowRequests() {
                axios.get('/account/follow-requests.json')
                .then(res => {
                    if(this.followRequestsPage == 1) {
                        this.followRequests = res.data;
                        this.followRequestsChecked = true;
                    } else {
                        this.followRequests.accounts.push(...res.data.accounts);
                    }
                    this.followRequestsPage++;
                })
                .catch(err => {
                    console.error('Error fetching follow requests:', err);
                    this.followRequestsChecked = true;
                });
            },

            showFollowRequests() {
                this.tabSwitching = false;
                this.filteredEmpty = false;
                this.filteredIterations = 0;
                this.tabIndex = 10;
            },

            handleFollowRequest(action, index) {
                if(!window.confirm('Are you sure you want to ' + action + ' this follow request?')) {
                    return;
                }

                axios.post('/account/follow-requests', {
                    action: action,
                    id: this.followRequests.accounts[index].rid
                })
                .then(res => {
                    this.followRequests.count--;
                    this.followRequests.accounts.splice(index, 1);
                    this.tabIndex = 0;
                })
            },

            truncate(str, len = 40) {
                return _.truncate(str, { length: len });
            }
        }
    }
</script>

<style lang="scss" scoped>
    .notification-metro-component {
        .notification-filters {
            background: transparent;
            border-color: var(--border-color);

            .nav-link {
                font-size: 12px;

                &.active {
                    background: var(--bg-light);
                    font-weight: bold;
                    color: var(--dark);
                    border-color: var(--border-color);
                }

                &-icon:not(.active) {
                    opacity: 0.5;
                }

                &:not(.active) {
                    color: var(--text-lighter);
                }
            }
        }
    }
</style>
