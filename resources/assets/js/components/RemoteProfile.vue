<template>
<div>
	<div v-if="relationship && relationship.blocking && warning" class="bg-white pt-3 border-bottom">
		<div class="container">
			<p class="text-center font-weight-bold">You are blocking this account</p>
			<p class="text-center font-weight-bold">Click <a href="#" class="cursor-pointer" @click.prevent="warning = false;">here</a> to view profile</p>
		</div>
	</div>
	<div v-if="loading" style="height: 80vh;" class="d-flex justify-content-center align-items-center">
		<img src="/img/pixelfed-icon-grey.svg" class="">
	</div>
	<div v-if="!loading && !warning" class="container">
		<div class="row">
			<div class="col-12 col-md-4 pt-5">
				<div class="card shadow-none border">
					<div class="card-header p-0 m-0">
						<img v-if="profile.header_bg" :src="profile.header_bg" style="width: 100%; height: 140px; object-fit: cover;">
						<div v-else class="bg-primary" style="width: 100%;height: 140px;"></div>
					</div>
					<div class="card-body pb-0">
						<div class="mt-n5 mb-3">
							<img class="rounded-circle p-1 border mt-n4 bg-white shadow" :src="profile.avatar" width="90px" height="90px;">
							<span class="float-right mt-n1">
								<span>
									<button v-if="relationship && relationship.following == false" class="btn btn-outline-light py-0 px-3 mt-n1" style="font-size:13px; font-weight: 500;" @click="followProfile();">Follow</button>
									<button v-if="relationship && relationship.following == true" class="btn btn-outline-light py-0 px-3 mt-n1" style="font-size:13px; font-weight: 500;" @click="unfollowProfile();">Unfollow</button>
								</span>
								<span class="mx-2">
									<a :href="'/account/direct/t/' + profile.id" class="btn btn-outline-light btn-sm mt-n1" style="padding-top:2px;padding-bottom:1px;">
										<i class="far fa-comment-dots cursor-pointer" style="font-size:13px;"></i>
									</a>
								</span>
								<span>
									<button class="btn btn-outline-light btn-sm mt-n1" @click="showCtxMenu()" style="padding-top:2px;padding-bottom:1px;">
										<i class="fas fa-cog cursor-pointer" style="font-size:13px;"></i>
									</button>
								</span>
							</span>
						</div>
						<p class="pl-2 h4 font-weight-bold mb-1">{{profile.display_name}}</p>
						<p class="pl-2 font-weight-bold mb-2"><a class="text-muted" :href="profile.url" @click.prevent="urlRedirectHandler(profile.url)">{{profile.acct}}</a></p>
						<p class="pl-2 text-muted small d-flex justify-content-between">
							<span>
								<span class="font-weight-bold text-dark">{{profile.statuses_count}}</span>
								<span>Posts</span>
							</span>
							<span>
								<span class="font-weight-bold text-dark">{{profile.following_count}}</span>
								<span>Following</span>
							</span>
							<span>
								<span class="font-weight-bold text-dark">{{profile.followers_count}}</span>
								<span>Followers</span>
							</span>
						</p>
						<p class="pl-2 text-muted small pt-2" v-html="profile.note"></p>
					</div>
				</div>
				<p class="small text-lighter p-2">Last updated: <time :datetime="profile.last_fetched_at">{{timeAgo(profile.last_fetched_at, 'ago')}}</time></p>
			</div>
			<div class="col-12 col-md-8 pt-5">
				<div class="row">
					<div class="col-12" v-for="(status, index) in feed" :key="'remprop' + index">
						<status-card
							:class="{'border-top': index === 0}"
							:status="status" />
					</div>

					<div v-if="feed.length == 0" class="col-12 mb-2">
						<div class="d-flex justify-content-center align-items-center bg-white border rounded" style="height:60vh;">
							<div class="text-center">
								<p class="lead">We haven't seen any posts from this account.</p>
							</div>
						</div>
					</div>

					<div v-else class="col-12 mt-4">
						<p v-if="showLoadMore" class="text-center mb-0 px-0">
							<button @click="loadMorePosts()" class="btn btn-outline-primary btn-block font-weight-bold">
								<span v-if="!loadingMore">Load More</span>
								<span v-else>
									<div class="spinner-border spinner-border-sm" role="status">
										<span class="sr-only">Loading...</span>
									</div>
								</span>
							</button>
						</p>
					</div>
				</div>
			</div>
		</div>
		<b-modal ref="visitorContextMenu"
			id="visitor-context-menu"
			hide-footer
			hide-header
			centered
			size="sm"
			body-class="list-group-flush p-0">
			<div class="list-group" v-if="relationship">
				<div class="list-group-item cursor-pointer text-center rounded text-dark" @click="copyProfileLink">
					Copy Link
				</div>
				<div v-if="user && !owner && !relationship.muting" class="list-group-item cursor-pointer text-center rounded" @click="muteProfile">
					Mute
				</div>
				<div v-if="user && !owner && relationship.muting" class="list-group-item cursor-pointer text-center rounded" @click="unmuteProfile">
					Unmute
				</div>
				<div v-if="user && !owner" class="list-group-item cursor-pointer text-center rounded text-dark" @click="reportProfile">
					Report User
				</div>
				<div v-if="user && !owner && !relationship.blocking" class="list-group-item cursor-pointer text-center rounded text-dark" @click="blockProfile">
					Block
				</div>
				<div v-if="user && !owner && relationship.blocking" class="list-group-item cursor-pointer text-center rounded text-dark" @click="unblockProfile">
					Unblock
				</div>
				
				<div class="list-group-item cursor-pointer text-center rounded text-muted" @click="$refs.visitorContextMenu.hide()">
					Close
				</div>
			</div>
		</b-modal>
		<b-modal ref="ctxModal"
			id="ctx-modal"
			hide-header
			hide-footer
			centered
			rounded
			size="sm"
			body-class="list-group-flush p-0 rounded">
			<div class="list-group text-center">
				<div v-if="ctxMenuStatus && profile.id != profile.id" class="list-group-item rounded cursor-pointer font-weight-bold text-danger" @click="ctxMenuReportPost()">Report inappropriate</div>
				<div v-if="ctxMenuStatus && profile.id != profile.id && ctxMenuRelationship && ctxMenuRelationship.following" class="list-group-item rounded cursor-pointer font-weight-bold text-danger" @click="ctxMenuUnfollow()">Unfollow</div>
				<div v-if="ctxMenuStatus && profile.id != profile.id && ctxMenuRelationship && !ctxMenuRelationship.following" class="list-group-item rounded cursor-pointer font-weight-bold text-primary" @click="ctxMenuFollow()">Follow</div>
				<div class="list-group-item rounded cursor-pointer" @click="ctxMenuGoToPost()">Go to post</div>
				<div class="list-group-item rounded cursor-pointer" @click="ctxMenuCopyLink()">Copy Link</div>
				<div v-if="profile && profile.is_admin == true" class="list-group-item rounded cursor-pointer" @click="ctxModMenuShow()">Moderation Tools</div>
				<div v-if="ctxMenuStatus && (profile.is_admin || profile.id == profile.id)" class="list-group-item rounded cursor-pointer" @click="deletePost(ctxMenuStatus)">Delete</div>
				<div class="list-group-item rounded cursor-pointer text-lighter" @click="closeCtxMenu()">Cancel</div>
			</div>
		</b-modal>
	</div>
</div>
</template>

<script type="text/javascript">
	import StatusCard from './partials/StatusCard.vue';

	export default {
		props: [
			'profile-id',
		],

		components: {
			StatusCard
		},

		data() {
			return {
				id: [],
				ids: [],
				user: false,
				profile: {},
				feed: [],
				min_id: null,
				max_id: null,
				loading: true,
				owner: false,
				layoutType: true,
				relationship: null,
				warning: false,
				ctxMenuStatus: false,
				ctxMenuRelationship: false,
				fetchingRemotePosts: false,
				showMutualFollowers: false,
				loadingMore: false,
				showLoadMore: true
			}
		},

		beforeMount() {
			this.fetchRelationships();
			this.fetchProfile();
		},

		updated() {
			document.querySelectorAll('.hashtag').forEach(function(i, e) {
			    i.href = App.util.format.rewriteLinks(i);
			});
		},

		methods: {
			fetchProfile() {
				axios.get('/api/pixelfed/v1/accounts/verify_credentials').then(res => {
					this.user = res.data
					window._sharedData.curUser = res.data;
					window.App.util.navatar();
				});
				axios.get('/api/pixelfed/v1/accounts/' + this.profileId)
				.then(res => {
					this.profile = res.data;
					this.fetchPosts();
				});
			},

			fetchPosts() {
				let apiUrl = '/api/pixelfed/v1/accounts/' + this.profileId + '/statuses';
				axios.get(apiUrl, {
					params: {
						only_media: true,
						min_id: 1,
					}
				})
				.then(res => {
					let data = res.data
						.filter(status => status.media_attachments.length > 0);
					let ids = data.map(status => status.id);
					this.ids = ids;
					this.min_id = Math.max(...ids);
					this.max_id = Math.min(...ids);
					this.feed = data;
					this.loading = false;
					//this.loadSponsor();
				}).catch(err => {
					swal('Oops, something went wrong',
						'Please release the page.',
						'error');
				});
			},

			loadMorePosts() {
				this.loadingMore = true;
				let apiUrl = '/api/pixelfed/v1/accounts/' + this.profileId + '/statuses';
				axios.get(apiUrl, {
					params: {
						only_media: true,
						max_id: this.max_id,
					}
				})
				.then(res => {
					let data = res.data
						.filter(status => this.ids.indexOf(status.id) === -1)
						.filter(status => status.media_attachments.length > 0)
						.map(status => {
							return {
								id: status.id,
								caption: {
									text: status.content_text,
									html: status.content
								},
								count: {
									likes: status.favourites_count,
									shares: status.reblogs_count,
									comments: status.reply_count
								},
								thumb: status.media_attachments[0].url,
								media: status.media_attachments,
								timestamp: status.created_at,
								type: status.pf_type,
								url: status.url,
								sensitive: status.sensitive,
								cw: status.sensitive,
								spoiler_text: status.spoiler_text
							}
					});
					let ids = data.map(status => status.id);
					this.ids.push(...ids);
					this.max_id = Math.min(...ids);
					this.feed.push(...data);
					this.loadingMore = false;
				}).catch(err => {
					this.loadingMore = false;
					this.showLoadMore = false;
				});
			},

			fetchRelationships() {
				if(document.querySelectorAll('body')[0].classList.contains('loggedIn') == false) {
					return;
				}
				axios.get('/api/pixelfed/v1/accounts/relationships', {
					params: {
						'id[]': this.profileId
					}
				}).then(res => {
					if(res.data.length) {
						this.relationship = res.data[0];
						if(res.data[0].blocking == true) {
							this.loading = false;
							this.warning = true;
						}
					}
				});
			},

			postPreviewUrl(post) {
				return 'background: url("'+post.thumb+'");background-size:cover';
			},

			timestampFormat(timestamp) {
				let ts = new Date(timestamp);
				return ts.toDateString() + ' ' + ts.toLocaleTimeString();
			},

			remoteProfileUrl(profile) {
				return '/i/web/profile/_/' + profile.id;
			},

			remotePostUrl(status) {
				return '/i/web/post/_/' + this.profile.id + '/' + status.id;
			},

			followProfile() {
				axios.post('/i/follow', {
					item: this.profileId
				}).then(res => {
					swal('Followed', 'You are now following ' + this.profile.username +'!', 'success');
					this.relationship.following = true;
				}).catch(err => {
					swal('Oops!', 'Something went wrong, please try again later.', 'error');
				});
			},

			unfollowProfile() {
				axios.post('/i/follow', {
					item: this.profileId
				}).then(res => {
					swal('Unfollowed', 'You are no longer following ' + this.profile.username +'.', 'warning');
					this.relationship.following = false;
				}).catch(err => {
					swal('Oops!', 'Something went wrong, please try again later.', 'error');
				});
			},

			showCtxMenu() {
				this.$refs.visitorContextMenu.show();
			},

			copyProfileLink() {
				navigator.clipboard.writeText(window.location.href);
				this.$refs.visitorContextMenu.hide();
			},

			muteProfile() {
				let id = this.profileId;
				axios.post('/i/mute', {
					type: 'user',
					item: id
				}).then(res => {
					this.fetchRelationships();
					this.$refs.visitorContextMenu.hide();
					swal('Success', 'You have successfully muted ' + this.profile.acct, 'success');
				}).catch(err => {
					swal('Error', 'Something went wrong. Please try again later.', 'error');
				});
				this.$refs.visitorContextMenu.hide();
			},

			unmuteProfile() {
				let id = this.profileId;
				axios.post('/i/unmute', {
					type: 'user',
					item: id
				}).then(res => {
					this.fetchRelationships();
					this.$refs.visitorContextMenu.hide();
					swal('Success', 'You have successfully unmuted ' + this.profile.acct, 'success');
				}).catch(err => {
					swal('Error', 'Something went wrong. Please try again later.', 'error');
				});
				this.$refs.visitorContextMenu.hide();
			},

			blockProfile() {
				let id = this.profileId;
				axios.post('/i/block', {
					type: 'user',
					item: id
				}).then(res => {
					this.warning = true;
					this.fetchRelationships();
					this.$refs.visitorContextMenu.hide();
					swal('Success', 'You have successfully blocked ' + this.profile.acct, 'success');
				}).catch(err => {
					swal('Error', 'Something went wrong. Please try again later.', 'error');
				});
				this.$refs.visitorContextMenu.hide();
			},

			unblockProfile() {
				let id = this.profileId;
				axios.post('/i/unblock', {
					type: 'user',
					item: id
				}).then(res => {
					this.warning = false;
					this.fetchRelationships();
					this.$refs.visitorContextMenu.hide();
					swal('Success', 'You have successfully unblocked ' + this.profile.acct, 'success');
				}).catch(err => {
					swal('Error', 'Something went wrong. Please try again later.', 'error');
				});
				this.$refs.visitorContextMenu.hide();
			},

			reportProfile() {
				window.location.href = '/l/i/report?type=profile&id=' + this.profileId;
				this.$refs.visitorContextMenu.hide();
			},

			ctxMenu(status) {
				this.ctxMenuStatus = status;
				let self = this;
				axios.get('/api/pixelfed/v1/accounts/relationships', {
					params: {
						'id[]': self.profileId
					}
				}).then(res => {
					self.ctxMenuRelationship = res.data[0];
					self.$refs.ctxModal.show();
				});
			},

			closeCtxMenu() {
				this.ctxMenuStatus = false;
				this.ctxMenuRelationship = false;
				this.$refs.ctxModal.hide();
			},

			ctxMenuCopyLink() {
				let status = this.ctxMenuStatus;
				navigator.clipboard.writeText(status.url);
				this.closeCtxMenu();
				return;
			},

			ctxMenuGoToPost() {
				let status = this.ctxMenuStatus;
				window.location.href = this.statusUrl(status);
				this.closeCtxMenu();
				return;
			},

			statusUrl(status) {
				return '/i/web/post/_/' + this.profile.id + '/' + status.id;
			},

			deletePost(status) {
				if(this.user.is_admin == false) {
					return;
				}

				if(window.confirm('Are you sure you want to delete this post?') == false) {
					return;
				}

				axios.post('/i/delete', {
					type: 'status',
					item: status.id
				}).then(res => {
					this.feed = this.feed.filter(s => {
						return s.id != status.id;
					});
					this.$refs.ctxModal.hide();
				}).catch(err => {
					swal('Error', 'Something went wrong. Please try again later.', 'error');
				});
			},

			manuallyFetchRemotePosts($event) {
				this.fetchingRemotePosts = true;
				event.target.blur();
				swal(
					'Fetching Remote Posts',
					'Check back in a few minutes!',
					'info'
				);
			},

			timeAgo(ts, suffix = false) {
				if(ts == null) {
					return 'never';
				}
				suffix = suffix ? ' ' + suffix : '';
				return App.util.format.timeAgo(ts) + suffix;
			},

			urlRedirectHandler(url) {
				let p = new URL(url);
				let path = '';
				if(p.hostname == window.location.hostname) {
					path = url;
				} else {
					path = '/i/redirect?url=';
					path += encodeURI(url);
				}
				window.location.href = path;
			}
		}
	}
</script>

<style type="text/css" scoped>
	@media (min-width: 1200px) {
		.container {
			max-width: 1050px;
		}
	}
</style>
