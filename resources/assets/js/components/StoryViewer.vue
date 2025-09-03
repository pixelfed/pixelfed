<template>
<div
	class="story-viewer-component container mt-0 mt-md-5 bg-black">
	<button type="button" class="d-none d-md-block btn btn-link fixed-top" style="left: auto;right:0;" @click="backToFeed">
		<i class="fal fa-times-circle fa-2x text-lighter"></i>
	</button>

	<div v-if="!viewWarning" class="row d-flex justify-content-center align-items-center">
		<div class="d-none d-md-block col-md-1 cursor-pointer text-center" @click="prev">
			<div v-if="storyIndex > 0">
				<i class="fas fa-chevron-circle-left text-muted fa-2x"></i>
			</div>
		</div>
		<div v-if="!loading" class="col-12 col-md-6 rounded-lg">

			<div v-if="activeReactionEmoji" style="position: absolute;z-index: 999;" class="w-100 h-100 d-flex justify-content-center align-items-center">
				<div class="d-flex justify-content-center align-items-center rounded-pill shadow-lg" style="width: 120px;height: 30px;font-size:13px;background-color: rgba(0, 0, 0, 0.6);">
					<span class="text-lighter">{{  $t("story.reactionSent") }}</span>
				</div>
			</div>

			<div v-if="activeReply" style="position: absolute;z-index: 999;" class="w-100 h-100 d-flex justify-content-center align-items-center">
				<div class="d-flex justify-content-center align-items-center rounded-pill shadow-lg" style="width: 120px;height: 30px;font-size:13px;background-color: rgba(0, 0, 0, 0.6);">
					<span class="text-lighter">{{ $t("story.replySent")  }}</span>
				</div>
			</div>

			<transition name="fade">
			<div v-if="stories[storyIndex].type == 'photo'" class="media-slot rounded-lg" :key="'msl:'+storyIndex" :style="{ background: 'url(' + stories[storyIndex].url + ')' }"></div>

			<div v-else-if="stories[storyIndex].type == 'poll'" class="media-slot rounded-lg" :key="'msl:'+storyIndex" :style="{ background: 'linear-gradient(to right, #F27121, #E94057, #8A2387)' }"></div>

			<video
				v-else-if="stories[storyIndex].type == 'video'"
				:key="'plyr'+stories[storyIndex].id"
				id="playr"
				class="media-slot rounded-lg"
				style="object-fit: contain;"
				:muted="muted"
				loop
				autoplay
				no-controls>
				<source :src="stories[storyIndex].url" type="video/mp4">
			</video>
			</transition>

			<div class="story-viewer-component-card card bg-transparent border-0 shadow-none d-flex justify-content-center">
				<div class="card-body">
					<div class="px-0 top-overlay">
						<div class="pt-4 pt-md-3 px-4 d-flex">
							<div style="width: 100%;height:5px;" class="d-none bg-muted"></div>
							<div
								v-for="(story, index) in stories"
								:key="'sp:s'+index"
								v-on:click="gotoSlide(index)"
								class="w-100 cursor-pointer"
								:class="{ 'mr-2': index != stories.length - 1 }">
								<div
									class="progress w-100"
									style="z-index:3;height: 4px;"
									:style="{opacity: story.progress == 0 ? 0.7 : 0.8}">
									<div
										:key="'sp:si'+index"
										class="progress-bar bg-light"
										role="progressbar"
										:aria-valuenow="story.progress"
										aria-valuemin="0"
										aria-valuemax="100"
										:style="{
											width: story.progress +'%',
											transition: 'none !important'
										}">
									</div>
								</div>
							</div>
						</div>
						<div class="pt-4 px-4 media align-items-center">
							<img :src="avatar" width="32" height="32" class="rounded-circle mr-2" onerror="this.onerror=null;this.src='/storage/avatars/default.png?v=2'">
							<div class="media-body d-flex justify-content-between align-items-center">
								<div class="user-select-none d-flex align-items-center">
									<span v-if="account.local" class="text-white font-weight-bold mr-2">
										{{username}}
									</span>
									<span v-else class="text-white font-weight-bold mr-3 text-truncate" style="max-width:200px;">
										<span class="d-block mb-n2">{{account.username}}</span>
										<span class="small">{{account.domain}}</span>
									</span>
									<span class="text-white font-weight-light" style="font-size: 14px;">{{timeago(stories[storyIndex].created_at)}}</span>
									<span v-if="stories[storyIndex].type == 'poll'">
										<span class="btn btn-outline-light font-weight-light btn-sm px-1 rounded py-0 ml-2">POLL</span>
									</span>
								</div>
								<div>
									<button class="btn btn-link btn-sm text-white mr-0 px-1" @click.prevent="pause">
										<i :class="[ paused ? 'fa-play' : 'fa-pause' ]" class="fas fa-lg"></i>
									</button>

									<button v-if="stories[storyIndex].type == 'video'" class="btn btn-link text-white px-2" @click="toggleMute">
										<i :class="[ muted ? 'fa-volume-mute' : 'fa-volume-up' ]" class="fas fa-lg"></i>
									</button>

									<button @click="showMenu" class="btn btn-link text-white px-1">
										<i class="fas fa-ellipsis-h fa-lg"></i>
									</button>

									<button class="d-inline-block d-md-none btn btn-link text-white pl-1 pr-0" @click="backToFeed">
										<i class="far fa-times-circle fa-lg"></i>
									</button>
								</div>
							</div>
						</div>
					</div>
					<div @click="pause" style="height: 70vh;">
						<div v-if="stories[storyIndex].type == 'poll'" class="w-100 h-100 d-flex justify-content-center align-items-center">
							<div>
								<p
									class="text-white pb-5 text-break font-weight-lighter"
									:class="[stories[storyIndex].question.length < 60 ? 'h1' : 'h3']">
									{{stories[storyIndex].question}}
								</p>
								<div class="text-center mt-3">
									<div v-for="(option, index) in stories[storyIndex].options" class="mb-3">
										<button
											class="btn border px-4 py-3 text-uppercase btn-block"
											:class="[
												option.length < 14 ? 'btn-lg': '',
												index == stories[storyIndex].voted_index ? 'btn-light' : 'btn-outline-light'
											]"
											style="min-width: 300px;"
											:disabled="stories[storyIndex].voted || owner"
											@click="selectPollOption(index)">
											<span
												class="text-break"
												:class="[
													index == stories[storyIndex].voted_index ? 'option-red' : ''
												]">
												{{ option }}
											</span>
										</button>
										<p
											v-if="owner && pollResults.length"
											class="small text-left mt-1 text-light">
												{{ pollPercent(index) }}% - {{ pollResults[index] }} {{ pollResults[index] == 1 ? 'vote' : 'votes' }}
										</p>
									</div>
								</div>
								<div v-if="owner && !showingPollResults && pollResults.length == 0" class="mt-3 text-center">
									<button class="btn btn-light font-weight-bold" @click="showPollResults" :disabled="loadingPollResults">
										{{ loadingPollResults ? 'Loading...' : 'View Results' }}
									</button>
								</div>
							</div>
						</div>
					</div>
				</div>
				<div v-if="!owner && stories[storyIndex] && stories[storyIndex].can_reply" class="card-footer bg-transparent border-0">
					<div class="px-0 bottom-overlay">
						<div class="px-3 form-group d-flex">
							<input class="form-control bg-transparent border border-white rounded-pill text-white" :placeholder="'Reply to ' + username + '...'" v-model="composeText">
							<button class="btn btn-outline-light rounded-pill ml-2" @click="comment">
								SEND
							</button>
						</div>
					</div>
				</div>
			</div>
		</div>
		<div class="d-none d-md-block col-md-1 cursor-pointer text-center">
			<div v-if="(storyIndex + 1) < stories.length" @click="next">
				<i class="fas fa-chevron-circle-right text-muted fa-2x"></i>
			</div>
			<div v-if="(storyIndex + 1) == stories.length && owner" @click="addToStory">
				<i class="fal fa-plus-circle text-muted fa-2x"></i>
			</div>
		</div>
		<div v-if="loading" class="col-12 col-md-6 rounded-lg">
			<div class="card border-0 shadow-none d-flex justify-content-center" style="background: #000;height: 90vh;">
				<div class="card-body d-flex justify-content-center align-items-center">
					<div class="spinner-border text-lighter" role="status">
						<span class="sr-only">Loading...</span>
					</div>
				</div>
			</div>
		</div>
	</div>

	<div v-else class="row d-flex justify-content-center align-items-center">
		<div v-if="!loading" class="col-12 col-md-6 rounded-lg p-0">
			<div v-if="stories[storyIndex].type == 'photo'" class="media-slot rounded-lg" :key="'msl:'+storyIndex" :style="{ backgroundImage: 'url(' + stories[storyIndex].url + ')' }"></div>
			<div class="story-viewer-component-card card bg-transparent border-0 shadow-none d-flex justify-content-center" style="backdrop-filter: blur(40px) brightness(0.3); -webkit-backdrop-filter: blur(10px);">
				<div class="card-body">
					<div class="w-100 h-100 d-flex justify-content-center align-items-center">
						<div class="text-center">
							<img :src="profile.avatar" width="120" height="120" class="rounded-circle border mb-3 shadow">
							<p class="lead text-lighter mb-1">View as <span class="text-white">{{profile.username}}</span></p>
							<p class="text-lighter font-weight-lighter px-md-5 py-3">
								<span class="text-white font-weight-bold">{{account.acct}}</span> will be able to see that you viewed their story.
							</p>
							<button class="btn btn-outline-lighter rounded-pill py-1 font-weight-bold" @click="confirmViewStory">View Story</button>
							<button class="btn btn-outline-lighter rounded-pill py-1 font-weight-bold" @click="cancelViewStory">Cancel</button>
						</div>
					</div>
				</div>
			</div>
		</div>
	</div>

	<div class="modal-stack">
		<b-modal ref="ctxMenu"
			id="ctx-modal"
			hide-header
			hide-footer
			centered
			rounded
			size="sm"
			body-class="list-group-flush p-0 rounded">
			<div class="list-group text-center">
				<div v-if="owner" class="list-group-item rounded py-3">
					<div class="d-flex justify-content-between align-items-center font-weight-light">
						<span>{{ $t("story.expiresIn")}} {{timeahead(stories[storyIndex].expires_at)}}</span>
						<span>
							<span class="btn btn-light btn-sm font-weight-bold">
								<i class="fas fa-eye"></i>
								{{ stories[storyIndex].view_count }}
							</span>
						</span>
					</div>
				</div>
				<div v-if="!owner && stories[storyIndex] && stories[storyIndex].can_react" class="list-group-item rounded d-flex justify-content-between">
					<button
						v-for="e in reactionEmoji"
						class="btn btn-light rounded-pill py-1 px-2"
						style="font-size: 20px;"
						@click="react(e)">
						{{ e }}
					</button>
				</div>
				<div v-if="owner" class="list-group-item rounded cursor-pointer" @click="fetchViewers">{{ $t("story.viewers")}}</div>
				<div v-if="!owner" class="list-group-item rounded cursor-pointer" @click="ctxMenuReport">{{ $t("story.report")}}</div>
				<div v-if="owner" class="list-group-item rounded cursor-pointer" @click="deleteStory">{{ $t("story.delete")}}</div>
				<div class="list-group-item rounded cursor-pointer text-muted" @click="closeCtxMenu">{{ $t("story.close")}}</div>
			</div>
		</b-modal>

		<b-modal ref="viewersModal"
			id="viewers"
			title="Viewers"
			header-class="border-0"
			hide-footer
			centered
			rounded
			scrollable
			lazy
			size="sm"
			body-class="list-group-flush p-0 rounded">
			<div class="list-group" style="max-height: 40vh;">
				<div v-for="(profile, index) in viewers" class="list-group-item">
					<div class="media align-items-center">
						<img :src="profile.avatar" width="32" height="32" class="rounded-circle border mr-2">
						<div v-if="profile.local" class="media-body user-select-none">
							<p class="font-weight-bold mb-0">{{profile.username}}</p>
						</div>
						<div v-else class="media-body user-select-none">
							<p class="font-weight-bold mb-0">{{profile.username}}</p>
							<p class="mb-0 small mt-n1 text-muted">{{profile.acct.split('@')[1]}}</p>
						</div>
					</div>
				</div>
				<div v-if="viewers.length == 0" class="list-group-item text-center text-dark font-weight-light py-5">
					No viewers yet
				</div>
				<div v-if="viewersHasMore" class="list-group-item text-center border-bottom-0">
					<button class="btn btn-light font-weight-bold border rounded-pill" @click="viewersLoadMore">Load More</button>
				</div>
				<div class="list-group-item text-center rounded cursor-pointer text-muted" @click="closeViewersModal">{{ $t("story.close")}}</div>
			</div>
		</b-modal>

		<b-modal ref="ctxReport"
			id="ctx-report"
			hide-header
			hide-footer
			centered
			rounded
			size="sm"
			body-class="list-group-flush p-0 rounded">
			<p class="py-2 px-3 mb-0">
				<div class="text-center font-weight-bold text-danger">Report</div>
				<div class="small text-center text-muted">Select one of the following options</div>
			</p>
			<div class="list-group text-center">
				<div class="list-group-item rounded cursor-pointer font-weight-bold" @click="sendReport('spam')">Spam</div>
				<div class="list-group-item rounded cursor-pointer font-weight-bold" @click="sendReport('sensitive')">Sensitive Content</div>
				<div class="list-group-item rounded cursor-pointer font-weight-bold" @click="sendReport('abusive')">Abusive or Harmful</div>
				<div class="list-group-item rounded cursor-pointer font-weight-bold" @click="openCtxReportOtherMenu()">Other</div>
				<!-- <div class="list-group-item rounded cursor-pointer" @click="ctxReportMenuGoBack()">Go Back</div> -->
				<div class="list-group-item rounded cursor-pointer text-lighter" @click="ctxReportMenuGoBack()">Cancel</div>
			</div>
		</b-modal>

		<b-modal ref="ctxReportOther"
			id="ctx-report-other"
			hide-header
			hide-footer
			centered
			rounded
			size="sm"
			body-class="list-group-flush p-0 rounded">
			<p class="py-2 px-3 mb-0">
				<div class="text-center font-weight-bold text-danger">Report</div>
				<div class="small text-center text-muted">Select one of the following options</div>
			</p>
			<div class="list-group text-center">
				<div class="list-group-item rounded cursor-pointer font-weight-bold" @click="sendReport('underage')">Underage Account</div>
				<div class="list-group-item rounded cursor-pointer font-weight-bold" @click="sendReport('copyright')">Copyright Infringement</div>
				<div class="list-group-item rounded cursor-pointer font-weight-bold" @click="sendReport('impersonation')">Impersonation</div>
				<div class="list-group-item rounded cursor-pointer font-weight-bold" @click="sendReport('scam')">Scam or Fraud</div>
				<!-- <div class="list-group-item rounded cursor-pointer font-weight-bold" @click="sendReport('terrorism')">Terrorism Related</div> -->
				<!-- <div class="list-group-item rounded cursor-pointer font-weight-bold" @click="sendReport('other')">Other or Not listed</div> -->
				<!-- <div class="list-group-item rounded cursor-pointer" @click="ctxReportOtherMenuGoBack()">Go Back</div> -->
				<div class="list-group-item rounded cursor-pointer text-lighter" @click="ctxReportOtherMenuGoBack()">Cancel</div>
			</div>
		</b-modal>
	</div>
</div>
</template>

<script type="text/javascript">
	export default {
		props: {
			pid: {
				type: String
			},

			selfProfile: {
				type: Object
			},

			redirectUrl: {
				type: String,
				default: '/'
			}
		},

		data() {
			return {
				loading: true,
				profile: null,
				account: {
					local: false
				},
				owner: false,
				stories: [],
			    username: 'loading...',
			    avatar: '/storage/avatars/default.jpg',
				storyIndex: 0,
				progress: 0,
				constInterval: 383,
				progressInterval: undefined,
				composeText: null,
				paused: false,
				muted: true,
				reactionEmoji: [ "❤️", "🔥", "💯", "😂", "😎", "👀" ],
				activeReactionEmoji: false,
				activeReply: false,
				showProgress: false,
				redirectOnEnd: '/',
				viewerSid: false,
				viewerPage: 1,
				loadingViewers: false,
				viewersHasMore: true,
				viewers: [],
				viewWarning: false,
				showingPollResults: false,
				loadingPollResults: false,
				pollResults: [],
				pollTotalVotes: 0
			}
		},

		watch: {
			composeText: function(val) {
				if(val.length == 0) {
					if(this.paused) {
						this.pause();
					}
				} else {
					if(!this.paused) {
						this.pause();
					}
				}
				event.currentTarget.focus();
			}
		},

		beforeMount() {
			this.redirectOnEnd = this.redirectUrl;
		},

		mounted() {
			let u = new URLSearchParams(window.location.search);
			if(u.has('t')) {
				switch(u.get('t')) {
					case '1':
						this.redirectOnEnd = '/';
					break;

					case '2':
						this.redirectOnEnd = '/timeline/public';
					break;

					case '3':
						this.redirectOnEnd = '/timeline/network';
					break;

					case '4':
						this.redirectOnEnd = '/' + window.location.pathname.split('/').slice(-1).pop();
					break;
				}
			} else {
				this.viewWarning = true;
			}

			if(!this.selfProfile || !this.selfProfile.hasOwnProperty('avatar')) {
				axios.get('/api/pixelfed/v1/accounts/verify_credentials')
				.then(res => {
					this.profile = res.data;
					this.fetchStories();
				});
			} else {
				this.profile = this.selfProfile;
			}
			let el = document.querySelector('body');
			el.style.width = '100%';
			el.style.height = '100vh !important';
			el.style.overflow = 'hidden';
			el.style.backgroundColor = '#262626';
		},

		methods: {
			init() {
				clearInterval(this.progressInterval);
				this.loading = false;
				this.constInterval = Math.ceil(this.stories[this.storyIndex].duration * 38.3);
				this.progressInterval = setInterval(() => {
					this.do();
				}, this.constInterval);
			},

			do() {
				this.loading = false;
				if(this.stories[this.storyIndex].progress != 100) {
					this.stories[this.storyIndex].progress = this.stories[this.storyIndex].progress + 4;
				} else {
					clearInterval(this.progressInterval);
					this.next();
				}
			},

			prev() {
				if(this.storyIndex == 0) {
					return;
				}
				this.pollResults = [];
				this.progress = 0;
				this.gotoSlide(this.storyIndex - 1);
			},

			next() {
				axios.post('/api/web/stories/v1/viewed', {
					id: this.stories[this.storyIndex].id
				});
				this.stories[this.storyIndex].progress = 100;
				if(this.storyIndex == this.stories.length - 1) {
					if(this.composeText && this.composeText.length) {
						return;
					}
					window.location.href = this.redirectOnEnd;
					return;
				}
				this.pollResults = [];
				this.progress = 0;
				this.muted = true;
				this.storyIndex = this.storyIndex + 1;
				this.init();
			},

			pause() {
				if(event) {
					event.currentTarget.blur();
				}

				if(this.paused) {
					this.paused = false;
					if(this.stories[this.storyIndex].type == 'video') {
						let el = document.getElementById('playr');
						el.play();
					}
					this.init();
				} else {
					clearInterval(this.progressInterval);
					if(this.stories[this.storyIndex].type == 'video') {
						let el = document.getElementById('playr');
						el.pause();
					}
					this.paused = true;
				}
			},

			toggleMute() {
				if(event) {
					event.currentTarget.blur();
				}
				if(this.stories[this.storyIndex].type == 'video') {
					this.muted = !this.muted;
					let el = document.getElementById('playr');
					el.muted = this.muted;
				}
			},

			gotoSlide(index) {
				this.paused = false;
				clearInterval(this.progressInterval);
				this.progressInterval = null;
				this.stories = this.stories.map(function(s,k) {
					if(k < index) {
						s.progress = 100;
					} else {
						s.progress = 0;
					}
					return s;
				});
				this.storyIndex = index;
				this.stories[index].progress = 0;
				this.init();
			},

			showMenu() {
				if(!this.paused) {
					this.pause();
				}
				event.currentTarget.blur();
				this.$refs.ctxMenu.show();
			},

			react(emoji) {
				this.$refs.ctxMenu.hide();
				this.activeReactionEmoji = true;

				axios.post('/api/web/stories/v1/react', {
					sid: this.stories[this.storyIndex].id,
					reaction: emoji
				})
				.then(res => {
					setTimeout(() => {
						this.activeReactionEmoji = false;
						this.pause();
					}, 2000);
				}).catch(err => {
					this.activeReactionEmoji = false;
					swal('Error', 'An error occured when attempting to react to this story. Please try again later.', 'error');
				});
			},

			comment() {
				if(this.composeText.length < 2) {
					return;
				}
				if(!this.paused) {
					this.pause();
				}
				this.activeReply = true;
				axios.post('/api/web/stories/v1/comment', {
					sid: this.stories[this.storyIndex].id,
					caption: this.composeText
				})
				.then(res => {
					this.composeText = null;
					setTimeout(() => {
						this.activeReply = false;
						this.pause();
					}, 2000);
				}).catch(err => {
					this.activeReply = false;
					swal('Error', 'An error occured when attempting to reply to this story. Please try again later.', 'error');
				});
			},

			closeCtxMenu() {
				this.$refs.ctxMenu.hide();
			},

			backToFeed() {
				if(this.composeText) {
					swal('Are you sure you want to leave without sending this reply?')
					.then(confirm => {
						if(confirm) {
							window.location.href = this.redirectOnEnd;
						}
					})
					return;
				} else {
					window.location.href = this.redirectOnEnd;
				}
			},

			timeago(ts) {
				return App.util.format.timeAgo(ts);
			},

			timeahead(ts) {
				let d = new Date(ts);
				return App.util.format.timeAhead(d.toUTCString());
			},

			fetchStories() {
				let self = this;
				axios.get('/api/web/stories/v1/profile/' + this.pid)
				.then(res => {
					if(res.data.length == 0) {
						window.location.href = this.redirectOnEnd;
					}
					self.account = res.data[0].account;
					if(self.account.local == false) {
						self.account.domain = self.account.acct.split('@')[1]
					}
					self.stories = res.data[0].nodes.map(function(i, k) {
						let r = {
							id: i.id,
							created_at: i.created_at,
							expires_at: i.expires_at,
							progress: i.progress == 100 && k == res.data[0].nodes.length - 1 ? 0 : i.progress,
							view_count: i.view_count,
							url: i.src,
							type: i.type,
							duration: i.duration,
							can_reply: i.can_reply,
							can_react: i.can_react,
						}

						if(r.type == 'poll') {
							r.question = i.question;
							r.options = i.options;
							r.voted = i.voted;
							r.voted_index = i.voted_index;
						}

						return r;
					});
					self.username = res.data[0].account.username;
					self.avatar = res.data[0].account.avatar;
					if(self.profile.id == res.data[0].account.id) {
						this.viewWarning = false;
					}
					if(this.viewWarning) {
						this.loading = false;
						return;
					}
					let seen = res.data[0].nodes.filter(function(i, k) {
						return i.seen == true;
					}).map(function(i, k) {
						return k;
					});
					if(seen.length && this.pid != this.profile.id) {
						let n = (seen[seen.length - 1] + 1) == self.stories.length ? seen[seen.length - 1] : (seen[seen.length - 1] + 1);
						self.gotoSlide(n);
					}
					if(this.pid == this.profile.id) {
						self.gotoSlide(self.stories.length - 1);
					}
					self.showProgress = true;
					if(self.profile.id == self.account.id) {
						self.owner = true;
					}
					if(res.data.length == 0) {
						window.location.href = this.redirectOnEnd;
						return;
					}
					this.init();
				})
				.catch(err => {
					return;
				});
			},

			fetchViewers() {
				this.closeCtxMenu();
				this.$refs.viewersModal.show();

				if(this.stories[this.storyIndex].id == this.viewerSid) {
					return;
				}

				this.loadingViewers = true;

				axios.get('/api/web/stories/v1/viewers', {
					params: {
						sid: this.stories[this.storyIndex].id
					}
				}).then(res => {
					this.viewerSid = this.stories[this.storyIndex].id;
					this.viewers = res.data;
					this.loadingViewers = false;
					this.viewerPage = 2;
					if(this.viewers.length == 10) {
						this.viewersHasMore = true;
					} else {
						this.viewersHasMore = false;
					}
				}).catch(err => {
					swal('Cannot load viewers', 'Cannot load viewers of this story, please try again later.', 'error');
				})
			},

			viewersLoadMore() {
				axios.get('/api/web/stories/v1/viewers', {
					params: {
						sid: this.stories[this.storyIndex].id,
						page: this.viewerPage
					}
				}).then(res => {
					if(!res.data || res.data.length == 0) {
						this.viewersHasMore = false;
						return;
					}
					if(res.data.length != 10) {
						this.viewersHasMore = false;
					}
					this.viewers.push(...res.data);
					this.viewerPage++;
				}).catch(err => {
					swal('Cannot load viewers', 'Cannot load viewers of this story, please try again later.', 'error');
				});
			},

			closeViewersModal() {
				this.$refs.viewersModal.hide();
			},

			deleteStory() {
				this.closeCtxMenu();
				if(!window.confirm('Are you sure you want to delete this story?')) {
					this.pause();
					return;
				}
				axios.delete('/api/web/stories/v1/delete/' + this.stories[this.storyIndex].id)
				.then(res => {
					let i = this.storyIndex;
					let c = this.stories.length;

					if(c == 1) {
						window.location.href = '/';
						return;
					}
					window.location.reload();
				});
			},

			selectPollOption(index) {
				if(!this.paused) {
					this.pause();
				}
				axios.post('/i/stories/viewed', {
					id: this.stories[this.storyIndex].id
				});
				axios.post('/api/web/stories/v1/poll/vote', {
					sid: this.stories[this.storyIndex].id,
					ci: index
				}).then(res => {
					this.stories[this.storyIndex].voted = true;
					this.stories[this.storyIndex].voted_index = index;
					this.next();
				})
			},

			ctxMenuReport() {
				this.$refs.ctxMenu.hide();
				this.$refs.ctxReport.show();
			},

			openCtxReportOtherMenu() {
				this.closeCtxMenu();
				this.$refs.ctxReport.hide();
				this.$refs.ctxReportOther.show();
			},

			ctxReportMenuGoBack() {
				this.closeMenus();
			},

			ctxReportOtherMenuGoBack() {
				this.closeMenus();
			},

			closeMenus() {
				this.$refs.ctxReportOther.hide();
				this.$refs.ctxReport.hide();
				this.$refs.ctxMenu.hide();
			},

			sendReport(type) {
				let id = this.stories[this.storyIndex].id;

				swal({
					'title': 'Confirm Report',
					'text': 'Are you sure you want to report this post?',
					'icon': 'warning',
					'buttons': true,
					'dangerMode': true
				}).then((res) => {
					if(res) {
						axios.post('/api/web/stories/v1/report', {
							'type': type,
							'id': id,
						}).then(res => {
							this.closeMenus();
							swal('Report Sent!', 'We have successfully received your report', 'success');
						}).catch(err => {
							if(err.response.status === 409) {
								swal('Already reported', 'You have already reported this story', 'info');
							} else {
								swal('Oops!', 'There was an issue reporting this story', 'error');
							}
						})
					} else {
						this.closeMenus();
					}
				});
			},

			cancelViewStory() {
				event.currentTarget.blur();
				location.href = '/i/web';
			},

			confirmViewStory() {
				let self = this;
				let seen = this.stories.filter(function(i, k) {
					return i.seen == true;
				}).map(function(i, k) {
					return k;
				});
				if(seen.length && this.pid != this.profile.id) {
					let n = (seen[seen.length - 1] + 1) == self.stories.length ? seen[seen.length - 1] : (seen[seen.length - 1] + 1);
					self.gotoSlide(n);
				}
				if(this.pid == this.profile.id) {
					self.gotoSlide(self.stories.length - 1);
				}
				self.showProgress = true;
				if(self.profile.username == self.username) {
					self.owner = true;
				}
				this.viewWarning = false;
				this.init();
			},

			showPollResults() {
				this.loadingPollResults = true;
				if(!this.paused) {
					this.pause();
				}

				axios.get('/api/web/stories/v1/poll/results', {
					params: {
						sid: this.stories[this.storyIndex].id
					}
				}).then(res => {
					this.loadingPollResults = false;
					this.pollResults = res.data;
					const sum = (a, b) => a + b;
					this.pollTotalVotes = this.pollResults.reduce(sum);
				});
			},

			addToStory() {
				window.location.href = '/i/stories/new';
			},

			pollPercent(index) {
				return this.pollTotalVotes == 0 ? 0 : Math.round((this.pollResults[index] / this.pollTotalVotes) * 100)
			}
		}
	}
</script>

<style lang="scss" scoped>
	#content {
		width: 100%;
		height: 100vh !important;
		overflow: hidden;
		background-color: #262626;
	}

	.story-viewer-component {

		&-card {
			height: 100vh;

			@media (min-width: 768px) {
				height: 90vh;
			}
		}

		&.bg-black {
			background-color: #262626;
		}

		.option-green {
			font-size: 20px;
			font-weight: 600;
			background: #11998e;  /* fallback for old browsers */
			background: -webkit-linear-gradient(180deg, #38ef7d, #11998e);
			background: linear-gradient(180deg, #38ef7d, #11998e);
			-webkit-background-clip: text;
  			-webkit-text-fill-color: transparent;
		}

		.option-red {
			font-weight: 600;
			background: linear-gradient(to right, #F27121, #E94057, #8A2387);
			-webkit-background-clip: text;
  			-webkit-text-fill-color: transparent;
		}

		.bg-black {
			background-color: #262626;
		}

		.fade-enter-active, .fade-leave-active {
			transition: opacity .5s;
		}

		.fade-enter, .fade-leave-to {
			opacity: 0;
		}

		.progress {
			background-color: #979a9a;
		}

		.media-slot {
			border-radius: 0;
			width: 100%;
			height: 100%;
			position: absolute;
			left: 0;
			top: 0;
			background: #000;
			background-size: cover !important;
			z-index: 0;
		}

		.card-body {
			.top-overlay {
				height:100px;
				margin-left: -35px;
				margin-right: -35px;
				margin-top: -20px;
				padding-bottom: 20px;
				border-radius: 5px;
				background: linear-gradient(180deg, rgba(38,38,38, 0.8) 0%, rgba(38,38,38,0) 100%);
			}
		}

		.card-footer {
			::placeholder {
				color: #fff;
				opacity: 1;
			}

			.bottom-overlay {
				margin-left: -35px;
				margin-right: -35px;
				margin-bottom: -20px;
				border-radius: 5px;
				background: linear-gradient(0deg, rgba(38,38,38, 0.8) 0%, rgba(38,38,38,0) 100%);

				.form-group {
					padding-top: 40px;
					padding-bottom: 20px;
					margin-bottom: 0;
				}
			}
		}
	}
</style>
