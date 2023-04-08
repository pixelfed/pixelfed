<template>
	<div class="landing-explore-component">
		<section class="page-wrapper">
			<div class="container container-compact">
				<div class="card bg-bluegray-900" style="border-radius: 10px;">
					<div class="card-header bg-bluegray-800 nav-menu" style="border-top-left-radius: 10px; border-top-right-radius: 10px;">
						<ul class="nav justify-content-around">
						  <li class="nav-item">
							<router-link to="/" class="nav-link">About</router-link>
							</li>
							<li v-if="config.show_directory" class="nav-item">
								<router-link to="/web/directory" class="nav-link">Directory</router-link>
							</li>
							<li v-if="config.show_explore_feed" class="nav-item">
								<router-link to="/web/explore" class="nav-link">Explore</router-link>
							</li>
						</ul>
					</div>

					<div class="card-body">
						<div class="py-3">
							<p class="lead text-center">Explore trending posts</p>
						</div>

						<div v-if="loading" class="d-flex justify-content-center align-items-center" style="min-height: 500px;">
							<b-spinner />
						</div>

						<div v-else class="feed-list">
							<post-card
								v-for="post in feed"
								:key="post.id"
								:post="post"
								:range="ranges[rangeIndex]" />
						</div>
					</div>
				</div>
			</div>

			<footer-component />
		</section>
	</div>
</template>

<script type="text/javascript">
	import PostCard from './partials/PostCard';

	export default {
		components: {
			"post-card": PostCard
		},

		data() {
			return {
				loading: true,
				config: window.pfl,
				isFetching: false,
				range: 'daily',
				ranges: ['daily', 'monthly', 'yearly'],
				rangeIndex: 0,
				feed: [],
			}
		},

		beforeMount() {
			if(this.config.show_explore_feed == false) {
				this.$router.push('/');
			}
		},

		mounted() {
			this.init();
		},

		methods: {
			init() {
				axios.get('/api/pixelfed/v2/discover/posts/trending?range=daily')
				.then(res => {
					if(res && res.data.length > 3) {
						this.feed = res.data;
						this.loading = false;
					} else {
						this.rangeIndex++;
						this.fetchTrending();
					}
				})
			},

			fetchTrending() {
				if(this.isFetching || this.rangeIndex >= 3) {
					return;
				}
				this.isFetching = true;

				axios.get('/api/pixelfed/v2/discover/posts/trending', {
					params: {
						range: this.ranges[this.rangeIndex]
					}
				})
				.then(res => {
					if(res && res.data.length) {
						if(this.rangeIndex == 2 && res.data.length > 3) {
							this.feed = res.data;
							this.loading = false;
						} else {
							this.rangeIndex++;
							this.isFetching = false;
							this.fetchTrending();
						}
					} else {
						this.rangeIndex++;
						this.isFetching = false;
						this.fetchTrending();
					}
				})
			}
		}
	}
</script>
