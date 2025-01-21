<template>
	<div class="group-topic-feed-component">
		<div v-if="isLoaded" class="bg-white py-5 border-bottom">
			<div class="container">
				<div class="d-flex justify-content-between align-items-center">
					<div>
						<h3 class="font-weight-bold mb-1">#{{ name }}</h3>
						<p class="mb-0 lead text-muted">
							<span>
								Posts in <a :href="group.url" class="text-muted font-weight-bold">{{ group.name }}</a>
							</span>
							<span>·</span>
							<span><i class="fas fa-globe"></i></span>
							<span>·</span>
							<span>{{ group.membership != 'all' ? 'Private' : 'Public'}} Group</span>
						</p>
					</div>
					<!-- <div>
						<button class="btn btn-light border btn-lg text-muted">
							<i class="fas fa-ellipsis-h"></i>
						</button>
					</div> -->
				</div>
			</div>
		</div>
		<div v-if="isLoaded" class="row justify-content-center mt-3">
			<div v-if="feed.length" class="col-12 col-md-5">
				<group-status
					v-for="(status, index) in feed"
					:key="'gs:' + status.id + index"
					:prestatus="status"
					:profile="profile"
					:group="group"
					:show-group-chevron="true"
					:group-id="gid" />

				<div v-if="feed.length > 2">
					<infinite-loading @infinite="infiniteFeed">
						<div slot="no-more"></div>
						<div slot="no-results"></div>
					</infinite-loading>
				</div>
			</div>
			<div v-else class="col-12 col-md-5 d-flex justify-content-center">
				<div class="mt-5">
					<p class="text-lighter text-center">
						<i class="fal fa-exclamation-circle fa-4x"></i>
					</p>

					<p class="font-weight-bold text-muted">Cannot load any posts containg the <span class="font-weight-normal">#{{ name }}</span> hashtag</p>

					<p class="text-left">
						This can happen for a few reasons:
					</p>

					<ul class="text-left">
						<li>There is a typo in the url</li>
						<li>No posts exist that contain this hashtag</li>
						<li>This hashtag has been banned by group admins</li>
						<li>The hashtag is new or used infrequently</li>
						<li>A technical issue has occured</li>
					</ul>
				</div>
			</div>
		</div>
	</div>
</template>

<script type="text/javascript">
	import GroupStatus from '@/groups/partials/GroupStatus.vue';

	export default {
		props: {
			gid: {
				type: String
			},

			name: {
				type: String
			}
		},

		components: {
			GroupStatus
		},

		data() {
			return {
				isLoaded: false,
				group: false,
				profile: false,
				feed: [],
				page: 1,
				ids: []
			}
		},

		mounted() {
			this.fetchProfile();
		},

		methods: {
			fetchProfile() {
				axios.get('/api/pixelfed/v1/accounts/verify_credentials')
				.then(res => {
					this.profile = res.data;
					this.fetchGroup();
				});
			},

			fetchGroup() {
				axios.get('/api/v0/groups/' + this.gid)
				.then(res => {
					this.group = res.data;
					this.fetchFeed();
				});
			},

			fetchFeed() {
				axios.get('/api/v0/groups/topics/tag', {
					params: {
						gid: this.gid,
						name: this.name
					}
				}).then(res => {
					this.feed = res.data;
					this.isLoaded = true;
					let self = this;
					res.data.forEach(d => {
						if(self.ids.indexOf(d.id) == -1) {
							self.ids.push(d.id);
						}
					});
					this.page++;
				})
			},

			infiniteFeed($state) {
				if(this.feed.length < 2) {
					$state.complete();
					return;
				}

				axios.get('/api/v0/groups/topics/tag', {
					params: {
						gid: this.gid,
						name: this.name,
						limit: 1,
						page: this.page
					},
				}).then(res => {
					if (res.data.length) {
						let data = res.data;
						let self = this;
						data.forEach(d => {
							if(self.ids.indexOf(d.id) == -1) {
								self.ids.push(d.id);
								self.feed.push(d);
							}
						});
						$state.loaded();
						this.page++;
					} else {
						$state.complete();
					}
				});
			}
		}
	}
</script>
