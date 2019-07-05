<template>
<div>
	<div class="container">
		<div class="profile-header row my-5">
			<div class="col-12 col-md-3">
				<div class="profile-avatar">
					<div class="bg-pixelfed mb-3 d-flex align-items-center justify-content-center display-4 font-weight-bold text-white" style="width: 172px; height: 172px; border-radius: 100%">#</div>
				</div>
			</div>
			<div class="col-12 col-md-9 d-flex align-items-center">
				<div class="profile-details">
					<div class="username-bar pb-2">
						<p class="h3">#{{hashtag}}</p>
						<p class="lead"><span class="font-weight-bold">{{hashtagCount}}</span> posts</p>
					</div>
				</div>
			</div>
		</div>
		<div class="tag-timeline">
			<div class="row">
				<div v-for="(tag, index) in tags" class="col-4 p-0 p-sm-2 p-md-3 hashtag-post-square">
					<a class="card info-overlay card-md-border-0" :href="tag.status.url">
						<div :class="[tag.status.filter ? 'square ' + tag.status.filter : 'square']">
							<div class="square-content" :style="'background-image: url('+tag.status.thumb+')'"></div>
							<div class="info-overlay-text">
								<h5 class="text-white m-auto font-weight-bold">
									<span class="pr-4">
										<span class="far fa-heart fa-lg pr-1"></span> {{tag.status.like_count}}
									</span>
									<span>
										<span class="fas fa-retweet fa-lg pr-1"></span> {{tag.status.share_count}}
									</span>
								</h5>
							</div>
						</div>
					</a>
				</div>
				<div v-if="tags.length" class="card card-body text-center shadow-none bg-transparent border-0">
					<infinite-loading @infinite="infiniteLoader">
						<div slot="no-results" class="font-weight-bold"></div>
						<div slot="no-more" class="font-weight-bold"></div>
					</infinite-loading>
				</div>
			</div>
		</div>
	</div>
</div>
</template>

<script type="text/javascript">
	export default {
		props: [
		'hashtag',
		'hashtagCount'
		],
		data() {
			return {
				page: 2,
				tags: []
			}
		},
		beforeMount() {
			this.getResults();
		},
		methods: {
			getResults() {
				axios.get('/api/v2/discover/tag', {
					params: {
						hashtag: this.hashtag
					}
				}).then(res => {
					this.tags = res.data.filter(n => {
						if(!n || n.length == 0) {
							return false;
						}
						return true;
					});
				});
			},

			infiniteLoader($state) {
				if(this.page > 19) {
					$state.complete();
					return;
				}
				axios.get('/api/v2/discover/tag', {
					params: {
						hashtag: this.hashtag,
						page: this.page,
					}
				}).then(res => {
					if(res.data.length) {
						let data = res.data.filter(n => {
							if(!n || n.length == 0) {
								return false;
							}
							return true;
						});
						this.tags.push(...data);
						if(data.length > 9) {
							$state.complete();
							return;
						}
						this.page++;
						$state.loaded();
					} else {
						$state.complete();
					}
				});
			}
		}
	}
</script>