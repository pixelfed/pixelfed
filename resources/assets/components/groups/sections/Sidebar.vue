<template>
    <div class="col-3 shadow groups-sidenav">
        <div class="p-1">
            <div class="d-flex justify-content-between align-items-center py-3">
                <p class="h2 font-weight-bold mb-0">Groups</p>
                <a class="btn btn-light px-2 rounded-circle" href="/settings/home">
                    <i class="fas fa-cog fa-lg"></i>
                </a>
            </div>

            <div class="mb-3">
                <autocomplete
                    :search="autocompleteSearch"
                    placeholder="Search groups by name"
                    aria-label="Search groups by name"
                    :get-result-value="getSearchResultValue"
                    :debounceTime="700"
                    @submit="onSearchSubmit"
                    ref="autocomplete"
                    >
                    <template #result="{ result, props }">
                        <li
                        v-bind="props"
                        class="autocomplete-result"
                        >

                            <div class="media align-items-center">
                                <img v-if="result.local && result.metadata && result.metadata.hasOwnProperty('header') && result.metadata.header.hasOwnProperty('url')" :src="result.metadata.header.url" width="32" height="32">
                                <div v-else class="icon-placeholder">
                                    <i class="fal fa-user-friends"></i>
                                </div>
                                <div class="media-body text-truncate mr-3">
                                    <p class="result-name mb-n1 font-weight-bold">
                                        {{ truncateName(result.name) }}
                                        <span v-if="result.verified" class="fa-stack ml-n2" title="Verified Group" data-toggle="tooltip">
                                            <i class="fas fa-circle fa-stack-1x fa-lg" style="color: #22a7f0cc;font-size:18px"></i>
                                            <i class="fas fa-check fa-stack-1x text-white" style="font-size:10px"></i>
                                        </span>
                                    </p>
                                    <p class="mb-0 text-muted" style="font-size: 10px;">
                                        <span v-if="!result.local" title="Remote Group">
                                            <i class="far fa-globe"></i>
                                        </span>
                                        <span v-if="!result.local">·</span>
                                        <span class="font-weight-bold">{{ result.member_count }} members</span>
                                    </p>
                                </div>
                            </div>
                        </li>
                    </template>
                </autocomplete>
            </div>

            <!-- <button
                class="btn btn-light group-nav-btn"
                :class="{ active: tab == 'feed' }"
                @click="switchTab('feed')">
                <div class="group-nav-btn-icon">
                    <i class="fas fa-list"></i>
                </div>
                <div class="group-nav-btn-name">
                    Your Feed
                </div>
            </button> -->

            <template v-for="tab in tabs">
                <router-link
                    class="btn btn-light group-nav-btn"
                    :to="tab.path">
                        <div class="group-nav-btn-icon">
                            <i :class="tab.icon"></i>
                        </div>
                        <div class="group-nav-btn-name">
                            {{ tab.name }}
                        </div>
                    </router-link>
            </template>

            <router-link
                to="/groups/create"
                class="btn btn-primary btn-block rounded-pill font-weight-bold mt-3">
                <i class="fas fa-plus mr-2"></i> Create New Group
            </router-link>

            <hr>
            <!-- <div v-for="group in groups" class="ml-2">
                <div class="card shadow-sm border text-decoration-none text-dark">
                    <img v-if="group.metadata && group.metadata.hasOwnProperty('header')" :src="group.metadata.header.url" class="card-img-top" style="width: 100%; height: auto;object-fit: cover;max-height: 160px;">
                    <div v-else class="bg-primary" style="width:100%;height:160px;"></div>
                    <div class="card-body">
                        <div class="lead font-weight-bold d-flex align-items-top" style="height: 60px;">
                            {{ group.name }}
                            <span v-if="group.verified" class="fa-stack ml-n2 mt-n2">
                                <i class="fas fa-circle fa-stack-1x fa-lg" style="color: #22a7f0cc;font-size:18px"></i>
                                <i class="fas fa-check fa-stack-1x text-white" style="font-size:10px"></i>
                            </span>
                        </div>
                        <div class="text-muted font-weight-light d-flex justify-content-between">
                            <span>{{group.member_count}} Members</span>
                            <span style="font-size: 12px;padding: 2px 5px;color: rgba(75, 119, 190, 1);background:rgba(137, 196, 244, 0.2);border:1px solid rgba(137, 196, 244, 0.3);font-weight:400;text-transform: capitalize;" class="rounded">{{ group.self.role }}</span>
                        </div>
                        <hr>
                        <p class="mb-0">
                            <a class="btn btn-light btn-block border rounded-lg font-weight-bold" :href="group.url">View Group</a>
                        </p>
                    </div>
                </div>
            </div> -->
        </div>
    </div>
</template>

<script>
    import Autocomplete from '@trevoreyre/autocomplete-vue'
    import '@trevoreyre/autocomplete-vue/dist/style.css'

    export default {
        data() {
            return {
                initialLoad: false,
                tabs: [
                    { name: 'Your Feed', icon: 'fas fa-list', path: '/groups/feed' },
                    { name: 'Discover', icon: 'fas fa-compass', path: '/groups/discover' },
                    { name: 'Your Groups', icon: 'fas fa-list', path: '/groups/joins' },
                    // { name: 'Notifications', icon: 'far fa-bell', path: '/groups/notifications' },
                    // { name: 'Find Remote Group', icon: 'far fa-search-plus', path: '/groups/search' },
                ],
                config: {},
                groups: [],
                profile: {},
                tab: null,
                searchQuery: undefined,
            };
        },

        components: {
            'autocomplete': Autocomplete,
        },

        methods: {
            autocompleteSearch(input) {
                if (!input || input.length < 2) {
                    return [];
                };

                this.searchQuery = input;
                // this.tab = 'searchresults';

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
    .groups-sidenav {
        display: none;
        font-family: var(--font-family-sans-serif);

        @media(min-width: 768px) {
            display: block;
            width: 100%;
            height: 100vh;
            background: #fff;
            top: 74px;
            border: none;
            overflow: hidden;
            z-index: 1;
            position: sticky;
        }

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
    }
</style>
