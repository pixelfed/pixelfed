<template>
<div class="container" style="">
	<div class="row">
		<div :class="[modes.distractionFree ? 'col-md-8 col-lg-8 offset-md-2 pt-sm-2 px-0 my-sm-3 timeline order-2 order-md-1':'col-md-8 col-lg-8 pt-sm-2 px-0 my-sm-3 timeline order-2 order-md-1']">
			<div style="padding-top:10px;">
				<div v-if="loading" class="text-center">
					<div class="spinner-border" role="status">
						<span class="sr-only">Loading...</span>
					</div>
				</div>
				<div :data-status-id="status.id" v-for="(status, index) in feed" :key="`${index}-${status.id}`">
					<div v-if="index == 2 && showSuggestions == true && suggestions.length" class="card mb-sm-4 status-card card-md-rounded-0">
						<div class="card-header d-flex align-items-center justify-content-between bg-white border-0 pb-0">
							<h6 class="text-muted font-weight-bold mb-0">Suggestions For You</h6>
							<span class="cursor-pointer text-muted" v-on:click="hideSuggestions"><i class="fas fa-times"></i></span>
						</div>
						<div class="card-body row mx-0">
							<div class="col-12 col-md-4 mb-3" v-for="(rec, index) in suggestions">
								<div class="card">
									<div class="card-body text-center pt-3">
										<p class="mb-0">
											<a :href="'/'+rec.username">
												<img :src="rec.avatar" class="img-fluid rounded-circle cursor-pointer" width="45px" height="45px">
											</a>
										</p>
										<div class="py-3">
											<p class="font-weight-bold text-dark cursor-pointer mb-0">
												<a :href="'/'+rec.username" class="text-decoration-none text-dark">
													{{rec.username}}
												</a>
											</p>
											<p class="small text-muted mb-0">{{rec.message}}</p>
										</div>
										<p class="mb-0">
											<a class="btn btn-primary btn-block font-weight-bold py-0" href="#" @click.prevent="expRecFollow(rec.id, index)">Follow</a>
										</p>
									</div>
								</div>
							</div>
						</div>
					</div>
					<div class="card mb-sm-4 status-card card-md-rounded-0">
						<div v-if="!modes.distractionFree" class="card-header d-inline-flex align-items-center bg-white">
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

						<div class="postPresenterContainer" v-on:dblclick="likeStatus(status)">
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
							<div v-if="!modes.distractionFree" class="reactions my-1">
								<h3 v-bind:class="[status.favourited ? 'fas fa-heart text-danger pr-3 m-0 cursor-pointer' : 'far fa-heart pr-3 m-0 like-btn cursor-pointer']" title="Like" v-on:click="likeStatus(status, $event)"></h3>
								<h3 v-if="!status.comments_disabled" class="far fa-comment pr-3 m-0 cursor-pointer" title="Comment" v-on:click="commentFocus(status, $event)"></h3>
								<h3 v-if="status.visibility == 'public'" v-bind:class="[status.reblogged ? 'far fa-share-square pr-3 m-0 text-primary cursor-pointer' : 'far fa-share-square pr-3 m-0 share-btn cursor-pointer']" title="Share" v-on:click="shareStatus(status, $event)"></h3>
							</div>

							<div class="likes font-weight-bold" v-if="expLc(status) == true && !modes.distractionFree">
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
							<div class="comments" v-if="status.id == replyId && !status.comments_disabled">
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
									<a v-if="modes.distractionFree" class="float-right" :href="status.url">
										<i class="fas fa-ellipsis-h fa-lg text-muted"></i>
									</a>
								</p>
							</div>
						</div>

						<div v-if="status.id == replyId && !status.comments_disabled" class="card-footer bg-white px-2 py-0">
							<ul class="nav align-items-center emoji-reactions" style="overflow-x: scroll;flex-wrap: unset;">
								<li class="nav-item" v-on:click="emojiReaction(status)">😂</li>
								<li class="nav-item" v-on:click="emojiReaction(status)">💯</li>
								<li class="nav-item" v-on:click="emojiReaction(status)">❤️</li>
								<li class="nav-item" v-on:click="emojiReaction(status)">🙌</li>
								<li class="nav-item" v-on:click="emojiReaction(status)">👏</li>
								<li class="nav-item" v-on:click="emojiReaction(status)">👌</li>
								<li class="nav-item" v-on:click="emojiReaction(status)">😍</li>
								<li class="nav-item" v-on:click="emojiReaction(status)">😯</li>
								<li class="nav-item" v-on:click="emojiReaction(status)">😢</li>
								<li class="nav-item" v-on:click="emojiReaction(status)">😅</li>
								<li class="nav-item" v-on:click="emojiReaction(status)">😁</li>
								<li class="nav-item" v-on:click="emojiReaction(status)">🙂</li>
								<li class="nav-item" v-on:click="emojiReaction(status)">😎</li>
								<li class="nav-item" v-on:click="emojiReaction(status)" v-for="e in emoji">{{e}}</li>
							</ul>
						</div>

						<div v-if="status.id == replyId && !status.comments_disabled" class="card-footer bg-white sticky-md-bottom p-0">
							<form class="border-0 rounded-0 align-middle" method="post" action="/i/comment" :data-id="status.id" data-truncate="false">
								<textarea class="form-control border-0 rounded-0" name="comment" placeholder="Add a comment…" autocomplete="off" autocorrect="off" style="height:56px;line-height: 18px;max-height:80px;resize: none; padding-right:4.2rem;" v-model="replyText"></textarea>
								<input type="button" value="Post" class="d-inline-block btn btn-link font-weight-bold reply-btn text-decoration-none" v-on:click.prevent="commentSubmit(status, $event)"/>
							</form>
						</div>
					</div>
				</div>
				<div v-if="!loading && feed.length > 0">
					<div class="card">
						<div class="card-body">
							<infinite-loading @infinite="infiniteTimeline" :distance="800">
							<div slot="no-more" class="font-weight-bold">No more posts to load</div>
							<div slot="no-results" class="font-weight-bold">No posts found</div>
							</infinite-loading>
						</div>
					</div>
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
		</div>

		<div v-if="!modes.distractionFree" class="col-md-4 col-lg-4 pt-2 my-3 order-1 order-md-2 d-none d-md-block">
			<div class="position-sticky" style="top:68px;">
				<div class="mb-4">
					<div class="">
						<div class="">
							<div class="media d-flex align-items-center">
								<a :href="profile.url">
									<img class="mr-3 rounded-circle box-shadow" :src="profile.avatar || '/storage/avatars/default.png'" alt="avatar" width="64px" height="64px">
								</a>
								<div class="media-body d-flex justify-content-between word-break" >
									<div>
										<p class="mb-0 px-0 font-weight-bold"><a :href="profile.url" class="text-dark">{{profile.username || 'loading...'}}</a></p>
										<p class="my-0 text-muted pb-0">{{profile.display_name || 'loading...'}}</p>
									</div>
									<div class="ml-2">
										<a class="text-muted" href="/settings/home"><i class="fas fa-cog fa-lg"></i></a>
									</div>
								</div>
							</div>
						</div>
						<!-- <div class="card-footer bg-white py-1 d-none">
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
						</div> -->
					</div>
				</div>

				<div v-show="modes.notify == true" class="mb-4">
					<notification-card></notification-card>
				</div>

				<div v-show="showSuggestions == true && suggestions.length && config.ab && config.ab.rec == true" class="mb-4">
					<div class="card">
						<div class="card-header bg-white d-flex align-items-center justify-content-between">
							<a class="small text-muted cursor-pointer" href="#" @click.prevent="refreshSuggestions" ref="suggestionRefresh"><i class="fas fa-sync-alt"></i></a>
							<div class="small text-dark text-uppercase font-weight-bold">Suggestions</div>
							<div class="small text-muted cursor-pointer" v-on:click="hideSuggestions"><i class="fas fa-times"></i></div>
						</div>
						<div class="card-body pt-0">
							<div v-for="(rec, index) in suggestions" class="media align-items-center mt-3">
								<a :href="'/'+rec.username">
									<img :src="rec.avatar" width="32px" height="32px" class="rounded-circle mr-3">
								</a>
								<div class="media-body">
									<p class="mb-0 font-weight-bold small">
										<a :href="'/'+rec.username" class="text-decoration-none text-dark">
											{{rec.username}}
										</a>
									</p>
									<p class="mb-0 small text-muted">{{rec.message}}</p>
								</div>
								<a class="font-weight-bold small" href="#" @click.prevent="expRecFollow(rec.id, index)">Follow</a>
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
							<a href="http://pixelfed.org" class="text-muted" rel="noopener" title="" data-toggle="tooltip">Powered by Pixelfed</a>
						</p>
					</div>
				</footer>
			</div>
		</div>
	</div>
<!--   <b-modal ref="followingModal"
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
          <a class="btn btn-outline-secondary btn-sm" href="#" @click.prevent="followModalAction(user.id, index, 'following')">Unfollow</a>
        </div>
      </div>
      <div v-if="following.length == 0" class="list-group-item border-0">
      	<div class="list-group-item border-0">
      		<p class="p-3 text-center mb-0 lead">You are not following anyone.</p>
      	</div>
      </div>
      <div v-if="following.length != 0 && followingMore" class="list-group-item text-center" v-on:click="followingLoadMore()">
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
  </b-modal> -->
  <b-modal
  	id="lightbox"
  	ref="lightboxModal"
  	hide-header
  	hide-footer
  	centered
  	size="lg"
  	body-class="p-0"
  	>
  	<div v-if="lightboxMedia" :class="lightboxMedia.filter_class">
  		<img :src="lightboxMedia.url" class="img-fluid" style="min-height: 100%; min-width: 100%">
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
	.reply-btn {
		position: absolute;
		bottom: 12px;
		right: 20px;
		width: 60px;
		text-align: center;
		border-radius: 0 3px 3px 0;
	}
	.emoji-reactions .nav-item {
		font-size: 1.2rem;
		padding: 9px;
		cursor: pointer;
	}
	.emoji-reactions::-webkit-scrollbar {
		width: 0px;
		height: 0px;
		background: transparent;
	}
</style>

<script type="text/javascript">
	export default {
		props: ['scope'],
		data() {
			return {
				ids: [],
				config: {},
				page: 2,
				feed: [],
				profile: {},
				min_id: 0,
				max_id: 0,
				stories: {},
				suggestions: {},
				loading: true,
				replies: [],
				replyId: null,
				modes: {
					'mod': false,
					'dark': false,
					'notify': true,
					'distractionFree': false
				},
				followers: [],
				followerCursor: 1,
				followerMore: true,
				following: [],
				followingCursor: 1,
				followingMore: true,
				lightboxMedia: false,
				showSuggestions: false,
				showReadMore: true,
				replyStatus: {},
				replyText: '',
				emoji: ['😀','🤣','😃','😄','😆','😉','😊','😋','😘','😗','😙','😚','🤗','🤩','🤔','🤨','😐','😑','😶','🙄','😏','😣','😥','😮','🤐','😪','😫','😴','😌','😛','😜','😝','🤤','😒','😓','😔','😕','🙃','🤑','😲','🙁','😖','😞','😟','😤','😭','😦','😧','😨','😩','🤯','😬','😰','😱','😳','🤪','😵','😡','😠','🤬','😷','🤒','🤕','🤢','🤮','🤧','😇','🤠','🤡','🤥','🤫','🤭','🧐','🤓','😈','👿','👹','👺','💀','👻','👽','🤖','💩','😺','😸','😹','😻','😼','😽','🙀','😿','😾','🤲','👐','🤝','👍','👎','👊','✊','🤛','🤜','🤞','✌️','🤟','🤘','👈','👉','👆','👇','☝️','✋','🤚','🖐','🖖','👋','🤙','💪','🖕','✍️','🙏','💍','💄','💋','👄','👅','👂','👃','👣','👁','👀','🧠','🗣','👤','👥']
			}
		},

		beforeMount() {
			axios.get('/api/v2/config')
			.then(res => {
				this.config = res.data;
				this.fetchProfile();
				this.fetchTimelineApi();

				// if(this.config.announcement.enabled == true) {
				// 	let msg = $('<div>')
				// 	.addClass('alert alert-warning mb-0 rounded-0 text-center font-weight-bold')
				// 	.html(this.config.announcement.message);
				// 	$('body').prepend(msg);
				// }
			});
		},

		mounted() {
			if($('link[data-stylesheet="dark"]').length != 0) {
				this.modes.dark = true;
			}

			if(localStorage.getItem('pf_metro_ui.exp.rec') == 'false') {
				this.showSuggestions = false;
			} else {
				this.showSuggestions = true;
			}

			if(localStorage.getItem('pf_metro_ui.exp.rm') == 'false') {
				this.showReadMore = false;
			} else {
				this.showReadMore = true;
			}

			if(localStorage.getItem('pf_metro_ui.exp.df') == 'true') {
				this.modes.distractionFree = true;
			} else {
				this.modes.distractionFree = false;
			}

			this.$nextTick(function () {
				$('[data-toggle="tooltip"]').tooltip()
			});
		},

		updated() {
			if(this.showReadMore == true) {
				pixelfed.readmore();
			}
		},

		methods: {
			fetchProfile() {
				axios.get('/api/v1/accounts/verify_credentials').then(res => {
					this.profile = res.data;
					if(this.profile.is_admin == true) {
						this.modes.mod = true;
					}
					$('.profile-card .loader').addClass('d-none');
					$('.profile-card .contents').removeClass('d-none');
					$('.profile-card .card-footer').removeClass('d-none');
					this.expRec();
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
						limit: 6
					}
				}).then(res => {
					let data = res.data;
					this.feed.push(...data);
					let ids = data.map(status => status.id);
					this.ids = ids;
					this.min_id = Math.max(...ids);
					this.max_id = Math.min(...ids);
					$('.timeline .pagination').removeClass('d-none');
					this.loading = false;
				}).catch(err => {
				});
			},

			infiniteTimeline($state) {
				if(this.loading) {
					return;
				}
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
						limit: 6
					},
				}).then(res => {
					if (res.data.length && this.loading == false) {
						let data = res.data;
						let self = this;
						data.forEach(d => {
							if(self.ids.indexOf(d.id) == -1) {
								self.feed.push(d);
								self.ids.push(d.id);
							} 
						});
						this.min_id = Math.max(...this.ids);
						this.max_id = Math.min(...this.ids);
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
						let ids = data.map(status => status.id);
						this.min_id = Math.min(...ids);
						if(this.page == 1) {
							this.max_id = Math.max(...ids);
						}
						this.feed.push(...data);
						this.page += 1;
						this.loading = false;
						event.target.innerText = 'Load more posts';
					} else {
					}
				});
			},

			reportUrl(status) {
				let type = status.in_reply_to ? 'comment' : 'post';
				let id = status.id;
				return '/i/report?type=' + type + '&id=' + id;
			},

			commentFocus(status, $event) {
				if(this.replyId == status.id || status.comments_disabled) {
					return;
				}
				this.replies = {};
				this.replyStatus = {};
				this.replyText = '';
				this.replyId = status.id;
				this.replyStatus = status;
				this.fetchStatusComments(status, '');
			},

			likeStatus(status) {
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
					let data = res.data.filter(res => {
						return res.sensitive == false;
					});
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
				if($('body').hasClass('loggedIn') == false || this.ownerOrAdmin(status) == false) {
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
					})
					swal('Success', 'You have successfully deleted this post', 'success');
				}).catch(err => {
					swal('Error', 'Something went wrong. Please try again later.', 'error');
				});
			},

			commentSubmit(status, $event) {
				let id = status.id;
				let comment = this.replyText;
				axios.post('/i/comment', {
					item: id,
					comment: comment
				}).then(res => {
					this.replyText = '';
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
			},

			expLc(status) {
				if(this.config.ab.lc == false) {
					return true;
				}
				if(this.statusOwner(status) == true) {
					return true;
				}
				return false;
			},

			expRec() {
				if(this.config.ab.rec == false) {
					return;
				}
				axios.get('/api/local/exp/rec')
				.then(res => {
					this.suggestions = res.data;
				})
			},

			expRecFollow(id, index) {
				if(this.config.ab.rec == false) {
					return;
				}

				axios.post('/i/follow', {
						item: id
				}).then(res => {
					this.suggestions.splice(index, 1);
				}).catch(err => {
					if(err.response.data.message) {
						swal('Error', err.response.data.message, 'error');
					}
				});
			},

			followModalAction(id, index, type = 'following') {
				axios.post('/i/follow', {
						item: id
				}).then(res => {
					if(type == 'following') {
						this.following.splice(index, 1);
					}
				}).catch(err => {
					if(err.response.data.message) {
						swal('Error', err.response.data.message, 'error');
					}
				});
			},

			owner(status) {
				return this.profile.id === status.account.id;
			},

			admin() {
				return this.profile.is_admin == true;
			},

			ownerOrAdmin(status) {
				return this.owner(status) || this.admin();
			},

			hideSuggestions() {
				localStorage.setItem('pf_metro_ui.exp.rec', false);
				this.showSuggestions = false;
			},

			emojiReaction(status) {
				let em = event.target.innerText;
				if(this.replyText.length == 0) {
					this.replyText = em + ' ';
					$('textarea[name="comment"]').focus();
				} else {
					this.replyText += em + ' ';
					$('textarea[name="comment"]').focus();
				}
			}, 

			refreshSuggestions() {
				let el = event.target.parentNode;
				if(el.classList.contains('disabled') == true) {
					return;
				}
				axios.get('/api/local/exp/rec', {
					params: {
						refresh: true
					}
				})
				.then(res => {
					this.suggestions = res.data;

					if (el.classList) {
						el.classList.add('disabled');
						el.classList.add('text-light');
					}
					else {
						el.className += ' ' + 'disabled text-light';
					}
					setTimeout(function() {
						el.setAttribute('href', '#');
						if (el.classList) {
							el.classList.remove('disabled');
							el.classList.remove('text-light');
						}
						else {
							el.className = el.className.replace(new RegExp('(^|\\b)' + className.split(' ').join('|') + '(\\b|$)', 'gi'), 'disabled text-light');
						}
					}, 10000);
				});
			}
		}
	}
</script>
