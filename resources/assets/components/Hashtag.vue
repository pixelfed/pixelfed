<template>
	<div class="hashtag-component">
		<div class="container-fluid mt-3">
			<div class="row">
				<div class="col-md-3 d-md-block">
					<sidebar :user="profile" />
				</div>
				<div class="col-md-9">
					<div class="card border-0 shadow-sm mb-3" style="border-radius: 18px;">
						<div class="card-body">
							<div class="media align-items-center py-3">
								<div class="media-body">
									<p class="h3 text-break mb-0">
										<span class="text-lighter">#</span>{{ hashtag.name }}
									</p>
									<p v-if="hashtag.count && hashtag.count > 100" class="mb-0 text-muted font-weight-bold">
										{{ formatCount(hashtag.count) }} Posts
									</p>
								</div>
								<template v-if="hashtag && hashtag.hasOwnProperty('following') && feed && feed.length">
									<button
										v-if="hashtag.following"
										:disabled="followingLoading"
										class="btn btn-light hashtag-follow border rounded-pill font-weight-bold py-1 px-4"
										@click="unfollowHashtag()"
										>
										<b-spinner v-if="followingLoading" small />
										<span v-else>
											{{ $t('profile.unfollow') }}
										</span>
									</button>

									<button
										v-else
										:disabled="followingLoading"
										class="btn btn-primary hashtag-follow font-weight-bold rounded-pill py-1 px-4"
										@click="followHashtag()"
										>
										<b-spinner v-if="followingLoading" small />
										<span v-else>
											{{ $t('profile.follow') }}
										</span>
									</button>
								</template>
							</div>

						</div>
					</div>

					<template v-if="isLoaded && feedLoaded">
						<div class="row mx-0 hashtag-feed">
							<div class="col-6 col-md-4 col-lg-3 p-1" v-for="(status, index) in feed" :key="'tlob:'+index">
								<a
									class="card info-overlay card-md-border-0"
									:href="statusUrl(status)"
									@click.prevent="goToPost(status)">
									<div class="square">
										<div v-if="status.sensitive" class="square-content">
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
												:hash="status.media_attachments[0].blurhash"
												/>
										</div>
										<div v-else class="square-content">
											<blur-hash-image
												width="32"
												height="32"
												:hash="status.media_attachments[0].blurhash"
												:src="status.media_attachments[0].url"
												/>
										</div>
										<span v-if="status.pf_type == 'photo:album'" class="float-right mr-3 post-icon"><i class="fas fa-images fa-2x"></i></span>
										<span v-if="status.pf_type == 'video'" class="float-right mr-3 post-icon"><i class="fas fa-video fa-2x"></i></span>
										<span v-if="status.pf_type == 'video:album'" class="float-right mr-3 post-icon"><i class="fas fa-film fa-2x"></i></span>
										<div class="info-overlay-text">
											<h5 class="text-white m-auto font-weight-bold">
												<span>
													<span class="far fa-comment fa-lg p-2 d-flex-inline"></span>
													<span class="d-flex-inline">{{formatCount(status.reply_count)}}</span>
												</span>
											</h5>
										</div>
									</div>
								</a>
							</div>

							<div v-if="canLoadMore" class="col-12">
								<intersect @enter="enterIntersect">
									<div class="d-flex justify-content-center py-5">
										<b-spinner />
									</div>
								</intersect>

								<!-- <div v-else class="ph-item">
									<div class="ph-picture big"></div>
								</div> -->
							</div>
						</div>

						<div v-if="feedLoaded && !feed.length" class="row mx-0 hashtag-feed justify-content-center">
							<div class="col-12 col-md-8 text-center">
								<img src="/img/illustrations/dk-nature-man-monochrome.svg" class="img-fluid" style="opacity: 0.6;max-width:400px">
								<p class="lead text-muted font-weight-bold">{{ $t('hashtags.emptyFeed') }}</p>
							</div>
						</div>
					</template>

					<template v-else>
						<div class="row justify-content-center align-items-center pt-5 mt-5">
							<b-spinner />
						</div>
					</template>
				</div>
			</div>
			<drawer />
		</div>
	</div>
</template>

<script type="text/javascript">
	import Drawer from './partials/drawer.vue';
	import Intersect from 'vue-intersect'
	import Sidebar from './partials/sidebar.vue';
	import Rightbar from './partials/rightbar.vue';

	export default {
		props: {
			id: {
				type: String
			}
		},

		components: {
			"drawer": Drawer,
			"intersect": Intersect,
            "sidebar": Sidebar,
            "rightbar": Rightbar,
        },

        data() {
        	return {
        		isLoaded: false,
        		profile: undefined,
        		canLoadMore: false,
        		isIntersecting: false,
        		feedLoaded: false,
        		feed: [],
        		page: 1,
        		hashtag: {
        			name: this.id,
        			count: 0
        		},
        		followingLoading: false,
        		maxId: undefined,
        	};
        },

		mounted() {
			this.init();
		},

		watch: {
			'$route': 'init'
		},

		methods: {
			init() {
				this.profile = window._sharedData.user;
				axios.get('/api/v1/tags/' + this.id, {
					params: {
						'_pe': 1
					}
				})
				.then(res => {
					this.hashtag = res.data;
				})
				.catch(err => {
					swal('Error', 'Something went wrong, please try again later!', 'error');
					this.isLoaded = true;
					this.feedLoaded = true;
				})
				.finally(() => {
					this.fetchFeed();
				})
			},

			fetchFeed() {
				axios.get('/api/v1/timelines/tag/' + this.id, {
					params: {
						limit: 80,
					}
				})
				.then(res => {
					if(res.data && res.data.length) {
						this.feed = res.data;
						this.maxId = res.data[res.data.length - 1].id;
						return true;
					} else {
						this.feedLoaded = true;
						this.isLoaded = true;
						return false;
					}
				})
				.then(res => {
					this.canLoadMore = res;
				})
				.finally(() => {
					this.feedLoaded = true;
					this.isLoaded = true;
				})
			},

			statusUrl(status) {
				return '/i/web/post/' + status.id;
			},

			formatCount(val) {
				return App.util.format.count(val);
			},

			enterIntersect() {
				if(this.isIntersecting) {
					return;
				}

				this.isIntersecting = true;
				axios.get('/api/v1/timelines/tag/' + this.id, {
					params: {
						max_id: this.maxId,
						limit: 40,
					}
				})
				.then(res => {
					if(res.data && res.data.length) {
						this.feed.push(...res.data);
						this.maxId = res.data[res.data.length - 1].id;
						return true;
					} else {
						return false;
					}
				})
				.then(res => {
					this.canLoadMore = res;
				})
				.finally(() => {
					this.isIntersecting = false;
				})
			},

			goToPost(status) {
				this.$router.push({
					name: 'post',
					path: `/i/web/post/${status.id}`,
					params: {
						id: status.id,
						cachedStatus: status,
						cachedProfile: this.profile
					}
				})
			},

			followHashtag() {
				this.followingLoading = true;
				axios.post('/api/v1/tags/' + this.id + '/follow')
				.then(res => {
					setTimeout(() => {
						this.hashtag.following = true;
						this.followingLoading = false;
					}, 500);
				});
			},

			unfollowHashtag() {
				this.followingLoading = true;
				axios.post('/api/v1/tags/' + this.id + '/unfollow')
				.then(res => {
					setTimeout(() => {
						this.hashtag.following = false;
						this.followingLoading = false;
					}, 500);
				});
			},
		}
	}
</script>

<style lang="scss">
	.hashtag-component {
		.hashtag-feed {
			.card,
			.info-overlay-text,
			.info-overlay-text-label,
			img,
			canvas {
				border-radius: 18px !important;
			}
		}

		.hashtag-follow {
			width: 200px;
		}

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
					border-radius: 18px;
				}

				& > * {
					margin-bottom: 0;
				}
			}
		}
	}
</style>
