<template>
	<div>
		<div class="container p-0 overflow-hidden">
			<div class="row">
				<div class="col-12 col-md-6 offset-md-3">
					<div class="card shadow-none border" style="height:100vh;">
						<div class="card-header d-flex justify-content-between align-items-center">
							<div
								@click="commentNavigateBack(status.id)"
								class="cursor-pointer"
								>
								<i class="fas fa-chevron-left fa-lg px-2"></i>
							</div>
							<div>
								<p class="font-weight-bold mb-0 h5">Comments</p>
							</div>
							<div>
								<i class="fas fa-cog fa-lg text-white"></i>
							</div>
						</div>
						<div class="card-body" style="overflow-y: auto !important">
							<div class="media">
								<img :src="status.account.avatar" class="rounded-circle border mr-3" width="32px" height="32px">
								<div class="media-body">
									<p class="d-flex justify-content-between align-items-top mb-0" style="overflow-y: hidden;">
										<span class="mr-2" style="font-size: 13px;">
											<a class="text-dark font-weight-bold mr-1 text-break" :href="profileUrl(status)" v-bind:title="status.account.username">{{trimCaption(status.account.username,15)}}</a>
											<span class="text-break comment-body" style="word-break: break-all;" v-html="status.content"></span>
										</span>
									</p>
								</div>
							</div>
							<hr>
							<div class="postCommentsLoader text-center py-2">
								<div class="spinner-border" role="status">
									<span class="sr-only">Loading...</span>
								</div>
							</div>
							<div class="postCommentsContainer d-none">
								<p v-if="replies.length" class="mb-1 text-center load-more-link my-4">
									<a
										href="#"
										class="text-dark"
										title="Load more comments"
										@click.prevent="loadMoreComments"
									>
										<svg class="bi bi-plus-circle" width="1em" height="1em" viewBox="0 0 16 16" fill="currentColor" xmlns="http://www.w3.org/2000/svg" style="font-size:2em;">  <path fill-rule="evenodd" d="M8 3.5a.5.5 0 01.5.5v4a.5.5 0 01-.5.5H4a.5.5 0 010-1h3.5V4a.5.5 0 01.5-.5z" clip-rule="evenodd"/>  <path fill-rule="evenodd" d="M7.5 8a.5.5 0 01.5-.5h4a.5.5 0 010 1H8.5V12a.5.5 0 01-1 0V8z" clip-rule="evenodd"/>  <path fill-rule="evenodd" d="M8 15A7 7 0 108 1a7 7 0 000 14zm0 1A8 8 0 108 0a8 8 0 000 16z" clip-rule="evenodd"/></svg>
									</a>
								</p>
								<div v-if="replies.length" v-for="(reply, index) in replies" class="pb-3 media" :key="'tl' + reply.id + '_' + index">
									<img :src="reply.account.avatar" class="rounded-circle border mr-3" width="32px" height="32px">
									<div class="media-body">
										<div v-if="reply.sensitive == true">
											<span class="py-3">
												<a class="text-dark font-weight-bold mr-3"  style="font-size: 13px;" :href="profileUrl(reply)" v-bind:title="reply.account.username">{{trimCaption(reply.account.username,15)}}</a>
												<span class="text-break" style="font-size: 13px;">
													<span class="font-italic text-muted">This comment may contain sensitive material</span>
													<span class="text-primary cursor-pointer pl-1" @click="reply.sensitive = false;">Show</span>
												</span>
											</span>
										</div>
										<div v-else>
											<p class="d-flex justify-content-between align-items-top read-more mb-0" style="overflow-y: hidden;">
												<span class="mr-3" style="font-size: 13px;">
													<a class="text-dark font-weight-bold mr-1 text-break" :href="profileUrl(reply)" v-bind:title="reply.account.username">{{trimCaption(reply.account.username,15)}}</a>
													<span class="text-break comment-body" style="word-break: break-all;" v-html="reply.content"></span>
												</span>
												<span class="text-right" style="min-width: 30px;">
													<span v-on:click="likeReply(reply, $event)"><i v-bind:class="[reply.favourited ? 'fas fa-heart fa-sm text-danger':'far fa-heart fa-sm text-lighter']"></i></span>
													<span class="pl-2 text-lighter cursor-pointer" @click="ctxMenu(reply)">
														<span class="fas fa-ellipsis-v text-lighter"></span>
													</span>
												</span>
											</p>
											<p class="mb-0">
												<a v-once class="text-muted mr-3 text-decoration-none small" style="width: 20px;" v-text="timeAgo(reply.created_at)" :href="statusUrl(reply)"></a>
												<span v-if="reply.favourites_count" class="text-muted comment-reaction font-weight-bold mr-3 small">{{reply.favourites_count == 1 ? '1 like' : reply.favourites_count + ' likes'}}</span>
												<span class="small text-muted comment-reaction font-weight-bold cursor-pointer" v-on:click="replyFocus(reply, index, true)">Reply</span>
											</p>
											<div v-if="reply.reply_count > 0" class="cursor-pointer pb-2" v-on:click="toggleReplies(reply)">
												<span class="show-reply-bar"></span>
												<span class="comment-reaction small font-weight-bold">{{reply.thread ? 'Hide' : 'View'}} Replies ({{reply.reply_count}})</span>
											</div>
											<div v-if="reply.thread == true" class="comment-thread">
												<div v-for="(s, sindex) in reply.replies" class="py-1 media" :key="'cr' + s.id + '_' + index">
													<img :src="s.account.avatar" class="rounded-circle border mr-3" width="25px" height="25px">
													<div class="media-body">
														<p class="d-flex justify-content-between align-items-top read-more mb-0" style="overflow-y: hidden;">
															<span class="mr-2" style="font-size: 13px;">
																<a class="text-dark font-weight-bold mr-1" :href="profileUrl(s)" :title="s.account.username">{{s.account.username}}</a>
																<span class="text-break comment-body" style="word-break: break-all;" v-html="s.content"></span>
															</span>
															<span>
																<span v-on:click="likeReply(s, $event)"><i v-bind:class="[s.favourited ? 'fas fa-heart fa-sm text-danger':'far fa-heart fa-sm text-lighter']"></i></span>
															</span>
														</p>
														<p class="mb-0">
															<a v-once class="text-muted mr-3 text-decoration-none small" style="width: 20px;" v-text="timeAgo(s.created_at)" :href="statusUrl(s)"></a>
															<span v-if="s.favourites_count" class="text-muted comment-reaction font-weight-bold mr-3">{{s.favourites_count == 1 ? '1 like' : s.favourites_count + ' likes'}}</span>
														</p>
													</div>
												</div>
											</div>
										</div>
									</div>
								</div>
								<div v-if="!replies.length">
									<p class="text-center text-muted font-weight-bold small">No comments yet</p>
								</div>
							</div>
						</div>
						<div class="card-footer mb-3">
							<div class="align-middle d-flex">
								<img
									:src="profile.avatar"
									width="36"
									height="36"
									class="rounded-circle border mr-3">
								<textarea
									class="form-control rounded-pill"
									name="comment"
									placeholder="Add a comment…"
									autocomplete="off"
									autocorrect="off"
									rows="1"
									maxlength="0"
									style="resize: none;overflow-y: hidden"
									@click="replyFocus(status)">
								</textarea>
							</div>
						</div>
					</div>
				</div>
			</div>
		</div>

		<context-menu
			ref="cMenu"
			:status="ctxMenuStatus"
			:profile="profile"
		/>

		<b-modal ref="replyModal"
			id="ctx-reply-modal"
			hide-footer
			centered
			rounded
			:title-html="status.account ? 'Reply to <span class=text-dark>' + status.account.username + '</span>' : ''"
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
	</div>
</template>

<script type="text/javascript">
	import ContextMenu from './ContextMenu.vue';

	export default {
		props: {
			'status': {
				type: Object
			},

			'profile': {
				type: Object
			},

			'backToStatus': {
				type: Boolean,
				default: false
			}
		},

		components: {
			"context-menu": ContextMenu
		},

		data() {
			return {
				ids: [],
				config: window.App.config,
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
				replies: [],
				replyId: null,
				replyText: '',
				replyNsfw: false,
				replySending: false,
				pagination: {},
				ctxMenuStatus: false,
				emoji: window.App.util.emoji
			}
		},

		beforeMount() {
			this.fetchComments();
		},

		methods: {
			commentNavigateBack(id) {
				if(this.backToStatus) {
					window.location.href = this.statusUrl(this.status);
					return;
				}

				$('nav').show();
				$('footer').show();
				$('.mobile-footer-spacer').attr('style', 'display:block');
				$('.mobile-footer').attr('style', 'display:block');
				this.$emit('current-layout', 'feed');

				let path = '/';
				window.history.pushState({}, '', path);
			},

			trimCaption(caption, len = 60) {
				return _.truncate(caption, {
					length: len
				});
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

			commentSubmit(status, $event) {
				this.replySending = true;
				let id = status.id;
				let comment = this.replyText;
				let limit = this.config.uploader.max_caption_length;
				if(comment.length > limit) {
					this.replySending = false;
					swal('Comment Too Long', 'Please make sure your comment is '+limit+' characters or less.', 'error');
					return;
				}
				axios.post('/i/comment', {
					item: id,
					comment: comment,
					sensitive: this.replyNsfw
				}).then(res => {
					this.replyText = '';
					this.replies.push(res.data.entity);
					this.$refs.replyModal.hide();
				});
				this.replySending = false;
			},

			timeAgo(ts) {
				return App.util.format.timeAgo(ts);
			},

			fetchComments() {
				console.log('Fetching comments...');
				let url = '/api/v2/comments/'+this.status.account.id+'/status/'+this.status.id;
				axios.get(url)
				.then(res => {
					this.replies = res.data.data;
					this.pagination = res.data.meta.pagination;
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

			loadMoreComments() {
				if(this.pagination.total_pages == 1 || this.pagination.current_page == this.pagination.total_pages) {
					$('.load-more-link').addClass('d-none');
					return;
				}
				$('.load-more-link').addClass('d-none');
				$('.postCommentsLoader').removeClass('d-none');
				let next = this.pagination.links.next;
				axios.get(next)
				.then(response => {
					let self = this;
					let res =  response.data.data;
					$('.postCommentsLoader').addClass('d-none');
					for(let i=0; i < res.length; i++) {
						this.replies.unshift(res[i]);
					}
					this.pagination = response.data.meta.pagination;
					$('.load-more-link').removeClass('d-none');
				});
			},

			likeReply(status, $event) {
				if($('body').hasClass('loggedIn') == false) {
					swal('Login', 'Please login to perform this action.', 'info');
					return;
				}

				axios.post('/i/like', {
					item: status.id
				}).then(res => {
					status.favourites_count = res.data.count;
					if(status.favourited == true) {
						status.favourited = false;
					} else {
						status.favourited = true;
					}
				}).catch(err => {
					swal('Error', 'Something went wrong, please try again later.', 'error');
				});
			},

			ctxMenu(status) {
				this.ctxMenuStatus = status;
				this.$refs.cMenu.open();
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
		}

	}
</script>
<style type="text/css" scoped>
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
