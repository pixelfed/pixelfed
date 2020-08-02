<template>
	<div>
		<div v-if="!loaded" style="height: 70vh;" class="d-flex justify-content-center align-items-center">
			<img src="/img/pixelfed-icon-grey.svg">
		</div>
		<div v-else>
			<div class="d-block d-md-none px-0 border-top-0 mx-n3">
				<input class="form-control rounded-0" placeholder="Search" v-model="searchTerm" v-on:keyup.enter="searchSubmit">
			</div>
			<div>
				<p class="display-4 font-weight-bold pt-5 text-lighter"><i class="far fa-compass"></i> DISCOVER</p>
			</div>
			<section class="d-none d-md-flex mb-md-2 pt-5 discover-bar" style="width:auto; overflow: auto hidden;" v-if="categories.length > 0">
				<!--<a v-if="config.ab.loops == true" class="text-decoration-none bg-transparent border border-success rounded d-inline-flex align-items-center justify-content-center mr-3 card-disc" href="/discover/loops">
					<p class="text-success lead font-weight-bold mb-0">Loops</p>
				</a>-->
				<a v-for="(category, index) in categories" :key="index+'_cat_'" class="bg-dark rounded d-inline-flex align-items-end justify-content-center mr-3 box-shadow card-disc text-decoration-none" :href="category.url" :style="'background: linear-gradient(rgba(0, 0, 0, 0.3),rgba(0, 0, 0, 0.3)),url('+category.thumb+');'">
					<p class="text-white font-weight-bold" style="text-shadow: 3px 3px 16px #272634;">{{category.name}}</p>
				</a>

			</section>
			<section class="mb-5 section-explore">
				<div class="profile-timeline">
					<div class="row p-0 mt-5">
						<div class="col-12 col-md-6">
							<div class="mb-4">
								<a class="card info-overlay card-md-border-0" :href="posts[0].url">
									<div class="square">
										<span v-if="posts[0].type == 'photo:album'" class="float-right mr-3 post-icon"><i class="fas fa-images fa-2x"></i></span>
										<span v-if="posts[0].type == 'video'" class="float-right mr-3 post-icon"><i class="fas fa-video fa-2x"></i></span>
										<span v-if="posts[0].type == 'video:album'" class="float-right mr-3 post-icon"><i class="fas fa-film fa-2x"></i></span>
										<div class="square-content" v-bind:style="{ 'background-image': 'url(' + posts[0].thumb + ')' }">
										</div>
									</div>
								</a>
							</div>				
						</div>
						<div class="col-12 col-md-6 row p-0 m-0">
							<div v-for="(post, index) in posts.slice(1,5)" class="col-6" style="margin-bottom:1.8rem;">
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
						<div v-for="(post, index) in posts.slice(5)" class="col-3 p-1 p-sm-2 p-md-3">
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
			<section class="mb-5">
				<p class="lead text-center">To view more posts, check the <a href="/" class="font-weight-bold">home</a> or <a href="/timeline/public" class="font-weight-bold">local</a> timelines.</p>
			</section>
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
				trending: {},
				categories: {},
				allCategories: {},
				searchTerm: '',
			}
		},
		mounted() {
			this.fetchData();
			this.fetchCategories();
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
			}
		}
	}
</script>