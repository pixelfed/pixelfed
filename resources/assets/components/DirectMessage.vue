<template>
	<div class="dm-page-component">
		<div v-if="isLoaded" class="container-fluid mt-lg-3 pb-lg-5">
			<div class="row dm-page-component-row">
				<div class="col-md-3 d-md-block">
					<sidebar :user="profile" />
				</div>

				<div class="col-md-6 p-0">
					<div v-if="loaded && page == 'read'" class="messages-page">
						<div class="card shadow-none">
							<div class="h4 card-header font-weight-bold text-dark d-flex justify-content-between align-items-center" style="letter-spacing: -0.3px;">
								<button class="btn btn-light rounded-pill text-dark" @click="goBack()">
									<i class="far fa-chevron-left fa-lg"></i>
								</button>

								<div>Direct Message</div>

								<button class="btn btn-light rounded-pill text-dark" @click="showOptions()">
									<i class="far fa-cog fa-lg"></i>
								</button>
							</div>
							<ul class="list-group list-group-flush" style="position:relative;">
								<li class="list-group-item border-bottom sticky-top">
									<p class="text-center small text-muted mb-0">
										Conversation with <span class="font-weight-bold">{{thread.username}}</span>
									</p>
								</li>
							</ul>

							<transition name="fade">
							<ul v-if="showDMPrivacyWarning && showPrivacyWarning" class="list-group list-group-flush dm-privacy-warning" style="position:absolute;top:105px;width:100%;">
								<li class="list-group-item border-bottom sticky-top bg-warning">
									<div class="d-flex align-items-center justify-content-between">
										<div class="d-none d-lg-block">
											<i class="fas fa-exclamation-triangle text-danger fa-lg mr-3"></i>
										</div>
										<div>
											<p class="small warning-text mb-0 font-weight-bold"><span class="d-inline d-lg-none">DMs</span><span class="d-none d-lg-inline">Direct messages on Pixelfed</span> are not end-to-end encrypted.
											</p>
											<p class="small warning-text mb-0 font-weight-bold">
												Use caution when sharing sensitive data.
											</p>
										</div>
										<button class="btn btn-link text-decoration-none" @click="togglePrivacyWarning">
											<i class="far fa-times-circle fa-lg"></i>
											<span class="d-none d-lg-block">Close</span>
										</button>
									</div>
								</li>
							</ul>
							</transition>

							<ul class="list-group list-group-flush dm-wrapper" style="overflow-y: scroll;position:relative;flex-direction: column-reverse;">
								<li v-for="(convo, index) in thread.messages" class="list-group-item border-0 chat-msg mb-n2">
									<message
										:convo="convo"
										:thread="thread"
										:hideAvatars="hideAvatars"
										:hideTimestamps="hideTimestamps"
										:largerText="largerText"
										v-on:confirm-delete="deleteMessage(index)" />
								</li>

								<li v-if="showLoadMore && thread.messages && thread.messages.length > 5" class="list-group-item border-0">
									<p class="text-center small text-muted">
										<button v-if="!loadingMessages" class="btn btn-primary font-weight-bold rounded-pill btn-sm px-3" @click="loadOlderMessages()">Load Older Messages</button>
										<button v-else class="btn btn-primary font-weight-bold rounded-pill btn-sm px-3" disabled>Loading...</button>
									</p>
								</li>
							</ul>

							<div class="card-footer bg-white p-0">
								<!-- <form class="border-0 rounded-0 align-middle" method="post" action="#">
									<textarea class="form-control border-0 rounded-0 no-focus" name="comment" placeholder="Reply ..." autocomplete="off" autocorrect="off" style="height:86px;line-height: 18px;max-height:80px;resize: none; padding-right:115.22px;" v-model="replyText" :disabled="blocked"></textarea>
									<input type="button" value="Send" :class="[replyText.length ? 'd-inline-block btn btn-sm btn-primary rounded-pill font-weight-bold reply-btn text-decoration-none text-uppercase' : 'd-inline-block btn btn-sm btn-primary rounded-pill font-weight-bold reply-btn text-decoration-none text-uppercase disabled']" :disabled="replyText.length == 0" @click.prevent="sendMessage"/>
								</form> -->
								<div class="dm-reply-form">
									<div class="dm-reply-form-input-group">
										<input
											class="form-control form-control-lg"
											placeholder="Type a message..."
											:disabled="uploading"
											v-model="replyText">

										<button
											class="upload-media-btn btn btn-link"
											:disabled="uploading"
											@click="uploadMedia">
											<i class="far fa-image fa-2x"></i>
										</button>
									</div>

									<button
										class="dm-reply-form-submit-btn btn btn-primary"
										:disabled="!replyText || !replyText.length || showReplyTooLong"
										@click="sendMessage">
										<i class="far fa-paper-plane fa-lg"></i>
									</button>
								</div>
							</div>

							<div v-if="uploading" class="card-footer dm-status-bar">
								<p>Uploading ({{uploadProgress}}%) ...</p>
							</div>

							<div v-if="showReplyLong" class="card-footer dm-status-bar">
								<p class="text-warning">{{ replyText.length }}/500</p>
							</div>

							<div v-if="showReplyTooLong" class="card-footer dm-status-bar">
								<p class="text-danger">{{ replyText.length }}/500 - Your message exceeds the limit of 500 characters</p>
							</div>

							<div class="d-none card-footer p-0">
								<p class="d-flex justify-content-between align-items-center mb-0 px-3 py-1 small">
									<!-- <span class="font-weight-bold" style="color: #D69E2E">
									<i class="fas fa-circle mr-1"></i>
									Typing ...
									</span> -->
									<span>
										<!-- <span class="btn btn-primary btn-sm font-weight-bold py-0 px-3 rounded-pill" @click="uploadMedia">
										<i class="fas fa-share mr-1"></i>
										Share
										</span> -->
										<span class="btn btn-primary btn-sm font-weight-bold py-0 px-3 rounded-pill" @click="uploadMedia">
											<i class="fas fa-upload mr-1"></i>
											Add Photo/Video
										</span>
									</span>
									<input type="file" id="uploadMedia" class="d-none" name="uploadMedia" accept="image/jpeg,image/png,image/gif,video/mp4" >
									<span class="text-muted font-weight-bold">{{replyText.length}}/500</span>
								</p>
							</div>
						</div>
					</div>

					<div v-if="loaded && page == 'options'" class="messages-page">
						<div class="card shadow-none">
							<div class="h4 card-header font-weight-bold text-dark d-flex justify-content-between align-items-center" style="letter-spacing: -0.3px;">
								<button class="btn btn-light rounded-pill text-dark" @click.prevent="goBack('read')">
									<i class="far fa-chevron-left fa-lg"></i>
								</button>

								<div>Direct Message Settings</div>

								<div class="btn btn-light rounded-pill text-dark">
									<i class="far fa-smile fa-lg"></i>
								</div>
							</div>

							<ul class="list-group list-group-flush" style="height: 698px;">
								<div class="list-group-item media border-bottom">
									<div class="d-inline-block custom-control custom-switch ml-3">
										<input type="checkbox" class="custom-control-input" id="customSwitch0" v-model="hideAvatars">
										<label class="custom-control-label" for="customSwitch0"></label>
									</div>
									<div class="d-inline-block ml-3 font-weight-bold">
										Hide Avatars
									</div>
								</div>
								<div class="list-group-item media border-bottom">
									<div class="d-inline-block custom-control custom-switch ml-3">
										<input type="checkbox" class="custom-control-input" id="customSwitch1" v-model="hideTimestamps">
										<label class="custom-control-label" for="customSwitch1"></label>
									</div>
									<div class="d-inline-block ml-3 font-weight-bold">
										Hide Timestamps
									</div>
								</div>
								<div class="list-group-item media border-bottom">
									<div class="d-inline-block custom-control custom-switch ml-3">
										<input type="checkbox" class="custom-control-input" id="customSwitch2" v-model="largerText">
										<label class="custom-control-label" for="customSwitch2"></label>
									</div>
									<div class="d-inline-block ml-3 font-weight-bold">
										Larger Text
									</div>
								</div>
								<!-- <div class="list-group-item media border-bottom">
								<div class="d-inline-block custom-control custom-switch ml-3">
								<input type="checkbox" class="custom-control-input" id="customSwitch3" v-model="autoRefresh">
								<label class="custom-control-label" for="customSwitch3"></label>
								</div>
								<div class="d-inline-block ml-3 font-weight-bold">
								Auto Refresh
								</div>
								</div> -->
								<div class="list-group-item media border-bottom d-flex align-items-center">
									<div class="d-inline-block custom-control custom-switch ml-3">
										<input type="checkbox" class="custom-control-input" id="customSwitch4" v-model="mutedNotifications">
										<label class="custom-control-label" for="customSwitch4"></label>
									</div>
									<div class="d-inline-block ml-3 font-weight-bold">
										Mute Notifications
										<p class="small mb-0">You will not receive any direct message notifications from <strong>{{thread.username}}</strong>.</p>
									</div>
								</div>
								<div class="list-group-item media border-bottom d-flex align-items-center">
									<div class="d-inline-block custom-control custom-switch ml-3">
										<input type="checkbox" class="custom-control-input" id="customSwitch5" v-model="showDMPrivacyWarning">
										<label class="custom-control-label" for="customSwitch5"></label>
									</div>
									<div class="d-inline-block ml-3 font-weight-bold">
										Show Privacy Warning
										<p class="small mb-0">Show privacy warning indicating that direct messages are not end-to-end encrypted and that caution is advised when sending sensitive/confidential information.</p>
									</div>
								</div>
							</ul>
						</div>
					</div>
				</div>

				<div v-if="conversationProfile" class="col-md-3 d-none d-md-block">
					<div class="card shadow-sm mb-3" style="border-radius: 15px;">
						<div class="small card-header font-weight-bold text-uppercase text-lighter" style="letter-spacing: -0.3px;">
							Conversation
						</div>
						<div class="card-body p-2">
							<div class="media user-card user-select-none">
								<div>
									<img :src="conversationProfile.avatar" class="avatar shadow cursor-pointer" draggable="false" onerror="this.onerror=null;this.src='/storage/avatars/default.png?v=0';">
								</div>
								<div class="media-body">
									<p
										class="display-name"
										v-html="conversationProfile.display_name"
										@click="gotoProfile(conversationProfile)"
										>
									</p>

									<p
										class="username primary"
										@click="gotoProfile(conversationProfile)">
										&commat;{{ conversationProfile.acct }}
									</p>
									<p class="stats">
										<span class="stats-following">
											<span class="following-count">{{ formatCount(conversationProfile.following_count) }}</span> Following
										</span>
										<span class="stats-followers">
											<span class="followers-count">{{ formatCount(conversationProfile.followers_count) }}</span> Followers
										</span>
									</p>
								</div>
							</div>
						</div>
					</div>

					<!-- <div class="card shadow-sm mb-3" style="border-radius: 15px;">
						<div class="small card-header font-weight-bold text-uppercase text-lighter" style="border-top-left-radius: 15px;letter-spacing: -0.3px;">
							History
						</div>
					</div> -->
					<!-- <div class="list-group shadow-sm">
						<div class="list-group-item border-0 border-bottom" style="border-width: 1px;">
							<p class="mb-0"><i class="far fa-user-plus mr-2"></i> You both follow each other</p>
						</div>
						<div class="list-group-item border-0">
							<p class="mb-0"><i class="far fa-users mr-2"></i> You both follow <a class="font-weight-bold">&commat;pixelfed</a>,<a class="font-weight-bold">&commat;pixeldev</a> and <a class="font-weight-bold">&commat;pixel</a></p>
						</div>
					</div> -->
				</div>
			</div>

		</div>
		<div v-else class="d-flex justify-content-center align-items-center" style="height:calc(100vh - 58px);">
			<b-spinner />
		</div>
	</div>
</template>

<script type="text/javascript">
	import Drawer from './partials/drawer.vue';
	import Sidebar from './partials/sidebar.vue';
	import Placeholder from './partials/placeholders/DirectMessagePlaceholder.vue';
	import Intersect from 'vue-intersect'
	import ProfileCard from './partials/profile/ProfileHoverCard.vue';
	import Message from './partials/direct/Message.vue';

	export default {
		props: ['accountId'],

		components: {
			"drawer": Drawer,
            "sidebar": Sidebar,
            "intersect": Intersect,
            "dm-placeholder": Placeholder,
            "profile-card": ProfileCard,
            "message": Message
        },

		data() {
			return {
				isLoaded: false,
				profile: undefined,
				conversationProfile: undefined,
				isIntersecting: false,

				config: window.App.config,
				hideAvatars: true,
				hideTimestamps: false,
				largerText: false,
				autoRefresh: false,
				mutedNotifications: false,
				blocked: false,
				loaded: false,
				page: 'read',
				pages: ['browse', 'add', 'read'],
				threads: [],
				thread: false,
				threadIndex: false,

				replyText: '',
				composeUsername: '',
				uploading: false,
				uploadProgress: null,

				min_id: null,
				max_id: null,
				loadingMessages: false,
				showLoadMore: true,

				showReplyLong: false,
				showReplyTooLong: false,

				showPrivacyWarning: true,
			}
		},

		mounted() {
			this.profile = window._sharedData.user;
			this.isLoaded = true;
			let self = this;
			axios.get('/api/v1/accounts/' + this.accountId)
			.then(res => {
				this.conversationProfile = res.data;
			});

			axios.get('/api/direct/thread', {
				params: {
					pid: self.accountId
				}
			})
			.then(res => {
				self.loaded = true;
				let d = res.data;
				this.thread = d;
				this.threads = [d];
				this.threadIndex = 0;
				let mids = d.messages.map(m => m.id);
				this.max_id = Math.max(...mids);
				this.min_id = Math.min(...mids);
				this.mutedNotifications = d.muted;
				this.markAsRead();
				//this.messagePoll();
				// setTimeout(function() {
				// 	let objDiv = document.querySelector('.dm-wrapper');
				// 	objDiv.scrollTop = objDiv.scrollHeight;
				// }, 300);
			});

			let options = localStorage.getItem('px_dm_options');
			if(options) {
				options = JSON.parse(options);
				this.hideAvatars = options.hideAvatars;
				this.hideTimestamps = options.hideTimestamps;
				this.largerText = options.largerText;
			}
		},

		computed: {
			showDMPrivacyWarning: {
				get() {
					return this.$store.state.showDMPrivacyWarning;
				},

				set(val) {
					window.localStorage.removeItem('pf_m2s.dmwarncounter');
					this.$store.commit('setShowDMPrivacyWarning', val);
				}
			},
		},

		watch: {
			mutedNotifications: function(v) {
				if(v) {
					axios.post('/api/direct/mute', {
						id: this.accountId
					}).then(res => {

					});
				} else {
					axios.post('/api/direct/unmute', {
						id: this.accountId
					}).then(res => {

					});
				}
				this.mutedNotifications = v;
			},

			hideAvatars: function(v) {
				this.hideAvatars = v;
				this.updateOptions();
			},

			hideTimestamps: function(v) {
				this.hideTimestamps = v;
				this.updateOptions();
			},

			largerText: function(v) {
				this.largerText = v;
				this.updateOptions();
			},

			replyText: function(v) {
				let limit = 500;

				if(v.length < limit) {
					this.showReplyLong = false;
					this.showReplyTooLong = false;
				}

				if(v.length > limit) {
					this.showReplyLong = false;
					this.showReplyTooLong = true;
					return;
				}

				if(v.length > (limit - 50)) {
					this.showReplyTooLong = false;
					this.showReplyLong = true;
					return;
				}
			}
		},

		methods: {
			sendMessage() {
				let self = this;
				let rt = this.replyText;
				axios.post('/api/direct/create', {
					'to_id': this.threads[this.threadIndex].id,
					'message': rt,
					'type': self.isEmoji(rt) && rt.length < 10 ? 'emoji' : 'text'
				}).then(res => {
					let msg = res.data;
					self.threads[self.threadIndex].messages.unshift(msg);
					let mids = self.threads[self.threadIndex].messages.map(m => m.id);
					this.max_id = Math.max(...mids)
					this.min_id = Math.min(...mids)
					// setTimeout(function() {
					// 	var objDiv = document.querySelector('.dm-wrapper');
					// 	objDiv.scrollTop = objDiv.scrollHeight;
					// }, 300);
				}).catch(err => {
					if(err.response.status == 403) {
						self.blocked = true;
						swal('Profile Unavailable', 'You cannot message this profile at this time.', 'error');
					}
				})
				this.replyText = '';
			},

			truncate(t) {
				return _.truncate(t);
			},

			deleteMessage(index) {
				let c = window.confirm('Are you sure you want to delete this message?');
				if(c) {
					axios.delete('/api/direct/message', {
						params: {
							id: this.thread.messages[index].reportId
						}
					}).then(res => {
						this.thread.messages.splice(index ,1);
					});
				}
			},

			reportMessage() {
				this.closeCtxMenu();
				let url = '/i/report?type=post&id=' + this.ctxContext.reportId;
				window.location.href = url;
				return;
			},

			uploadMedia(event) {
				let self = this;
				$(document).on('change', '#uploadMedia', function(e) {
					self.handleUpload();
				});
				let el = $(event.target);
				el.attr('disabled', '');
				$('#uploadMedia').click();
				el.blur();
				el.removeAttr('disabled');
			},

			handleUpload() {
				let self = this;
				if(self.uploading) {
					return;
				}
				self.uploading = true;
				let io = document.querySelector('#uploadMedia');
				if(!io.files.length) {
					this.uploading = false;
				}

				Array.prototype.forEach.call(io.files, function(io, i) {
					let type = io.type;
					let acceptedMimes = self.config.uploader.media_types.split(',');
					let validated = $.inArray(type, acceptedMimes);
					if(validated == -1) {
						swal('Invalid File Type', 'The file you are trying to add is not a valid mime type. Please upload a '+self.config.uploader.media_types+' only.', 'error');
						self.uploading = false;
						return;
					}

					let form = new FormData();
					form.append('file', io);
					form.append('to_id', self.threads[self.threadIndex].id);

					let xhrConfig = {
						onUploadProgress: function(e) {
							let progress = Math.round( (e.loaded * 100) / e.total );
							self.uploadProgress = progress;
						}
					};

					axios.post('/api/direct/media', form, xhrConfig)
					.then(function(e) {
						self.uploadProgress = 100;
						self.uploading = false;
						let msg = {
							id: e.data.id,
							type: e.data.type,
							reportId: e.data.reportId,
							isAuthor: true,
							text: null,
							media: e.data.url,
							timeAgo: '1s',
							seen: null
						};
						self.threads[self.threadIndex].messages.unshift(msg);
						// setTimeout(function() {
						// 	var objDiv = document.querySelector('.dm-wrapper');
						// 	objDiv.scrollTop = objDiv.scrollHeight;
						// }, 300);

					}).catch(function(e) {
						if(e.hasOwnProperty('response') && e.response.hasOwnProperty('status') ) {
							switch(e.response.status) {
								case 451:
								self.uploading = false;
								io.value = null;
								swal('Banned Content', 'This content has been banned and cannot be uploaded.', 'error');
								break;

								default:
								self.uploading = false;
								io.value = null;
								swal('Oops, something went wrong!', 'An unexpected error occurred.', 'error');
								break;
							}
						}
					});
					io.value = null;
					self.uploadProgress = 0;
				});
			},

			viewOriginal() {
				let url = this.ctxContext.media;
				window.location.href = url;
				return;
			},

			isEmoji(text) {
				const onlyEmojis = text.replace(new RegExp('[\u0000-\u1eeff]', 'g'), '')
				const visibleChars = text.replace(new RegExp('[\n\r\s]+|( )+', 'g'), '')
				return onlyEmojis.length === visibleChars.length
			},

			copyText() {
				window.App.util.clipboard(this.ctxContext.text);
				this.closeCtxMenu();
				return;
			},

			clickLink() {
				let url = this.ctxContext.text;
				if(this.ctxContext.meta.local != true) {
					url = '/i/redirect?url=' + encodeURI(this.ctxContext.text);
				}
				window.location.href = url;
			},

			markAsRead() {
				return;
				axios.post('/api/direct/read', {
					pid: this.accountId,
					sid: this.max_id
				}).then(res => {
				}).catch(err => {
				});
			},

			loadOlderMessages() {
				let self = this;
				this.loadingMessages = true;

				axios.get('/api/direct/thread', {
					params: {
						pid: this.accountId,
						max_id: this.min_id,
					}
				}).then(res => {
					let d = res.data;
					if(!d.messages.length) {
						this.showLoadMore = false;
						this.loadingMessages = false;
						return;
					}
					let cids = this.thread.messages.map(m => m.id);
					let m = d.messages.filter(m => {
						return cids.indexOf(m.id) == -1;
					}).reverse();
					let mids = m.map(m => m.id);
					let min_id = Math.min(...mids);
					if(min_id == this.min_id) {
						this.showLoadMore = false;
						this.loadingMessages = false;
						return;
					}
					this.min_id = min_id;
					this.thread.messages.push(...m);
					setTimeout(function() {
						self.loadingMessages = false;
					}, 500);
				}).catch(err => {
					this.loadingMessages = false;
				})
			},

			messagePoll() {
				let self = this;
				setInterval(function() {
					axios.get('/api/direct/thread', {
						params: {
							pid: self.accountId,
							min_id: self.thread.messages[self.thread.messages.length - 1].id
						}
					}).then(res => {
					});
				}, 5000);
			},

			showOptions() {
				this.page = 'options';
			},

			updateOptions() {
				let options = {
					v: 1,
					hideAvatars: this.hideAvatars,
					hideTimestamps: this.hideTimestamps,
					largerText: this.largerText
				}
				window.localStorage.setItem('px_dm_options', JSON.stringify(options));
			},

			formatCount(val) {
				return window.App.util.format.count(val);
			},

			goBack(page = false) {
				if(page) {
					this.page = page;
				} else {
					this.$router.push('/i/web/direct');
				}
			},

			gotoProfile(profile) {
				this.$router.push(`/i/web/profile/${profile.id}`);
			},

			togglePrivacyWarning() {
				console.log('clicked toggle privacy warning');
				let ls = window.localStorage;
				let key = 'pf_m2s.dmwarncounter';
				this.showPrivacyWarning = false;
				if(ls.getItem(key)) {
					let count = ls.getItem(key);
					count++;
					ls.setItem(key, count);
					if(count > 5) {
						this.showDMPrivacyWarning = false;
					}
				} else {
					ls.setItem(key, 1);
				}
			}
		}
	}
</script>

<style lang="scss" scoped>
	.dm-page-component {
		font-family: -apple-system,BlinkMacSystemFont,"Segoe UI",Roboto,Helvetica,Arial,sans-serif;

		.user-card {
			align-items: center;

			.avatar {
				width: 60px;
				height: 60px;
				border-radius: 15px;
				margin-right: 0.8rem;
				border: 1px solid var(--border-color);
			}

			.avatar-update-btn {
				position: absolute;
				right: 12px;
				bottom: 0;
				width: 20px;
				height: 20px;
				background: rgba(255,255,255,0.9);
				border: 1px solid #dee2e6 !important;
				padding: 0;
				border-radius: 50rem;

				&-icon {
					font-family: 'Font Awesome 5 Free';
					font-weight: 400;
					-webkit-font-smoothing: antialiased;
					display: inline-block;
					font-style: normal;
					font-variant: normal;
					text-rendering: auto;
					line-height: 1;

					&:before {
						content: "\F013";
					}
				}
			}

			.username {
				font-weight: 600;
				font-size: 13px;
				margin-bottom: 0;
				cursor: pointer;
			}

			.display-name {
				color: var(--body-color);
				line-height: 0.8;
				font-size: 14px;
				font-weight: 800 !important;
				user-select: all;
				font-family: -apple-system,BlinkMacSystemFont,"Segoe UI",Roboto,Helvetica,Arial,sans-serif;
				margin-bottom: 0;
				cursor: pointer;
			}

			.stats {
				margin-top: 0;
				margin-bottom: 0;
				font-size: 12px;
				user-select: none;

				.stats-following {
					margin-right: 0.8rem;
				}

				.following-count,
				.followers-count {
					font-weight: 800;
				}
			}
		}

		.dm-reply-form {
			display: flex;
			justify-content: space-between;
			background-color: var(--card-bg);
			padding: 1rem;

			.btn:focus,
			.btn.focus,
			input:focus,
			input.focus {
				outline: 0;
				box-shadow: none;
			}

			:disabled {
				opacity: 20% !important;
			}

			&-input-group {
				width: 100%;
				margin-right: 10px;
				position: relative;


				input {
					position: absolute;
					padding-right: 60px;
					background-color: var(--comment-bg);
					border-radius: 25px;
					border-color: var(--comment-bg) !important;
					font-size: 15px;
					color: var(--dark);

				}

				.upload-media-btn {
					position: absolute;
					right: 10px;
					top: 50%;
					transform: translateY(-50%);
					color: var(--text-lighter);
				}
			}

			&-submit-btn {
				width: 48px;
				height: 48px;
				border-radius: 24px;
			}
		}

		.dm-status-bar {
			font-size: 12px;
			font-weight: 600;
			color: var(--text-lighter);

			p {
				margin-bottom: 0;
			}
		}

		.dm-privacy-warning {
			p,
			.btn {
				color: #000;
			}

			.warning-text {
				text-align: left;

				@media (min-width: 992px) {
					text-align: center;
				}
			}
		}

		&-row {
			.dm-wrapper {
				padding-top: 100px;
				height: calc(100vh - 240px);

				@media (min-width: 500px) {
					min-height: 40vh;
				}

				@media (min-width: 700px) {
					height: 60vh;
				}
			}
		}
	}

</style>
