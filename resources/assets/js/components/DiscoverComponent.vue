<template>
	<div>
		<div v-if="!loaded" style="height: 70vh;" class="d-flex justify-content-center align-items-center">
			<img src="/img/pixelfed-icon-grey.svg">
		</div>
		<div v-else>
			<div v-if="authenticated" class="d-block d-md-none border-top-0 pt-3">
				<input class="form-control rounded-pill shadow-sm" placeholder="Search" v-model="searchTerm" v-on:keyup.enter="searchSubmit">
			</div>

			<section class="mt-3 mb-5 section-explore">
				<div class="profile-timeline">
					<div class="row p-0 mt-5">
						<div class="col-12 mb-3 d-flex justify-content-between align-items-center">
							<p class="d-block d-md-none h1 font-weight-bold mb-0">Trending</p>
							<p class="d-none d-md-block display-4 font-weight-bold mb-0">Trending</p>
							<div>
								<div class="btn-group">
									<button @click="trendingRangeToggle('daily')" :class="trendingRange == 'daily' ? 'btn py-1 font-weight-bold px-3 text-uppercase btn-sm btn-danger':'btn py-1 font-weight-bold px-3 text-uppercase btn-sm btn-outline-danger'">Daily</button>
									<button @click="trendingRangeToggle('monthly')" :class="trendingRange == 'monthly' ? 'btn py-1 font-weight-bold px-3 text-uppercase btn-sm btn-danger':'btn py-1 font-weight-bold px-3 text-uppercase btn-sm btn-outline-danger'">Monthly</button>
								</div>
							</div>
						</div>
					</div>
					<div v-if="!trendingLoading" class="row p-0 d-flex">
						<div v-if="trending.length" v-for="(s, index) in trending.slice(0, 12)" class="col-4 p-1 p-sm-2 p-md-3 pt-0">
							<a class="card info-overlay card-md-border-0" :href="s.url">
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
									<span v-if="s.pf_type == 'photo:album'" class="float-right mr-3 post-icon"><i class="fas fa-images fa-2x"></i></span>
									<span v-if="s.pf_type == 'video'" class="float-right mr-3 post-icon"><i class="fas fa-video fa-2x"></i></span>
									<span v-if="s.pf_type == 'video:album'" class="float-right mr-3 post-icon"><i class="fas fa-film fa-2x"></i></span>
									<div class="info-overlay-text">
										<h5 class="text-white m-auto font-weight-bold">
											<span>
												<span class="far fa-comment fa-lg p-2 d-flex-inline"></span>
												<span class="d-flex-inline">{{formatCount(s.reply_count)}}</span>
											</span>
										</h5>
									</div>
								</div>
							</a>
						</div>
						<div v-else class="col-12 d-flex align-items-center justify-content-center bg-light border" style="min-height: 40vh;">
							<div class="h2">No posts found :(</div>
						</div>
					</div>
					<div v-else class="row d-flex align-items-center justify-content-center bg-light border" style="min-height: 40vh;">
						<div class="spinner-border" role="status">
							<span class="sr-only">Loading...</span>
						</div>
					</div>
				</div>
			</section>

			<section v-if="authenticated" class="pt-5 mb-5 section-explore">
				<div class="profile-timeline pt-3">
					<div class="row p-0 mt-5">
						<div class="col-12 mb-3 d-flex justify-content-between align-items-center">
							<p class="d-block d-md-none h1 font-weight-bold mb-0">For You</p>
							<p class="d-none d-md-block display-4 font-weight-bold mb-0">For You</p>
						</div>
					</div>
					<div v-if="!recommendedLoading" class="row p-0 d-flex">
						<div v-if="posts.length" v-for="(s, index) in posts" :key="'rmki:'+index" class="col-4 p-1 p-sm-2 p-md-3 pt-0">
							<a class="card info-overlay card-md-border-0" :href="s.url">
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
									<span v-if="s.pf_type == 'photo:album'" class="float-right mr-3 post-icon"><i class="fas fa-images fa-2x"></i></span>
									<span v-if="s.pf_type == 'video'" class="float-right mr-3 post-icon"><i class="fas fa-video fa-2x"></i></span>
									<span v-if="s.pf_type == 'video:album'" class="float-right mr-3 post-icon"><i class="fas fa-film fa-2x"></i></span>
									<div class="info-overlay-text">
										<h5 class="text-white m-auto font-weight-bold">
											<span>
												<span class="far fa-comment fa-lg p-2 d-flex-inline"></span>
												<span class="d-flex-inline">{{formatCount(s.reply_count)}}</span>
											</span>
										</h5>
									</div>
								</div>
							</a>
						</div>
						<div v-else class="col-12 d-flex align-items-center justify-content-center bg-light border" style="min-height: 40vh;">
							<div class="h2">No posts found :(</div>
						</div>
					</div>
					<div v-else class="row d-flex align-items-center justify-content-center bg-light border" style="min-height: 40vh;">
						<div class="spinner-border" role="status">
							<span class="sr-only">Loading...</span>
						</div>
					</div>
				</div>
			</section>
		</div>
	</div>
</template>

<script type="text/javascript">
	export default {
		data() {
			return {
				authenticated: false,
				loaded: false,
				config: window.App.config,
				posts: {},
				trending: {},
				trendingDaily: {},
				trendingMonthly: {},
				searchTerm: '',
				trendingRange: 'daily',
				trendingLoading: true,
				recommendedLoading: true
			}
		},

		beforeMount() {
			this.authenticated = $('body').hasClass('loggedIn');
		},

		mounted() {
			this.loaded = true;
			this.loadTrending();
			if($('body').hasClass('loggedIn') == true) {
				this.fetchData();
				axios.get('/api/pixelfed/v1/accounts/verify_credentials').then(res => {
					window._sharedData.curUser = res.data;
					window.App.util.navatar();
				});
			}
		},

		methods: {
			fetchData() {
				if(!this.recommendedLoading) {
					return;
				}
				axios.get('/api/v1/discover/posts')
				.then((res) => {
					this.posts = res.data.posts.filter(r => r != null);
					this.recommendedLoading = false;
				});
			},

			searchSubmit() {
				if(this.searchTerm.length > 1) {
					window.location.href = '/i/results?q=' + this.searchTerm;
				}
			},

			loadTrending() {
				if(this.trendingRange == 'daily' && this.trendingDaily.length) {
					this.trending = this.trendingDaily;
					this.trendingLoading = false;
				}
				if(this.trendingRange == 'monthly' && this.trendingMonthly.length) {
					this.trending = this.trendingMonthly;
					this.trendingLoading = false;
				}
				axios.get('/api/pixelfed/v2/discover/posts/trending', {
					params: {
						range: this.trendingRange
					}
				})
				.then(res => {
					let data = res.data.filter(r => {
						return r !== null;
					});
					if(this.trendingRange == 'daily') {
						this.trendingDaily = data.filter(t => t.sensitive == false);
					}
					if(this.trendingRange == 'monthly') {
						this.trendingMonthly = data.filter(t => t.sensitive == false);
					}
					this.trending = data;
					this.trendingLoading = false;
				});
			},

			trendingRangeToggle(r) {
				this.trendingLoading = true;
				this.trendingRange = r;
				this.loadTrending();
			},

			formatCount(s) {
				return App.util.format.count(s);
			}
		}
	}
</script>
