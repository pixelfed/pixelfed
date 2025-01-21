<template>
    <div class="w-100 h-100">
        <div v-if="loading" class="container">
            <div class="d-flex justify-content-center align-items-center" style="height: 100vh;">
                <b-spinner />
            </div>
        </div>

        <div v-else class="container">
            <div class="row py-5">
                <div class="col-12">
                    <div class="d-flex align-items-center flex-column">
                        <img
                        	v-if="settings.show_avatar"
                        	:src="profile.avatar"
                        	width="60"
                        	height="60"
                        	class="rounded-circle shadow"
                        	onerror="this.src='/storage/avatars/default.png?v=0';this.onerror=null;">

                        <div class="py-3 text-center" style="max-width: 60%">
                            <h1 class="font-weight-bold">{{ profile.username }}</h1>
                            <p v-if="settings.show_bio" class="font-weight-light mb-0 text-break">{{ profile.note_text }}</p>
                        </div>

                        <div v-if="settings.show_profile_button || (settings.rss_enabled && settings.show_rss_button)" class="pb-3 text-center d-flex flex-column flex-sm-row" style="max-width: 60%;gap: 1rem;">
                        	<a
                        		v-if="settings.show_profile_button"
                        		class="btn btn-outline-primary btn-custom-color"
                        		:href="profile.url"
                        		target="_blank">
                        		View Profile
                        	</a>

                        	<a
                        		v-if="settings.rss_enabled && settings.show_rss_button"
                        		class="btn btn-outline-primary btn-custom-color"
                        		:href="settings.rss_feed_url"
                        		target="_blank">
                        		<i class="far fa-rss"></i> &nbsp; RSS
                        	</a>
                        </div>
                    </div>
                </div>
            </div>

            <div class="container mb-5 pb-5">
                <div :class="[ settings.profile_layout === 'masonry' ? 'card-columns' : 'row']" id="portContainer">
                    <template v-if="settings.profile_layout ==='grid'">
                        <div v-for="(res, index) in feed" class="col-12 col-md-4 mb-1 p-1">
                            <div class="square">
                                <a :href="postUrl(res)">
                                	<div class="lazy-img">
	                                	<blur-hash-canvas
	                                		width="32"
	                                		height="32"
	                                		:hash="res.media_attachments[0].blurhash"
	                                		class="square-content pr-1"
	                                	/>

                                		<img
                                			src=""
	                                    	:data-src="res.media_attachments[0].url"
	                                    	width="100%"
	                                    	height="300"
	                                    	style="overflow: hidden;object-fit: cover;z-index: -1;"
	                                    	class="square-content pr-1 img-placeholder"
	                                    	loading="lazy" />
                                	</div>
                                </a>
                            </div>
                        </div>
                    </template>

                    <div v-else-if="settings.profile_layout ==='album'" class="col-12 mb-1 p-1">
                        <div class="d-flex justify-content-center">
                            <p class="text-color font-weight-bold">{{ albumIndex + 1 }} <span class="font-weight-light">/</span> {{ feed.length }}</p>
                        </div>
                        <div class="d-flex justify-content-between align-items-center">
                            <span v-if="albumIndex === 0">
                                <i class="fa fa-arrow-circle-left fa-3x text-color-lighter" />
                            </span>
                            <a v-else @click.prevent="albumPrev()" href="#">
                                <i class="fa fa-arrow-circle-left fa-3x text-color"/>
                            </a>
                            <transition name="slide-fade">
                                <a :href="postUrl(feed[albumIndex])" class="mx-4" :key="albumIndex">
                                    <img
                                        :src="feed[albumIndex].media_attachments[0].url"
                                        width="100%"
                                        class="user-select-none"
                                        style="height: 60vh; overflow: hidden;object-fit: contain;"
                                        :draggable="false"
                                        >
                                </a>
                            </transition>
                            <span v-if="albumIndex === feed.length - 1">
                                <i class="fa fa-arrow-circle-right fa-3x text-color-lighter" />
                            </span>
                            <a v-else @click.prevent="albumNext()" href="#">
                                <i class="fa fa-arrow-circle-right fa-3x text-color"/>
                            </a>
                        </div>
                    </div>

                    <div v-else-if="settings.profile_layout ==='masonry'" class="col-12 p-0 m-0">
                        <div v-for="(res, index) in feed" class="p-1">
                            <a :href="postUrl(res)" data-fancybox="recent" :data-src="res.media_attachments[0].url" :data-width="res.media_attachments[0].width" :data-height="res.media_attachments[0].height">
	                                <img
	                                    :src="res.media_attachments[0].url"
	                                    width="100%"
	                                    class="user-select-none"
	                                    style="overflow: hidden;object-fit: contain;"
	                                    :draggable="false"
	                                    >
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            <div class="d-flex fixed-bottom p-3 justify-content-between align-items-center">
                <a v-if="user" class="logo-mark logo-mark-sm mb-0 p-1" href="/">
                    <span class="text-gradient-primary">portfolio</span>
                </a>
                <span v-else class="logo-mark logo-mark-sm mb-0 p-1">
                    <span class="text-gradient-primary">portfolio</span>
                </span>
                <p v-if="user && user.id == profile.id" class="text-center mb-0">
                    <a :href="settingsUrl" class="link-color"><i class="far fa-cog fa-lg"></i></a>
                </p>
            </div>
        </div>
    </div>
</template>

<script type="text/javascript">
    import '@fancyapps/fancybox/dist/jquery.fancybox.js';
    import '@fancyapps/fancybox/dist/jquery.fancybox.css';

    export default {
        props: [ 'initialData' ],

        data() {
            return {
                loading: true,
                user: undefined,
                profile: undefined,
                settings: undefined,
                feed: [],
                albumIndex: 0,
                settingsUrl: window._portfolio.path + '/settings',
            }
        },

        mounted() {
            const initialData = JSON.parse(this.initialData);
            this.profile = initialData.profile;
            this.fetchUser();
        },

        methods: {
            async fetchUser() {
                axios.get('/api/v1/accounts/verify_credentials')
                .then(res => {
                    this.user = res.data;
                })
                .catch(err => {
                });

                await axios.get('/api/portfolio/account/settings.json', {
                    params: {
                        id: this.profile.id
                    }
                })
                .then(res => {
                    this.settings = res.data;

                    if(res.data.hasOwnProperty('background_color')) {
                    	this.updateCssVariable('--body-bg', res.data.background_color);
                    }

                    if(res.data.hasOwnProperty('text_color')) {
                    	this.updateCssVariable('--text-color', res.data.text_color);
                    	this.updateCssVariable('--link-color', res.data.text_color);
                    }
                })
                .then(() => {
                    this.fetchFeed();
                })

            },

            async fetchFeed() {
                axios.get('/api/portfolio/' + this.profile.id + '/feed')
                .then(res => {
                    this.feed = res.data.filter(p => ['photo', 'photo:album'].includes(p.pf_type));
                })
                .then(() => {
                    this.setAlbumSlide();
                })
                .then(() => {
                    setTimeout(() => {
                        this.loading = false;
                    }, 500);
                })
                .then(() => {
                    if(this.settings.profile_layout === 'masonry') {
                        setTimeout(() => {
                            this.initMasonry();
                        }, 500);
                    }
                })
                .then(() => {
                	setTimeout(() => {
                		this.bootIntersectors()
                	}, 500);
                })
                .catch(err => {
                    this.loading = false;
                })
            },

            postUrl(res) {
                return `${window._portfolio.path}/${this.profile.username}/${res.id}`;
            },

            albumPrev() {
                if(this.albumIndex === 0) {
                    return;
                }
                if(this.albumIndex === 1) {
                    this.albumIndex--;
                    const url = new URL(window.location);
                    url.searchParams.delete('slide');
                    window.history.pushState({}, '', url);
                    return;
                }
                this.albumIndex--;
                const url = new URL(window.location);
                url.searchParams.set('slide', this.albumIndex + 1);
                window.history.pushState({}, '', url);
            },

            albumNext() {
                if(this.albumIndex === this.feed.length - 1) {
                    return;
                }
                this.albumIndex++;
                const url = new URL(window.location);
                url.searchParams.set('slide', this.albumIndex + 1);
                window.history.pushState({}, '', url);
            },

            setAlbumSlide() {
                const url = new URL(window.location);
                if(url.searchParams.has('slide')) {
                    const slide = Number.parseInt(url.searchParams.get('slide'));
                    if(Number.isNaN(slide)) {
                        return;
                    }
                    if(slide <= 0) {
                        return;
                    }
                    if(slide > this.feed.length) {
                        return;
                    }
                    this.albumIndex = url.searchParams.get('slide') - 1;
                }
            },

            initMasonry() {
                $('[data-fancybox="recent"]').fancybox({
                    gutter: 20,
                    modal: false,
                });
            },

            updateCssVariable(k, v) {
            	let rs = document.querySelector(':root');
            	rs.style.setProperty(k, v);
            },

            bootIntersectors() {
            	var lazyImages = [].slice.call(document.querySelectorAll("img.img-placeholder"));

            	if ("IntersectionObserver" in window) {
            		let lazyImageObserver = new IntersectionObserver(function(entries, observer) {
            			entries.forEach(function(entry) {
            				if (entry.isIntersecting) {
            					let lazyImage = entry.target;
            					lazyImage.src = lazyImage.dataset.src;
            					lazyImage.style.zIndex = 2;
            					lazyImage.classList.remove("img-placeholder");
            					lazyImageObserver.unobserve(lazyImage);
            				}
            			});
            		});

            		lazyImages.forEach(function(lazyImage) {
            			lazyImageObserver.observe(lazyImage);
            		});
            	} else {
            		lazyImages.forEach(function(img) {
            			img.src = img.dataset.src;
            			img.style.zIndex = 2;
            		})
            	}
            }
        }
    }
</script>
