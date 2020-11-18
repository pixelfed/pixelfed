<template>
<div>
	<div v-if="loaded && page == 'read'" class="container messages-page p-0 p-md-2 mt-n4" style="min-height: 60vh;">
		<div class="col-12 col-md-8 offset-md-2 p-0 px-md-2">
			<div class="card shadow-none border mt-4">
				<div class="card-header bg-white d-flex justify-content-between align-items-center">
					<span>
						<a href="/account/direct" class="text-muted">
							<i class="fas fa-chevron-left fa-lg"></i>
						</a>
					</span>
					<span>
						<div class="media">
							<img class="mr-3 rounded-circle img-thumbnail" :src="thread.avatar" alt="Generic placeholder image" width="40px">
							<div class="media-body">
								<p class="mb-0">
									<span class="font-weight-bold">{{thread.name}}</span>
								</p>
								<p class="mb-0">
									<a v-if="!thread.isLocal" :href="'/'+thread.username" class="text-decoration-none text-muted">{{thread.username}}</a>
									<a v-else :href="'/'+thread.username" class="text-decoration-none text-muted">&commat;{{thread.username}}</a>
								</p>
							</div>
						</div>   
					</span>
					<span><a href="#" class="text-muted" @click.prevent="showOptions()"><i class="fas fa-cog fa-lg"></i></a></span>
				</div>
				<ul class="list-group list-group-flush dm-wrapper" style="height:60vh;overflow-y: scroll;">
					<li class="list-group-item border-0">
						<p class="text-center small text-muted">
							Conversation with <span class="font-weight-bold">{{thread.username}}</span>
						</p>
						<hr>
					</li>
					<li v-if="showLoadMore && thread.messages && thread.messages.length > 5" class="list-group-item border-0 mt-n4">
						<p class="text-center small text-muted">
							<button v-if="!loadingMessages" class="btn btn-primary font-weight-bold rounded-pill btn-sm px-3" @click="loadOlderMessages()">Load Older Messages</button>
							<button v-else class="btn btn-primary font-weight-bold rounded-pill btn-sm px-3" disabled>Loading...</button>
						</p>
					</li> 
					<li v-for="(convo, index) in thread.messages" class="list-group-item border-0 chat-msg cursor-pointer" @click="openCtxMenu(convo, index)">
						<div v-if="!convo.isAuthor" class="media d-inline-flex mb-0">
							<img v-if="!hideAvatars" class="mr-3 mt-2 rounded-circle img-thumbnail" :src="thread.avatar" alt="avatar" width="32px">
							<div class="media-body">
								<p v-if="convo.type == 'photo'" class="pill-to p-0 shadow">
									<img :src="convo.media" width="140px" style="border-radius:20px;">
								</p>
								<div v-else-if="convo.type == 'link'" class="media d-inline-flex mb-0 cursor-pointer">
									<div class="media-body">
										<div class="card mb-2 rounded border shadow" style="width:240px;" :title="convo.text">
											<div class="card-body p-0">
												<div class="media d-flex align-items-center">
													<div v-if="convo.meta.local" class="bg-primary mr-3 border-right p-3">
														<i class="fas fa-link text-white fa-2x"></i>
													</div>
													<div v-else class="bg-light mr-3 border-right p-3">
														<i class="fas fa-link text-lighter fa-2x"></i>
													</div>
													<div class="media-body text-muted small text-truncate pr-2 font-weight-bold">
														{{convo.meta.local ? convo.text.substr(8) : convo.meta.domain}}
													</div>
												</div>
											</div>
										</div>
									</div>
								</div>
								<p v-else-if="convo.type == 'video'" class="pill-to p-0 shadow">
									<!-- <video :src="convo.media" width="140px" style="border-radius:20px;"></video> -->
									<span class="d-block bg-primary d-flex align-items-center justify-content-center" style="width:200px;height: 110px;border-radius: 20px;">
										<div class="text-center">
											<p class="mb-1">
												<i class="fas fa-play fa-2x text-white"></i>
											</p>
											<p class="mb-0 small font-weight-bold text-white">
												Play
											</p>
										</div>
									</span>
								</p>
								<p v-else-if="convo.type == 'emoji'" class="p-0 emoji-msg">
									{{convo.text}}
								</p>
								<p v-else :class="[largerText ? 'pill-to shadow larger-text text-break':'pill-to shadow text-break']">
									{{convo.text}}
								</p>
								<p v-if="!hideTimestamps" class="small text-muted font-weight-bold ml-2 d-flex align-items-center justify-content-start" data-timestamp="timestamp"> <span v-if="convo.hidden" class="mr-2 small" title="Filtered Message" data-toggle="tooltip" data-placement="bottom"><i class="fas fa-lock"></i></span> {{convo.timeAgo}}</p>
							</div>
						</div>
						<div v-else class="media d-inline-flex float-right mb-0">
							<div class="media-body">
								<p v-if="convo.type == 'photo'" class="pill-from p-0 shadow">
									<img :src="convo.media" width="140px" style="border-radius:20px;">
								</p>
								<div v-else-if="convo.type == 'link'" class="media d-inline-flex float-right mb-0 cursor-pointer">
									<div class="media-body">
										<div class="card mb-2 rounded border shadow" style="width:240px;" :title="convo.text">
											<div class="card-body p-0">
												<div class="media d-flex align-items-center">
													<div v-if="convo.meta.local" class="bg-primary mr-3 border-right p-3">
														<i class="fas fa-link text-white fa-2x"></i>
													</div>
													<div v-else class="bg-light mr-3 border-right p-3">
														<i class="fas fa-link text-lighter fa-2x"></i>
													</div>
													<div class="media-body text-muted small text-truncate pr-2 font-weight-bold">
														{{convo.meta.local ? convo.text.substr(8) : convo.meta.domain}}
													</div>
												</div>
											</div>
										</div>
									</div>
								</div>
								<p v-else-if="convo.type == 'video'" class="pill-from p-0 shadow">
									<!-- <video :src="convo.media" width="140px" style="border-radius:20px;"></video> -->
									<span class="rounded-pill bg-primary d-flex align-items-center justify-content-center" style="width:200px;height: 110px">
										<div class="text-center">
											<p class="mb-1">
												<i class="fas fa-play fa-2x text-white"></i>
											</p>
											<p class="mb-0 small font-weight-bold">
												Play
											</p>
										</div>
									</span>
								</p>
								<p v-else-if="convo.type == 'emoji'" class="p-0 emoji-msg">
									{{convo.text}}
								</p>
								<p v-else :class="[largerText ? 'pill-from shadow larger-text text-break':'pill-from shadow text-break']">
									{{convo.text}}
								</p>
								<p v-if="!hideTimestamps" class="small text-muted font-weight-bold text-right mr-2"> <span v-if="convo.hidden" class="mr-2 small" title="Filtered Message" data-toggle="tooltip" data-placement="bottom"><i class="fas fa-lock"></i></span> {{convo.timeAgo}}
								</p>
							</div>
							<img v-if="!hideAvatars" class="ml-3 mt-2 rounded-circle img-thumbnail" :src="profile.avatar" alt="avatar" width="32px">
						</div>
					</li>

				</ul>
				<div class="card-footer bg-white p-0">
					<form class="border-0 rounded-0 align-middle" method="post" action="#">
						<textarea class="form-control border-0 rounded-0 no-focus" name="comment" placeholder="Reply ..." autocomplete="off" autocorrect="off" style="height:86px;line-height: 18px;max-height:80px;resize: none; padding-right:115.22px;" v-model="replyText" :disabled="blocked"></textarea>
						<input type="button" value="Send" :class="[replyText.length ? 'd-inline-block btn btn-sm btn-primary rounded-pill font-weight-bold reply-btn text-decoration-none text-uppercase' : 'd-inline-block btn btn-sm btn-primary rounded-pill font-weight-bold reply-btn text-decoration-none text-uppercase disabled']" :disabled="replyText.length == 0" @click.prevent="sendMessage"/>
					</form>
				</div>
				<div class="card-footer p-0">
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
						<span class="text-muted font-weight-bold">{{replyText.length}}/600</span>
					</p>
				</div>
			</div>
		</div>
	</div>
	<div v-if="loaded && page == 'options'" class="container messages-page p-0 p-md-2 mt-n4" style="min-height: 60vh;">
		<div class="col-12 col-md-8 offset-md-2 p-0 px-md-2">
			<div class="card shadow-none border mt-4">
				<div class="card-header bg-white d-flex justify-content-between align-items-center">
					<span>
						<a href="#" class="text-muted" @click.prevent="page='read'">
							<i class="fas fa-chevron-left fa-lg"></i>
						</a>
					</span>
					<span>
						<p class="mb-0 lead font-weight-bold py-2">Message Settings</p>
					</span>
					<span class="text-lighter" data-toggle="tooltip" data-placement="bottom" title="Have a nice day!"><i class="far fa-smile fa-lg"></i></span>
				</div>
				<ul class="list-group list-group-flush dm-wrapper" style="height: 698px;">
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
				</ul>
			</div>
		</div>
	</div>
	<b-modal ref="ctxModal"
	id="ctx-modal"
	hide-header
	hide-footer
	centered
	rounded
	size="sm"
	body-class="list-group-flush p-0 rounded">
	<div class="list-group text-center">
		<div v-if="ctxContext && ctxContext.type == 'photo'" class="list-group-item rounded cursor-pointer font-weight-bold text-dark" @click="viewOriginal()">View Original</div>
		<div v-if="ctxContext && ctxContext.type == 'video'" class="list-group-item rounded cursor-pointer font-weight-bold text-dark" @click="viewOriginal()">Play</div>
		<div v-if="ctxContext && ctxContext.type == 'link'" class="list-group-item rounded cursor-pointer" @click="clickLink()">
			<p class="mb-0" style="font-size:12px;">
				Navigate to 
			</p>
			<p class="mb-0 font-weight-bold text-dark">
				{{this.ctxContext.meta.domain}}
			</p>
		</div>
		<div v-if="ctxContext && (ctxContext.type == 'text' || ctxContext.type == 'emoji' || ctxContext.type == 'link')" class="list-group-item rounded cursor-pointer text-dark" @click="copyText()">Copy</div>
		<div v-if="ctxContext && !ctxContext.isAuthor" class="list-group-item rounded cursor-pointer text-muted" @click="reportMessage()">Report</div>
		<div v-if="ctxContext && ctxContext.isAuthor" class="list-group-item rounded cursor-pointer text-muted" @click="deleteMessage()">Delete</div>
		<div class="list-group-item rounded cursor-pointer text-lighter" @click="closeCtxMenu()">Cancel</div>
	</div>
	</b-modal>
</div>
</template>

<style type="text/css" scoped>
.reply-btn {
	position: absolute;
	bottom: 54px;
	right: 20px;
	width: 90px;
	text-align: center;
	border-radius: 0 3px 3px 0;
}
.media-body .bg-primary {
	background: linear-gradient(135deg, #2EA2F4 0%, #0B93F6 100%) !important;
}
.pill-to {
	background:#EDF2F7;
	font-weight: 500;
	border-radius: 20px !important;
	padding-left: 1rem;
	padding-right: 1rem;
	padding-top: 0.5rem;
	padding-bottom: 0.5rem;
	margin-right: 3rem;
	margin-bottom: 0.25rem;
}
.pill-from {
	color: white !important;
	text-align: right !important;
	/*background: #53d769;*/
	background: linear-gradient(135deg, #2EA2F4 0%, #0B93F6 100%) !important;
	font-weight: 500;
	border-radius: 20px !important;
	padding-left: 1rem;
	padding-right: 1rem;
	padding-top: 0.5rem;
	padding-bottom: 0.5rem;
	margin-left: 3rem;
	margin-bottom: 0.25rem;
}
.chat-msg:hover {
	background: #f7fbfd;
}
.no-focus:focus {
	outline: none !important;
	outline-width: 0 !important;
	box-shadow: none;
	-moz-box-shadow: none;
	-webkit-box-shadow: none;
}
.emoji-msg {
	font-size: 4rem !important;
	line-height: 30px !important;
	margin-top: 10px !important;
}
.larger-text {
	font-size: 22px;
}
</style>

<script type="text/javascript">
	export default {
		props: ['accountId'],
		data() {
			return {
				config: window.App.config,
				hideAvatars: true,
				hideTimestamps: false,
				largerText: false,
				autoRefresh: false,
				mutedNotifications: false,
				blocked: false,
				loaded: false,
				profile: {},
				page: 'read',
				pages: ['browse', 'add', 'read'],
				threads: [],
				thread: false,
				threadIndex: false,

				replyText: '',
				composeUsername: '',

				ctxContext: null,
				ctxIndex: null,

				uploading: false,
				uploadProgress: null,

				min_id: null,
				max_id: null,
				loadingMessages: false,
				showLoadMore: true,
			}
		},

		mounted() {
			this.fetchProfile();
			let self = this;
			axios.get('/api/direct/thread', {
				params: {
					pid: self.accountId
				}
			})
			.then(res => {
				self.loaded = true;
				let d = res.data;
				d.messages.reverse();
				this.thread = d;
				this.threads = [d];
				this.threadIndex = 0;
				let mids = d.messages.map(m => m.id);
				this.max_id = Math.max(...mids);
				this.min_id = Math.min(...mids);
				this.mutedNotifications = d.muted;
				this.markAsRead();
				//this.messagePoll();
				setTimeout(function() {
					let objDiv = document.querySelector('.dm-wrapper');
					objDiv.scrollTop = objDiv.scrollHeight;
				}, 300);
			});
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
		},

		updated() {
			$('[data-toggle="tooltip"]').tooltip();
		},

		methods: {
			fetchProfile() {
				axios.get('/api/pixelfed/v1/accounts/verify_credentials').then(res => {
					this.profile = res.data;
					window._sharedData.curUser = res.data;
				});
			},

			sendMessage() {
				let self = this;
				let rt = this.replyText;
				axios.post('/api/direct/create', {
					'to_id': this.threads[this.threadIndex].id,
					'message': rt,
					'type': self.isEmoji(rt) && rt.length < 10 ? 'emoji' : 'text'
				}).then(res => {
					let msg = res.data;
					self.threads[self.threadIndex].messages.push(msg);
					let mids = self.threads[self.threadIndex].messages.map(m => m.id);
					this.max_id = Math.max(...mids)
					this.min_id = Math.min(...mids)
					setTimeout(function() {
						var objDiv = document.querySelector('.dm-wrapper');
						objDiv.scrollTop = objDiv.scrollHeight;
					}, 300);
				}).catch(err => {
					if(err.response.status == 403) {
						self.blocked = true;
						swal('Profile Unavailable', 'You cannot message this profile at this time.', 'error');
					}
				})
				this.replyText = '';
			},

			openCtxMenu(r, i) {
				this.ctxIndex = i;
				this.ctxContext = r;
				this.$refs.ctxModal.show();
			},

			closeCtxMenu() {
				this.$refs.ctxModal.hide();
			},

			truncate(t) {
				return _.truncate(t);
			},

			deleteMessage() {
				let self = this;
				let c = window.confirm('Are you sure you want to delete this message?');
				if(c) {
					axios.delete('/api/direct/message', {
						params: {
							id: self.ctxContext.id
						}
					}).then(res => {
						self.threads[self.threadIndex].messages.splice(self.ctxIndex,1);
						self.closeCtxMenu();
					});
				} else {
					self.closeCtxMenu();
				}
			},

			reportMessage() {
				this.closeCtxMenu();
				let url = '/i/report?type=post&id=' + this.ctxContext.id;
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
							id: Date.now(),
							type: e.data.type,
							isAuthor: true,
							text: null,
							media: e.data.url,
							timeAgo: '1s',
							seen: null
						};
						self.threads[self.threadIndex].messages.push(msg);
						setTimeout(function() {
							var objDiv = document.querySelector('.dm-wrapper');
							objDiv.scrollTop = objDiv.scrollHeight;
						}, 300);

					}).catch(function(e) {
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
					this.thread.messages.unshift(...m);
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
			}
		}
	}
</script>