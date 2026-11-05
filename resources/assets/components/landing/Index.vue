<template>
    <div class="landing-index-component">
        <section class="landing-hero">
            <img :src="config.about.banner_image" class="landing-hero-image" alt=""
                onerror="this.src='/storage/headers/default.jpg';this.onerror=null;">

            <div class="container landing-container landing-hero-inner">
                <div class="landing-hero-card">
                    <p class="landing-hero-domain">{{ config.domain }}</p>
                    <h1 class="landing-hero-title">{{ config.name }}</h1>
                    <p v-if="config.about.short_description" class="landing-hero-lede">{{ config.about.short_description
                        }}</p>
                    <p v-else class="landing-hero-lede" v-html="$t('landing.decentralized_by_pixelfed')"></p>

                    <div v-if="canRegister" class="landing-hero-ctas d-none d-md-flex">
                        <a :href="regLink" class="btn btn-primary btn-primary-alt rounded-pill font-weight-bold px-4">{{
                            $t('landing.create_account') }}</a>
                        <a href="/login" class="btn btn-outline-light rounded-pill font-weight-bold px-4">{{
                            $t('landing.login') }}</a>
                    </div>

                    <div class="landing-hero-meta">
                        <span><b>{{ formatCount(config.stats.posts_count) }}</b> {{ $t('landing.posts').toLowerCase()
                            }}</span>
                        <span><b>{{ formatCount(config.stats.active_users) }}</b> {{ $t('landing.active_this_month')
                            }}</span>
                        <a v-if="config.contact.account" :href="config.contact.account.url" class="landing-hero-admin"
                            target="_blank">
                            <img :src="config.contact.account.avatar" width="22" height="22"
                                :alt="`${config.contact.account.username}'s avatar`"
                                onerror="this.src='/storage/avatars/default.jpg';this.onerror=null;">
                            <span>{{ $t('landing.run_by') }} <b>&commat;{{ config.contact.account.username }}</b></span>
                        </a>
                    </div>
                </div>
            </div>
        </section>

        <div class="container landing-container landing-body">
            <section v-if="config.show_explore_feed && (loadingFeed || feed.length)" class="landing-section">
                <div class="landing-section-head">
                    <h2>{{ $t('landing.recently_on', { name: config.name }) }}</h2>
                    <router-link to="/web/explore">{{ $t('landing.explore_all') }}</router-link>
                </div>

                <div class="row no-gutters landing-feed">
                    <template v-if="loadingFeed">
                        <div v-for="n in 8" :key="`ph-${n}`" class="col-4 col-md-3">
                            <div class="landing-tile landing-tile-placeholder"></div>
                        </div>
                    </template>
                    <template v-else>
                        <div v-for="post in feed" :key="post.id" class="col-4 col-md-3">
                            <a :href="post.url" class="landing-tile" target="_blank">
                                <img :src="thumbnail(post)" :alt="altText(post)" loading="lazy"
                                    onerror="this.src='/storage/no-preview.png';this.onerror=null;">
                                <span class="landing-tile-by d-none d-md-inline-block">&commat;{{ post.account.username
                                    }}</span>
                            </a>
                        </div>
                    </template>
                </div>
            </section>

            <div class="row landing-info">
                <div class="col-12 col-md-6 mb-3">
                    <div class="landing-card h-100">
                        <h3>{{ $t('landing.server_rules') }}</h3>
                        <ol v-if="config.rules && config.rules.length" class="landing-rules">
                            <li v-for="rule in config.rules" :key="rule.id">{{ rule.text }}</li>
                        </ol>
                        <p v-else class="landing-muted mb-0">{{ $t('landing.no_rules') }}</p>
                    </div>
                </div>

                <div class="col-12 col-md-6 mb-3">
                    <div class="landing-card h-100">
                        <h3>{{ $t('landing.about') }}</h3>
                        <div v-if="config.about.description" class="landing-about" v-html="config.about.description">
                        </div>

                        <div class="landing-chips">
                            <span>{{ $t('landing.up_to_n_photos', { num: config.uploader.album_limit }) }}</span>
                            <span>{{ $t('landing.max_file_size', { size: formatBytes(config.uploader.max_photo_size) })
                                }}</span>
                            <span v-if="config.features.video">{{ $t('landing.features.videos') }}</span>
                            <span v-if="config.features.stories">{{ $t('landing.features.stories') }}</span>
                            <span v-if="config.features.mobile_apis">{{ $t('landing.features.mobile_app') }}</span>
                            <span v-if="config.features.federation">{{ $t('landing.features.federation') }}</span>
                        </div>

                        <p v-if="config.contact.email" class="landing-contact mb-0">
                            <span class="landing-muted">{{ $t('landing.contact') }}</span>
                            <a :href="`mailto:${config.contact.email}?subject=Regarding ${config.domain}`">{{
                                config.contact.email
                                }}</a>
                        </p>

                        <router-link v-if="config.show_directory" to="/web/directory" class="landing-directory-link">
                            {{ $t('landing.discover_accounts') }}
                        </router-link>
                    </div>
                </div>
            </div>
        </div>

        <footer-component />

        <div class="landing-sticky-cta d-md-none">
            <a href="/login" class="btn btn-outline-light rounded-pill font-weight-bold flex-fill">{{
                $t('landing.login') }}</a>
            <a v-if="canRegister" :href="regLink"
                class="btn btn-primary btn-primary-alt rounded-pill font-weight-bold flex-fill">{{
                    $t('landing.create_account')
                }}</a>
        </div>
    </div>
</template>

<script type="text/javascript">
export default {
    data() {
        return {
            config: window.pfl,
            feed: [],
            loadingFeed: true,
            ranges: ['daily', 'monthly', 'yearly']
        }
    },

    computed: {
        canRegister() {
            return this.config.open_registration || this.config.curated_onboarding;
        },

        regLink() {
            if (this.config.open_registration) {
                return '/register';
            }

            return '/auth/sign_up';
        }
    },

    mounted() {
        if (this.config.show_explore_feed) {
            this.fetchFeed(0);
        } else {
            this.loadingFeed = false;
        }
    },

    methods: {
        fetchFeed(rangeIndex) {
            if (rangeIndex >= this.ranges.length) {
                this.loadingFeed = false;
                return;
            }

            axios.get('/api/pixelfed/v2/discover/posts/trending', {
                params: {
                    range: this.ranges[rangeIndex]
                }
            })
                .then(res => {
                    const posts = Array.isArray(res.data) ? res.data.filter(this.isDisplayable) : [];

                    if (posts.length >= 4) {
                        this.feed = posts.slice(0, posts.length >= 8 ? 8 : 4);
                        this.loadingFeed = false;
                        return;
                    }

                    this.fetchFeed(rangeIndex + 1);
                })
                .catch(() => {
                    this.loadingFeed = false;
                });
        },

        isDisplayable(post) {
            if (!post || post.sensitive || !post.account) {
                return false;
            }

            if (!post.media_attachments || !post.media_attachments.length) {
                return false;
            }

            return !!this.thumbnail(post);
        },

        thumbnail(post) {
            const media = post.media_attachments[0];
            if (!media) {
                return null;
            }

            const preview = media.preview_url;
            if (preview && (preview.endsWith('no-preview.png') || preview.endsWith('no-preview.jpg'))) {
                return null;
            }

            if (media.type === 'image') {
                return preview || media.url;
            }

            return preview && preview !== media.url ? preview : null;
        },

        altText(post) {
            const media = post.media_attachments[0];
            if (media && media.description) {
                return media.description;
            }

            return `Post by @${post.account.username}`;
        },

        formatCount(val) {
            if (!val) {
                return 0;
            }

            return val.toLocaleString('en-CA', { compactDisplay: "short", notation: "compact" });
        },

        formatBytes(bytes) {
            const units = ['byte', 'kilobyte', 'megabyte', 'gigabyte', 'terabyte'];
            const navigatorLocal = navigator.languages && navigator.languages.length >= 0 ? navigator.languages[0] : 'en-US';
            const unitIndex = Math.max(0, Math.min(Math.floor(Math.log(bytes) / Math.log(1024)), units.length - 1));
            return Intl.NumberFormat(navigatorLocal, {
                style: 'unit',
                unit: units[unitIndex],
                useGrouping: false,
                maximumFractionDigits: 0,
                roundingMode: 'ceil'
            }).format(bytes / (1024 ** unitIndex))
        }
    }
}
</script>
