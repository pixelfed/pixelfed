<template>
    <div class="discover-feed-component">
        <section class="mt-3 mb-5 section-explore">
            <b-breadcrumb class="font-default" :items="breadcrumbItems"></b-breadcrumb>

            <div class="profile-timeline">
                <div class="row p-0 mt-5">
                    <div class="col-12 mb-4 d-flex justify-content-between align-items-center">
                        <p class="d-block d-md-none h1 font-weight-bold mb-0 font-default">Trending</p>
                        <p class="d-none d-md-block display-4 font-weight-bold mb-0 font-default">Trending</p>

                        <div>
                            <div class="btn-group trending-range">
                                <button @click="rangeToggle('daily')" :class="range == 'daily' ? 'btn py-1 font-weight-bold px-3 text-uppercase btn-sm btn-danger':'btn py-1 font-weight-bold px-3 text-uppercase btn-sm btn-outline-danger'">Today</button>
                                <button @click="rangeToggle('monthly')" :class="range == 'monthly' ? 'btn py-1 font-weight-bold px-3 text-uppercase btn-sm btn-danger':'btn py-1 font-weight-bold px-3 text-uppercase btn-sm btn-outline-danger'">This month</button>
                                <button @click="rangeToggle('yearly')" :class="range == 'yearly' ? 'btn py-1 font-weight-bold px-3 text-uppercase btn-sm btn-danger':'btn py-1 font-weight-bold px-3 text-uppercase btn-sm btn-outline-danger'">This year</button>
                            </div>
                        </div>
                    </div>
                </div>
                <div v-if="!loading" class="row p-0 px-lg-3">
                    <div v-if="trending.length" v-for="(s, index) in trending" class="col-6 col-lg-4 col-xl-3 p-1">
                        <a class="card info-overlay card-md-border-0" :href="s.url" @click.prevent="goToPost(s)">
                            <div class="square square-next">
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
                                        :hash="s.media_attachments[0].blurhash"
                                        />
                                </div>
                                <div v-else class="square-content">
                                    <blur-hash-image
                                        width="32"
                                        height="32"
                                        :hash="s.media_attachments[0].blurhash"
                                        :src="s.media_attachments[0].preview_url"
                                        />
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
                        </a>
                    </div>
                    <div v-else class="col-12 d-flex align-items-center justify-content-center bg-light border" style="min-height: 40vh;">
                        <div class="h2">No posts found :(</div>
                    </div>
                </div>

                <div v-else class="row p-0 px-lg-3">
                    <div class="col-12 d-flex align-items-center justify-content-center" style="min-height: 40vh;">
                        <b-spinner size="lg" />
                    </div>
                </div>
            </div>
        </section>
    </div>
</template>

<script type="text/javascript">
    export default {
        props: {
            profile: {
                type: Object
            }
        },

        data() {
            return {
                loading: true,
                trending: [],
                range: 'daily',
                breadcrumbItems: [
                    {
                        text: 'Discover',
                        href: '/i/web/discover'
                    },
                    {
                        text: 'Trending',
                        active: true
                    }
                ]
            }
        },

        mounted() {
            this.loadTrending();
        },

        methods: {
            fetchData() {
                axios.get('/api/pixelfed/v2/discover/posts')
                .then((res) => {
                    this.posts = res.data.posts.filter(r => r != null);
                    this.recommendedLoading = false;
                });
            },

            loadTrending() {
                this.loading = true;

                axios.get('/api/pixelfed/v2/discover/posts/trending', {
                    params: {
                        range: this.range
                    }
                })
                .then(res => {
                    let data = res.data.filter(r => {
                        return r !== null;
                    });
                    this.trending = data.filter(t => t.sensitive == false);

                    if(this.range == 'daily' && data.length == 0) {
                        this.range = 'yearly';
                        this.loadTrending();
                    }

                    this.loading = false;
                });
            },

            formatCount(s) {
                return App.util.format.count(s);
            },

            goToPost(status) {
                this.$router.push({
                    name: 'post',
                    params: {
                        id: status.id,
                        cachedStatus: status,
                        cachedProfile: this.profile
                    }
                })
            },

            rangeToggle(range) {
                event.currentTarget.blur();
                this.range = range;
                this.loadTrending();
            }
        }
    }
</script>

<style lang="scss">
    .discover-feed-component {
        .font-default {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
            letter-spacing: -0.7px;
        }

        .info-overlay {
            border-radius: 15px !important;
        }

        .square-next {
            img,
            .info-overlay-text {
                border-radius: 15px !important;
            }
        }

        .trending-range {
            .btn {
                &:hover:not(.btn-danger) {
                    background-color: #fca5a5
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
    }
</style>
