<template>
<div>
	<div class="col-12">
		<p class="font-weight-bold text-lighter text-uppercase">Perfis</p>
		<div v-if="loaded" class="">
			<div class="row">
				<div class="col-12 col-md-6 p-1" v-for="(profile, index) in profiles">
					<div class="card card-body border shadow-none py-2">
						<div class="media">
							<a :href="profile.url"><img :src="profile.avatar" class="rounded-circle border mr-3" alt="..." width="40px" height="40px"></a>
							<div class="media-body">
								<p class="mt-0 mb-0 font-weight-bold">
									<a :href="profile.url" class="text-dark">{{profile.username}}</a>
								</p>
								<p class="mb-1 small text-lighter d-flex justify-content-between font-weight-bold">
									<span>
										<span>{{prettyCount(profile.statuses_count)}}</span> POSTS
									</span>
									<span>
										<span>{{prettyCount(profile.followers_count)}}</span> SEGUIDORES
									</span>
								</p>
								<p class="mb-1">
									<span v-for="(post, i) in profile.posts" class="shadow-sm" :key="'profile_posts_'+i">
										<a :href="post.url" class="text-decoration-none mr-1">
											<img :src="thumbUrl(post)" width="62.3px" height="62.3px" class="border rounded">
										</a>
									</span>
								</p>

							</div>
						</div>
					</div>
				</div>

				<div v-if="showLoadMore" class="col-12">
					<p class="text-center mb-0 pt-3">
						<button class="btn btn-outline-secondary btn-sm px-4 py-1 font-weight-bold" @click="loadMore()">Mais</button>
					</p>
				</div>
			</div>

		</div>
		<div v-else>
			<div class="row">
				<div class="col-12 d-flex justify-content-center align-items-center">
					<div class="spinner-border" role="status">
						<span class="sr-only">Carregando...</span>
					</div>
				</div>
			</div>
		</div>
	</div>
</div>
</template>

<style type="text/css" scoped></style>

<script type="text/javascript">
	export default {
		data() {
			return {
				loaded: false,
				showLoadMore: true,
				profiles: [],
				page: 1
			}
		},

		beforeMount() {
			this.fetchData();
		},

		methods: {
			fetchData() {
				axios.get('/api/pixelfed/v2/discover/profiles', {
					params: {
						page: this.page
					}
				})
				.then(res => {
					if(res.data.length == 0) {
						this.showLoadMore = false;
						this.loaded = true;
						return;
					}
					this.profiles = res.data;
					this.showLoadMore = this.profiles.length == 8;
					this.loaded = true;
				});
			},

			prettyCount(val) {
				return App.util.format.count(val);
			},

			loadMore() {
				this.loaded = false;
				this.page++;
				this.fetchData();
			},

			thumbUrl(p) {
				return p.media_attachments[0].url;
			}
		}
	}
</script>
