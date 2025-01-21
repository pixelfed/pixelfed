<template>
    <div class="profile-carousel-component">
        <template v-if="showSplash">
            <SplashScreen />
        </template>

        <template v-else>
            <template v-if="emptyFeed">
                <div class="bg-dark d-flex justify-content-center align-items-center w-100 h-100">
                    <div>
                        <h2 class="text-light">Oops! This account hasn't posted yet or is private.</h2>
                        <a href="/" class="font-weight-bold text-muted">Go back home</a>
                    </div>
                </div>
            </template>

            <template v-else>
                <FullscreenCarousel
                    :feed="feed"
                    :withLinks="withLinks"
                    :withOverlay="withOverlay"
                    :autoPlay="autoPlay"
                    :autoPlayInterval="autoPlayInterval"
                    :canLoadMore="hasMoreData"
                    @load-more="loadMoreData"
                  />
            </template>
        </template>
    </div>
</template>

<script>
    import SplashScreen from './SplashScreen.vue';
    import FullscreenCarousel from './FullscreenCarousel.vue'

    export default {
        props: ['profile-id'],

        components: {
            SplashScreen,
            FullscreenCarousel
        },

        data() {
            return {
                showSplash: true,
                profile: {},
                feed: [],
                emptyFeed: false,
                hasMoreData: false,
                withLinks: true,
                withOverlay: true,
                autoPlay: false,
                autoPlayInterval: 5000,
                maxId: null
            }
        },

        mounted() {
            const url = new URL(window.location.href);
            const params = url.searchParams;
            if(params.has('linkless') == true) {
                this.withLinks = false;
            }

            if(params.has('clean') == true) {
                this.withOverlay = false;
            }

            if(params.has('interval') == true) {
                const val = parseInt(params.get('interval'));
                const valid = this.validateIntegerRange(val, { min: 1000, max: 30000 })
                if(valid) {
                    this.autoPlayInterval = val;
                }
            }

            if(params.has('autoplay') == true) {
                this.autoPlay = true;

            }
            this.init();
        },

        methods: {
            async init() {
                await axios.get(`/api/pixelfed/v1/accounts/${this.profileId}/statuses?media_type=photo&limit=10`)
                .then(res => {
                    if(!res || !res.data || !res.data.length) {
                        this.emptyFeed = true;
                        return;
                    }

                    this.maxId = this.arrayMinId(res.data);
                    const posts = res.data.flatMap(post =>
                      post.media_attachments.filter(media => {
                        return ['image/jpeg','image/png', 'image/jpg', 'image/webp'].includes(media.mime)
                      }).map(media => ({
                        media_url: media.url,
                        id: post.id,
                        caption: post.content_text,
                        created_at: post.created_at,
                        url: post.url,
                        account: {
                            username: post.account.username,
                            url: post.account.url
                        }
                      }))
                    );
                    this.feed = posts;
                    this.hasMoreData = res.data.length === 10;
                    setTimeout(() => {
                        this.showSplash = false;
                    }, 3000);
                })
            },

            async fetchMore() {
                await axios.get(`/api/pixelfed/v1/accounts/${this.profileId}/statuses?media_type=photo&limit=10&max_id=${this.maxId}`)
                .then(res => {
                    this.maxId = this.arrayMinId(res.data);
                    const posts = res.data.flatMap(post =>
                      post.media_attachments.filter(media => {
                        return ['image/jpeg','image/png', 'image/jpg', 'image/webp'].includes(media.mime)
                      }).map(media => ({
                        media_url: media.url,
                        id: post.id,
                        caption: post.content_text,
                        created_at: post.created_at,
                        url: post.url,
                        account: {
                            username: post.account.username,
                            url: post.account.url
                        }
                      }))
                    );
                    this.feed.push(...posts);
                    this.hasMoreData = res.data.length === 10;
                })
            },

            arrayMinId(arr) {
                if (arr.length === 0) return null;
                let smallest = BigInt(arr[0].id);
                for (let i = 1; i < arr.length; i++) {
                    const current = BigInt(arr[i].id);
                    if (current < smallest) {
                        smallest = current;
                    }
                }
                return smallest.toString();
            },

            loadMoreData() {
                this.fetchMore();
            },

            validateIntegerRange(value, options = {}) {
                if (typeof value !== 'number' || !Number.isInteger(value)) {
                    return false;
                }

                const {
                    min = Number.MIN_SAFE_INTEGER,
                    max = Number.MAX_SAFE_INTEGER,
                    inclusiveMin = true,
                    inclusiveMax = true
                } = options;

                if (min !== undefined && !Number.isInteger(min)) {
                    return false;
                }
                if (max !== undefined && !Number.isInteger(max)) {
                    return false;
                }
                if (min > max) {
                    return false;
                }

                const aboveMin = inclusiveMin ? value >= min : value > min;
                const belowMax = inclusiveMax ? value <= max : value < max;

                return aboveMin && belowMax;
            }
        }
    }
</script>

<style type="text/css">
    .profile-carousel-component {
        display: block;
        width: 100dvw;
        height: 100dvh;
        z-index: 2;
        background: #000;
    }
</style>
