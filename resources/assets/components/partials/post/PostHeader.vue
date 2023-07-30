<template>
    <div>
        <div v-if="isReblog" class="card-header bg-light border-0" style="border-top-left-radius: 15px;border-top-right-radius: 15px;">
            <div class="media align-items-center" style="height:10px;">
                <a :href="reblogAccount.url" class="mx-2" @click.prevent="goToProfileById(reblogAccount.id)">
                    <img :src="reblogAccount.avatar" style="border-radius:10px;" width="24" height="24" onerror="this.onerror=null;this.src='/storage/avatars/default.png?v=0';">
                </a>
                <div style="font-size:12px;font-weight:bold">
                    <i class="far fa-retweet text-warning mr-1"></i> Reblogged by <a :href="reblogAccount.url" class="text-dark" @click.prevent="goToProfileById(reblogAccount.id)">&commat;{{ reblogAccount.acct }}</a>
                </div>
            </div>
        </div>
        <div class="card-header border-0" style="border-top-left-radius: 15px;border-top-right-radius: 15px;">
            <div class="media align-items-center">
                <a :href="status.account.url" @click.prevent="goToProfile()" style="margin-right: 10px;">
                    <img :src="getStatusAvatar()" style="border-radius:15px;" width="44" height="44" onerror="this.onerror=null;this.src='/storage/avatars/default.png?v=0';">
                </a>

                <div class="media-body">
                    <p class="font-weight-bold username">
                        <a :href="status.account.url" class="text-dark" :id="'apop_'+status.id" @click.prevent="goToProfile">
                            {{ status.account.acct }}
                        </a>
                        <b-popover :target="'apop_'+status.id" triggers="hover" placement="bottom" custom-class="shadow border-0 rounded-px">
                            <profile-hover-card
                                :profile="status.account"
                                v-on:follow="follow"
                                v-on:unfollow="unfollow" />
                        </b-popover>
                    </p>
                    <p class="text-lighter mb-0" style="font-size: 13px;">
                        <span v-if="status.account.is_admin" class="d-none d-md-inline-block">
                            <span class="badge badge-light text-danger user-select-none" title="Admin account">ADMIN</span>
                            <span class="mx-1 text-lighter">·</span>
                        </span>
                        <a class="timestamp text-lighter" :href="status.url" @click.prevent="goToPost()" :title="status.created_at">
                            {{ timeago(status.created_at) }}
                        </a>

                        <span v-if="config.ab.pue && status.hasOwnProperty('edited_at') && status.edited_at">
                            <span class="mx-1 text-lighter">·</span>
                            <a class="text-lighter" href="#" @click.prevent="openEditModal">Edited</a>
                        </span>

                        <span class="mx-1 text-lighter">·</span>
                        <span class="visibility text-lighter" :title="scopeTitle(status.visibility)"><i :class="scopeIcon(status.visibility)"></i></span>

                        <span v-if="status.place && status.place.hasOwnProperty('name')" class="d-none d-md-inline-block">
                            <span class="mx-1 text-lighter">·</span>
                            <span class="location text-lighter"><i class="far fa-map-marker-alt"></i> {{ status.place.name }}, {{ status.place.country }}</span>
                        </span>
                    </p>
                </div>

                <button v-if="!useDropdownMenu" class="btn btn-link text-lighter" @click="openMenu">
                    <i class="far fa-ellipsis-v fa-lg"></i>
                </button>

                <b-dropdown
                    v-else
                    no-caret
                    right
                    variant="link"
                    toggle-class="text-lighter"
                    html="<i class='far fa-ellipsis-v fa-lg px-3'></i>"
                    >
                    <b-dropdown-item>
                        <p class="mb-0 font-weight-bold">{{ $t('menu.viewPost') }}</p>
                    </b-dropdown-item>
                    <b-dropdown-item>
                        <p class="mb-0 font-weight-bold">{{ $t('common.copyLink') }}</p>
                    </b-dropdown-item>
                    <b-dropdown-item v-if="status.local">
                        <p class="mb-0 font-weight-bold">{{ $t('menu.embed') }}</p>
                    </b-dropdown-item>
                    <b-dropdown-divider v-if="!owner"></b-dropdown-divider>
                    <b-dropdown-item v-if="!owner">
                        <p class="mb-0 font-weight-bold">{{ $t('menu.report') }}</p>
                        <p class="small text-muted mb-0">Report content that violate our rules</p>
                    </b-dropdown-item>
                    <b-dropdown-item v-if="!owner && status.hasOwnProperty('relationship')">
                        <p class="mb-0 font-weight-bold">{{ status.relationship.muting ? 'Unmute' : 'Mute' }}</p>
                        <p class="small text-muted mb-0">Hide posts from this account in your feeds</p>
                    </b-dropdown-item>
                    <b-dropdown-item v-if="!owner && status.hasOwnProperty('relationship')">
                        <p class="mb-0 font-weight-bold text-danger">{{ status.relationship.blocking ? 'Unblock' : 'Block' }}</p>
                        <p class="small text-muted mb-0">Restrict all content from this account</p>
                    </b-dropdown-item>
                    <b-dropdown-divider v-if="owner || admin"></b-dropdown-divider>
                    <b-dropdown-item v-if="owner || admin">
                        <p class="mb-0 font-weight-bold text-danger">
                            {{ $t('common.delete') }}
                        </p>
                    </b-dropdown-item>
                </b-dropdown>
            </div>

            <edit-history-modal ref="editModal" :status="status" />
        </div>
    </div>
</template>

<script type="text/javascript">
    import ProfileHoverCard from './../profile/ProfileHoverCard.vue';
    import EditHistoryModal from './EditHistoryModal.vue';

    export default {
        props: {
            status: {
                type: Object
            },

            profile: {
                type: Object
            },

            useDropdownMenu: {
                type: Boolean,
                default: false
            },

            isReblog: {
                type: Boolean,
                default: false
            },

            reblogAccount: {
                type: Object
            }
        },

        components: {
            "profile-hover-card": ProfileHoverCard,
            "edit-history-modal": EditHistoryModal
        },

        data() {
            return {
                config: window.App.config,
                menuLoading: true,
                owner: false,
                admin: false,
                license: false
            }
        },

        methods: {
            timeago(ts) {
                let short = App.util.format.timeAgo(ts);
                if(
                    short.endsWith('s') ||
                    short.endsWith('m') ||
                    short.endsWith('h')
                ) {
                    return short;
                }
                const intl = new Intl.DateTimeFormat(undefined, {
                    year:  'numeric',
                    month: 'short',
                    day:   'numeric',
                    hour: 'numeric',
                    minute: 'numeric'
                });
                return intl.format(new Date(ts));
            },

            openMenu() {
                this.$emit('menu');
            },

            scopeIcon(scope) {
                switch(scope) {
                    case 'public':
                        return 'far fa-globe';
                    break;

                    case 'unlisted':
                        return 'far fa-lock-open';
                    break;

                    case 'private':
                        return 'far fa-lock';
                    break;

                    default:
                        return 'far fa-globe';
                    break;
                }
            },

            scopeTitle(scope) {
                switch(scope) {
                    case 'public':
                        return 'Visible to everyone';
                    break;

                    case 'unlisted':
                        return 'Hidden from public feeds';
                    break;

                    case 'private':
                        return 'Only visible to followers';
                    break;

                    default:
                        return '';
                    break;
                }
            },

            goToPost() {
                if(location.pathname.split('/').pop() == this.status.id) {
                    location.href = this.status.local ? this.status.url + '?fs=1' : this.status.url;
                    return;
                }

                this.$router.push({
                    name: 'post',
                    path: `/i/web/post/${this.status.id}`,
                    params: {
                        id: this.status.id,
                        cachedStatus: this.status,
                        cachedProfile: this.profile
                    }
                })
            },

            goToProfileById(id) {
                this.$nextTick(() => {
                    this.$router.push({
                        name: 'profile',
                        path: `/i/web/profile/${id}`,
                        params: {
                            id: id,
                            cachedUser: this.profile
                        }
                    });
                });
            },

            goToProfile() {
                this.$nextTick(() => {
                    this.$router.push({
                        name: 'profile',
                        path: `/i/web/profile/${this.status.account.id}`,
                        params: {
                            id: this.status.account.id,
                            cachedProfile: this.status.account,
                            cachedUser: this.profile
                        }
                    });
                });
            },

            toggleContentWarning() {
                this.key++;
                this.sensitive = true;
                this.status.sensitive = !this.status.sensitive;
            },

            like() {
                event.currentTarget.blur();
                if(this.status.favourited) {
                    this.$emit('unlike');
                } else {
                    this.$emit('like');
                }
            },

            toggleMenu(bvEvent) {
                setTimeout(() => {
                    this.menuLoading = false;
                }, 500);
            },

            closeMenu(bvEvent) {
                setTimeout(() => {
                    bvEvent.target.parentNode.firstElementChild.blur();
                }, 100);
            },

            showLikes() {
                event.currentTarget.blur();
                this.$emit('likes-modal');
            },

            showShares() {
                event.currentTarget.blur();
                this.$emit('shares-modal');
            },

            showComments() {
                event.currentTarget.blur();
                this.showCommentDrawer = !this.showCommentDrawer;
            },

            copyLink() {
                event.currentTarget.blur();
                App.util.clipboard(this.status.url);
            },

            shareToOther() {
                if (navigator.canShare) {
                    navigator.share({
                        url: this.status.url
                    })
                    .then(() => console.log('Share was successful.'))
                    .catch((error) => console.log('Sharing failed', error));
                } else {
                    swal('Not supported', 'Your current device does not support native sharing.', 'error');
                }
            },

            counterChange(type) {
                this.$emit('counter-change', type);
            },

            showCommentLikes(post) {
                this.$emit('comment-likes-modal', post);
            },

            shareStatus() {
                this.$emit('share');
            },

            unshareStatus() {
                this.$emit('unshare');
            },

            handleReport(post) {
                this.$emit('handle-report', post);
            },

            follow() {
                this.$emit('follow');
            },

            unfollow() {
                this.$emit('unfollow');
            },

            handleReblog() {
                this.isReblogging = true;
                if(this.status.reblogged) {
                    this.$emit('unshare');
                } else {
                    this.$emit('share');
                }

                setTimeout(() => {
                    this.isReblogging = false;
                }, 5000);
            },

            handleBookmark() {
                event.currentTarget.blur();
                this.isBookmarking = true;
                this.$emit('bookmark');

                setTimeout(() => {
                    this.isBookmarking = false;
                }, 5000);
            },

            getStatusAvatar() {
                if(window._sharedData.user.id == this.status.account.id) {
                    return window._sharedData.user.avatar;
                }

                return this.status.account.avatar;
            },

            openModTools() {
                this.$emit('mod-tools');
            },

            openEditModal() {
                this.$refs.editModal.open();
            }
        }
    }
</script>
