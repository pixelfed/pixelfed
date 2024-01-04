<template>
	<div>
		<div v-if="loaded">
			<div v-if="showReplyForm" class="card card-body shadow-none border bg-light">
				<div class="media">
					<img :src="profile.avatar" class="rounded-circle border mr-3" width="32px" height="32px">
					<div class="media-body">
						<div class="reply-form form-group mb-0">
							<input v-if="!composeText || composeText.length < 40" class="form-control rounded-pill" placeholder="Add a comment..." v-model="composeText">
							<textarea v-else class="form-control" placeholder="Add a comment..." v-model="composeText" rows="4"></textarea>
							<div v-if="composeText && composeText.length" class="btn btn-primary btn-sm rounded-pill font-weight-bold px-3" @click="submitComment">
								<span v-if="postingComment">
									<div class="spinner-border spinner-border-sm" role="status">
										<span class="sr-only">Loading...</span>
									</div>
								</span>
								<span v-else>Post</span>
							</div>
						</div>

						<div v-if="composeText" class="reply-options">
							<select class="form-control form-control-sm rounded-pill font-weight-bold" v-model="visibility">
								<option value="public">Public</option>
								<option value="private">Followers Only</option>
							</select>
							<div class="custom-control custom-switch">
								<input type="checkbox" class="custom-control-input" id="sensitive" v-model="sensitive">
								<label class="custom-control-label font-weight-bold text-lighter" for="sensitive">
									<span class="d-none d-md-inline-block">Sensitive/</span>NSFW
								</label>
							</div>
							<span class="text-muted font-weight-bold small">
								{{ composeText.length }} / 500
							</span>
						</div>
					</div>
				</div>
			</div>

			<div class="d-none card card-body shadow-none border rounded-0 border-top-0 bg-light">
				<div class="d-flex justify-content-between align-items-center">
					<p class="font-weight-bold text-muted mb-0 mr-md-5">
						<i class="fas fa-comment mr-1"></i>
						{{ formatCount(pagination.total) }}
					</p>
					<h4 class="font-weight-bold mb-0 text-lighter">Comments</h4>
					<div class="form-group mb-0">
						<select class="form-control form-control-sm">
							<option>New</option>
							<option>Oldest</option>
						</select>
					</div>
				</div>
			</div>

			<status-card v-for="(reply, index) in feed" :key="'replies:'+index" :status="reply" size="small" />

			<div v-if="pagination.links.hasOwnProperty('next')" class="card card-body shadow-none rounded-0 border border-top-0 py-3">
				<button v-if="loadingMoreComments" class="btn btn-primary" disabled>
					<div class="spinner-border spinner-border-sm" role="status">
						<span class="sr-only">Loading...</span>
					</div>
				</button>
				<button v-else class="btn btn-primary font-weight-bold" @click="loadMoreComments">Load more comments</button>
			</div>

			<context-menu
				v-if="ctxStatus && profile"
				ref="cMenu"
				:status="ctxStatus"
				:profile="profile"
				v-on:status-delete="statusDeleted" />

		</div>
		<div v-else>
		</div>
	</div>
</template>

<script type="text/javascript">
	import ContextMenu from './ContextMenu.vue';
	import StatusCard from './StatusCard.vue';

	export default {
		props: {
			status: {
				type: Object,
			},

			currentProfile: {
				type: Object
			},

			showReplyForm: {
				type: Boolean,
				default: true
			}
		},

		components: {
			"context-menu": ContextMenu,
			"status-card": StatusCard
		},

		data() {
			return {
				loaded: false,
				profile: undefined,
				feed: [],
				pagination: undefined,
				ctxStatus: false,
				composeText: null,
				visibility: 'public',
				sensitive: false,
				postingComment: false,
				loadingMoreComments: false,
				page: 2
			}
		},

		beforeMount() {
			this.fetchProfile();
		},

		mounted() {
			// if(this.currentProfile && !this.currentProfile.hasOwnProperty('id')) {
			// } else {
			// 	this.profile = this.currentProfile;
			// }
		},

		methods: {
			fetchProfile() {
				axios.get('/api/pixelfed/v1/accounts/verify_credentials').then(res => {
					this.profile = res.data;
				});
				this.fetchComments();
			},

			fetchComments() {
				let url = '/api/v2/comments/'+this.status.account.id+'/status/'+this.status.id;
				axios.get(url)
					.then(res => {
						this.feed = res.data.data;
						this.pagination = res.data.meta.pagination;
						this.loaded = true;
					}).catch(error => {
						this.loaded = true;
						if(!error.response) {

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

			trimCaption(caption) {
				return caption;
			},

			profileUrl(status) {
				return status.url;
			},

			statusUrl(status) {
				return status.url;
			},

			replyFocus() {

			},

			likeReply() {

			},

			timeAgo(ts) {
				return App.util.format.timeAgo(ts);
			},

			statusDeleted() {

			},

			ctxMenu(index) {
				this.ctxStatus = this.feed[index];
				setTimeout(() => {
					this.$refs.cMenu.open();
				}, 300);
			},

			submitComment() {
				this.postingComment = true;

				let data = {
					item: this.status.id,
					comment: this.composeText,
					sensitive: this.sensitive
				}

				let self = this;

				axios.post('/i/comment', data)
				.then(res => {
					self.composeText = null;
					let entity = res.data.entity;
					self.postingComment = false;
					self.feed.unshift(entity);
					self.pagination.total++;
				}).catch(err => {
					swal('Oops!', 'An error occured, please try again later.', 'error');
					self.postingComment = false;
				})
			},

			formatCount(i) {
				return App.util.format.count(i);
			},

			loadMoreComments() {
				let self = this;
				this.loadingMoreComments = true;
				let url = '/api/v2/comments/'+this.status.account.id+'/status/'+this.status.id;
				axios.get(url, {
					params: {
						page: this.page
					}
				}).then(res => {
					self.feed.push(...res.data.data);
					self.pagination = res.data.meta.pagination;
					self.loadingMoreComments = false;
					self.page++;
				}).catch(error => {
					self.loadingMoreComments = false;
				});
			}
		}
	}
</script>

<style lang="scss" scoped>
	.reply-form {
		position:relative;

		input {
			padding-right: 90px;
		}

		textarea {
			padding-right: 80px;
			align-items: center;
		}

		.btn {
			position:absolute;
			top: 50%;
			transform: translateY(-50%);
			right: 6px;
		}
	}

	.reply-options {
		display: flex;
		justify-content: space-between;
		align-items: center;
		margin-top: 15px;

		.form-control {
			max-width: 140px;
		}
	}
</style>
