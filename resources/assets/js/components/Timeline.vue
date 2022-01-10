<template>
	<div>
		<div v-if="currentLayout === 'feed'" class="container">
			<div class="row">
				<div v-if="morePostsAvailable == true" class="col-12 mt-5 pt-3 mb-3 fixed-top">
					<p class="text-center">
						<button class="btn btn-dark px-4 rounded-pill font-weight-bold shadow" @click="syncNewPosts">Load New Posts</button>
					</p>
				</div>

				<div class="col-md-8 col-lg-8 px-0 mb-sm-3 timeline order-2 order-md-1">
					<div style="margin-top:-2px;">
						<story-component v-if="config.features.stories" :scope="scope"></story-component>
					</div>
					<div class="pt-4">
						<div v-if="loading" class="text-center" style="padding-top:10px;">
							<div class="spinner-border" role="status">
								<span class="sr-only">Loading...</span>
							</div>
						</div>

						<div :data-status-id="status.id" v-for="(status, index) in feed" :key="`feed-${index}-${status.id}`">
							<div v-if="index == 1 && !loading && showPromo" class="">
								<div class="card rounded-0 shadow-none border border-top-0 py-5">
									<div class="card-body p-5 my-5">
										<h1>A New Experience Awaits</h1>
										<p class="lead">Try out an early release of our new design</p>
										<p class="mb-0 d-flex align-items-center">
											<a class="btn btn-primary font-weight-bold py-1 px-4 rounded-pill mr-4" href="/i/web">Try new UI</a>
											<a class="font-weight-bold text-muted" href="/" @click.prevent="hidePromo()">Hide</a>
										</p>
									</div>
								</div>
							</div>
							<!-- <div v-if="index == 0 && showTips && !loading" class="my-4 card-tips">
								<announcements-card v-on:show-tips="showTips = $event"></announcements-card>
							</div> -->

							<!-- <div v-if="index == 2 && showSuggestions == true && suggestions.length" class="card status-card rounded-0 shadow-none border">
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
														<img :src="rec.avatar" class="img-fluid rounded-circle cursor-pointer" width="45px" height="45px" onerror="this.onerror=null;this.src='/storage/avatars/default.png?v=2'" alt="avatar">
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
							</div> -->

							<!-- <div v-if="index == 4 && showHashtagPosts && hashtagPosts.length" class="card status-card rounded-0 shadow-none border border-top-0">
								<div class="card-header bg-white border-0 mb-0">
									<div class="d-flex align-items-center justify-content-between pt-2">
										<div></div>
										<div>
											<h6 class="text-muted lead font-weight-bold mb-0"><a :href="'/discover/tags/'+hashtagPostsName+'?src=tr'">#{{hashtagPostsName}}</a></h6>
										</div>
										<div class="cursor-pointer text-muted" v-on:click="showHashtagPosts = false"><i class="fas fa-times"></i></div>
									</div>
									<p class="small text-muted text-center mb-0">You follow this hashtag. <a href="/site/kb/hashtags">Learn more</a></p>
								</div>
								<div class="card-body row mx-0">
									<div v-for="(tag, index) in hashtagPosts" class="col-4 p-1 hashtag-post-square">
										<a class="card info-overlay card-md-border-0" :href="tag.status.url">
											<div class="square">
												<div v-if="tag.status.sensitive" class="square-content">
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
														onerror="this.onerror=null;this.src='/storage/no-preview.png'"
														/>
												</div>
											</div>
										</a>
									</div>
								</div>
							</div> -->

							<status-card
								:class="{ 'border-top': index === 0 }"
								:status="status"
								:reaction-bar="reactionBar"
								size="small"
								v-on:status-delete="deleteStatus"
								v-on:comment-focus="commentFocus"
								v-on:followed="followedAccount"
								v-on:unfollowed="unfollowedAccount"
							/>
						</div>

						<div v-if="!loading && feed.length">
							<div class="card rounded-0 border-top-0 status-card rounded-0 shadow-none border">
								<div class="card-body py-5 my-5">
									<infinite-loading @infinite="infiniteTimeline" :distance="800">
										<div slot="no-more">
											<div v-if="recentFeed">
												<p class="text-center"><i class="far fa-check-circle fa-8x text-lighter"></i></p>
												<p class="text-center h3 font-weight-light">You're All Caught Up!</p>
												<p class="text-center text-muted font-weight-light">You've seen all the new posts from the accounts you follow.</p>
												<p class="text-center mb-0">
													<a class="btn btn-link font-weight-bold px-4" href="/?a=vop">View Older Posts</a>
												</p>
												<p class="text-center mb-0">
													<a class="btn btn-link font-weight-bold px-4" href="/" @click.prevent="alwaysViewOlderPosts()">Always show older posts on this device</a>
												</p>
											</div>
											<div v-else>
												<p class="text-center h3 font-weight-light">You've reached the end of this feed</p>
												<p class="text-center mb-0">
													<a class="btn btn-link font-weight-bold px-4" href="/discover">Discover new posts and people</a>
												</p>
											</div>
										</div>
										<div slot="no-results">
											<div v-if="recentFeed">
												<p class="text-center"><i class="far fa-check-circle fa-8x text-lighter"></i></p>
												<p class="text-center h3 font-weight-light">You're All Caught Up!</p>
												<p class="text-center text-muted font-weight-light">You've seen all the new posts from the accounts you follow.</p>
												<p class="text-center mb-0">
													<a class="btn btn-link font-weight-bold px-4" href="/?a=vop">View Older Posts</a>
												</p>
												<p class="text-center mb-0">
													<a class="btn btn-link font-weight-bold px-4" href="/" @click.prevent="alwaysViewOlderPosts()">Always show older posts on this device</a>
												</p>
											</div>
											<div v-else>
												<p class="text-center h3 font-weight-light">You've reached the end of this feed</p>
												<p class="text-center mb-0">
													<a class="btn btn-link font-weight-bold px-4" href="/discover">Discover new posts and people</a>
												</p>
											</div>
										</div>
									</infinite-loading>
								</div>
							</div>
						</div>

						<div v-if="!loading && scope == 'home' && feed.length == 0">
							<div class="card rounded-0 mt-4 status-card rounded-0 shadow-none border">
								<div v-if="profile.following_count != '0'" class="card-body py-5 my-5">
									<p class="text-center"><i class="far fa-check-circle fa-8x text-lighter"></i></p>
									<p class="text-center h3 font-weight-light">You're All Caught Up!</p>
									<p class="text-center text-muted font-weight-light">You've seen all the new posts from the accounts you follow.</p>
									<p class="text-center mb-0">
										<a class="btn btn-link font-weight-bold px-4" href="/?a=vop">View Older Posts</a>
									</p>
									<p class="text-center mb-0">
										<a class="btn btn-link font-weight-bold px-4" href="/" @click.prevent="alwaysViewOlderPosts()">Always show older posts on this device</a>
									</p>
								</div>
								<div v-else class="card-body py-5 my-5">
									<p class="text-center"><i class="far fa-smile fa-8x text-lighter"></i></p>
									<p class="text-center h3 font-weight-light">Hello {{profile.username}}</p>
									<p class="text-center text-muted font-weight-light">Accounts you follow will appear in this feed.</p>
									<p class="text-center mb-0">
										<a class="btn btn-link font-weight-bold px-4" href="/discover">Discover new posts and people</a>
									</p>
								</div>
							</div>
						</div>

						<div v-if="!loading && scope == 'home' && recentFeed && discover_feed.length" class="pt-3">
							<p class="h5 font-weight-bold py-3 d-flex justify-content-between align-items-center">
								<span>Suggested Posts</span>
								<a href="/?a=vop" class="small font-weight-bold">Older Posts</a>
							</p>
						</div>

						<div
							 v-if="!loading && scope == 'home' && recentFeed && discover_feed.length"
							 :data-status-id="status.id"
							 v-for="(status, index) in discover_feed"
							 :key="`discover_feed-${index}-${status.id}`">

							<status-card
								:class="{'border-top': index === 0}"
								:status="status"
								:recommended="true" />
						</div>

						<div v-if="!loading && emptyFeed && scope !== 'home'">
							<div class="card rounded-0 mt-3 status-card rounded-0 shadow-none border">
								<div class="card-body py-5 my-5">
									<p class="text-center"><i class="fas fa-battery-empty fa-8x text-lighter"></i></p>
									<p class="text-center h3 font-weight-light">empty_timeline.jpg</p>
									<p class="text-center text-muted font-weight-light">We cannot find any posts for this timeline.</p>
									<p class="text-center mb-0">
										<a class="btn btn-link font-weight-bold px-4" href="/discover">Discover new posts and people</a>
									</p>
								</div>
							</div>
						</div>
					</div>
				</div>
				<div class="col-md-4 col-lg-4 my-4 order-1 order-md-2 d-none d-md-block">
					<div>

						<div class="mb-4">
							<div v-show="!loading" class="">
								<div class="pb-2">
									<div class="media d-flex align-items-center">
										<a :href="!userStory ? profile.url : '/stories/' + profile.acct" class="mr-3">
											<!-- <img class="mr-3 rounded-circle box-shadow" :src="profile.avatar || '/storage/avatars/default.png'" alt="avatar" width="64px" height="64px" onerror="this.onerror=null;this.src='/storage/avatars/default.png?v=2'"> -->
											<div v-if="userStory" class="has-story cursor-pointer shadow-sm" @click="storyRedirect()">
												<img class="rounded-circle box-shadow" :src="profile.avatar" width="64px" height="64px" onerror="this.onerror=null;this.src='/storage/avatars/default.png?v=2'" alt="avatar">
											</div>
											<div v-else>
												<img class="rounded-circle box-shadow" :src="profile.avatar" width="64px" height="64px" onerror="this.onerror=null;this.src='/storage/avatars/default.png?v=2'" alt="avatar">
											</div>
										</a>
										<div class="media-body d-flex justify-content-between word-break" >
											<div>
												<p class="mb-0 px-0 font-weight-bold"><a :href="profile.url" class="text-dark">{{profile.username || 'loading...'}}</a></p>
												<p class="my-0 text-muted pb-0">{{profile.display_name || 'loading...'}}</p>
											</div>
											<div class="ml-2">
												<a class="text-muted" href="/settings/home">
													<i class="fas fa-cog fa-lg"></i>
													<span class="sr-only">User Settings</span>
												</a>
											</div>
										</div>
									</div>
								</div>
								<!-- <div class="card-footer bg-transparent border-top mt-2 py-1">
									<div class="d-flex justify-content-between text-center">
										<span class="cursor-pointer" @click="redirect(profile.url)">
											<p class="mb-0 font-weight-bold">{{formatCount(profile.statuses_count)}}</p>
											<p class="mb-0 small text-muted">Posts</p>
										</span>
										<span class="cursor-pointer" @click="redirect(profile.url+'?md=followers')">
											<p class="mb-0 font-weight-bold">{{formatCount(profile.followers_count)}}</p>
											<p class="mb-0 small text-muted">Followers</p>
										</span>
										<span class="cursor-pointer" @click="redirect(profile.url+'?md=following')">
											<p class="mb-0 font-weight-bold">{{formatCount(profile.following_count)}}</p>
											<p class="mb-0 small text-muted">Following</p>
										</span>
									</div>
								</div> -->
							</div>
							<div class="card-footer bg-transparent border-0 pt-0 pb-1">
                                <div class="d-flex justify-content-between text-center">
                                    <span class="cursor-pointer" @click="redirect(profile.url)">
                                        <p class="mb-0 font-weight-bold">{{formatCount(profile.statuses_count)}}</p>
                                        <p class="mb-0 small text-muted">Posts</p>
                                    </span>
                                    <span class="cursor-pointer" @click="redirect(profile.url+'?md=followers')">
                                        <p class="mb-0 font-weight-bold">{{formatCount(profile.followers_count)}}</p>
                                        <p class="mb-0 small text-muted">Followers</p>
                                    </span>
                                    <span class="cursor-pointer" @click="redirect(profile.url+'?md=following')">
                                        <p class="mb-0 font-weight-bold">{{formatCount(profile.following_count)}}</p>
                                        <p class="mb-0 small text-muted">Following</p>
                                    </span>
                                </div>
                            </div>
						</div>

						<div v-show="modes.notify == true && !loading" class="mb-4">
							<notification-card></notification-card>
						</div>

						<div v-show="showSuggestions == true && suggestions.length && config.ab && config.ab.rec == true" class="mb-4">
							<div class="card shadow-none border">
								<div class="card-header bg-white d-flex align-items-center justify-content-between">
									<a class="small text-muted cursor-pointer" href="#" @click.prevent="refreshSuggestions" ref="suggestionRefresh"><i class="fas fa-sync-alt"></i></a>
									<div class="small text-dark text-uppercase font-weight-bold">Suggestions</div>
									<div class="small text-muted cursor-pointer" v-on:click="hideSuggestions"><i class="fas fa-times"></i></div>
								</div>
								<div class="card-body pt-0">
									<div v-for="(rec, index) in suggestions" class="media align-items-center mt-3">
										<a :href="'/'+rec.username">
											<img :src="rec.avatar" width="32px" height="32px" class="rounded-circle mr-3" onerror="this.onerror=null;this.src='/storage/avatars/default.png?v=2'" alt="avatar">
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
							<div class="container px-0 pb-5">
								<p class="mb-2 small text-justify">
									<a href="/site/about" class="text-lighter pr-2">About</a>
									<a href="/site/help" class="text-lighter pr-2">Help</a>
									<a href="/site/language" class="text-lighter pr-2">Language</a>
									<a href="/discover/places" class="text-lighter pr-2">Places</a>
									<a href="/site/privacy" class="text-lighter pr-2">Privacy</a>
									<a href="/site/terms" class="text-lighter pr-2">Terms</a>
								</p>
								<p class="mb-0 text-uppercase text-muted small">
									<a href="https://pixelfed.org" class="text-lighter" rel="noopener" title="" data-toggle="tooltip">Powered by Pixelfed</a>
								</p>
							</div>
						</footer>
					</div>
				</div>
			</div>
		</div>

		<comment-card
			v-if="replyStatus && replyStatus.hasOwnProperty('id')"
			:status="replyStatus"
			:profile="profile"
			v-on:current-layout="setCurrentLayout"
		/>

		<div class="modal-stack">
			<b-modal ref="replyModal"
				id="ctx-reply-modal"
				hide-footer
				centered
				rounded
				:title-html="replyStatus.account ? 'Reply to <span class=text-dark>' + replyStatus.account.username + '</span>' : ''"
				title-tag="p"
				title-class="font-weight-bold text-muted"
				size="md"
				body-class="p-2 rounded">
				<div>
					<vue-tribute :options="tributeSettings">
						<textarea
							class="form-control replyModalTextarea"
							rows="4"
							v-model="replyText">
						</textarea>
					</vue-tribute>

					<div class="border-top border-bottom my-2">
						<ul class="nav align-items-center emoji-reactions" style="overflow-x: scroll;flex-wrap: unset;">
							<li class="nav-item" v-on:click="emojiReaction(status)" v-for="e in emoji">{{e}}</li>
						</ul>
					</div>
					<div class="d-flex justify-content-between align-items-center">
						<div>
							<span class="pl-2 small text-muted font-weight-bold text-monospace">
								<span :class="[replyText.length > config.uploader.max_caption_length ? 'text-danger':'text-dark']">{{replyText.length > config.uploader.max_caption_length ? config.uploader.max_caption_length - replyText.length : replyText.length}}</span>/{{config.uploader.max_caption_length}}
							</span>
						</div>
						<div class="d-flex align-items-center">
							<div class="custom-control custom-switch mr-3">
								<input type="checkbox" class="custom-control-input" id="replyModalCWSwitch" v-model="replyNsfw">
								<label :class="[replyNsfw ? 'custom-control-label font-weight-bold text-dark':'custom-control-label text-lighter']" for="replyModalCWSwitch">Mark as NSFW</label>
							</div>

							<button class="btn btn-primary btn-sm py-2 px-4 lead text-uppercase font-weight-bold" v-on:click.prevent="commentSubmit(status, $event)" :disabled="replyText.length == 0">
								{{replySending == true ? 'POSTING' : 'POST'}}
							</button>
						</div>
					</div>
				</div>
			</b-modal>
			<b-modal ref="ctxStatusModal"
				id="ctx-status-modal"
				hide-header
				hide-footer
				centered
				rounded
				size="xl"
				body-class="list-group-flush p-0 m-0 rounded">
				<!-- <post-component
					v-if="ctxMenuStatus"
					:status-template="ctxMenuStatus.pf_type"
					:status-id="ctxMenuStatus.id"
					:status-username="ctxMenuStatus.account.username"
					:status-url="ctxMenuStatus.url"
					:status-profile-url="ctxMenuStatus.account.url"
					:status-avatar="ctxMenuStatus.account.avatar"
					:status-profile-id="ctxMenuStatus.account.id"
					profile-layout="metro">
				</post-component> -->
			</b-modal>
		</div>
	</div>
</template>

<script type="text/javascript">
	import VueTribute from 'vue-tribute'
	import StatusCard from './partials/StatusCard.vue';
	import CommentCard from './partials/CommentCard.vue';

	export default {
		props: ['scope', 'layout'],

		components: {
			VueTribute,
			StatusCard,
			CommentCard
		},

		data() {
			return {
				ids: [],
				config: window.App.config,
				page: 2,
				feed: [],
				profile: {},
				min_id: 0,
				max_id: 0,
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
				showSuggestions: true,
				showReadMore: true,
				replyStatus: {},
				replyText: '',
				replyNsfw: false,
				emoji: [],
				showHashtagPosts: false,
				hashtagPosts: [],
				hashtagPostsName: '',
				copiedEmbed: false,
				showTips: true,
				userStory: false,
				replySending: false,
				morePostsAvailable: false,
				mpCount: 0,
				mpData: false,
				mpInterval: 15000,
				mpEnabled: false,
				mpPoller: null,
				confirmModalTitle: 'Are you sure?',
				confirmModalIdentifer: null,
				confirmModalType: false,
				currentLayout: 'feed',
				pagination: {},
				tributeSettings: {
					collection: [
						{
							trigger: '@',
							menuShowMinLength: 2,
							values: (function (text, cb) {
								let url = '/api/compose/v0/search/mention';
								axios.get(url, { params: { q: text }})
								.then(res => {
									cb(res.data);
								})
								.catch(err => {
									console.log(err);
								})
							})
						},
						{
							trigger: '#',
							menuShowMinLength: 2,
							values: (function (text, cb) {
								let url = '/api/compose/v0/search/hashtag';
								axios.get(url, { params: { q: text }})
								.then(res => {
									cb(res.data);
								})
								.catch(err => {
									console.log(err);
								})
							})
						}
					]
				},
				discover_min_id: 0,
				discover_max_id: 0,
				discover_feed: [],
				recentFeed: false,
				recentFeedMin: null,
				recentFeedMax: null,
				reactionBar: true,
				emptyFeed: false,
				filters: [],
				showPromo: false,
			}
		},

		beforeMount() {
			this.fetchProfile();
		},

		mounted() {
			// todo: release after dark mode updates
			/* if(window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches || $('link[data-stylesheet="dark"]').length != 0) {
				this.modes.dark = true;

				let el = document.querySelector('link[data-stylesheet="light"]');
				el.setAttribute('href', '/css/appdark.css?id=' + Date.now());
				el.setAttribute('data-stylesheet', 'dark');
			}*/

			if(this.config.ab.spa === true) {
				this.showPromo = localStorage.getItem('pf_metro_ui.exp.spa') == 'false' ? false : true;
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

			if(localStorage.getItem('metro-tips') == 'false') {
				this.showTips = false;
			}

			this.$nextTick(() => {
				$('[data-toggle="tooltip"]').tooltip();
				let u = new URLSearchParams(window.location.search);
				if(u.has('a')) {
					switch(u.get('a')) {
						case 'co':
							$('#composeModal').modal('show');
						break;
					}
				}

				if(this.scope != 'home') {
					axios.get('/api/pixelfed/v2/filters')
					.then(res => {
						this.filters = res.data;
						this.fetchTimelineApi();
					});
				} else {
					this.fetchTimelineApi();
				}
			});
		},

		updated() {
			if(this.showReadMore == true) {
				pixelfed.readmore();
			}
		},

		methods: {
			fetchProfile() {
				axios.get('/api/pixelfed/v1/accounts/verify_credentials').then(res => {
					this.profile = res.data;
					if(this.profile.is_admin == true) {
						this.modes.mod = true;
					}
					window._sharedData.curUser = res.data;
					window.App.util.navatar();
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
					apiUrl = '/api/pixelfed/v1/timelines/home';
					break;

					case 'local':
					apiUrl = '/api/pixelfed/v1/timelines/public';
					break;

					case 'network':
					apiUrl = '/api/pixelfed/v1/timelines/network';
					break;
				}
				axios.get(apiUrl, {
					params: {
						max_id: this.max_id,
						limit: 12,
						recent_feed: this.recentFeed
					}
				}).then(res => {
					let data = res.data;

					if(!data || !data.length) {
						this.loading = false;
						this.emptyFeed = true;
						return;
					}

					if(this.filters.length) {
						data = data.filter(d => {
							return this.filters.includes(d.account.id) == false;
						});
					}

					this.feed.push(...data);
					let ids = data.map(status => status.id);
					this.ids = ids;
					this.min_id = Math.max(...ids).toString();
					this.max_id = Math.min(...ids).toString();
					this.loading = false;
					this.$nextTick(() => {
						this.hasStory();
					});

					setTimeout(function() {
						document.querySelectorAll('.timeline .card-body .comments .comment-body a').forEach(function(i, e) {
							i.href = App.util.format.rewriteLinks(i);
						});
					}, 500);
				}).catch(err => {
					swal(
						'Oops, something went wrong',
						'Please reload the page.',
						'error'
						);
				});
			},

			infiniteTimeline($state) {
				if(this.loading) {
					$state.complete();
					return;
				}
				if(this.page > 40) {
					this.loading = false;
					$state.complete();
				}

				let apiUrl = false;

				switch(this.scope) {
					case 'home':
					apiUrl = '/api/pixelfed/v1/timelines/home';
					break;

					case 'local':
					apiUrl = '/api/pixelfed/v1/timelines/public';
					break;

					case 'network':
					apiUrl = '/api/pixelfed/v1/timelines/network';
					break;
				}

				axios.get(apiUrl, {
					params: {
						max_id: this.max_id,
						limit: 6,
						recent_feed: this.recentFeed
					},
				}).then(res => {
					if (res.data.length && this.loading == false) {
						let data = res.data;
						let self = this;
						let vids = [];
						if(self.recentFeed && self.ids.indexOf(data[0].id) != -1) {
							this.loading = false;
							$state.complete();
							return;
						}
						data.forEach((d, index) => {
							if(self.ids.indexOf(d.id) == -1) {
								self.feed.push(d);
								self.ids.push(d.id);
								// vids.push({
								//  sid: d.id,
								//  pid: d.account.id
								// });
							}
						});
						this.min_id = Math.max(...this.ids).toString();
						this.max_id = Math.min(...this.ids).toString();
						this.page += 1;
						$state.loaded();
						this.loading = false;
						// axios.post('/api/status/view', {
						//  '_v': vids,
						// });
					} else {
						$state.complete();
					}
				}).catch(err => {
					this.loading = false;
					$state.complete();
				});
			},

			redirect(url) {
				window.location.href = url;
				return;
			},

			expRec() {
				//return;

				if(this.config.ab.rec == false) {
					return;
				}
				axios.get('/api/local/exp/rec')
				.then(res => {
					this.suggestions = res.data;
				})
			},

			expRecFollow(id, index) {
				return;

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
				return;

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
			},

			fetchHashtagPosts() {
				axios.get('/api/local/discover/tag/list')
				.then(res => {
					let tags = res.data;
					if(tags.length == 0) {
						return;
					}
					let hashtag = tags[Math.floor(Math.random(), tags.length)];
					this.hashtagPostsName = hashtag;
					axios.get('/api/v2/discover/tag', {
						params: {
							hashtag: hashtag
						}
					}).then(res => {
						if(res.data.tags.length > 3) {
							this.showHashtagPosts = true;
							this.hashtagPosts = res.data.tags.splice(0,9);
						}
					})
				})
			},

			commentFocus(status, $event) {
                if(status.comments_disabled) {
                    return;
                }

                // if(this.status && this.status.id == status.id) {
                //  this.$refs.replyModal.show();
                //  return;
                // }

                this.status = status;
                this.replies = {};
                this.replyStatus = {};
                this.replyText = '';
                this.replyId = status.id;
                this.replyStatus = status;
                // this.$refs.replyModal.show();
                this.fetchStatusComments(status, '');

                $('nav').hide();
                $('footer').hide();
                $('.mobile-footer-spacer').attr('style', 'display:none !important');
                $('.mobile-footer').attr('style', 'display:none !important');
                this.currentLayout = 'comments';
                window.history.pushState({}, '', this.statusUrl(status));
                return;
            },

            fetchStatusComments(status, card) {
				let url = '/api/v2/comments/'+status.account.id+'/status/'+status.id;
				axios.get(url)
				.then(response => {
					let self = this;
					this.replies = _.reverse(response.data.data);
					this.pagination = response.data.meta.pagination;
					if(this.replies.length > 0) {
						$('.load-more-link').removeClass('d-none');
					}
					$('.postCommentsLoader').addClass('d-none');
					$('.postCommentsContainer').removeClass('d-none');
				}).catch(error => {
					if(!error.response) {
						$('.postCommentsLoader .lds-ring')
						.attr('style','width:100%')
						.addClass('pt-4 font-weight-bold text-muted')
						.text('An error occurred, cannot fetch comments. Please try again later.');
					} else {
						switch(error.response.status) {
							case 401:
							$('.postCommentsLoader .lds-ring')
							.attr('style','width:100%')
							.addClass('pt-4 font-weight-bold text-muted')
							.text('Please login to view.');
							break;
							default:
							$('.postCommentsLoader .lds-ring')
							.attr('style','width:100%')
							.addClass('pt-4 font-weight-bold text-muted')
							.text('An error occurred, cannot fetch comments. Please try again later.');
							break;
						}
					}
				});
			},

			statusUrl(status) {
				if(status.local == true) {
					return status.url;
				}

				return '/i/web/post/_/' + status.account.id + '/' + status.id;
			},

			profileUrl(status) {
				if(status.local == true) {
					return status.account.url;
				}

				return '/i/web/profile/_/' + status.account.id;
			},

			formatCount(count) {
				return App.util.format.count(count);
			},

			hasStory() {
				axios.get('/api/web/stories/v1/exists/'+this.profile.id)
				.then(res => {
					this.userStory = res.data;
				})
			},

			// real time watcher
			rtw() {
				this.mpPoller = setInterval(() => {
					let apiUrl = false;
					this.mpCount++;
					if(this.mpCount > 10) {
						this.mpInterval = 30000;
					}
					if(this.mpCount > 50) {
						this.mpInterval = (5 * 60 * 1000);
					}
					switch(this.scope) {
						case 'home':
						apiUrl = '/api/pixelfed/v1/timelines/home';
						break;

						case 'local':
						apiUrl = '/api/pixelfed/v1/timelines/public';
						break;

						case 'network':
						apiUrl = '/api/pixelfed/v1/timelines/network';
						break;
					}
					axios.get(apiUrl, {
						params: {
							max_id: 0,
							limit: 20,
							recent_feed: this.recentFeed
						}
					}).then(res => {
						let self = this;
						let tids = this.feed.map(status => status.id);
						let data = res.data.filter(d => {
							return d.id > self.min_id && tids.indexOf(d.id) == -1;
						});
						let ids = data.map(status => status.id);
						let max = Math.max(...ids).toString();
						let newer = max > this.min_id;
						if(newer) {
							this.morePostsAvailable = true;
							this.mpData = data;
						}
					});
				}, this.mpInterval);
			},

			syncNewPosts() {
				let self = this;
				let data = this.mpData;
				let ids = data.map(s => s.id);
				this.min_id = Math.max(...ids).toString();
				this.max_id = Math.min(...ids).toString();
				this.feed.unshift(...data);
				this.morePostsAvailable = false;
				this.mpData = null;
			},

			toggleReplies(reply) {
				if(reply.thread) {
					reply.thread = false;
				} else {
					if(reply.replies.length > 0) {
						reply.thread = true;
						return;
					}
					let url = '/api/v2/comments/'+reply.account.id+'/status/'+reply.id;
					axios.get(url)
					.then(response => {
						reply.replies = _.reverse(response.data.data);
						reply.thread = true;
					});
				}
			},

			replyFocus(e, index, prependUsername = false) {
				if($('body').hasClass('loggedIn') == false) {
					this.redirect('/login?next=' + encodeURIComponent(window.location.pathname));
					return;
				}
				if(this.status.comments_disabled) {
					return;
				}

				this.replyToIndex = index;
				this.replyingToId = e.id;
				this.replyingToUsername = e.account.username;
				this.reply_to_profile_id = e.account.id;
				let username = e.account.local ? '@' + e.account.username + ' '
				: '@' + e.account.acct + ' ';
				if(prependUsername == true) {
					this.replyText = username;
				}
				this.$refs.replyModal.show();
				setTimeout(function() {
					$('.replyModalTextarea').focus();
				}, 500);
			},

			alwaysViewOlderPosts() {
				// Set Feed:Always View Older Posts
				window.localStorage.setItem('pf.feed:avop', 'always');
				window.location.href = '/';
			},

			setCurrentLayout(layout) {
				this.currentLayout = layout;
			},

			deleteStatus(status) {
				this.feed = this.feed.filter(s => {
					return s.id != status;
				});
			},

			followedAccount(id) {
				this.feed = this.feed.map(s => {
					if(s.account.id == id) {
						if(s.hasOwnProperty('relationship') && s.relationship.following == false) {
							s.relationship.following = true;
						}
					}
					return s;
				});
			},

			unfollowedAccount(id) {
				this.feed = this.feed.map(s => {
					if(s.account.id == id) {
						if(s.hasOwnProperty('relationship') && s.relationship.following == true) {
							s.relationship.following = false;
						}
					}
					return s;
				});
			},

			hidePromo() {
				localStorage.setItem('pf_metro_ui.exp.spa', 'false');
				this.showPromo = false;
			}
		},

		beforeDestroy () {
			clearInterval(this.mpInterval);
		}
	}
</script>

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
	/*.reply-btn {
		position: absolute;
		bottom: 30px;
		right: 20px;
		width: 60px;
		text-align: center;
		font-size: 13px;
		border-radius: 0 3px 3px 0;
	}*/
	.reply-btn[disabled] {
		opacity: .3;
		color: #3897f0;
	}
	.replyModalTextarea {
		border: none;
		font-size: 18px;
		resize: none;
		white-space: pre-wrap;
		outline: none;
	}
	.has-story {
		width: 64px;
		height: 64px;
		border-radius: 50%;
		padding: 2px;
		background: radial-gradient(ellipse at 70% 70%, #ee583f 8%, #d92d77 42%, #bd3381 58%);
	}
	.has-story img {
		width: 60px;
		height: 60px;
		border-radius: 50%;
		padding: 3px;
		background: #fff;
	}
	.has-story.has-story-sm {
		width: 32px;
		height: 32px;
		border-radius: 50%;
		padding: 2px;
		background: radial-gradient(ellipse at 70% 70%, #ee583f 8%, #d92d77 42%, #bd3381 58%);
	}
	.has-story.has-story-sm img {
		width: 28px;
		height: 28px;
		border-radius: 50%;
		padding: 3px;
		background: #fff;
	}
	#ctx-reply-modal .form-control:focus {
		border: none;
		outline: 0;
		box-shadow: none;
	}
</style>
