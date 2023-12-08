<template>
    <div class="timeline-section-component">
        <div v-if="!isLoaded">
            <status-placeholder />
            <status-placeholder />
            <status-placeholder />
            <status-placeholder />
        </div>

        <div v-else>
            <transition name="fade">
                <div v-if="showReblogBanner && getScope() === 'home'" class="card bg-g-amin card-body shadow-sm mb-3" style="border-radius: 15px;">
                    <div class="d-flex justify-content-around align-items-center">
                        <div class="flex-grow-1 ft-std">
                            <h2 class="font-weight-bold text-white mb-0">Introducing Reblogs in feeds</h2>
                            <hr />
                            <p class="lead text-white mb-0">
                                See reblogs from accounts you follow in your home feed!
                            </p>
                            <p class="text-white small mb-1" style="opacity:0.6">
                                You can disable reblogs in feeds on the Timeline Settings page.
                            </p>
                            <hr />
                            <div class="d-flex">
                                <button class="btn btn-light rounded-pill font-weight-bold btn-block mr-2" @click.prevent="enableReblogs()">
                                    <template v-if="!enablingReblogs">Show reblogs in home feed</template>
                                    <b-spinner small v-else />
                                </button>
                                <button class="btn btn-outline-light rounded-pill font-weight-bold px-5" @click.prevent="hideReblogs()">Hide</button>
                            </div>
                        </div>
                    </div>
                </div>
            </transition>
            <status
                v-for="(status, index) in feed"
                :key="'pf_feed:' + status.id + ':idx:' + index + ':fui:' + forceUpdateIdx"
                :status="status"
                :profile="profile"
                v-on:like="likeStatus(index)"
                v-on:unlike="unlikeStatus(index)"
                v-on:share="shareStatus(index)"
                v-on:unshare="unshareStatus(index)"
                v-on:menu="openContextMenu(index)"
                v-on:counter-change="counterChange(index, $event)"
                v-on:likes-modal="openLikesModal(index)"
                v-on:shares-modal="openSharesModal(index)"
                v-on:follow="follow(index)"
                v-on:unfollow="unfollow(index)"
                v-on:comment-likes-modal="openCommentLikesModal"
                v-on:handle-report="handleReport"
                v-on:bookmark="handleBookmark(index)"
                v-on:mod-tools="handleModTools(index)"
            />

            <div v-if="showLoadMore" class="text-center">
                <button
                    class="btn btn-primary rounded-pill font-weight-bold"
                    @click="tryToLoadMore">
                    Load more
                </button>
            </div>

            <div v-if="canLoadMore">
                <intersect @enter="enterIntersect">
                    <status-placeholder style="margin-bottom: 10rem;"/>
                </intersect>
            </div>

            <div v-if="!isLoaded && feed.length && endFeedReached" style="margin-bottom: 50vh">
                <div class="card card-body shadow-sm mb-3" style="border-radius: 15px;">
                    <p class="display-4 text-center">✨</p>
                    <p class="lead mb-0 text-center">You have reached the end of this feed</p>
                </div>
            </div>

            <timeline-onboarding
                v-if="scope == 'home' && !feed.length"
                :profile="profile"
                v-on:update-profile="updateProfile" />

            <empty-timeline v-if="isLoaded && scope !== 'home' && !feed.length" />
        </div>

        <context-menu
            v-if="showMenu"
            ref="contextMenu"
            :status="feed[postIndex]"
            :profile="profile"
            v-on:moderate="commitModeration"
            v-on:delete="deletePost"
            v-on:report-modal="handleReport"
            v-on:edit="handleEdit"
        />

        <likes-modal
            v-if="showLikesModal"
            ref="likesModal"
            :status="likesModalPost"
            :profile="profile"
        />

        <shares-modal
            v-if="showSharesModal"
            ref="sharesModal"
            :status="sharesModalPost"
            :profile="profile"
        />

        <report-modal
            ref="reportModal"
            :key="reportedStatusId"
            :status="reportedStatus"
        />

        <post-edit-modal
            ref="editModal"
            v-on:update="mergeUpdatedPost"
            />
    </div>
</template>

<script type="text/javascript">
    import StatusPlaceholder from './../partials/StatusPlaceholder.vue';
    import Status from './../partials/TimelineStatus.vue';
    import Intersect from 'vue-intersect';
    import ContextMenu from './../partials/post/ContextMenu.vue';
    import LikesModal from './../partials/post/LikeModal.vue';
    import SharesModal from './../partials/post/ShareModal.vue';
    import ReportModal from './../partials/modal/ReportPost.vue';
    import EmptyTimeline from './../partials/placeholders/EmptyTimeline.vue'
    import TimelineOnboarding from './../partials/placeholders/TimelineOnboarding.vue'
    import PostEditModal from './../partials/post/PostEditModal.vue';

    export default {
        props: {
            scope: {
                type: String,
                default: "home"
            },

            profile: {
                type: Object
            },

            refresh: {
                type: Boolean,
                default: false
            }
        },

        components: {
            "intersect": Intersect,
            "status-placeholder": StatusPlaceholder,
            "status": Status,
            "context-menu": ContextMenu,
            "likes-modal": LikesModal,
            "shares-modal": SharesModal,
            "report-modal": ReportModal,
            "empty-timeline": EmptyTimeline,
            "timeline-onboarding": TimelineOnboarding,
            "post-edit-modal": PostEditModal
        },

        data() {
            return {
                settings: [],
                isLoaded: false,
                feed: [],
                ids: [],
                max_id: 0,
                canLoadMore: true,
                showLoadMore: false,
                loadMoreTimeout: undefined,
                loadMoreAttempts: 0,
                isFetchingMore: false,
                endFeedReached: false,
                postIndex: 0,
                showMenu: false,
                showLikesModal: false,
                likesModalPost: {},
                showReportModal: false,
                reportedStatus: {},
                reportedStatusId: 0,
                showSharesModal: false,
                sharesModalPost: {},
                forceUpdateIdx: 0,
                showReblogBanner: false,
                enablingReblogs: false,
                baseApi: '/api/v1/pixelfed/timelines/',
            }
        },

        mounted() {
            if(window.App.config.features.hasOwnProperty('timelines')) {
                if(this.scope == 'local' && !window.App.config.features.timelines.local) {
                    swal('Error', 'Cannot load this timeline', 'error');
                    return;
                };
                if(this.scope == 'network' && !window.App.config.features.timelines.network) {
                    swal('Error', 'Cannot load this timeline', 'error');
                    return;
                };
            }
            if(window.App.config.ab.hasOwnProperty('cached_home_timeline')) {
                const cht = window.App.config.ab.cached_home_timeline == true;
                this.baseApi = cht ? '/api/v1/timelines/' : '/api/pixelfed/v1/timelines/';
            }
            this.fetchSettings();
        },

        methods: {
            getScope() {
                switch(this.scope) {
                    case 'local':
                        return 'public'
                    break;

                    case 'global':
                        return 'network'
                    break;

                    default:
                        return 'home';
                    break;
                }
            },

            fetchSettings() {
                axios.get('/api/pixelfed/v1/web/settings')
                .then(res => {
                    this.settings = res.data;

                    if(!res.data) {
                        this.showReblogBanner = true;
                    } else {
                        if(res.data.hasOwnProperty('hide_reblog_banner')) {
                        } else if(res.data.hasOwnProperty('enable_reblogs')) {
                            if(!res.data.enable_reblogs) {
                                this.showReblogBanner = true;
                            }
                        } else {
                            this.showReblogBanner = true;
                        }
                    }
                })
                .finally(() => {
                    this.fetchTimeline();
                })
            },

            fetchTimeline(scrollToTop = false) {
                let url, params;
                if(this.getScope() === 'home' && this.settings && this.settings.hasOwnProperty('enable_reblogs') && this.settings.enable_reblogs) {
                    url = this.baseApi + `home`;
                    params = {
                        '_pe': 1,
                        max_id: this.max_id,
                        limit: 6,
                        include_reblogs: true,
                    }
                } else {
                    url = this.baseApi + this.getScope();
                    params = {
                        max_id: this.max_id,
                        limit: 6,
                        '_pe': 1,
                    }
                }
                if(this.getScope() === 'network') {
                    params.remote = true;
                    url = this.baseApi + `public`;
                }
                axios.get(url, {
                    params: params
                }).then(res => {
                    let ids = res.data.map(p => {
                        if(p && p.hasOwnProperty('relationship')) {
                            this.$store.commit('updateRelationship', [p.relationship]);
                        }
                        return p.id
                    });
                    this.isLoaded = true;
                    if(res.data.length == 0) {
                        return;
                    }
                    this.ids = ids;
                    this.max_id = Math.min(...ids);
                    this.feed = res.data;

                    if(res.data.length < 4) {
                        this.canLoadMore = false;
                        this.showLoadMore = true;
                    }
                })
                .then(() => {
                    if(scrollToTop) {
                        this.$nextTick(() => {
                            window.scrollTo({
                                top: 0,
                                left: 0,
                                behavior: 'smooth'
                            });
                            this.$emit('refreshed');
                        });
                    }
                })
            },

            enterIntersect() {
                if(this.isFetchingMore) {
                    return;
                }

                this.isFetchingMore = true;

                let url, params;
                if(this.getScope() === 'home' && this.settings && this.settings.hasOwnProperty('enable_reblogs') && this.settings.enable_reblogs) {
                    url = this.baseApi + `home`;

                    params = {
                        '_pe': 1,
                        max_id: this.max_id,
                        limit: 6,
                        include_reblogs: true,
                    }
                } else {
                    url = this.baseApi + this.getScope();
                    params = {
                        max_id: this.max_id,
                        limit: 6,
                        '_pe': 1,
                    }
                }
                if(this.getScope() === 'network') {
                    params.remote = true;
                    url = this.baseApi + `public`;

                }
                axios.get(url, {
                    params: params
                }).then(res => {
                    if(!res.data.length) {
                        this.endFeedReached = true;
                        this.canLoadMore = false;
                        this.isFetchingMore = false;
                    }
                    setTimeout(() => {
                        res.data.forEach(p => {
                            if(this.ids.indexOf(p.id) == -1) {
                                if(this.max_id > p.id) {
                                    this.max_id = p.id;
                                }
                                this.ids.push(p.id);
                                this.feed.push(p);
                                if(p && p.hasOwnProperty('relationship')) {
                                    this.$store.commit('updateRelationship', [p.relationship]);
                                }
                            }
                        });
                        this.isFetchingMore = false;
                    }, 100);
                });
            },

            tryToLoadMore() {
                this.loadMoreAttempts++;
                if(this.loadMoreAttempts >= 3) {
                    this.showLoadMore = false;
                }
                this.showLoadMore = false;
                this.canLoadMore = true;
                this.loadMoreTimeout = setTimeout(() => {
                    this.canLoadMore = false;
                    this.showLoadMore = true;
                }, 5000);
            },

            likeStatus(index) {
                let status = this.feed[index];
                if(status.reblog) {
                    status = status.reblog;
                    let state = status.favourited;
                    let count = status.favourites_count;
                    this.feed[index].reblog.favourites_count = count + 1;
                    this.feed[index].reblog.favourited = !status.favourited;
                } else {
                    let state = status.favourited;
                    let count = status.favourites_count;
                    this.feed[index].favourites_count = count + 1;
                    this.feed[index].favourited = !status.favourited;
                }

                axios.post('/api/v1/statuses/' + status.id + '/favourite')
                .then(res => {
                    //
                }).catch(err => {
                    if(status.reblog) {
                        this.feed[index].reblog.favourites_count = count;
                        this.feed[index].reblog.favourited = false;
                    } else {
                        this.feed[index].favourites_count = count;
                        this.feed[index].favourited = false;
                    }

                    let el = document.createElement('p');
                    el.classList.add('text-left');
                    el.classList.add('mb-0');
                    el.innerHTML = '<span class="lead">We limit certain interactions to keep our community healthy and it appears that you have reached that limit. <span class="font-weight-bold">Please try again later.</span></span>';
                    let wrapper = document.createElement('div');
                    wrapper.appendChild(el);

                    if(err.response.status === 429) {
                        swal({
                            title: 'Too many requests',
                            content: wrapper,
                            icon: 'warning',
                            buttons: {
                                // moreInfo: {
                                //  text: "Contact a human",
                                //  visible: true,
                                //  value: "more",
                                //  className: "text-lighter bg-transparent border"
                                // },
                                confirm: {
                                    text: "OK",
                                    value: false,
                                    visible: true,
                                    className: "bg-transparent primary",
                                    closeModal: true
                                }
                            }
                        })
                        .then((val) => {
                            if(val == 'more') {
                                location.href = '/site/contact'
                            }
                            return;
                        });
                    }
                })
            },

            unlikeStatus(index) {
                let status = this.feed[index];
                if(status.reblog) {
                    status = status.reblog;
                    let state = status.favourited;
                    let count = status.favourites_count;
                    this.feed[index].reblog.favourites_count = count - 1;
                    this.feed[index].reblog.favourited = !status.favourited;
                } else {
                    let state = status.favourited;
                    let count = status.favourites_count;
                    this.feed[index].favourites_count = count - 1;
                    this.feed[index].favourited = !status.favourited;
                }

                axios.post('/api/v1/statuses/' + status.id + '/unfavourite')
                .then(res => {
                    //
                }).catch(err => {
                    if(status.reblog && status.pf_type == 'share') {
                        this.feed[index].reblog.favourites_count = count;
                        this.feed[index].reblog.favourited = false;
                    } else {
                        this.feed[index].favourites_count = count;
                        this.feed[index].favourited = false;
                    }
                })
            },

            openContextMenu(idx) {
                this.postIndex = idx;
                this.showMenu = true;
                this.$nextTick(() => {
                    this.$refs.contextMenu.open();
                });
            },

            handleModTools(idx) {
                this.postIndex = idx;
                this.showMenu = true;
                this.$nextTick(() => {
                    this.$refs.contextMenu.openModMenu();
                });
            },

            openLikesModal(idx) {
                this.postIndex = idx;
                let post = this.feed[this.postIndex];
                this.likesModalPost = post.reblog ? post.reblog : post;
                this.showLikesModal = true;
                this.$nextTick(() => {
                    this.$refs.likesModal.open();
                });
            },

            openSharesModal(idx) {
                this.postIndex = idx;
                let post = this.feed[this.postIndex];
                this.sharesModalPost = post.reblog ? post.reblog : post;
                this.showSharesModal = true;
                this.$nextTick(() => {
                    this.$refs.sharesModal.open();
                });
            },

            commitModeration(type) {
                let idx = this.postIndex;

                switch(type) {
                    case 'addcw':
                        this.feed[idx].sensitive = true;
                    break;

                    case 'remcw':
                        this.feed[idx].sensitive = false;
                    break;

                    case 'unlist':
                        this.feed.splice(idx, 1);
                    break;

                    case 'spammer':
                        let id = this.feed[idx].account.id;

                        this.feed = this.feed.filter(post => {
                            return post.account.id != id;
                        });
                    break;
                }
            },

            deletePost() {
                this.feed.splice(this.postIndex, 1);
            },

            counterChange(index, type) {
                let post = this.feed[index];
                switch(type) {
                    case 'comment-increment':
                        if(post.reblog != null) {
                            this.feed[index].reblog.reply_count = this.feed[index].reblog.reply_count + 1;
                        } else {
                            this.feed[index].reply_count = this.feed[index].reply_count + 1;
                        }
                    break;

                    case 'comment-decrement':
                        if(post.reblog != null) {
                            this.feed[index].reblog.reply_count = this.feed[index].reblog.reply_count - 1;
                        } else {
                            this.feed[index].reply_count = this.feed[index].reply_count - 1;
                        }
                    break;
                }
            },

            openCommentLikesModal(post) {
                if(post.reblog != null) {
                    this.likesModalPost = post.reblog;
                } else {
                    this.likesModalPost = post;
                }
                this.showLikesModal = true;
                this.$nextTick(() => {
                    this.$refs.likesModal.open();
                });
            },

            shareStatus(index) {
                let status = this.feed[index];
                if(status.reblog) {
                    status = status.reblog;
                    let state = status.reblogged;
                    let count = status.reblogs_count;
                    this.feed[index].reblog.reblogs_count = count + 1;
                    this.feed[index].reblog.reblogged = !status.reblogged;
                } else {
                    let state = status.reblogged;
                    let count = status.reblogs_count;
                    this.feed[index].reblogs_count = count + 1;
                    this.feed[index].reblogged = !status.reblogged;
                }

                axios.post('/api/v1/statuses/' + status.id + '/reblog')
                .then(res => {
                    //
                }).catch(err => {
                    if(status.reblog) {
                        this.feed[index].reblog.reblogs_count = count;
                        this.feed[index].reblog.reblogged = false;
                    } else {
                        this.feed[index].reblogs_count = count;
                        this.feed[index].reblogged = false;
                    }
                })
            },

            unshareStatus(index) {
                let status = this.feed[index];
                if(status.reblog) {
                    status = status.reblog;
                    let state = status.reblogged;
                    let count = status.reblogs_count;
                    this.feed[index].reblog.reblogs_count = count - 1;
                    this.feed[index].reblog.reblogged = !status.reblogged;
                } else {
                    let state = status.reblogged;
                    let count = status.reblogs_count;
                    this.feed[index].reblogs_count = count - 1;
                    this.feed[index].reblogged = !status.reblogged;
                }

                axios.post('/api/v1/statuses/' + status.id + '/unreblog')
                .then(res => {
                    //
                }).catch(err => {
                    if(status.reblog) {
                        this.feed[index].reblog.reblogs_count = count;
                        this.feed[index].reblog.reblogged = false;
                    } else {
                        this.feed[index].reblogs_count = count;
                        this.feed[index].reblogged = false;
                    }
                })
            },

            handleReport(post) {
                this.reportedStatusId = post.id;
                this.$nextTick(() => {
                    this.reportedStatus = post;
                    this.$refs.reportModal.open();
                });
            },

            handleBookmark(index) {
                let p = this.feed[index];

                if(p.reblog) {
                    p = p.reblog;
                }

                axios.post('/i/bookmark', {
                    item: p.id
                })
                .then(res => {
                    if(this.feed[index].reblog) {
                        this.feed[index].reblog.bookmarked = !p.bookmarked;
                    } else {
                        this.feed[index].bookmarked = !p.bookmarked;
                    }
                })
                .catch(err => {
                    // this.feed[index].bookmarked = false;
                    this.$bvToast.toast('Cannot bookmark post at this time.', {
                        title: 'Bookmark Error',
                        variant: 'danger',
                        autoHideDelay: 5000
                    });
                });
            },

            follow(index) {
                if(this.feed[index].reblog) {
                    axios.post('/api/v1/accounts/' + this.feed[index].reblog.account.id + '/follow')
                    .then(res => {
                        this.$store.commit('updateRelationship', [res.data]);
                        this.updateProfile({ following_count: this.profile.following_count + 1 });
                        this.feed[index].reblog.account.followers_count = this.feed[index].reblog.account.followers_count + 1;
                    }).catch(err => {
                        swal('Oops!', 'An error occured when attempting to follow this account.', 'error');
                        this.feed[index].reblog.relationship.following = false;
                    });
                } else {
                    axios.post('/api/v1/accounts/' + this.feed[index].account.id + '/follow')
                    .then(res => {
                        this.$store.commit('updateRelationship', [res.data]);
                        this.updateProfile({ following_count: this.profile.following_count + 1 });
                        this.feed[index].account.followers_count = this.feed[index].account.followers_count + 1;
                    }).catch(err => {
                        swal('Oops!', 'An error occured when attempting to follow this account.', 'error');
                        this.feed[index].relationship.following = false;
                    });
                }
            },

            unfollow(index) {
                if(this.feed[index].reblog) {
                    axios.post('/api/v1/accounts/' + this.feed[index].reblog.account.id + '/unfollow')
                    .then(res => {
                        this.$store.commit('updateRelationship', [res.data]);
                        this.updateProfile({ following_count: this.profile.following_count - 1 });
                        this.feed[index].reblog.account.followers_count = this.feed[index].reblog.account.followers_count - 1;
                    }).catch(err => {
                        swal('Oops!', 'An error occured when attempting to unfollow this account.', 'error');
                        this.feed[index].reblog.relationship.following = true;
                    });
                } else {
                    axios.post('/api/v1/accounts/' + this.feed[index].account.id + '/unfollow')
                    .then(res => {
                        this.$store.commit('updateRelationship', [res.data]);
                        this.updateProfile({ following_count: this.profile.following_count - 1 });
                        this.feed[index].account.followers_count = this.feed[index].account.followers_count - 1;
                    }).catch(err => {
                        swal('Oops!', 'An error occured when attempting to unfollow this account.', 'error');
                        this.feed[index].relationship.following = true;
                    });
                }
            },

            updateProfile(delta) {
                this.$emit('update-profile', delta);
            },

            handleRefresh() {
                this.isLoaded = false;
                this.feed = [];
                this.ids = [];
                this.max_id = 0;
                this.canLoadMore = true;
                this.showLoadMore = false;
                this.loadMoreTimeout = undefined;
                this.loadMoreAttempts = 0;
                this.isFetchingMore = false;
                this.endFeedReached = false;
                this.postIndex = 0;
                this.showMenu = false;
                this.showLikesModal = false;
                this.likesModalPost = {};
                this.showReportModal = false;
                this.reportedStatus = {};
                this.reportedStatusId = 0;
                this.showSharesModal = false;
                this.sharesModalPost = {};

                this.$nextTick(() => {
                    this.fetchTimeline(true);
                });
            },

            handleEdit(status) {
                this.$refs.editModal.show(status);
            },

            mergeUpdatedPost(post) {
                this.feed = this.feed.map(p => {
                    if(p.id == post.id) {
                        p = post;
                    }
                    return p;
                });
                this.$nextTick(() => {
                    this.forceUpdateIdx++;
                });
            },

            enableReblogs() {
                this.enablingReblogs = true;

                axios.post('/api/pixelfed/v1/web/settings', {
                    field: 'enable_reblogs',
                    value: true
                })
                .then(res => {
                    setTimeout(() => {
                        window.location.reload();
                    }, 1000);
                })
            },

            hideReblogs() {
                this.showReblogBanner = false;
                axios.post('/api/pixelfed/v1/web/settings', {
                    field: 'hide_reblog_banner',
                    value: true
                })
                .then(res => {
                })
            },
        },

        watch: {
            'refresh': 'handleRefresh'
        },

        beforeDestroy() {
            clearTimeout(this.loadMoreTimeout);
        }
    }
</script>
