<template>
    <div class="profile-feed-component">
        <div class="profile-feed-component-nav d-flex justify-content-center justify-content-md-between align-items-center mb-4">
            <div class="d-none d-md-block border-bottom flex-grow-1 profile-nav-btns">
                <div class="btn-group">
                    <button
                        class="btn btn-link"
                        :class="[ tabIndex === 1 ? 'active' : '' ]"
                        @click="toggleTab(1)"
                        >
                        {{ $t("profile.posts")}}
                    </button>

                    <button
                        v-if="isOwner"
                        class="btn btn-link"
                        :class="[ tabIndex === 'archives' ? 'active' : '' ]"
                        @click="toggleTab('archives')">
                        {{ $t("profile.archives")}}
                    </button>

                    <button
                        v-if="isOwner"
                        class="btn btn-link"
                        :class="[ tabIndex === 'bookmarks' ? 'active' : '' ]"
                        @click="toggleTab('bookmarks')">
                        {{ $t("profile.bookmarks")}}
                    </button>

                    <button
                        v-if="canViewCollections"
                        class="btn btn-link"
                        :class="[ tabIndex === 2 ? 'active' : '' ]"
                        @click="toggleTab(2)">
                        {{ $t("profile.collections")}}
                    </button>

                    <button
                        v-if="isOwner"
                        class="btn btn-link"
                        :class="[ tabIndex === 3 ? 'active' : '' ]"
                        @click="toggleTab(3)">
                        {{ $t("profile.likes")}}
                    </button>
                </div>
            </div>

            <div v-if="tabIndex === 1" class="btn-group layout-sort-toggle">
                <button
                    class="btn btn-sm"
                    :class="[ layoutIndex === 0 ? 'btn-dark' : 'btn-light' ]"
                    @click="toggleLayout(0, true)">
                    <i class="far fa-th fa-lg"></i>
                </button>

                <button
                    class="btn btn-sm"
                    :class="[ layoutIndex === 1 ? 'btn-dark' : 'btn-light' ]"
                    @click="toggleLayout(1, true)">
                    <i class="fas fa-th-large fa-lg"></i>
                </button>

                <button
                    class="btn btn-sm"
                    :class="[ layoutIndex === 2 ? 'btn-dark' : 'btn-light' ]"
                    @click="toggleLayout(2, true)">
                    <i class="far fa-bars fa-lg"></i>
                </button>
            </div>
        </div>

        <div v-if="tabIndex == 0" class="d-flex justify-content-center mt-5">
            <b-spinner />
        </div>

        <div v-else-if="tabIndex == 1" class="px-0 mx-0">
            <div v-if="layoutIndex === 0" class="row">
                <div class="col-4 p-1" v-for="(s, index) in feed" :key="'tlob:'+index+s.id">
                    <a v-if="s.hasOwnProperty('pf_type') && s.pf_type == 'video'" class="card info-overlay card-md-border-0" :href="statusUrl(s)">
                        <div class="square">
                            <div v-if="s.sensitive" class="square-content">
                                <div class="info-overlay-text-label">
                                    <h5 class="text-white m-auto font-weight-bold">
                                        <span>
                                            <span class="far fa-eye-slash fa-lg p-2 d-flex-inline"></span>
                                        </span>
                                    </h5>
                                </div>
                                <blur-hash-canvas
                                    width="32"
                                    height="32"
                                    :hash="s.media_attachments[0].blurhash">
                                </blur-hash-canvas>
                            </div>
                            <div v-else class="square-content">
                                <blur-hash-image
                                    width="32"
                                    height="32"
                                    :hash="s.media_attachments[0].blurhash"
                                    :src="s.media_attachments[0].preview_url">
                                </blur-hash-image>
                            </div>
                            <div class="info-overlay-text">
                                <div class="text-white m-auto">
                                    <p class="info-overlay-text-field font-weight-bold">
                                        <span class="far fa-heart fa-lg p-2 d-flex-inline"></span>
                                        <span class="d-flex-inline">{{formatCount(s.favourites_count)}}</span>
                                    </p>

                                    <p class="info-overlay-text-field font-weight-bold">
                                        <span class="far fa-comment fa-lg p-2 d-flex-inline"></span>
                                        <span class="d-flex-inline">{{formatCount(s.reply_count)}}</span>
                                    </p>

                                    <p class="mb-0 info-overlay-text-field font-weight-bold">
                                        <span class="far fa-sync fa-lg p-2 d-flex-inline"></span>
                                        <span class="d-flex-inline">{{formatCount(s.reblogs_count)}}</span>
                                    </p>
                                </div>
                            </div>
                        </div>

                        <span class="badge badge-light video-overlay-badge">
                            <i class="far fa-video fa-2x"></i>
                        </span>

                        <span class="badge badge-light timestamp-overlay-badge">
                            {{ timeago(s.created_at) }}
                        </span>
                    </a>
                    <a v-else class="card info-overlay card-md-border-0" :href="statusUrl(s)">
                        <div class="square">
                            <div v-if="s.sensitive" class="square-content">
                                <div class="info-overlay-text-label">
                                    <h5 class="text-white m-auto font-weight-bold">
                                        <span>
                                            <span class="far fa-eye-slash fa-lg p-2 d-flex-inline"></span>
                                        </span>
                                    </h5>
                                </div>
                                <blur-hash-canvas
                                    width="32"
                                    height="32"
                                    :hash="s.media_attachments[0].blurhash">
                                </blur-hash-canvas>
                            </div>
                            <div v-else class="square-content">
                                <blur-hash-image
                                    width="32"
                                    height="32"
                                    :hash="s.media_attachments[0].blurhash"
                                    :src="s.media_attachments[0].url">
                                </blur-hash-image>
                            </div>
                            <div class="info-overlay-text">
                                <div class="text-white m-auto">
                                    <p class="info-overlay-text-field font-weight-bold">
                                        <span class="far fa-heart fa-lg p-2 d-flex-inline"></span>
                                        <span class="d-flex-inline">{{formatCount(s.favourites_count)}}</span>
                                    </p>

                                    <p class="info-overlay-text-field font-weight-bold">
                                        <span class="far fa-comment fa-lg p-2 d-flex-inline"></span>
                                        <span class="d-flex-inline">{{formatCount(s.reply_count)}}</span>
                                    </p>

                                    <p class="mb-0 info-overlay-text-field font-weight-bold">
                                        <span class="far fa-sync fa-lg p-2 d-flex-inline"></span>
                                        <span class="d-flex-inline">{{formatCount(s.reblogs_count)}}</span>
                                    </p>
                                </div>
                            </div>
                        </div>

                        <span class="badge badge-light timestamp-overlay-badge">
                            {{ timeago(s.created_at) }}
                        </span>
                        <span v-if="s.pinned" class="badge badge-light pinned-overlay-badge">
                            <i class="fa fa-tag" aria-hidden="true"></i>
                        </span>
                    </a>
                </div>

                <intersect v-if="canLoadMore" @enter="enterIntersect">
                    <div class="col-4 ph-wrapper">
                        <div class="ph-item">
                           <div class="ph-picture big"></div>
                       </div>
                   </div>
               </intersect>
            </div>

            <div v-else-if="layoutIndex === 1" class="row">
                <masonry
                    :cols="{default: 3, 800: 2}"
                    :gutter="{default: '5px'}">

                    <div class="p-1" v-for="(s, index) in feed" :key="'tlog:'+index+s.id">
                        <a v-if="s.hasOwnProperty('pf_type') && s.pf_type == 'video'" class="card info-overlay card-md-border-0" :href="statusUrl(s)">
                            <div class="square">
                                <div class="square-content">
                                    <blur-hash-image
                                        width="32"
                                        height="32"
                                        class="rounded"
                                        :hash="s.media_attachments[0].blurhash"
                                        :src="s.media_attachments[0].preview_url">
                                    </blur-hash-image>
                                </div>
                            </div>

                            <span class="badge badge-light video-overlay-badge">
                                <i class="far fa-video fa-2x"></i>
                            </span>

                            <span class="badge badge-light timestamp-overlay-badge">
                                {{ timeago(s.created_at) }}
                            </span>

                            <span v-if="s.pinned" class="badge badge-light pinned-overlay-badge">
                                <i class="fa fa-tag" aria-hidden="true"></i>
                            </span>
                        </a>

                        <a v-else-if="s.sensitive" class="card info-overlay card-md-border-0" :href="statusUrl(s)">
                            <div class="square">
                                <div class="square-content">
                                    <div class="info-overlay-text-label rounded">
                                        <h5 class="text-white m-auto font-weight-bold">
                                            <span>
                                                <span class="far fa-eye-slash fa-lg p-2 d-flex-inline"></span>
                                            </span>
                                        </h5>
                                    </div>
                                    <blur-hash-canvas
                                        width="32"
                                        height="32"
                                        class="rounded"
                                        :hash="s.media_attachments[0].blurhash">
                                    </blur-hash-canvas>
                                </div>
                            </div>
                        </a>

                        <a v-else class="card info-overlay card-md-border-0" :href="statusUrl(s)">
                            <img :src="previewUrl(s)" class="img-fluid w-100 rounded-lg" onerror="this.onerror=null;this.src='/storage/no-preview.png?v=0'">
                            <span class="badge badge-light timestamp-overlay-badge">
                                {{ timeago(s.created_at) }}
                            </span>
                            <span v-if="s.pinned" class="badge badge-light pinned-overlay-badge">
                                <i class="fa fa-tag" aria-hidden="true"></i>
                            </span>
                        </a>
                    </div>

                    <intersect v-if="canLoadMore" @enter="enterIntersect">
                        <div class="p-1 ph-wrapper">
                            <div class="ph-item">
                                <div class="ph-picture big"></div>
                            </div>
                        </div>
                    </intersect>
                </masonry>
            </div>

            <div v-else-if="layoutIndex === 2" class="row justify-content-center">
                <div class="col-12 col-md-10">
                    <status-card
                        v-for="(s, index) in feed"
                        :key="'prs'+s.id+':'+index"
                        :profile="user"
                        :status="s"
                        v-on:like="likeStatus(index, 'feed')"
                        v-on:unlike="unlikeStatus(index, 'feed')"
                        v-on:share="shareStatus(index, 'feed')"
                        v-on:unshare="unshareStatus(index, 'feed')"
                        v-on:menu="openContextMenu(index, 'feed')"
                        v-on:counter-change="counterChange(index, $event)"
                        v-on:likes-modal="openLikesModal(index, 'feed')"
                        v-on:shares-modal="openSharesModal(index, 'feed')"
                        v-on:comment-likes-modal="openCommentLikesModal"
                        v-on:bookmark="handleBookmark(index)"
                        v-on:handle-report="handleReport" />
                </div>

                <intersect v-if="canLoadMore" @enter="enterIntersect">
                    <div class="col-12 col-md-10">
                        <status-placeholder style="margin-bottom: 10rem;" />
                     </div>
                </intersect>
            </div>

            <div v-if="feedLoaded && !feed.length">
                <div class="row justify-content-center">
                    <div class="col-12 col-md-8 text-center">
                        <img src="/img/illustrations/dk-nature-man-monochrome.svg" class="img-fluid" style="opacity: 0.6;">
                        <p class="lead text-muted font-weight-bold">{{ $t('profile.emptyPosts') }}</p>
                    </div>
                </div>
            </div>
        </div>

        <div v-else-if="tabIndex === 'private'" class="row justify-content-center">
            <div class="col-12 col-md-8 text-center">
                <img src="/img/illustrations/dk-secure-feed.svg" class="img-fluid" style="opacity: 0.6;">
                <p class="h3 text-dark font-weight-bold mt-3 py-3">{{ $t("profile.private")}}</p>
                <div class="lead text-muted px-3">
                    Only approved followers can see <span class="font-weight-bold text-dark text-break">&commat;{{ profile.acct }}</span>'s <br />
                    posts. To request access, click <span class="font-weight-bold">Follow</span>.
                </div>
            </div>
        </div>

        <div v-else-if="tabIndex == 2" class="row justify-content-center">
            <div class="col-12 col-md-8">
                <div class="list-group">
                    <a
                        v-for="(collection, index) in collections"
                        class="list-group-item text-decoration-none text-dark"
                        :href="collection.url">
                        <div class="media">
                            <img :src="collection.thumb" width="65" height="65" style="object-fit: cover;" class="rounded-lg border mr-3" onerror="this.onerror=null;this.src='/storage/no-preview.png';">
                            <div class="media-body text-left">
                                <p class="lead mb-0">{{ collection.title ? collection.title : $t("profile.untitled") }}</p>
                                <p class="small text-muted mb-1">{{ collection.description || $t("profile.noDescription") }}</p>
                                <p class="small text-lighter mb-0 font-weight-bold">
                                    <span>{{ collection.post_count }} {{ $t("profile.posts")}}</span>
                                    <span>&middot;</span>
                                    <span v-if="collection.visibility === 'public'" class="text-dark">{{ $t("profile.public")}}</span>
                                    <span v-else-if="collection.visibility === 'private'" class="text-dark"><i class="far fa-lock fa-sm"></i> Followers Only</span>
                                    <span v-else-if="collection.visibility === 'draft'" class="primary"><i class="far fa-lock fa-sm"></i> {{ $t("profile.draft")}}</span>
                                    <span>&middot;</span>
                                    <span v-if="collection.published_at">Created {{ timeago(collection.published_at) }} ago</span>
                                    <span v-else class="text-warning">UNPUBLISHED</span>
                                </p>
                            </div>
                        </div>
                    </a>
                </div>
            </div>

            <div v-if="collectionsLoaded && !collections.length" class="col-12 col-md-8 text-center">
                <img src="/img/illustrations/dk-nature-man-monochrome.svg" class="img-fluid" style="opacity: 0.6;">
                <p class="lead text-muted font-weight-bold">{{ $t('profile.emptyCollections') }}</p>
            </div>

            <div v-if="canLoadMoreCollections" class="col-12 col-md-8">
                <intersect @enter="enterCollectionsIntersect">
                    <div class="d-flex justify-content-center mt-5">
                        <b-spinner small />
                    </div>
                </intersect>
            </div>
        </div>

        <div v-else-if="tabIndex == 3" class="px-0 mx-0">
            <div class="row justify-content-center">
                <div class="col-12 col-md-10">
                    <status-card
                        v-for="(s, index) in favourites"
                        :key="'prs'+s.id+':'+index"
                        :profile="user"
                        :status="s"
                        v-on:like="likeStatus(index, 'likes')"
                        v-on:unlike="unlikeStatus(index, 'likes')"
                        v-on:menu="openContextMenu(index, 'likes')"
                        v-on:share="shareStatus(index, 'likes')"
                        v-on:unshare="unshareStatus(index, 'likes')"
                        v-on:counter-change="counterChange(index, $event)"
                        v-on:likes-modal="openLikesModal(index, 'likes')"
                        v-on:shares-modal="openSharesModal(index, 'likes')"
                        v-on:bookmark="handleBookmark(index, 'likes')"
                        v-on:comment-likes-modal="openCommentLikesModal"
                        v-on:handle-report="handleReport" />
                </div>

                <div v-if="canLoadMoreFavourites" class="col-12 col-md-10">
                    <intersect @enter="enterFavouritesIntersect">
                        <status-placeholder style="margin-bottom: 10rem;" />
                    </intersect>
                </div>
            </div>

            <div v-if="!favourites || !favourites.length" class="row justify-content-center">
                <div class="col-12 col-md-8 text-center">
                    <img src="/img/illustrations/dk-nature-man-monochrome.svg" class="img-fluid" style="opacity: 0.6;">
                    <p class="lead text-muted font-weight-bold">{{ $t("profile.emptyLikes")}}</p>
                </div>
            </div>
        </div>

        <div v-else-if="tabIndex == 'bookmarks'" class="px-0 mx-0">
            <div class="row justify-content-center">
                <div class="col-12 col-md-10">
                    <status-card
                        v-for="(s, index) in bookmarks"
                        :key="'prs'+s.id+':'+index"
                        :profile="user"
                        :new-reactions="true"
                        :status="s"
                        v-on:like="likeStatus(index, 'bookmarks')"
                        v-on:unlike="unlikeStatus(index, 'bookmarks')"
                        v-on:menu="openContextMenu(index, 'bookmarks')"
                        v-on:counter-change="counterChange(index, $event)"
                        v-on:share="shareStatus(index, 'bookmarks')"
                        v-on:unshare="unshareStatus(index, 'bookmarks')"
                        v-on:likes-modal="openLikesModal(index, 'bookmarks')"
                        v-on:bookmark="handleBookmark(index, 'bookmarks')"
                        v-on:shares-modal="openSharesModal(index, 'bookmarks')"
                        v-on:comment-likes-modal="openCommentLikesModal"
                        v-on:handle-report="handleReport" />
                </div>

                <div class="col-12 col-md-10">
                    <intersect v-if="canLoadMoreBookmarks" @enter="enterBookmarksIntersect">
                        <status-placeholder style="margin-bottom: 10rem;" />
                    </intersect>
                </div>
            </div>

            <div v-if="!bookmarks || !bookmarks.length" class="row justify-content-center">
                <div class="col-12 col-md-8 text-center">
                    <img src="/img/illustrations/dk-nature-man-monochrome.svg" class="img-fluid" style="opacity: 0.6;">
                    <p class="lead text-muted font-weight-bold">{{ $t("profile.emptyBookmarks")}}</p>
                </div>
            </div>
        </div>

        <div v-else-if="tabIndex == 'archives'" class="px-0 mx-0">
            <div class="row justify-content-center">
                <div class="col-12 col-md-10">
                    <status-card
                        v-for="(s, index) in archives"
                        :key="'prarc'+s.id+':'+index"
                        :profile="user"
                        :new-reactions="true"
                        :reaction-bar="false"
                        :status="s"
                        v-on:menu="openContextMenu(index, 'archive')"
                        />
                </div>

                <div v-if="canLoadMoreArchives" class="col-12 col-md-10">
                    <intersect @enter="enterArchivesIntersect">
                        <status-placeholder style="margin-bottom: 10rem;" />
                    </intersect>
                </div>
            </div>

            <div v-if="!archives || !archives.length" class="row justify-content-center">
                <div class="col-12 col-md-8 text-center">
                    <img src="/img/illustrations/dk-nature-man-monochrome.svg" class="img-fluid" style="opacity: 0.6;">
                    <p class="lead text-muted font-weight-bold">{{ $t("profile.emptyArchives") }}</p>
                </div>
            </div>
        </div>

        <context-menu
            v-if="showMenu"
            ref="contextMenu"
            :status="contextMenuPost"
            :profile="user"
            v-on:moderate="commitModeration"
            v-on:delete="deletePost"
            v-on:archived="handleArchived"
            v-on:unarchived="handleUnarchived"
            v-on:report-modal="handleReport"
        />

        <likes-modal
            v-if="showLikesModal"
            ref="likesModal"
            :status="likesModalPost"
            :profile="user"
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
    </div>
</template>

<script type="text/javascript">
    import Intersect from 'vue-intersect'
    import StatusCard from './../TimelineStatus.vue';
    import StatusPlaceholder from './../StatusPlaceholder.vue';
    import BlurHashCanvas from './../BlurhashCanvas.vue';
    import ContextMenu from './../../partials/post/ContextMenu.vue';
    import LikesModal from './../../partials/post/LikeModal.vue';
    import SharesModal from './../../partials/post/ShareModal.vue';
    import ReportModal from './../../partials/modal/ReportPost.vue';
    import { parseLinkHeader } from '@web3-storage/parse-link-header';

    export default {
        props: {
            profile: {
                type: Object
            },

            relationship: {
                type: Object
            }
        },

        components: {
            "intersect": Intersect,
            "status-card": StatusCard,
            "bh-canvas": BlurHashCanvas,
            "status-placeholder": StatusPlaceholder,
            "context-menu": ContextMenu,
            "likes-modal": LikesModal,
            "shares-modal": SharesModal,
            "report-modal": ReportModal
        },

        data() {
            return {
                isLoaded: false,
                user: {},
                isOwner: false,
                layoutIndex: 0,
                tabIndex: 0,
                ids: [],
                feed: [],
                feedLoaded: false,
                collections: [],
                collectionsLoaded: false,
                canLoadMore: false,
                max_id: 1,
                cursor: null,
                isIntersecting: false,
                postIndex: 0,
                showMenu: false,
                showLikesModal: false,
                likesModalPost: {},
                showReportModal: false,
                reportedStatus: {},
                reportedStatusId: 0,
                favourites: [],
                favouritesLoaded: false,
                favouritesPage: 1,
                canLoadMoreFavourites: false,
                bookmarks: [],
                bookmarksLoaded: false,
                bookmarksPage: 1,
                bookmarksCursor: undefined,
                canLoadMoreBookmarks: false,
                canLoadMoreCollections: false,
                collectionsPage: 1,
                isCollectionsIntersecting: false,
                canViewCollections: false,
                showSharesModal: false,
                sharesModalPost: {},
                archives: [],
                archivesLoaded: false,
                archivesPage: 1,
                canLoadMoreArchives: false,
                contextMenuPost: {},
                contextMenuType: undefined,
            }
        },

        mounted() {
            this.init();
        },

        methods: {
            init() {
                this.user = window._sharedData.user;

                if(this.$store.state.profileLayout != 'grid') {
                    let index = this.$store.state.profileLayout === 'masonry' ? 1 : 2;
                    this.toggleLayout(index);
                }

                if(this.user) {
                    this.isOwner = this.user.id == this.profile.id;
                    if(this.isOwner) {
                        this.canViewCollections = true;
                    }
                }

                if(this.profile.locked) {
                    this.privateProfileCheck();
                } else {
                    if(this.profile.local) {
                        this.canViewCollections = true;
                    }
                    this.fetchFeed();
                }
            },

            privateProfileCheck() {
                if(this.relationship.following || this.isOwner) {
                    this.canViewCollections = true;
                    this.fetchFeed();
                } else {
                    this.tabIndex = 'private';
                    this.isLoaded = true;
                }
            },

            fetchFeed() {
                axios.get('/api/pixelfed/v1/accounts/' + this.profile.id + '/statuses', {
                    params: {
                        limit: 9,
                        only_media: true,
                        min_id: 1,
                        pinned: true,
                    }
                })
                .then(res => {
                    this.tabIndex = 1;
                    let data = res.data.filter(status => status.media_attachments.length > 0);
                    let ids = data.map(status => status.id);
                    this.ids = ids;
                    data.forEach(s => {
                        this.feed.push(s);
                    });

                    if(res.headers && res.headers.link) {
                        const links = parseLinkHeader(res.headers.link);
                        if(links.prev) {
                            this.cursor = links.prev.cursor;
                            this.canLoadMore = true;
                        } else {
                            this.cursor = null;
                            this.canLoadMore = false;
                        }
                    } else {
                        this.cursor = null;
                        this.canLoadMore = false;
                    }
                    setTimeout(() => {
                       this.feedLoaded = true;
                    }, 500);
                });
            },

            enterIntersect() {
                if(this.isIntersecting || !this.cursor) {
                    return;
                }
                this.isIntersecting = true;

                axios.get('/api/pixelfed/v1/accounts/' + this.profile.id + '/statuses', {
                    params: {
                        limit: 9,
                        only_media: true,
                        pinned: true,
                        cursor: this.cursor
                    }
                })
                .then(res => {
                    if(!res.data || !res.data.length) {
                        this.canLoadMore = false;
                    }
                    let data = res.data
                        .filter(status => status.media_attachments.length > 0)
                        .filter(status => this.ids.indexOf(status.id) == -1)

                    if(!data || !data.length) {
                        this.isIntersecting = false;
                        return;
                    }

                    let filtered = data.forEach(status => {
                            this.ids.push(status.id);
                            this.feed.push(status);
                        });

                    if(res.headers && res.headers.link) {
                        const links = parseLinkHeader(res.headers.link);
                        if(links.prev) {
                            this.cursor = links.prev.cursor;
                            this.canLoadMore = true;
                        } else {
                            this.cursor = null;
                            this.canLoadMore = false;
                        }
                    } else {
                        this.cursor = null;
                        this.canLoadMore = false;
                    }
                    this.isIntersecting = false;
                }).catch(err => {
                    this.canLoadMore = false;
                });
            },

            toggleLayout(idx, blur = false) {
                if(blur) {
                    event.currentTarget.blur();
                }
                this.layoutIndex = idx;
                this.isIntersecting = false;
            },

            toggleTab(idx) {
                event.currentTarget.blur();

                switch(idx) {
                    case 1:
                        this.isIntersecting = false;
                        this.tabIndex = 1;
                    break;

                    case 2:
                        this.fetchCollections();
                    break;

                    case 3:
                        this.fetchFavourites();
                    break;

                    case 'bookmarks':
                        this.fetchBookmarks();
                    break;

                    case 'archives':
                        this.fetchArchives();
                    break;
                }
            },

            fetchCollections() {
                if(this.collectionsLoaded) {
                    this.tabIndex = 2;
                }

                axios.get('/api/local/profile/collections/' + this.profile.id)
                .then(res => {
                    this.collections = res.data;
                    this.collectionsLoaded = true;
                    this.tabIndex = 2;
                    this.collectionsPage++;
                    this.canLoadMoreCollections = res.data.length === 9;
                })
            },

            enterCollectionsIntersect() {
                if(this.isCollectionsIntersecting) {
                    return;
                }
                this.isCollectionsIntersecting = true;

                axios.get('/api/local/profile/collections/' + this.profile.id, {
                    params: {
                        limit: 9,
                        page: this.collectionsPage
                    }
                })
                .then(res => {
                    if(!res.data || !res.data.length) {
                        this.canLoadMoreCollections = false;
                    }
                    this.collectionsLoaded = true;
                    this.collections.push(...res.data);
                    this.collectionsPage++;
                    this.canLoadMoreCollections = res.data.length > 0;
                    this.isCollectionsIntersecting = false;
                }).catch(err => {
                    this.canLoadMoreCollections = false;
                    this.isCollectionsIntersecting = false;
                });
            },

            fetchFavourites() {
                this.tabIndex = 0;
                axios.get('/api/pixelfed/v1/favourites')
                .then(res => {
                    this.tabIndex = 3;
                    this.favourites = res.data;
                    this.favouritesPage++;
                    this.favouritesLoaded = true;

                    if(res.data.length != 0) {
                        this.canLoadMoreFavourites = true;
                    }
                })
            },

            enterFavouritesIntersect() {
                if(this.isIntersecting) {
                    return;
                }
                this.isIntersecting = true;

                axios.get('/api/pixelfed/v1/favourites', {
                    params: {
                        page: this.favouritesPage,
                    }
                })
                .then(res => {
                    this.favourites.push(...res.data);
                    this.favouritesPage++;
                    this.canLoadMoreFavourites = res.data.length != 0;
                    this.isIntersecting = false;
                })
                .catch(err => {
                    this.canLoadMoreFavourites = false;
                })
            },

            fetchBookmarks() {
                this.tabIndex = 0;
                axios.get('/api/v1/bookmarks', {
                    params: {
                        '_pe': 1
                    }
                })
                .then(res => {
                    this.tabIndex = 'bookmarks';
                    this.bookmarks = res.data;

                    if(res.headers && res.headers.link) {
                        const links = parseLinkHeader(res.headers.link);
                        if(links.next) {
                            this.bookmarksPage = links.next.cursor;
                            this.canLoadMoreBookmarks = true;
                        } else {
                            this.canLoadMoreBookmarks = false;
                        }
                    }

                    this.bookmarksLoaded = true;
                })
            },

            enterBookmarksIntersect() {
                if(this.isIntersecting) {
                    return;
                }
                this.isIntersecting = true;

                axios.get('/api/v1/bookmarks', {
                    params: {
                        '_pe': 1,
                        cursor: this.bookmarksPage,
                    }
                })
                .then(res => {
                    this.bookmarks.push(...res.data);
                    if(res.headers && res.headers.link) {
                        const links = parseLinkHeader(res.headers.link);
                        if(links.next) {
                            this.bookmarksPage = links.next.cursor;
                            this.canLoadMoreBookmarks = true;
                        } else {
                            this.canLoadMoreBookmarks = false;
                        }
                    }
                    this.isIntersecting = false;
                })
                .catch(err => {
                    this.canLoadMoreBookmarks = false;
                })
            },

            fetchArchives() {
                this.tabIndex = 0;
                axios.get('/api/pixelfed/v2/statuses/archives')
                .then(res => {
                    this.tabIndex = 'archives';
                    this.archives = res.data;
                    this.archivesPage++;
                    this.archivesLoaded = true;

                    if(res.data.length != 0) {
                        this.canLoadMoreArchives = true;
                    }
                })
            },

            formatCount(val) {
                return App.util.format.count(val);
            },

            statusUrl(s) {
                return '/i/web/post/' + s.id;
            },

            previewUrl(status) {
                return status.sensitive ? '/storage/no-preview.png?v=' + new Date().getTime() : status.media_attachments[0].url;
            },

            timeago(ts) {
                return App.util.format.timeAgo(ts);
            },

            likeStatus(index, source = 'feed') {
                const sourceMap = {
                    'feed': this.feed,
                    'likes': this.favourites,
                    'bookmarks': this.bookmarks
                };

                const sourceArray = sourceMap[source] || this.feed;
                const status = sourceArray[index];
                const originalFavourited = status.favourited;
                const originalCount = status.favourites_count;

                sourceArray[index].favourites_count = originalCount + 1;
                sourceArray[index].favourited = !originalFavourited;

                axios.post(`/api/v1/statuses/${status.id}/favourite`)
                    .catch(err => {
                        sourceArray[index].favourites_count = originalCount;
                        sourceArray[index].favourited = originalFavourited;
                    });
            },

            unlikeStatus(index, source = 'feed') {
                const sourceMap = {
                    'feed': this.feed,
                    'likes': this.favourites,
                    'bookmarks': this.bookmarks
                };

                const sourceArray = sourceMap[source] || this.feed;
                const status = sourceArray[index];
                const originalFavourited = status.favourited;
                const originalCount = status.favourites_count;

                sourceArray[index].favourites_count = originalCount - 1;
                sourceArray[index].favourited = !originalFavourited;

                axios.post(`/api/v1/statuses/${status.id}/unfavourite`)
                    .catch(err => {
                        sourceArray[index].favourites_count = originalCount;
                        sourceArray[index].favourited = originalFavourited;
                    });
            },

            openContextMenu(idx, type = 'feed') {
                const sourceMap = {
                    'feed': this.feed,
                    'likes': this.favourites,
                    'bookmarks': this.bookmarks,
                    'archive': this.archives
                };

                const sourceArray = sourceMap[type] || this.feed;

                this.postIndex = idx;
                this.contextMenuPost = sourceArray[idx];
                this.contextMenuType = type;
                this.showMenu = true;
                this.$nextTick(() => {
                    this.$refs.contextMenu.open();
                });
            },

            openLikesModal(idx, source = 'feed') {
                this.postIndex = idx;
                switch(source) {
                    case 'feed':
                        this.likesModalPost = this.feed[this.postIndex];
                    break;

                    case 'likes':
                        this.likesModalPost = this.favourites[this.postIndex];
                    break;

                    case 'bookmarks':
                        this.likesModalPost = this.bookmarks[this.postIndex];
                    break;

                    default:
                        this.likesModalPost = this.feed[this.postIndex];
                    break;
                }
                this.showLikesModal = true;
                this.$nextTick(() => {
                    this.$refs.likesModal.open();
                });
            },

            openSharesModal(idx, source = 'feed') {
                this.postIndex = idx;
                switch(source) {
                    case 'feed':
                        this.sharesModalPost = this.feed[this.postIndex];
                    break;

                    case 'likes':
                        this.sharesModalPost = this.favourites[this.postIndex];
                    break;

                    case 'bookmarks':
                        this.sharesModalPost = this.bookmarks[this.postIndex];
                    break;

                    default:
                        this.sharesModalPost = this.feed[this.postIndex];
                    break;
                }
                this.showSharesModal = true;
                this.$nextTick(() => {
                    this.$refs.sharesModal.open();
                });
            },

            commitModeration(type) {
                let idx = this.postIndex;

                const sourceMap = {
                    'feed': this.feed,
                    'likes': this.favourites,
                    'bookmarks': this.bookmarks,
                    'archive': this.archives
                };

                const sourceType = this.contextMenuType;

                switch(type) {
                    case 'addcw':
                        sourceMap[sourceType][idx].sensitive = true;
                    break;

                    case 'remcw':
                        sourceMap[sourceType][idx].sensitive = false;
                    break;

                    case 'unlist':
                        sourceMap[sourceType].splice(idx, 1);
                    break;

                    case 'spammer':
                        let id = sourceMap[sourceType][idx].account.id;

                        sourceMap[sourceType] = sourceMap[sourceType].filter(post => {
                            return post.account.id != id;
                        });
                    break;
                }
            },

            counterChange(index, type) {
                switch(type) {
                    case 'comment-increment':
                        this.feed[index].reply_count = this.feed[index].reply_count + 1;
                    break;

                    case 'comment-decrement':
                        this.feed[index].reply_count = this.feed[index].reply_count - 1;
                    break;
                }
            },

            openCommentLikesModal(post) {
                this.likesModalPost = post;
                this.showLikesModal = true;
                this.$nextTick(() => {
                    this.$refs.likesModal.open();
                });
            },

            shareStatus(index, source = 'feed') {
                const sourceMap = {
                    'feed': this.feed,
                    'likes': this.favourites,
                    'bookmarks': this.bookmarks
                };

                const sourceArray = sourceMap[source] || this.feed;
                const status = sourceArray[index];
                const originalReblogged = status.reblogged;
                const originalCount = status.reblogs_count;

                sourceArray[index].reblogs_count = originalCount + 1;
                sourceArray[index].reblogged = !originalReblogged;

                axios.post(`/api/v1/statuses/${status.id}/reblog`)
                    .catch(err => {
                        sourceArray[index].reblogs_count = originalCount;
                        sourceArray[index].reblogged = originalReblogged;
                    });
            },

            unshareStatus(index, source = 'feed') {
                const sourceMap = {
                    'feed': this.feed,
                    'likes': this.favourites,
                    'bookmarks': this.bookmarks
                };

                const sourceArray = sourceMap[source] || this.feed;
                const status = sourceArray[index];
                const originalReblogged = status.reblogged;
                const originalCount = status.reblogs_count;

                sourceArray[index].reblogs_count = originalCount - 1;
                sourceArray[index].reblogged = !originalReblogged;

                axios.post(`/api/v1/statuses/${status.id}/unreblog`)
                    .catch(err => {
                        sourceArray[index].reblogs_count = originalCount;
                        sourceArray[index].reblogged = originalReblogged;
                    });
            },

            handleReport(post) {
                this.reportedStatusId = post.id;
                this.$nextTick(() => {
                    this.reportedStatus = post;
                    this.$refs.reportModal.open();
                });
            },

            deletePost() {
                this.feed.splice(this.postIndex, 1);
            },

            handleArchived(id) {
                this.feed.splice(this.postIndex, 1);
            },

            handleUnarchived(id) {
                this.feed = [];
                this.fetchFeed();
            },

            enterArchivesIntersect() {
                if(this.isIntersecting) {
                    return;
                }
                this.isIntersecting = true;

                axios.get('/api/pixelfed/v2/statuses/archives', {
                    params: {
                        page: this.archivesPage
                    }
                })
                .then(res => {
                    this.archives.push(...res.data);
                    this.archivesPage++;
                    this.canLoadMoreArchives = res.data.length != 0;
                    this.isIntersecting = false;
                })
                .catch(err => {
                    this.canLoadMoreArchives = false;
                })
            },

            handleBookmark(index, source = 'feed') {
                this.postIndex = index;

                const sourceMap = {
                    'feed': this.feed,
                    'likes': this.favourites,
                    'bookmarks': this.bookmarks
                };

                const sourceArray = sourceMap[source] || this.feed;
                const item = sourceArray[this.postIndex];

                if(item.bookmarked) {
                    if(!window.confirm('Are you sure you want to unbookmark this post?')) {
                        return;
                    }
                }

                axios.post('/i/bookmark', {
                    item: item.id
                })
                .then(res => {
                    item.bookmarked = !item.bookmarked;
                })
                .catch(err => {
                    this.$bvToast.toast('Cannot bookmark post at this time.', {
                        title: 'Bookmark Error',
                        variant: 'danger',
                        autoHideDelay: 5000
                    });
                });
            },
        }
    }
</script>

<style lang="scss">
    .profile-feed-component {
        margin-top: 0;

        .ph-wrapper {
            padding: 0.25rem;

            .ph-item {
                margin: 0;
                padding: 0;
                border: none;
                background-color: transparent;

                .ph-picture {
                    height: auto;
                    padding-bottom: 100%;
                    border-radius: 5px;
                }

                & > * {
                    margin-bottom: 0;
                }
            }
        }

        .info-overlay-text-field {
            font-size: 13.5px;
            margin-bottom: 2px;

            @media (min-width: 768px) {
                font-size: 20px;
                margin-bottom: 15px;
            }
        }

        .video-overlay-badge {
            position: absolute;
            top: 10px;
            right: 10px;
            opacity: 0.6;
            color: var(--dark);
            padding-bottom: 1px;
        }

        .timestamp-overlay-badge {
            position: absolute;
            bottom: 10px;
            right: 10px;
            opacity: 0.6;
        }

        .pinned-overlay-badge {
            position: absolute;
            top: 10px;
            left: 10px;
            color: var(--dark);
            font-size: 120%;
        }

        .profile-nav-btns {
            margin-right: 1rem;

            .btn-group {
                min-height: 45px;
            }

            .btn-link {
                color: var(--text-lighter);
                font-size: 14px;
                border-radius: 0;
                margin-right: 1rem;
                font-weight: bold;

                &:hover {
                    color: var(--text-muted);
                    text-decoration: none;
                }

                &.active {
                    color: var(--dark);
                    border-bottom: 1px solid var(--dark);
                    transition: border-bottom 250ms ease-in-out;
                }
            }
        }

        .layout-sort-toggle {
            .btn {
                border: none;

                &.btn-light {
                    opacity: 0.4;
                }
            }
        }
    }
</style>
