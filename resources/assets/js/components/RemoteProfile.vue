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
							<span class="cursor-pointer" @click="followingModal()">
								<span class="font-weight-bold text-dark">{{profile.following_count}}</span>
								<span>Following</span>
							</span>
							<span class="cursor-pointer" @click="followersModal()">
								<span class="font-weight-bold text-dark">{{profile.followers_count}}</span>
								<span>Followers</span>
							</span>
						</p>
						<p class="pl-2 text-muted small pt-2" v-html="profile.note"></p>
					</div>
				</div>
				<p class="small text-lighter p-2">Last updated: <time :datetime="profile.last_fetched_at">{{timeAgo(profile.last_fetched_at, 'ago')}}</time></p>
				<p class="card border-left-primary card-body small py-2 text-muted font-weight-bold shadow-none border-top border-bottom border-right">You are viewing a profile from a remote server, it may not contain up-to-date information.</p>
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

		<b-modal
			v-if="profile && following"
			ref="followingModal"
			id="following-modal"
			hide-footer
			centered
			scrollable
			title="Following"
			body-class="list-group-flush py-3 px-0"
			dialog-class="follow-modal">
			<div v-if="!followingLoading" class="list-group" style="max-height: 60vh;">
				<div v-if="!following.length" class="list-group-item border-0">
					<p class="text-center mb-0 font-weight-bold text-muted py-5">
						<span class="text-dark">{{profileUsername}}</span> is not following yet</p>
				</div>
				<div v-else>
					<div v-if="owner == true" class="list-group-item border-0 pt-0 px-0 mt-n2 mb-3">
						<span class="d-flex px-4 pb-0 align-items-center">
							<i class="fas fa-search text-lighter"></i>
							<input type="text" class="form-control border-0 shadow-0 no-focus" placeholder="Search Following..." v-model="followingModalSearch" v-on:keyup="followingModalSearchHandler">
						</span>
					</div>
					<div class="list-group-item border-0 py-1 mb-1" v-for="(user, index) in following" :key="'following_'+index">
						<div class="media">
							<a :href="profileUrlRedirect(user)">
								<img class="mr-3 rounded-circle box-shadow" :src="user.avatar" :alt="user.username + '’s avatar'" width="30px" loading="lazy" onerror="this.onerror=null;this.src='/storage/avatars/default.png?v=0'">
							</a>
							<div class="media-body text-truncate">
								<p class="mb-0" style="font-size: 14px">
									<a :href="profileUrlRedirect(user)" class="font-weight-bold text-dark">
										{{user.username}}
									</a>
								</p>
								<p v-if="!user.local" class="text-muted mb-0 text-break mr-3" style="font-size: 14px" :title="user.acct" data-toggle="dropdown" data-placement="bottom">
									<span class="font-weight-bold">{{user.acct.split('@')[0]}}</span><span class="text-lighter">&commat;{{user.acct.split('@')[1]}}</span>
								</p>
								<p v-else class="text-muted mb-0 text-truncate" style="font-size: 14px">
									{{user.display_name ? user.display_name : user.username}}
								</p>
							</div>
							<div v-if="owner">
								<a class="btn btn-outline-dark btn-sm font-weight-bold" href="#" @click.prevent="followModalAction(user.id, index, 'following')">Following</a>
							</div>
						</div>
					</div>
					<div v-if="followingModalSearch && following.length == 0" class="list-group-item border-0">
						<div class="list-group-item border-0 pt-5">
							<p class="p-3 text-center mb-0 lead">No Results Found</p>
						</div>
					</div>
					<div v-if="following.length > 0 && followingMore" class="list-group-item text-center" v-on:click="followingLoadMore()">
						<p class="mb-0 small text-muted font-weight-light cursor-pointer">Load more</p>
					</div>
				</div>
			</div>
			<div v-else class="text-center py-5">
				<div class="spinner-border" role="status">
					<span class="sr-only">Loading...</span>
				</div>
			</div>
		</b-modal>
		<b-modal ref="followerModal"
			id="follower-modal"
			hide-footer
			centered
			scrollable
			title="Followers"
			body-class="list-group-flush py-3 px-0"
			dialog-class="follow-modal"
			>
			<div v-if="!followerLoading" class="list-group" style="max-height: 60vh;">
				<div v-if="!followers.length" class="list-group-item border-0">
					<p class="text-center mb-0 font-weight-bold text-muted py-5">
						<span class="text-dark">{{profileUsername}}</span> has no followers yet</p>
				</div>

				<div v-else>
					<div class="list-group-item border-0 py-1 mb-1" v-for="(user, index) in followers" :key="'follower_'+index">
						<div class="media mb-0">
							<a :href="profileUrlRedirect(user)">
								<img class="mr-3 rounded-circle box-shadow" :src="user.avatar" :alt="user.username + '’s avatar'" width="30px" height="30px" loading="lazy" onerror="this.onerror=null;this.src='/storage/avatars/default.png?v=0'">
							</a>
							<div class="media-body mb-0">
								<p class="mb-0" style="font-size: 14px">
									<a :href="profileUrlRedirect(user)" class="font-weight-bold text-dark">
										{{user.username}}
									</a>
								</p>
								<p v-if="!user.local" class="text-muted mb-0 text-break mr-3" style="font-size: 14px" :title="user.acct" data-toggle="dropdown" data-placement="bottom">
									<span class="font-weight-bold">{{user.acct.split('@')[0]}}</span><span class="text-lighter">&commat;{{user.acct.split('@')[1]}}</span>
								</p>
								<p v-else class="text-muted mb-0 text-truncate" style="font-size: 14px">
									{{user.display_name ? user.display_name : user.username}}
								</p>
							</div>
							<!-- <button class="btn btn-primary font-weight-bold btn-sm py-1">FOLLOW</button> -->
						</div>
					</div>
					<div v-if="followers.length && followerMore" class="list-group-item text-center" v-on:click="followersLoadMore()">
						<p class="mb-0 small text-muted font-weight-light cursor-pointer">Load more</p>
					</div>
				</div>
			</div>
			<div v-else class="text-center py-5">
				<div class="spinner-border" role="status">
					<span class="sr-only">Loading...</span>
				</div>
			</div>
		</b-modal>
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
				showLoadMore: true,
				followers: [],
				followerCursor: 1,
				followerMore: true,
				followerLoading: true,
				following: [],
				followingCursor: 1,
				followingMore: true,
				followingLoading: true,
				followingModalSearch: null,
				followingModalSearchCache: null,
				followingModalTab: 'following',
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
			},

			followingModal() {
				if(this.followingCursor > 1) {
					this.$refs.followingModal.show();
					return;
				} else {
					axios.get('/api/pixelfed/v1/accounts/'+this.profileId+'/following', {
						params: {
							page: this.followingCursor
						}
					})
					.then(res => {
						this.following = res.data;
						this.followingModalSearchCache = res.data;
						this.followingCursor++;
						if(res.data.length < 10) {
							this.followingMore = false;
						}
						this.followingLoading = false;
					});
					this.$refs.followingModal.show();
					return;
				}
			},

			followersModal() {
				if(this.followerCursor > 1) {
					this.$refs.followerModal.show();
					return;
				} else {
					axios.get('/api/pixelfed/v1/accounts/'+this.profileId+'/followers', {
						params: {
							page: this.followerCursor
						}
					})
					.then(res => {
						this.followers.push(...res.data);
						this.followerCursor++;
						if(res.data.length < 10) {
							this.followerMore = false;
						}
						this.followerLoading = false;
					})
					this.$refs.followerModal.show();
					return;
				}
			},

			followingLoadMore() {
				axios.get('/api/pixelfed/v1/accounts/'+this.profile.id+'/following', {
					params: {
						page: this.followingCursor,
						fbu: this.followingModalSearch
					}
				})
				.then(res => {
					if(res.data.length > 0) {
						this.following.push(...res.data);
						this.followingCursor++;
						this.followingModalSearchCache = this.following;
					}
					if(res.data.length < 10) {
						this.followingModalSearchCache = this.following;
						this.followingMore = false;
					}
				});
			},

			followersLoadMore() {
				axios.get('/api/pixelfed/v1/accounts/'+this.profile.id+'/followers', {
					params: {
						page: this.followerCursor
					}
				})
				.then(res => {
					if(res.data.length > 0) {
						this.followers.push(...res.data);
						this.followerCursor++;
					}
					if(res.data.length < 10) {
						this.followerMore = false;
					}
				});
			},

			profileUrlRedirect(profile) {
				if(profile.local == true) {
					return profile.url;
				}

				return '/i/web/profile/_/' + profile.id;
			},

			followingModalSearchHandler() {
				let self = this;
				let q = this.followingModalSearch;

				if(q.length == 0) {
					this.following = this.followingModalSearchCache;
					this.followingModalSearch = null;
				}
				if(q.length > 0) {
					let url = '/api/pixelfed/v1/accounts/' +
						self.profileId + '/following?page=1&fbu=' +
						q;

					axios.get(url).then(res => {
						this.following = res.data;
					}).catch(err => {
						self.following = self.followingModalSearchCache;
						self.followingModalSearch = null;
					});
				}
			},
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
