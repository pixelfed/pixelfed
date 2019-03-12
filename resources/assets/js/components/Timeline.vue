<template>
<div class="container" style="">
	<div class="row">
		<div class="col-md-8 col-lg-8 pt-sm-2 px-0 my-sm-3 timeline order-2 order-md-1">
			<div v-if="loading" class="text-center">
				<div class="spinner-border" role="status">
					<span class="sr-only">Loading...</span>
				</div>
			</div>
			<div class="card mb-sm-4 status-card card-md-rounded-0" :data-status-id="status.id" v-for="(status, index) in feed" :key="status.id">

				<div class="card-header d-inline-flex align-items-center bg-white">
					<img v-bind:src="status.account.avatar" width="32px" height="32px" style="border-radius: 32px;">
					<a class="username font-weight-bold pl-2 text-dark" v-bind:href="status.account.url">
						{{status.account.username}}
					</a>
					<div class="text-right" style="flex-grow:1;">
						<button class="btn btn-link text-dark no-caret dropdown-toggle py-0" type="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false" title="Post options">
							<span class="fas fa-ellipsis-v fa-lg text-muted"></span>
						</button>
						<div class="dropdown-menu dropdown-menu-right">
							<a class="dropdown-item font-weight-bold" :href="status.url">Go to post</a>
							<!-- <a class="dropdown-item font-weight-bold" href="#">Share</a>
							<a class="dropdown-item font-weight-bold" href="#">Embed</a> -->
							<span v-if="statusOwner(status) == false">
								<a class="dropdown-item font-weight-bold" :href="reportUrl(status)">Report</a>
								<a class="dropdown-item font-weight-bold" v-on:click="muteProfile(status)">Mute Profile</a>
								<a class="dropdown-item font-weight-bold" v-on:click="blockProfile(status)">Block Profile</a>
							</span>
							<span v-if="statusOwner(status) == true">
								<a class="dropdown-item font-weight-bold text-danger" v-on:click="deletePost(status)">Delete</a>
							</span>
							<span v-if="profile.is_admin == true && modes.mod == true">
								<div class="dropdown-divider"></div>
								<a v-if="!statusOwner(status)" class="dropdown-item font-weight-bold text-danger" v-on:click="deletePost(status)">Delete</a>
								<div class="dropdown-divider"></div>
								<h6 class="dropdown-header">Mod Tools</h6>
								<a class="dropdown-item font-weight-bold" v-on:click="moderatePost(status, 'autocw')">
									<p class="mb-0" data-toggle="tooltip" data-placement="bottom" title="Adds a CW to every post made by this account.">Enforce CW</p>
								</a>
								<a class="dropdown-item font-weight-bold" v-on:click="moderatePost(status, 'noautolink')">
									<p class="mb-0" title="Do not transform mentions, hashtags or urls into HTML.">No Autolinking</p>
								</a>
								<a class="dropdown-item font-weight-bold" v-on:click="moderatePost(status, 'unlisted')">
									<p class="mb-0" title="Removes account from public/network timelines.">Unlisted Posts</p>
								</a>
								<a class="dropdown-item font-weight-bold" v-on:click="moderatePost(status, 'disable')">
									<p class="mb-0" title="Temporarily disable account until next time user log in.">Disable Account</p>
								</a>
								<a class="dropdown-item font-weight-bold" v-on:click="moderatePost(status, 'suspend')">
									<p class="mb-0" title="This prevents any new interactions, without deleting existing data.">Suspend Account</p>
								</a>

							</span>
						</div>
					</div>
				</div>

				<div class="postPresenterContainer">
					<div v-if="status.pf_type === 'photo'" class="w-100">
						<photo-presenter :status="status" v-on:lightbox="lightbox"></photo-presenter>
					</div>

					<div v-else-if="status.pf_type === 'video'" class="w-100">
						<video-presenter :status="status"></video-presenter>
					</div>

					<div v-else-if="status.pf_type === 'photo:album'" class="w-100">
						<photo-album-presenter :status="status" v-on:lightbox="lightbox"></photo-album-presenter>
					</div>

					<div v-else-if="status.pf_type === 'video:album'" class="w-100">
						<video-album-presenter :status="status"></video-album-presenter>
					</div>

					<div v-else-if="status.pf_type === 'photo:video:album'" class="w-100">
						<mixed-album-presenter :status="status" v-on:lightbox="lightbox"></mixed-album-presenter>
					</div>

					<div v-else class="w-100">
						<p class="text-center p-0 font-weight-bold text-white">Error: Problem rendering preview.</p>
					</div>
				</div>

				<div class="card-body">
					<div class="reactions my-1">
						<h3 v-bind:class="[status.favourited ? 'fas fa-heart text-danger pr-3 m-0 cursor-pointer' : 'far fa-heart pr-3 m-0 like-btn cursor-pointer']" title="Like" v-on:click="likeStatus(status, $event)"></h3>
						<h3 class="far fa-comment pr-3 m-0 cursor-pointer" title="Comment" v-on:click="commentFocus(status, $event)"></h3>
						<h3 v-bind:class="[status.reblogged ? 'far fa-share-square pr-3 m-0 text-primary cursor-pointer' : 'far fa-share-square pr-3 m-0 share-btn cursor-pointer']" title="Share" v-on:click="shareStatus(status, $event)"></h3>
					</div>

					<div class="likes font-weight-bold">
						<span class="like-count">{{status.favourites_count}}</span> {{status.favourites_count == 1 ? 'like' : 'likes'}}
					</div>
					<div class="caption">
						<p class="mb-2 read-more" style="overflow: hidden;">
							<span class="username font-weight-bold">
								<bdi><a class="text-dark" :href="status.account.url">{{status.account.username}}</a></bdi>
							</span>
							<span v-html="status.content"></span>
						</p>
					</div>
					<div class="comments" v-if="status.id == replyId">
						<p class="mb-0 d-flex justify-content-between align-items-top read-more" style="overflow-y: hidden;" v-for="(reply, index) in replies">
							<span>
								<a class="text-dark font-weight-bold mr-1" :href="reply.account.url">{{reply.account.username}}</a>
								<span v-html="reply.content"></span>
							</span>
							<span class="mb-0" style="min-width:38px">
								<span v-on:click="likeStatus(reply, $event)"><i v-bind:class="[reply.favourited ? 'fas fa-heart fa-sm text-danger':'far fa-heart fa-sm text-lighter']"></i></span>
								<post-menu :status="reply" :profile="profile" size="sm" :modal="'true'" :feed="feed" class="d-inline-flex pl-2"></post-menu>
							</span>
						</p>
					</div>
					<div class="timestamp mt-2">
						<p class="small text-uppercase mb-0">
							<a :href="status.url" class="text-muted">
								<timeago :datetime="status.created_at" :auto-update="60" :converter-options="{includeSeconds:true}" :title="timestampFormat(status.created_at)" v-b-tooltip.hover.bottom></timeago>
							</a>
						</p>
					</div>
				</div>

				<div class="card-footer bg-white" v-if="status.id == replyId">
					<form class="" v-on:submit.prevent="commentSubmit(status, $event)">
						<input type="hidden" name="item" value="">
						<input class="form-control status-reply-input" name="comment" placeholder="Add a comment…" autocomplete="off">
					</form>
				</div>
			</div>
			<div v-if="modes.infinite == true && !loading && feed.length > 0">
				<div class="card">
					<div class="card-body">
						<infinite-loading @infinite="infiniteTimeline">
						<div slot="no-more" class="font-weight-bold">No more posts to load</div>
						<div slot="no-results" class="font-weight-bold">No posts found</div>
						</infinite-loading>
					</div>
				</div>
			</div>
			<div v-if="modes.infinite == false && !loading && feed.length > 0" class="pagination">
				<p class="btn btn-outline-secondary font-weight-bold btn-block" v-on:click="loadMore">Load more posts</p>
			</div>
			<div v-if="!loading && scope == 'home' && feed.length == 0">
				<div class="card">
					<div class="card-body text-center">
						<p class="h2 font-weight-lighter p-5">Hello, {{profile.acct}}</p>
						<p class="text-lighter"><i class="fas fa-camera-retro fa-5x"></i></p>
						<p class="h3 font-weight-lighter p-5">Start following people to build your timeline.</p>
						<p><a href="/discover" class="btn btn-primary font-weight-bold py-0">Discover new people and posts</a></p>
					</div>
				</div>
			</div>
		</div>

		<div class="col-md-4 col-lg-4 pt-2 my-3 order-1 order-md-2  d-none d-md-block">
			<div class="mb-4">
				<div class="card profile-card">
					<div class="card-body loader text-center">
						<div class="spinner-border" role="status">
							<span class="sr-only">Loading...</span>
						</div>
					</div>
					<div class="card-body contents d-none">
						<div class="media d-flex align-items-center">
							<a :href="profile.url">
								<img class="mr-3 rounded-circle box-shadow" :src="profile.avatar || '/storage/avatars/default.png'" alt="avatar" width="64px" height="64px">
							</a>
							<div class="media-body d-flex justify-content-between word-break">
								<div>
									<p class="mb-0 px-0 font-weight-bold"><a :href="profile.url" class="text-dark">&commat;{{profile.username}}</a></p>
									<p class="my-0 text-muted pb-0">{{profile.display_name}}</p>
								</div>
								<div class="ml-2">
									<a :class="[optionMenuState == true ? 'text-primary' :'text-muted']" v-on:click="toggleOptionsMenu()"><i class="fas fa-cog"></i></a>
								</div>
							</div>
						</div>
					</div>
					<div class="card-footer bg-white py-1 d-none">
						<div class="d-flex justify-content-between text-center">
							<span class="pl-3 cursor-pointer" v-on:click="redirect(profile.url)">
								<p class="mb-0 font-weight-bold">{{profile.statuses_count}}</p>
								<p class="mb-0 small text-muted">Posts</p>
							</span>
							<span class="cursor-pointer" v-on:click="followersModal()">
								<p class="mb-0 font-weight-bold">{{profile.followers_count}}</p>
								<p class="mb-0 small text-muted">Followers</p>
							</span>
							<span class="pr-3 cursor-pointer" v-on:click="followingModal()">
								<p class="mb-0 font-weight-bold">{{profile.following_count}}</p>
								<p class="mb-0 small text-muted">Following</p>
							</span>
						</div>
					</div>
				</div>
			</div>

			<div v-if="optionMenuState == true" class="mb-4">
				<div class="card options-card">
					<div class="card-body small">
						<div v-if="profile.is_admin" class="custom-control custom-switch mb-3">
							<input type="checkbox" class="custom-control-input" id="mode-mod" v-on:click="modeModToggle()" v-model="modes.mod">
							<label class="custom-control-label font-weight-bold" for="mode-mod">Moderator Mode</label>
						</div>
						<!-- <div class="custom-control custom-switch mb-3">
							<input type="checkbox" class="custom-control-input" id="mode-notify" v-on:click="modeNotifyToggle()"  v-model="!modes.notify">
							<label class="custom-control-label font-weight-bold" for="mode-notify">Disable Notifications</label>
						</div> -->
						<div class="custom-control custom-switch">
							<input type="checkbox" class="custom-control-input" id="mode-infinite" v-on:click="modeInfiniteToggle()" v-model="modes.infinite">
							<label class="custom-control-label font-weight-bold" for="mode-infinite">Enable Infinite Scroll</label>
						</div>
						<hr>
						<p class="font-weight-bold">BETA FEATURES</p>
						<div class="custom-control custom-switch">
							<input type="checkbox" class="custom-control-input" id="mode-dark" v-on:click="modeDarkToggle()" v-model="modes.dark">
							<label class="custom-control-label font-weight-bold" for="mode-dark">Dark Mode</label>
						</div>
					</div>
				</div>
			</div>

			<div v-show="modes.notify == true" class="mb-4">
				<div class="card notification-card">
					<div class="card-header bg-white">
						<p class="mb-0 d-flex align-items-center justify-content-between">
							<span class="text-muted font-weight-bold">Notifications</span>
							<a class="text-dark small" href="/account/activity">See All</a>
						</p>
					</div>
					<div class="card-body loader text-center" style="height: 270px;">
						<div class="spinner-border" role="status">
							<span class="sr-only">Loading...</span>
						</div>
					</div>
					<div class="card-body pt-2 contents" style="max-height: 270px; overflow-y: scroll;">
						<div v-if="notifications.length > 0" class="media mb-3 align-items-center" v-for="(n, index) in notifications">
							<img class="mr-2 rounded-circle" style="border:1px solid #ccc" :src="n.account.avatar" alt="" width="32px" height="32px">
							<div class="media-body font-weight-light small">
								<div v-if="n.type == 'favourite'">
									<p class="my-0">
										<a :href="n.account.url" class="font-weight-bold text-dark word-break">{{n.account.username}}</a> liked your <a class="font-weight-bold" v-bind:href="replyUrl(n.status)">post</a>.
									</p>
								</div>
								<div v-else-if="n.type == 'comment'">
									<p class="my-0">
										<a :href="n.account.url" class="font-weight-bold text-dark word-break">{{n.account.username}}</a> commented on your <a class="font-weight-bold" v-bind:href="replyUrl(n.status)">post</a>.
									</p>
								</div>
								<div v-else-if="n.type == 'mention'">
									<p class="my-0">
										<a :href="n.account.url" class="font-weight-bold text-dark word-break">{{n.account.username}}</a> <a class="font-weight-bold" v-bind:href="mentionUrl(n.status)">mentioned</a> you.
									</p>
								</div>
								<div v-else-if="n.type == 'follow'">
									<p class="my-0">
										<a :href="n.account.url" class="font-weight-bold text-dark word-break">{{n.account.username}}</a> followed you.
									</p>
								</div>
								<div v-else-if="n.type == 'share'">
									<p class="my-0">
										<a :href="n.account.url" class="font-weight-bold text-dark word-break">{{n.account.username}}</a> shared your <a class="font-weight-bold" v-bind:href="n.status.reblog.url">post</a>.
									</p>
								</div>
							</div>
						</div>
						<div v-if="notifications.length">
							<infinite-loading @infinite="infiniteNotifications">
								<div slot="no-results" class="font-weight-bold"></div>
								<div slot="no-more" class="font-weight-bold"></div>
							</infinite-loading>
						</div>
						<div v-if="notifications.length == 0" class="text-lighter text-center py-3">
							<p class="mb-0"><i class="fas fa-inbox fa-3x"></i></p>
							<p class="mb-0 small font-weight-bold">0 Notifications!</p>
						</div>
					</div>
				</div>
			</div>

			<footer>
				<div class="container pb-5">
					<p class="mb-0 text-uppercase font-weight-bold text-muted small">
						<a href="/site/about" class="text-dark pr-2">About Us</a>
						<a href="/site/help" class="text-dark pr-2">Help</a>
						<a href="/site/open-source" class="text-dark pr-2">Open Source</a>
						<a href="/site/language" class="text-dark pr-2">Language</a>
						<a href="/site/terms" class="text-dark pr-2">Terms</a>
						<a href="/site/privacy" class="text-dark pr-2">Privacy</a>
						<a href="/site/platform" class="text-dark pr-2">API</a>
					</p>
					<p class="mb-0 text-uppercase font-weight-bold text-muted small">
						<a href="http://pixelfed.org" class="text-muted" rel="noopener" title="" data-toggle="tooltip">Powered by PixelFed</a>
					</p>
				</div>
			</footer>
		</div>
	</div>
  <b-modal ref="followingModal"
    id="following-modal"
    hide-footer
    centered
    title="Following"
    body-class="list-group-flush p-0">
    <div class="list-group">
      <div class="list-group-item border-0" v-for="(user, index) in following" :key="'following_'+index">
        <div class="media">
          <a :href="user.url">
            <img class="mr-3 rounded-circle box-shadow" :src="user.avatar" :alt="user.username + '’s avatar'" width="30px">
          </a>
          <div class="media-body">
            <p class="mb-0" style="font-size: 14px">
              <a :href="user.url" class="font-weight-bold text-dark">
                {{user.username}}
              </a>
            </p>
            <p class="text-muted mb-0" style="font-size: 14px">
                {{user.display_name}}
            </p>
          </div>
        </div>
      </div>
      <div v-if="followingMore" class="list-group-item text-center" v-on:click="followingLoadMore()">
	  	<p class="mb-0 small text-muted font-weight-light cursor-pointer">Load more</p>
      </div>
    </div>
  </b-modal>
  <b-modal ref="followerModal"
    id="follower-modal"
    hide-footer
    centered
    title="Followers"
    body-class="list-group-flush p-0">
    <div class="list-group">
      <div class="list-group-item border-0" v-for="(user, index) in followers" :key="'follower_'+index">
        <div class="media">
          <a :href="user.url">
            <img class="mr-3 rounded-circle box-shadow" :src="user.avatar" :alt="user.username + '’s avatar'" width="30px">
          </a>
          <div class="media-body">
            <p class="mb-0" style="font-size: 14px">
              <a :href="user.url" class="font-weight-bold text-dark">
                {{user.username}}
              </a>
            </p>
            <p class="text-muted mb-0" style="font-size: 14px">
                {{user.display_name}}
            </p>
          </div>
        </div>
      </div>
      <div v-if="followerMore" class="list-group-item text-center" v-on:click="followersLoadMore()">
	  	<p class="mb-0 small text-muted font-weight-light cursor-pointer">Load more</p>
      </div>
    </div>
  </b-modal>
  <b-modal 
  	id="lightbox" 
  	ref="lightboxModal"
  	hide-header="true"
  	hide-footer="true"
  	centered
  	size="lg"
  	body-class="p-0"
  	>
  	<div v-if="lightboxMedia" :class="lightboxMedia.filter_class">
  		<img :src="lightboxMedia.url" class="img-fluid">
  	</div>
  </b-modal>
</div>
</template>

<style type="text/css" scoped>
	.postPresenterContainer {
		display: flex;
		align-items: center;
		background: #fff;
	}
	.word-break {
		word-break: break-all;
	}
	.small .custom-control-label {
		padding-top: 3px;
	}
</style>

<script type="text/javascript">
	export default {
		props: ['scope'],
		data() {
			return {
				page: 2,
				feed: [],
				profile: {},
				min_id: 0,
				max_id: 0,
				notifications: {},
				notificationCursor: 2,
				stories: {},
				suggestions: {},
				loading: true,
				replies: [],
				replyId: null,
				optionMenuState: false,
				modes: {
					'mod': false,
					'dark': false,
					'notify': true,
					'infinite': true
				},
				followers: [],
				followerCursor: 1,
				followerMore: true,
				following: [],
				followingCursor: 1,
				followingMore: true,
				lightboxMedia: false
			}
		},

		beforeMount() {
			this.fetchTimelineApi();
			this.fetchProfile();
		},

		mounted() {
			this.$nextTick(function () {
				$('[data-toggle="tooltip"]').tooltip()
			});
		},

		updated() {
			pixelfed.readmore();
		},

		methods: {
			fetchProfile() {
				axios.get('/api/v1/accounts/verify_credentials').then(res => {
					this.profile = res.data;
					$('.profile-card .loader').addClass('d-none');
					$('.profile-card .contents').removeClass('d-none');
					$('.profile-card .card-footer').removeClass('d-none');
				}).catch(err => {
					swal(
						'Oops, something went wrong',
						'Please reload the page.',
						'error'
					);
				});
			},

			fetchTimelineApi() {
				let apiUrl = false;
				switch(this.scope) {
					case 'home':
					apiUrl = '/api/v1/timelines/home';
					break;

					case 'local':
					apiUrl = '/api/v1/timelines/public';
					break;

					case 'network':
					apiUrl = '/api/v1/timelines/network';
					break;
				}
				axios.get(apiUrl, {
					params: {
						max_id: this.max_id,
						limit: 4
					}
				}).then(res => {
					let data = res.data;
					this.feed.push(...data);
					let ids = data.map(status => status.id);
					this.min_id = Math.max(...ids);
					this.max_id = Math.min(...ids);
					$('.timeline .pagination').removeClass('d-none');
					this.loading = false;
					this.fetchNotifications();
				}).catch(err => {
				});
			},

			infiniteTimeline($state) {
				let apiUrl = false;
				switch(this.scope) {
					case 'home':
					apiUrl = '/api/v1/timelines/home';
					break;

					case 'local':
					apiUrl = '/api/v1/timelines/public';
					break;

					case 'network':
					apiUrl = '/api/v1/timelines/network';
					break;
				}
				axios.get(apiUrl, {
					params: {
						max_id: this.max_id,
						limit: 4
					},
				}).then(res => {
					if (res.data.length && this.loading == false) {
						let data = res.data;
						this.feed.push(...data);
						let ids = data.map(status => status.id);
						this.min_id = Math.max(...ids);
						this.max_id = Math.min(...ids);
						this.page += 1;
						$state.loaded();
						this.loading = false;
					} else {
						$state.complete();
					}
				});
			},

			loadMore(event) {
				let homeTimeline = '/api/v1/timelines/home';
				let localTimeline = '/api/v1/timelines/public';
				let apiUrl = this.scope == 'home' ? homeTimeline : localTimeline;
				event.target.innerText = 'Loading...';
				axios.get(apiUrl, {
					params: {
						page: this.page,
					},
				}).then(res => {
					if (res.data.length && this.loading == false) {
						let data = res.data;
						this.feed.push(...data);
						let ids = data.map(status => status.id);
						this.min_id = Math.min(...ids);
						if(this.page == 1) {
							this.max_id = Math.max(...ids);
						}
						this.page += 1;
						this.loading = false;
						event.target.innerText = 'Load more posts';
					} else {
					}
				});
			},

			fetchNotifications() {
				axios.get('/api/v1/notifications')
				.then(res => {
					let data = res.data.filter(n => {
						if(n.type == 'share' && !status) {
							return false;
						}
						return true;
					});
					this.notifications = data;
					$('.notification-card .loader').addClass('d-none');
					$('.notification-card .contents').removeClass('d-none');
				});
			},

			infiniteNotifications($state) {
				if(this.notificationCursor > 10) {
					$state.complete();
					return;
				}
				axios.get('/api/v1/notifications', {
					params: {
						page: this.notificationCursor
					}
				}).then(res => {
					if(res.data.length) {
						let data = res.data.filter(n => {
							if(n.type == 'share' && !status) {
								return false;
							}
							return true;
						});
						this.notifications.push(...data);
						this.notificationCursor++;
						$state.loaded();
					} else {
						$state.complete();
					}
				});
			},

			reportUrl(status) {
				let type = status.in_reply_to ? 'comment' : 'post';
				let id = status.id;
				return '/i/report?type=' + type + '&id=' + id;
			},

			commentFocus(status, $event) {
				if(this.replyId == status.id) {
					return;
				}
				this.replies = {};
				this.replyId = status.id;
				this.fetchStatusComments(status, '');
			},

			likeStatus(status, $event) {
				if($('body').hasClass('loggedIn') == false) {
					return;
				}

				axios.post('/i/like', {
					item: status.id
				}).then(res => {
					status.favourites_count = res.data.count;
					status.favourited = !status.favourited;
				}).catch(err => {
					swal('Error', 'Something went wrong, please try again later.', 'error');
				});
			},

			shareStatus(status, $event) {
				if($('body').hasClass('loggedIn') == false) {
					return;
				}

				axios.post('/i/share', {
					item: status.id
				}).then(res => {
					status.reblogs_count = res.data.count;
					status.reblogged = !status.reblogged;
				}).catch(err => {
					swal('Error', 'Something went wrong, please try again later.', 'error');
				});
			},

			timestampFormat(timestamp) {
				let ts = new Date(timestamp);
				return ts.toDateString() + ' ' + ts.toLocaleTimeString();
			},

			editUrl(status) {
				return status.url + '/edit';
			},

			redirect(url) {
				window.location.href = url;
				return;
			},

			replyUrl(status) {
				let username = this.profile.username;
				let id = status.account.id == this.profile.id ? status.id : status.in_reply_to_id;
				return '/p/' + username + '/' + id;
			},

			mentionUrl(status) {
				let username = status.account.username;
				let id = status.id;
				return '/p/' + username + '/' + id;
			},

			statusOwner(status) {
				let sid = status.account.id;
				let uid = this.profile.id;
				if(sid == uid) {
					return true;
				} else {
					return false;
				}
			},

			fetchStatusComments(status, card) {
				axios.get('/api/v2/status/'+status.id+'/replies')
				.then(res => {
					let data = res.data;
					this.replies = _.reverse(data);
				}).catch(err => {
				})
			},

			muteProfile(status) {
				if($('body').hasClass('loggedIn') == false) {
					return;
				}
				axios.post('/i/mute', {
					type: 'user',
					item: status.account.id
				}).then(res => {
					this.feed = this.feed.filter(s => s.account.id !== status.account.id);
					swal('Success', 'You have successfully muted ' + status.account.acct, 'success');
				}).catch(err => {
					swal('Error', 'Something went wrong. Please try again later.', 'error');
				});
			},

			blockProfile(status) {
				if($('body').hasClass('loggedIn') == false) {
					return;
				}

				axios.post('/i/block', {
					type: 'user',
					item: status.account.id
				}).then(res => {
					this.feed = this.feed.filter(s => s.account.id !== status.account.id);
					swal('Success', 'You have successfully blocked ' + status.account.acct, 'success');
				}).catch(err => {
					swal('Error', 'Something went wrong. Please try again later.', 'error');
				});
			},

			deletePost(status, index) {
				if($('body').hasClass('loggedIn') == false || status.account.id !== this.profile.id) {
					return;
				}

				axios.post('/i/delete', {
					type: 'status',
					item: status.id
				}).then(res => {
					this.feed.splice(index,1);
					swal('Success', 'You have successfully deleted this post', 'success');
				}).catch(err => {
					swal('Error', 'Something went wrong. Please try again later.', 'error');
				});
			},

			commentSubmit(status, $event) {
				let id = status.id;
				let form = $event.target;
				let input = $(form).find('input[name="comment"]');
				let comment = input.val();
				let comments = form.parentElement.parentElement.getElementsByClassName('comments')[0];
				axios.post('/i/comment', {
					item: id,
					comment: comment
				}).then(res => {
					form.reset();
					form.blur();
					this.replies.push(res.data.entity);
				});
			},

			moderatePost(status, action, $event) {
				let username = status.account.username;
				console.log('action: ' + action + ' status id' + status.id);
				switch(action) {
					case 'autocw':
						let msg = 'Are you sure you want to enforce CW for ' + username + ' ?';
						swal({
							title: 'Confirm',
							text: msg,
							icon: 'warning',
							buttons: true,
							dangerMode: true
						}).then(res =>  {
							if(res) {
								axios.post('/api/v2/moderator/action', {
									action: action,
									item_id: status.id,
									item_type: 'status'
								}).then(res => {
									swal('Success', 'Successfully enforced CW for ' + username, 'success');
								}).catch(err => {
									swal(
										'Error',
										'Something went wrong, please try again later.',
										'error'
									);
								});
							}
						});
					break;

					case 'noautolink':
						msg = 'Are you sure you want to disable auto linking for ' + username + ' ?';
						swal({
							title: 'Confirm',
							text: msg,
							icon: 'warning',
							buttons: true,
							dangerMode: true
						}).then(res =>  {
							if(res) {
								axios.post('/api/v2/moderator/action', {
									action: action,
									item_id: status.id,
									item_type: 'status'
								}).then(res => {
									swal('Success', 'Successfully disabled autolinking for ' + username, 'success');
								}).catch(err => {
									swal(
										'Error',
										'Something went wrong, please try again later.',
										'error'
									);
								});
							}
						});
					break;
					case 'unlisted':
						msg = 'Are you sure you want to unlist from timelines for ' + username + ' ?';
						swal({
							title: 'Confirm',
							text: msg,
							icon: 'warning',
							buttons: true,
							dangerMode: true
						}).then(res =>  {
							if(res) {
								axios.post('/api/v2/moderator/action', {
									action: action,
									item_id: status.id,
									item_type: 'status'
								}).then(res => {
									swal('Success', 'Successfully unlisted for ' + username, 'success');
								}).catch(err => {
									swal(
										'Error',
										'Something went wrong, please try again later.',
										'error'
									);
								});
							}
						});
					break;

					case 'disable':
						msg = 'Are you sure you want to disable ' + username + '’s account ?';
						swal({
							title: 'Confirm',
							text: msg,
							icon: 'warning',
							buttons: true,
							dangerMode: true
						}).then(res =>  {
							if(res) {
								axios.post('/api/v2/moderator/action', {
									action: action,
									item_id: status.id,
									item_type: 'status'
								}).then(res => {
									swal('Success', 'Successfully disabled ' + username + '’s account', 'success');
								}).catch(err => {
									swal(
										'Error',
										'Something went wrong, please try again later.',
										'error'
									);
								});
							}
						});
					break;

					case 'suspend':
						msg = 'Are you sure you want to suspend ' + username + '’s account ?';
						swal({
							title: 'Confirm',
							text: msg,
							icon: 'warning',
							buttons: true,
							dangerMode: true
						}).then(res =>  {
							if(res) {
								axios.post('/api/v2/moderator/action', {
									action: action,
									item_id: status.id,
									item_type: 'status'
								}).then(res => {
									swal('Success', 'Successfully suspend ' + username + '’s account', 'success');
								}).catch(err => {
									swal(
										'Error',
										'Something went wrong, please try again later.',
										'error'
									);
								});
							}
						});
					break;
				}
			},

			toggleOptionsMenu() {
				this.optionMenuState = !this.optionMenuState;
			},

			modeModToggle() {
				this.modes.mod = !this.modes.mod;
				window.ls.set('pixelfed-classicui-settings', this.modes);
			},

			modeNotifyToggle() {
				this.modes.notify = !this.modes.notify;
				window.ls.set('pixelfed-classicui-settings', this.modes);
			},

			modeDarkToggle() {
				// todo: more graceful stylesheet change
				if(this.modes.dark == true) {
					this.modes.dark = false;
					$('link[data-stylesheet=dark]').remove();
				} else {
					this.modes.dark = true;
					let head = document.head;
					let link = document.createElement("link");
					link.type = "text/css";
					link.rel = "stylesheet";
					link.href = "/css/appdark.css";
					link.setAttribute('data-stylesheet','dark');
					head.appendChild(link);
				}
				window.ls.set('pixelfed-classicui-settings', this.modes);
			},

			modeInfiniteToggle() {
				this.modes.infinite = !this.modes.infinite
				window.ls.set('pixelfed-classicui-settings', this.modes);
			},

			followingModal() {
				if(this.following.length > 0) {
					this.$refs.followingModal.show();
					return;
				}
				axios.get('/api/v1/accounts/'+this.profile.id+'/following', {
					params: {
						page: this.followingCursor
					}
				})
				.then(res => {
					this.following = res.data;
					this.followingCursor++;
				});
        		if(res.data.length < 10) {
					this.followingMore = false;
				}
				this.$refs.followingModal.show();
			},

			followersModal() {
				if(this.followers.length > 0) {
					this.$refs.followerModal.show();
					return;
				}
				axios.get('/api/v1/accounts/'+this.profile.id+'/followers', {
					params: {
						page: this.followerCursor
					}
				})
				.then(res => {
					this.followers = res.data;
					this.followerCursor++;
				})
        		if(res.data.length < 10) {
					this.followerMore = false;
				}
				this.$refs.followerModal.show();
			},

			followingLoadMore() {
				axios.get('/api/v1/accounts/'+this.profile.id+'/following', {
					params: {
						page: this.followingCursor
					}
				})
				.then(res => {
					if(res.data.length > 0) {
						this.following.push(...res.data);
						this.followingCursor++;
					}
          			if(res.data.length < 10) {
						this.followingMore = false;
					}
				});
			},

			followersLoadMore() {
				axios.get('/api/v1/accounts/'+this.profile.id+'/followers', {
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

			lightbox(src) {
				this.lightboxMedia = src;
				this.$refs.lightboxModal.show();
			}
		}
	}
</script>
