<template>
	<div class="discover-daily-trending">
		<div class="card bg-stellar">
			<div class="card-body m-5">
				<div class="row d-flex align-items-center">
					<div class="col-12 col-md-5">
						<p class="font-default text-light mb-0">Popular and trending posts</p>
						<h1 class="display-4 font-default text-white" style="font-weight: 700;">Daily Trending</h1>
						<button class="btn btn-outline-light rounded-pill" @click="viewMore()">View more trending posts</button>
					</div>
					<div class="col-12 col-md-7">
						<div v-if="isLoaded" class="row">
							<div v-for="(post, index) in trending" class="col-4">
								<a :href="post.url" @click.prevent="gotoPost(post.id)">
									<img :src="post.media_attachments[0].url" class="shadow m-1" width="170" height="170" style="object-fit: cover;border-radius:8px">
								</a>
							</div>
						</div>
						<div v-else class="row">
							<div class="col-12 d-flex justify-content-center">
								<b-spinner type="grow" variant="light" />
							</div>
						</div>
					</div>
				</div>
			</div>
		</div>
	</div>
</template>

<script type="text/javascript">
	export default {
		data() {
			return {
				isLoaded: false,
				initialFetch: false,
				trending: []
			}
		},

		mounted() {
			if(!this.initialFetch) {
				this.fetchTrending();
			}
		},
		methods: {
			fetchTrending() {
				axios.get('/api/pixelfed/v2/discover/posts/trending', {
					params: {
						range: 'daily'
					}
				})
				.then(res => {
					this.trending = res.data.filter(p => p.pf_type === 'photo').slice(0, 9);
					this.isLoaded = true;
					this.initialFetch = true;
				});
			},

			gotoPost(id) {
				this.$router.push('/i/web/post/' + id);
			},

			viewMore() {
				this.$emit('btn-click', 'trending');
			}
		}
	}
</script>

<style lang="scss">
	.discover-daily-trending {
		.bg-stellar {
			background: #7474BF;
			background: -webkit-linear-gradient(to right, #348AC7, #7474BF);
			background: linear-gradient(to right, #348AC7, #7474BF);
		}
		.font-default {
			font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
			letter-spacing: -0.7px;
		}
	}
</style>
