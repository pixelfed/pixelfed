<template>
<div>
    <div class="header bg-primary pb-3 mt-n4">
        <div class="container-fluid">
            <div class="header-body">
                <div class="row align-items-center py-4">
                    <div class="col-lg-6 col-7">
                        <p class="display-1 text-white d-inline-block mb-0">Hashtags</p>
                    </div>
                </div>
                <div class="row">
                    <div class="col-xl-2 col-md-6">
                        <div class="mb-3">
                            <h5 class="text-light text-uppercase mb-0">Unique Hashtags</h5>
                            <span class="text-white h2 font-weight-bold mb-0 human-size">{{ prettyCount(stats.total_unique) }}</span>
                        </div>
                    </div>

                    <div class="col-xl-2 col-md-6">
                        <div class="mb-3">
                            <h5 class="text-light text-uppercase mb-0">Total Hashtags</h5>
                            <span class="text-white h2 font-weight-bold mb-0 human-size">{{ prettyCount(stats.total_posts) }}</span>
                        </div>
                    </div>

                    <div class="col-xl-2 col-md-6">
                        <div class="mb-3">
                            <h5 class="text-light text-uppercase mb-0">New (past 14 days)</h5>
                            <span class="text-white h2 font-weight-bold mb-0 human-size">{{ prettyCount(stats.added_14_days) }}</span>
                        </div>
                    </div>

                    <div class="col-xl-2 col-md-6">
                        <div class="mb-3">
                            <h5 class="text-light text-uppercase mb-0">Banned Hashtags</h5>
                            <span class="text-white h2 font-weight-bold mb-0 human-size">{{ prettyCount(stats.total_banned) }}</span>
                        </div>
                    </div>

                    <div class="col-xl-2 col-md-6">
                        <div class="mb-3">
                            <h5 class="text-light text-uppercase mb-0">NSFW Hashtags</h5>
                            <span class="text-white h2 font-weight-bold mb-0 human-size">{{ prettyCount(stats.total_nsfw) }}</span>
                        </div>
                    </div>
                    <div class="col-xl-2 col-md-6">
                        <div class="mb-3">
                            <h5 class="text-light text-uppercase mb-0">Clear Trending Cache</h5>
                            <button class="btn btn-outline-white btn-block btn-sm py-0 mt-1" @click="clearTrendingCache">Clear Cache</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div v-if="!loaded" class="my-5 text-center">
        <b-spinner />
    </div>

    <div v-else class="m-n2 m-lg-4">
        <div class="container-fluid mt-4">
            <div class="row mb-3 justify-content-between">
                <div class="col-12 col-md-8">
                    <ul class="nav nav-pills">
                        <li class="nav-item">
                            <button :class="['nav-link', { active: tabIndex == 0}]" @click="toggleTab(0)">All</button>
                        </li>
                        <li class="nav-item">
                            <button :class="['nav-link', { active: tabIndex == 1}]" @click="toggleTab(1)">Trending</button>
                        </li>
                        <li class="nav-item">
                            <button :class="['nav-link', { active: tabIndex == 2}]" @click="toggleTab(2)">Banned</button>
                        </li>
                        <li class="nav-item">
                            <button :class="['nav-link', { active: tabIndex == 3}]" @click="toggleTab(3)">NSFW</button>
                        </li>
                    </ul>
                </div>
                <div class="col-12 col-md-4">
                    <autocomplete
                        :search="composeSearch"
                        :disabled="searchLoading"
                        placeholder="Search hashtags"
                        aria-label="Search hashtags"
                        :get-result-value="getTagResultValue"
                        @submit="onSearchResultClick"
                        ref="autocomplete"
                        >
                            <template #result="{ result, props }">
                                <li
                                v-bind="props"
                                class="autocomplete-result d-flex justify-content-between align-items-center"
                                >
                                <div class="font-weight-bold" :class="{ 'text-danger': result.is_banned }">
                                    #{{ result.name }}
                                </div>
                                <div class="small text-muted">
                                    {{ prettyCount(result.cached_count) }} posts
                                </div>
                            </li>
                        </template>
                    </autocomplete>
                </div>
            </div>

            <div v-if="[0, 2, 3].includes(this.tabIndex)" class="table-responsive">
                <table class="table table-dark">
                    <thead class="thead-dark">
                        <tr>
                            <th scope="col" class="cursor-pointer" v-html="buildColumn('ID', 'id')" @click="toggleCol('id')"></th>
                            <th scope="col" class="cursor-pointer" v-html="buildColumn('Hashtag', 'name')" @click="toggleCol('name')"></th>
                            <th scope="col" class="cursor-pointer" v-html="buildColumn('Count', 'cached_count')" @click="toggleCol('cached_count')"></th>
                            <th scope="col" class="cursor-pointer" v-html="buildColumn('Can Search', 'can_search')" @click="toggleCol('can_search')"></th>
                            <th scope="col" class="cursor-pointer" v-html="buildColumn('Can Trend', 'can_trend')" @click="toggleCol('can_trend')"></th>
                            <th scope="col" class="cursor-pointer" v-html="buildColumn('NSFW', 'is_nsfw')" @click="toggleCol('is_nsfw')"></th>
                            <th scope="col" class="cursor-pointer" v-html="buildColumn('Banned', 'is_banned')" @click="toggleCol('is_banned')"></th>
                            <th scope="col">Created</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="(hashtag, idx) in hashtags">
                            <td class="font-weight-bold text-monospace text-muted">
                                <a href="#" @click.prevent="openEditHashtagModal(hashtag, idx)">
                                    {{ hashtag.id }}
                                </a>
                            </td>
                            <td class="font-weight-bold">{{ hashtag.name }}</td>
                            <td class="font-weight-bold">
                                <a :href="`/i/web/hashtag/${hashtag.slug}`">
                                    {{ hashtag.cached_count ?? 0 }}
                                </a>
                            </td>
                            <td class="font-weight-bold" v-html="boolIcon(hashtag.can_search, 'text-success', 'text-danger')"></td>
                            <td class="font-weight-bold" v-html="boolIcon(hashtag.can_trend, 'text-success', 'text-danger')"></td>
                            <td class="font-weight-bold" v-html="boolIcon(hashtag.is_nsfw, 'text-danger')"></td>
                            <td class="font-weight-bold" v-html="boolIcon(hashtag.is_banned, 'text-danger')"></td>
                            <td class="font-weight-bold">{{ timeAgo(hashtag.created_at) }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div v-if="[0, 2, 3].includes(this.tabIndex)" class="d-flex align-items-center justify-content-center">
                <button
                    class="btn btn-primary rounded-pill"
                    :disabled="!pagination.prev"
                    @click="paginate('prev')">
                    Prev
                </button>
                <button
                    class="btn btn-primary rounded-pill"
                    :disabled="!pagination.next"
                    @click="paginate('next')">
                    Next
                </button>
            </div>

            <div v-if="this.tabIndex == 1" class="table-responsive">
                <table class="table table-dark">
                    <thead class="thead-dark">
                        <tr>
                            <th scope="col">ID</th>
                            <th scope="col">Hashtag</th>
                            <th scope="col">Trending Count</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="(hashtag, idx) in trendingTags">
                            <td class="font-weight-bold text-monospace text-muted">
                                <a href="#" @click.prevent="openEditHashtagModal(hashtag, idx)">
                                    {{ hashtag.id }}
                                </a>
                            </td>
                            <td class="font-weight-bold">{{ hashtag.hashtag }}</td>
                            <td class="font-weight-bold">
                                <a :href="`/i/web/hashtag/${hashtag.hashtag}`">
                                    {{ hashtag.total ?? 0 }}
                                </a>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <b-modal v-model="showEditModal" title="Edit Hashtag" :ok-only="true" :lazy="true" :static="true">
        <div v-if="editingHashtag && editingHashtag.name" class="list-group">
            <div class="list-group-item d-flex align-items-center justify-content-between">
                <div class="text-muted small">Name</div>
                <div class="font-weight-bold">{{ editingHashtag.name }}</div>
            </div>
            <div class="list-group-item d-flex align-items-center justify-content-between">
                <div class="text-muted small">Total Uses</div>
                <div class="font-weight-bold">{{ editingHashtag.cached_count.toLocaleString('en-CA', { compactDisplay: "short"}) }}</div>
            </div>
            <div class="list-group-item d-flex align-items-center justify-content-between">
                <div class="text-muted small">Can Trend</div>
                <div class="mr-n2 mb-1">
                    <b-form-checkbox v-model="editingHashtag.can_trend" switch size="lg"></b-form-checkbox>
                </div>
            </div>
            <div class="list-group-item d-flex align-items-center justify-content-between">
                <div class="text-muted small">Can Search</div>
                <div class="mr-n2 mb-1">
                    <b-form-checkbox v-model="editingHashtag.can_search" switch size="lg"></b-form-checkbox>
                </div>
            </div>
            <div class="list-group-item d-flex align-items-center justify-content-between">
                <div class="text-muted small">Banned</div>
                <div class="mr-n2 mb-1">
                    <b-form-checkbox v-model="editingHashtag.is_banned" switch size="lg"></b-form-checkbox>
                </div>
            </div>
            <div class="list-group-item d-flex align-items-center justify-content-between">
                <div class="text-muted small">NSFW</div>
                <div class="mr-n2 mb-1">
                    <b-form-checkbox v-model="editingHashtag.is_nsfw" switch size="lg"></b-form-checkbox>
                </div>
            </div>
        </div>
        <transition name="fade">
            <div v-if="editingHashtag && editingHashtag.name && editSaved">
                <p class="text-primary small font-weight-bold text-center mt-1 mb-0">Hashtag changes successfully saved!</p>
            </div>
        </transition>
    </b-modal>
</div>
</template>

<script type="text/javascript">
    import Autocomplete from '@trevoreyre/autocomplete-vue'
    import '@trevoreyre/autocomplete-vue/dist/style.css'

    export default {
        components: {
            Autocomplete,
        },

        data() {
            return {
                loaded: false,
                tabIndex: 0,
                stats: {
                    "total_unique": 0,
                    "total_posts": 0,
                    "added_14_days": 0,
                    "total_banned": 0,
                    "total_nsfw": 0
                },
                hashtags: [],
                pagination: [],
                sortCol: undefined,
                sortDir: undefined,
                trendingTags: [],
                bannedTags: [],
                showEditModal: false,
                editingHashtag: undefined,
                editSaved: false,
                editSavedTimeout: undefined,
                searchLoading: false
            }
        },

        mounted() {
            this.fetchStats();
            this.fetchHashtags();

            this.$root.$on('bv::modal::hidden', (bvEvent, modalId) => {
                this.editSaved = false;
                clearTimeout(this.editSavedTimeout);
                this.editingHashtag = undefined;
            });
        },

        watch: {
            editingHashtag: {
                deep: true,
                immediate: true,
                handler: function(updated, old) {
                    if(updated != null && old != null) {
                        this.storeHashtagEdit(updated);
                    }
                }
            }
        },

        methods: {
            fetchStats() {
                axios.get('/i/admin/api/hashtags/stats')
                .then(res => {
                    this.stats = res.data;
                })
            },

            fetchHashtags(url = '/i/admin/api/hashtags/query') {
                axios.get(url)
                .then(res => {
                    this.hashtags = res.data.data;
                    this.pagination = {
                        next: res.data.links.next,
                        prev: res.data.links.prev
                    };
                    this.loaded = true;
                })
            },

            prettyCount(str) {
                if(str) {
                   return str.toLocaleString('en-CA', { compactDisplay: "short", notation: "compact"});
                }
                return str;
            },

            timeAgo(str) {
                if(!str) {
                    return str;
                }
                return App.util.format.timeAgo(str);
            },

            boolIcon(val, success = 'text-success', danger = 'text-muted') {
                if(val) {
                    return `<i class="far fa-check-circle fa-lg ${success}"></i>`;
                }

                return `<i class="far fa-times-circle fa-lg ${danger}"></i>`;
            },

            paginate(dir) {
                event.currentTarget.blur();
                let url = dir == 'next' ? this.pagination.next : this.pagination.prev;
                this.fetchHashtags(url);
            },

            toggleCol(col) {
                this.sortCol = col;

                if(!this.sortDir) {
                    this.sortDir = 'desc';
                } else {
                    this.sortDir = this.sortDir == 'asc' ? 'desc' : 'asc';
                }

                let url = '/i/admin/api/hashtags/query?sort=' + col + '&dir=' + this.sortDir;
                this.fetchHashtags(url);
            },

            buildColumn(name, col) {
                let icon = `<i class="far fa-sort"></i>`;
                if(col == this.sortCol) {
                    icon = this.sortDir == 'desc' ?
                    `<i class="far fa-sort-up"></i>` :
                    `<i class="far fa-sort-down"></i>`
                }
                return `${name} ${icon}`;
            },

            toggleTab(idx) {
                this.loaded = false;
                this.tabIndex = idx;

                if(idx === 0) {
                    this.fetchHashtags();
                } else if(idx === 1) {
                    axios.get('/api/v1.1/discover/posts/hashtags')
                    .then(res => {
                        this.trendingTags = res.data;
                        this.loaded = true;
                    })
                } else if(idx === 2) {
                    let url = '/i/admin/api/hashtags/query?action=banned';
                    this.fetchHashtags(url);
                } else if(idx === 3) {
                    let url = '/i/admin/api/hashtags/query?action=nsfw';
                    this.fetchHashtags(url);
                }
            },

            openEditHashtagModal(hashtag) {
                this.editSaved = false;
                clearTimeout(this.editSavedTimeout);

                this.$nextTick(() => {
                    axios.get('/i/admin/api/hashtags/get', {
                        params: {
                            id: hashtag.id
                        }
                    })
                    .then(res => {
                        this.editingHashtag = res.data.data;
                        this.showEditModal = true;
                    })
                });
            },

            storeHashtagEdit(hashtag, idx) {
                this.editSaved = false;

                if(hashtag.is_banned && (hashtag.can_trend || hashtag.can_search)) {
                    swal('Banned Hashtag Limits', 'Banned hashtags cannot trend or be searchable, to allow those you need to unban the hashtag', 'error');
                }

                axios.post('/i/admin/api/hashtags/update', hashtag)
                .then(res => {
                    this.editSaved = true;

                    if(this.tabIndex !== 1) {
                        this.hashtags = this.hashtags.map(h => {
                            if(h.id == hashtag.id) {
                                h = res.data.data
                            }
                            return h;
                        });
                    }

                    this.editSavedTimeout = setTimeout(() => {
                        this.editSaved = false;
                    }, 5000);
                })
                .catch(err => {
                    swal('Oops!', 'An error occured, please try again.', 'error');
                    console.log(err);
                })
            },

            composeSearch(input) {
                if (input.length < 1) { return []; };
                return axios.get('/i/admin/api/hashtags/query', {
                    params: {
                        q: input,
                        sort: 'cached_count',
                        dir: 'desc'
                    }
                }).then(res => {
                    return res.data.data;
                });
            },

            getTagResultValue(result) {
                return result.name;
            },

            onSearchResultClick(result) {
                this.openEditHashtagModal(result);
                return;
            },

            clearTrendingCache() {
                event.currentTarget.blur();
                if(!window.confirm('Are you sure you want to clear the trending hashtags cache?')){
                    return;
                }
                axios.post('/i/admin/api/hashtags/clear-trending-cache')
                .then(res => {
                    swal('Cache Cleared!', 'Successfully cleared the trending hashtag cache!', 'success');
                });
            }
        }
    }
</script>
