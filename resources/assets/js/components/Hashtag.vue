<template>
<div>
	<div v-if="loaded" class="container">
		<div class="profile-header row my-5">
			<div class="col-12 col-md-3">
				<div class="profile-avatar">
					<div class="bg-primary mb-3 d-flex align-items-center justify-content-center display-4 font-weight-bold text-white" style="width: 172px; height: 172px; border-radius: 100%">#</div>
				</div>
			</div>
			<div class="col-12 col-md-9 d-flex align-items-center">
				<div class="profile-details">
					<div class="username-bar pb-2">
						<p class="tag-header mb-0">#{{hashtag}}</p>
						<p class="lead"><span class="font-weight-bold">{{tags.length ? hashtagCount : '0'}}</span> posts</p>
						<div class="d-flex justify-content-between align-items-center">
							<p v-if="authenticated && tags.length" class="pt-3 mr-4">
								<button v-if="!following" type="button" class="btn btn-primary font-weight-bold py-1 px-5" @click="followHashtag">
									Follow
								</button>
								<button v-else type="button" class="btn btn-outline-secondary font-weight-bold py-1 px-5" @click="unfollowHashtag">
									Unfollow
								</button>
							</p>
							<div class="custom-control custom-switch">
								<input type="checkbox" class="custom-control-input" id="nsfwSwitch" v-model="forceNsfw">
								<label class="custom-control-label font-weight-bold text-muted" for="nsfwSwitch">Show NSFW Content</label>
							</div>
						</div>
					</div>
				</div>
			</div>
		</div>
		<div v-if="tags.length" class="tag-timeline">
			<p v-if="top.length" class="font-weight-bold text-muted mb-0">Top Posts</p>
			<div class="row pb-5">
				<div v-for="(tag, index) in top" class="col-3 p-0 p-sm-2 p-md-3 hashtag-post-square">
					<a class="card info-overlay card-md-border-0" :href="tag.status.url">
						<div :class="[tag.status.filter ? 'square ' + tag.status.filter : 'square']">
							<div v-if="tag.status.sensitive && forceNsfw == false" class="square-content">
								<blur-hash-image
									v-if="s.sensitive"
									width="32"
									height="32"
									punch="1"
									:hash="tag.status.media_attachments[0].blurhash"
									/>
							</div>
							<div v-else class="square-content" :style="'background-image: url('+tag.status.media_attachments[0].preview_url+')'"></div>
							<div class="info-overlay-text">
								<h5 class="text-white m-auto font-weight-bold">
									<span>
										<span class="fas fa-retweet fa-lg pr-1"></span> {{tag.status.share_count}}
									</span>
								</h5>
							</div>
						</div>
					</a>
				</div>
			</div>
			<p class="font-weight-bold text-muted mb-0">Most Recent</p>
			<div class="row">
				<div v-for="(tag, index) in tags" class="col-3 p-1 hashtag-post-square">
					<a class="card info-overlay card-md-border-0" :href="tag.status.url">
						<div :class="[tag.status.filter ? 'square ' + tag.status.filter : 'square']">
							<div v-if="tag.status.sensitive && forceNsfw == false" class="square-content">
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
									:hash="tag.status.media_attachments[0].blurhash"
									/>
							</div>
							<div v-else class="square-content">
								<blur-hash-image
									width="32"
									height="32"
									:hash="tag.status.media_attachments[0].blurhash"
									:src="tag.status.media_attachments[0].preview_url"
									/>
							</div>
							<span v-if="tag.status.pf_type == 'photo:album'" class="float-right mr-3 post-icon"><i class="fas fa-images fa-2x"></i></span>
							<span v-if="tag.status.pf_type == 'video'" class="float-right mr-3 post-icon"><i class="fas fa-video fa-2x"></i></span>
							<span v-if="tag.status.pf_type == 'video:album'" class="float-right mr-3 post-icon"><i class="fas fa-film fa-2x"></i></span>
							<div class="info-overlay-text">
								<h5 class="text-white m-auto font-weight-bold">
									<span>
										<span class="far fa-comment fa-lg pr-1"></span> {{tag.status.reply_count}}
									</span>
								</h5>
							</div>
						</div>
					</a>
				</div>
				<div v-if="tags.length && loaded" class="col-12 text-center mt-4">
					<infinite-loading @infinite="infiniteLoader">
						<div slot="no-results" class="font-weight-bold"></div>
						<div slot="no-more" class="font-weight-bold"></div>
					</infinite-loading>
				</div>
			</div>
		</div>
		<div v-else>
			<p class="text-center lead font-weight-bold">No public posts found.</p>
		</div>
	</div>
	<div v-else class="mt-5 text-center">
		<div class="spinner-border" role="status">
			<span class="sr-only">Loading...</span>
		</div>
	</div>
</div>
</template>

<style type="text/css" scoped>
.tag-header {
	font-size: 28px;
	font-weight: 300;
}
</style>

<script type="text/javascript">
	export default {
		props: [
		'hashtag',
		'hashtagCount'
		],
		data() {
			return {
				loaded: false,
				page: 1,
				authenticated: false,
				following: false,
				tags: [],
				top: [],
				forceNsfw: false,
			}
		},
		beforeMount() {
			this.authenticated = $('body').hasClass('loggedIn');
			this.getResults();
			this.hashtagCount = window.App.util.format.count(this.hashtagCount);
		},
		methods: {
			getResults() {
				if(this.authenticated) {
					axios.get('/api/pixelfed/v1/accounts/verify_credentials').then(res => {
						window._sharedData.curUser = res.data;
						window.App.util.navatar();
					});
				}
				axios.get('/api/v2/discover/tag', {
					params: {
						hashtag: this.hashtag,
						page: this.page
					}
				}).then(res => {
					let data = res.data;
					let tags = data.tags.filter(n => {
						if(!n || n.length == 0 || n.status == null) {
							return false;
						}
						return true;
					});
					this.tags = tags;
					//this.top = tags.slice(6, 9);
					this.loaded = true;
					this.following = data.follows;
					this.page++;
				});
			},

			infiniteLoader($state) {
				if(this.page > (this.authenticated ? 29 : 10)) {
					$state.complete();
					return;
				}
				axios.get('/api/v2/discover/tag', {
					params: {
						hashtag: this.hashtag,
						page: this.page,
					}
				}).then(res => {
					let data = res.data;
					if(data.tags.length) {
						let tags = data.tags.filter(n => {
							if(!n || n.length == 0 || n.status == null) {
								return false;
							}
							return true;
						});
						this.tags.push(...tags);
						if(tags.length > 9) {
							$state.complete();
							return;
						}
						this.page++;
						$state.loaded();
					} else {
						$state.complete();
					}
				});
			},

			followHashtag() {
				axios.post('/api/local/discover/tag/subscribe', {
					name: this.hashtag
				}).then(res => {
					this.following = true;
				});
			},

			unfollowHashtag() {
				axios.post('/api/local/discover/tag/subscribe', {
					name: this.hashtag
				}).then(res => {
					this.following = false;
				});
			},

		}
	}
</script>
