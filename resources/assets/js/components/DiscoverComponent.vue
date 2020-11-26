<template>
	<div>
		<div v-if="!loaded" style="height: 70vh;" class="d-flex justify-content-center align-items-center">
			<img src="/img/pixelfed-icon-grey.svg">
		</div>
		<div v-else>
			<div class="d-block d-md-none border-top-0 pt-3">
				<input class="form-control rounded-pill shadow-sm" placeholder="Search" v-model="searchTerm" v-on:keyup.enter="searchSubmit">
			</div>
			<div class="pt-3">
				<p class="d-block d-md-none h1 font-weight-bold text-lighter pt-3" style="opacity: 0.4"><i class="far fa-compass"></i> DISCOVER</p>
				<p class="d-none d-md-block display-3 font-weight-bold text-lighter pt-3" style="opacity: 0.4"><i class="far fa-compass"></i> DISCOVER</p>
			</div>
			

			<section v-if="hashtags.length" class="mb-4 pb-5 section-explore mt-4 pt-4">
					<div class="lead">
						<i class="fas fa-hashtag text-lighter fa-lg mr-3"></i>
						<a v-for="(tag, index) in hashtags" :href="tag.url" class="badge badge-light rounded-pill border py-2 px-3 mr-2 mb-2 shadow-sm border-danger text-danger">{{tag.name}}</a>
					</div>
					<div class="lead mt-4">
						<i class="fas fa-map-marker-alt text-lighter fa-lg mr-3"></i>
						<a v-for="(tag, index) in places" :href="tag.url" class="badge badge-light rounded-pill border py-2 px-3 mr-2 mb-2 shadow-sm border-danger text-danger">{{tag.name}}, {{tag.country}}</a>
					</div>
			</section>

			<section v-if="trending.length" class="mb-5 section-explore">
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
					<div class="row p-0" style="display: flex;">
						<div v-for="(post, index) in trending.slice(0, 12)" class="col-4 p-1 p-sm-2 p-md-3 pt-0">
							<a class="card info-overlay card-md-border-0" :href="post.url">
								<div class="square">
									<span v-if="post.pf_type == 'photo:album'" class="float-right mr-3 post-icon"><i class="fas fa-images fa-2x"></i></span>
									<span v-if="post.pf_type == 'video'" class="float-right mr-3 post-icon"><i class="fas fa-video fa-2x"></i></span>
									<span v-if="post.pf_type == 'video:album'" class="float-right mr-3 post-icon"><i class="fas fa-film fa-2x"></i></span>
									<div class="square-content" v-bind:style="{ 'background-image': 'url(' + post.media_attachments[0].preview_url + ')' }">
									</div>
								</div>
							</a>
						</div>
					</div>
				</div>
			</section>

			<section v-if="categories.length > 0" class="mb-5 section-explore">
				<div class="profile-timeline pt-3">
					<div class="row p-0 mt-5">
						<div class="col-12 mb-4 d-flex justify-content-between align-items-center">
							<p class="d-block d-md-none h1 font-weight-bold mb-0">Categories</p>
							<p class="d-none d-md-block display-4 font-weight-bold mb-0">Categories</p>
						</div>
					</div>
					<section class="d-none d-md-flex mb-md-2 discover-bar" style="width:auto; overflow: auto hidden;">
						<a v-for="(category, index) in categories" :key="index+'_cat_'" class="bg-dark rounded d-inline-flex align-items-end justify-content-center mr-3 box-shadow card-disc text-decoration-none" :href="category.url" :style="'background: linear-gradient(rgba(0, 0, 0, 0.3),rgba(0, 0, 0, 0.3)),url('+category.thumb+');'">
							<p class="text-white font-weight-bold" style="text-shadow: 3px 3px 16px #272634;">{{category.name}}</p>
						</a>

					</section>
				</div>
			</section>
			<section v-if="categories.length > 0" class="py-5 mb-5 section-explore bg-warning rounded">
				<div class="profile-timeline py-3">
					<div class="row p-0 my-5">
						<div class="col-12 mb-3 text-center text-dark">
							<p class="d-none d-md-block display-3 font-weight-bold">Discover. Categories.</p>
							<p class="d-block d-md-none h1 font-weight-bold">Discover. Categories.</p>
							<p class="h4 font-weight-light mb-0">Discover amazing posts, people, places and hashtags.</p>
						</div>
					</div>
				</div>
			</section>
			<section v-if="posts.length" class="pt-5 mb-5 section-explore">
				<div class="profile-timeline pt-3">
					<div class="row p-0 mt-5">
						<!-- <div class="col-12 mb-3 d-flex justify-content-between align-items-center">
							<p class="d-block d-md-none h1 font-weight-bold mb-0">Spotlight</p>
							<p class="d-none d-md-block display-4 font-weight-bold mb-0">Spotlight</p>
							<div>
								<div class="btn-group">
									<button class="btn btn-danger py-1 font-weight-bold px-3 text-uppercase btn-sm">Today</button>
									<button class="btn btn-outline-danger py-1 font-weight-bold px-3 text-uppercase btn-sm">Yesterday</button>
								</div>
							</div>
						</div> -->
						<!-- <div class="col-12 col-md-6">
							<div class="mb-4">
								<a class="card info-overlay card-md-border-0" :href="posts[10].url">
									<div class="square">
										<span v-if="posts[10].type == 'photo:album'" class="float-right mr-3 post-icon"><i class="fas fa-images fa-2x"></i></span>
										<span v-if="posts[10].type == 'video'" class="float-right mr-3 post-icon"><i class="fas fa-video fa-2x"></i></span>
										<span v-if="posts[10].type == 'video:album'" class="float-right mr-3 post-icon"><i class="fas fa-film fa-2x"></i></span>
										<div class="square-content" v-bind:style="{ 'background-image': 'url(' + posts[10].thumb + ')' }">
										</div>
									</div>
								</a>
							</div>				
						</div>
						<div class="col-12 col-md-6 row p-0 m-0">
							<div v-for="(post, index) in posts.slice(11,15)" class="col-6" style="margin-bottom:1.8rem;">
								<a class="card info-overlay card-md-border-0" :href="post.url">
									<div class="square">
										<span v-if="post.type == 'photo:album'" class="float-right mr-3 post-icon"><i class="fas fa-images fa-2x"></i></span>
										<span v-if="post.type == 'video'" class="float-right mr-3 post-icon"><i class="fas fa-video fa-2x"></i></span>
										<span v-if="post.type == 'video:album'" class="float-right mr-3 post-icon"><i class="fas fa-film fa-2x"></i></span>
										<div class="square-content" v-bind:style="{ 'background-image': 'url(' + post.thumb + ')' }">
										</div>
									</div>
								</a>
							</div>
						</div> -->
						<div class="col-12 mb-3 d-flex justify-content-between align-items-center">
							<p class="d-block d-md-none h1 font-weight-bold mb-0">For You</p>
							<p class="d-none d-md-block display-4 font-weight-bold mb-0">For You</p>
						</div>
					</div>
					<div class="row p-0" style="display: flex;">
						<div v-for="(post, index) in posts" class="col-4 p-1 p-sm-2 p-md-3 pt-0">
							<a class="card info-overlay card-md-border-0" :href="post.url">
								<div class="square">
									<span v-if="post.type == 'photo:album'" class="float-right mr-3 post-icon"><i class="fas fa-images fa-2x"></i></span>
									<span v-if="post.type == 'video'" class="float-right mr-3 post-icon"><i class="fas fa-video fa-2x"></i></span>
									<span v-if="post.type == 'video:album'" class="float-right mr-3 post-icon"><i class="fas fa-film fa-2x"></i></span>
									<div class="square-content" v-bind:style="{ 'background-image': 'url(' + post.thumb + ')' }">
									</div>
								</div>
							</a>
						</div>
					</div>
				</div>
			</section>
			<!-- <section class="pt-5 mb-5 section-explore">
				<div class="profile-timeline pt-3">
					<div class="row p-0 mt-5">
						<div class="col-12 mb-3 d-flex justify-content-between align-items-center">
							<p class="display-4 font-weight-bold mb-0">Recommended</p>
							<!-- <div>
								<div class="btn-group">
									<button class="btn btn-dark py-1 font-weight-bold px-3 text-uppercase btn-sm">Today</button>
									<button class="btn btn-outline-secondary py-1 font-weight-bold px-3 text-uppercase btn-sm">Weekly</button>
									<button class="btn btn-outline-secondary py-1 font-weight-bold px-3 text-uppercase btn-sm">Monthly</button>
								</div>
							</div> - - ->
						</div>
						<div class="col-12 col-md-6">
							<div class="mb-4">
								<a class="card info-overlay card-md-border-0" :href="posts[20].url">
									<div class="square">
										<span v-if="posts[20].type == 'photo:album'" class="float-right mr-3 post-icon"><i class="fas fa-images fa-2x"></i></span>
										<span v-if="posts[20].type == 'video'" class="float-right mr-3 post-icon"><i class="fas fa-video fa-2x"></i></span>
										<span v-if="posts[20].type == 'video:album'" class="float-right mr-3 post-icon"><i class="fas fa-film fa-2x"></i></span>
										<div class="square-content" v-bind:style="{ 'background-image': 'url(' + posts[20].thumb + ')' }">
										</div>
									</div>
								</a>
							</div>				
						</div>
						<div class="col-12 col-md-6 row p-0 m-0">
							<div v-for="(post, index) in posts.slice(21,25)" class="col-6" style="margin-bottom:1.8rem;">
								<a class="card info-overlay card-md-border-0" :href="post.url">
									<div class="square">
										<span v-if="post.type == 'photo:album'" class="float-right mr-3 post-icon"><i class="fas fa-images fa-2x"></i></span>
										<span v-if="post.type == 'video'" class="float-right mr-3 post-icon"><i class="fas fa-video fa-2x"></i></span>
										<span v-if="post.type == 'video:album'" class="float-right mr-3 post-icon"><i class="fas fa-film fa-2x"></i></span>
										<div class="square-content" v-bind:style="{ 'background-image': 'url(' + post.thumb + ')' }">
										</div>
									</div>
								</a>
							</div>
						</div>
					</div>
					<div class="row p-0" style="display: flex;">
						<div v-for="(post, index) in posts.slice(25, 29)" class="col-3 p-1 p-sm-2 p-md-3 pt-0">
							<a class="card info-overlay card-md-border-0" :href="post.url">
								<div class="square">
									<span v-if="post.type == 'photo:album'" class="float-right mr-3 post-icon"><i class="fas fa-images fa-2x"></i></span>
									<span v-if="post.type == 'video'" class="float-right mr-3 post-icon"><i class="fas fa-video fa-2x"></i></span>
									<span v-if="post.type == 'video:album'" class="float-right mr-3 post-icon"><i class="fas fa-film fa-2x"></i></span>
									<div class="square-content" v-bind:style="{ 'background-image': 'url(' + post.thumb + ')' }">
									</div>
								</div>
							</a>
						</div>
					</div>
				</div>
			</section> -->
		</div>
	</div>
</template>

<style type="text/css" scoped>
.discover-bar::-webkit-scrollbar { 
	display: none; 
}
.card-disc {
	flex: 0 0 160px;
	width:160px;
	height:100px;
	background-size: cover !important;
}
.post-icon {
	color: #fff;
	position:relative;
	margin-top: 10px;
	z-index: 9;
	opacity: 0.6;
	text-shadow: 3px 3px 16px #272634;
}
</style>

<script type="text/javascript">
	export default {
		data() {
			return {
				loaded: false,
				config: window.App.config,
				posts: {},
				hashtags: {},
				places: {},
				trending: {},
				trendingDaily: {},
				trendingMonthly: {},
				categories: {},
				allCategories: {},
				searchTerm: '',
				trendingRange: 'daily'
			}
		},
		mounted() {
			this.fetchData();
			this.fetchCategories();
			this.loadTrending();
			this.loadTrendingHashtags();
			this.loadTrendingPlaces();
		},

		methods: {
			fetchData() {
				axios.get('/api/pixelfed/v2/discover/posts')
				.then((res) => {
					this.posts = res.data.posts;
					this.loaded = true;
				});
			},

			fetchCategories() {
				axios.get('/api/v2/discover/categories')
				.then(res => {
					this.allCategories = res.data;
					this.categories = res.data;
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
					return;
				}
				if(this.trendingRange == 'monthly' && this.trendingMonthly.length) {
					this.trending = this.trendingMonthly;
					return;
				}
				axios.get('/api/pixelfed/v2/discover/posts/trending', {
					params: {
						range: this.trendingRange
					}
				})
				.then(res => {
					if(this.trendingRange == 'daily') {
						this.trendingDaily = res.data.filter(t => t.sensitive == false);
					}
					if(this.trendingRange == 'monthly') {
						this.trendingMonthly = res.data.filter(t => t.sensitive == false);
					}
					this.trending = res.data;
				});
			},

			trendingRangeToggle(r) {
				this.trendingRange = r;
				this.loadTrending();
			},

			loadTrendingHashtags() {
				axios.get('/api/pixelfed/v2/discover/posts/hashtags')
				.then(res => {
					this.hashtags = res.data;
				});
			},

			loadTrendingPlaces() {
				axios.get('/api/pixelfed/v2/discover/posts/places')
				.then(res => {
					this.places = res.data;
				});
			}
		}
	}
</script>