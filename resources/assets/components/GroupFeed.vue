<template>
    <div class="groups-home-component w-100 h-100">
        <div v-if="initialLoad" class="row border-bottom m-0 p-0">
            <sidebar />

                    <self-feed :profile="profile" v-on:switchtab="switchTab" />
                    <!-- <self-discover v-if="tab == 'discover'" :profile="profile" />
                    <self-notifications v-if="tab == 'notifications'" :profile="profile" />
                    <self-invitations v-if="tab == 'invitations'" :profile="profile" />
                    <self-remote-search v-if="tab == 'remotesearch'" :profile="profile" />
                    <self-groups v-if="tab == 'mygroups'" :profile="profile" />
                    <create-group v-if="tab == 'creategroup'" :profile="profile" />
                    <div v-if="tab == 'gsearch'">
                        <div class="col-12 px-5">
                            <div class="my-4">
                                <p class="h1 font-weight-bold mb-1">Group Search</p>
                                <p class="lead text-muted mb-0">Search and explore groups.</p>
                            </div>
                            <div class="media align-items-center text-lighter">
                                <i class="far fa-chevron-left fa-lg mr-3"></i>
                                <div class="media-body">
                                    <p class="lead mb-0">Use the search bar on the side menu</p>
                                </div>
                            </div>
                        </div>
                    </div> -->

        </div>
        <div v-else class="row justify-content-center mt-5">
            <b-spinner />
        </div>
    </div>
</template>

<script type="text/javascript">
    import GroupStatus from '@/groups/partials/GroupStatus.vue';
    import SelfFeed from '@/groups/partials/SelfFeed.vue';
    import SelfDiscover from '@/groups/partials/SelfDiscover.vue';
    import SelfGroups from '@/groups/partials/SelfGroups.vue';
    import SelfNotifications from '@/groups/partials/SelfNotifications.vue';
    import SelfInvitations from '@/groups/partials/SelfInvitations.vue';
    import SelfRemoteSearch from '@/groups/partials/SelfRemoteSearch.vue';
    import CreateGroup from '@/groups/CreateGroup.vue';
    import SidebarComponent from '@/groups/sections/Sidebar.vue';
    import Autocomplete from '@trevoreyre/autocomplete-vue'
    import '@trevoreyre/autocomplete-vue/dist/style.css'

    export default {
        data() {
            return {
                initialLoad: false,
                config: {},
                groups: [],
                profile: {},
                tab: null,
                searchQuery: undefined,
            };
        },

        components: {
            'autocomplete-input': Autocomplete,
            'group-status': GroupStatus,
            'self-discover': SelfDiscover,
            'self-groups': SelfGroups,
            'self-feed': SelfFeed,
            'self-notifications': SelfNotifications,
            'self-invitations': SelfInvitations,
            'self-remote-search': SelfRemoteSearch,
            "create-group": CreateGroup,
            "sidebar": SidebarComponent
        },

        mounted() {
            this.fetchConfig();
        },

        methods: {
            init() {
                document.querySelectorAll("footer").forEach(e => e.parentNode.removeChild(e));
                document.querySelectorAll(".mobile-footer-spacer").forEach(e => e.parentNode.removeChild(e));
                document.querySelectorAll(".mobile-footer").forEach(e => e.parentNode.removeChild(e));
                // let u = new URLSearchParams(window.location.search);
                // if(u.has('ct')) {
                //     if(['mygroups', 'notifications', 'discover', 'remotesearch', 'creategroup', 'gsearch'].includes(u.get('ct'))) {
                //         if(u.get('ct') == 'creategroup' && this.config.limits.user.create.new == false) {
                //             this.tab = 'feed';
                //             history.pushState(null, null, '/groups/feed');
                //         } else {
                //             this.tab = u.get('ct');
                //         }
                //     } else {
                //         this.tab = 'feed';
                //         history.pushState(null, null, '/groups/feed');
                //     }
                // } else {
                //     this.tab = 'feed';
                // }
                this.initialLoad = true;
            },

            fetchConfig() {
                axios.get('/api/v0/groups/config')
                .then(res => {
                    this.config = res.data;
                    this.fetchProfile();
                });
            },

            fetchProfile() {
                axios.get('/api/pixelfed/v1/accounts/verify_credentials')
                .then(res => {
                    this.profile = res.data;
                    this.init();
                    window._sharedData.curUser = res.data;
                    window.App.util.navatar();
                })
            },

            fetchSelfGroups() {
                axios.get('/api/v0/groups/self/list')
                .then(res => {
                    this.groups = res.data;
                });
            },

            switchTab(tab) {
                event.currentTarget.blur();
                window.scrollTo(0,0);
                this.tab = tab;

                if(tab != 'feed') {
                    history.pushState(null, null, '/groups/home?ct=' + tab);
                } else {
                    history.pushState(null, null, '/groups/home');
                }
            },

            autocompleteSearch(input) {
                if (!input || input.length < 2) {
                    if(this.tab = 'searchresults') {
                        this.tab = 'feed';
                    }
                    return [];
                };

                this.searchQuery = input;
                // this.tab = 'searchresults';

                if(input.startsWith('http')) {
                    let url = new URL(input);
                    if(url.hostname == location.hostname) {
                        location.href = input;
                        return [];
                    }
                    return [];
                }

                if(input.startsWith('#')) {
                    this.$bvToast.toast(input, {
                        title: 'Hashtag detected',
                        variant: 'info',
                        autoHideDelay: 5000
                    });
                    return [];
                }

                return axios.post('/api/v0/groups/search/global', {
                    q: input,
                    v: '0.2'
                })
                .then(res => {
                    this.searchLoading = false;
                    return res.data;
                }).catch(err => {

                    if(err.response.status === 422) {
                        this.$bvToast.toast(err.response.data.error.message, {
                            title: 'Cannot display search results',
                            variant: 'danger',
                            autoHideDelay: 5000
                        });
                    }

                    return [];
                })
            },

            getSearchResultValue(result) {
                return result.name;
            },

            onSearchSubmit(result) {
                if (result.length < 1) {
                    return [];
                }

                location.href = result.url;
            },

            truncateName(val) {
                if(val.length < 24) {
                    return val;
                }

                return val.substr(0, 23) + '...';
            }
        }
    }
</script>

<style lang="scss">
    .groups-home-component {
        font-family: var(--font-family-sans-serif);

        .group-nav-btn {
            display: block;
            width: 100%;
            padding-left: 0;
            padding-top: 0.3rem;
            padding-bottom: 0.3rem;
            margin-bottom: 0.3rem;
            border-radius: 1.5rem;
            text-align: left;
            color: #6c757d;
            background-color: transparent;
            border-color: transparent;
            justify-content: flex-start;

            &.active {
                background-color: #EFF6FF !important;
                border:1px solid #DBEAFE !important;
                color: #212529;

                .group-nav-btn-icon {
                    background-color: #2c78bf !important;
                    color: #fff !important;
                }
            }

            &-icon {
                display: inline-flex;
                width: 35px;
                height: 35px;
                padding: 12px;
                background-color: #E5E7EB;
                border-radius: 17px;
                margin: auto 0.3rem;
                align-items: center;
                justify-content: center;
            }

            &-name {
                display: inline-block;
                margin-left: 0.3rem;
                font-weight: 700;
            }
        }

        .autocomplete-input {
            height: 2.375rem;
            background-color: #f8f9fa !important;
            font-size: 0.9rem;
            color: #495057;
            border-radius: 50rem;
            border-color: transparent;

            &:focus,
            &[aria-expanded=true] {
                box-shadow: none;
            }
        }

        .autocomplete-result {
            background: none;
            padding: 12px;

            &:hover,
            &:focus {
                background-color: #EFF6FF !important;
            }

            .media {
                img {
                    object-fit: cover;
                    border-radius: 4px;
                    margin-right: 0.6rem;
                }

                .icon-placeholder {
                    display: flex;
                    width: 32px;
                    height: 32px;
                    background-color: #2c78bf;
                    border-radius: 4px;
                    justify-content: center;
                    align-items: center;
                    color: #fff;
                    margin-right: 0.6rem;
                }
            }
        }

        .autocomplete-result-list {
            padding-bottom: 0;
        }

        .fade-enter-active, .fade-leave-active {
            transition: opacity 200ms;
        }

        .fade-enter, .fade-leave-to {
            opacity: 0;
        }
    }
</style>
