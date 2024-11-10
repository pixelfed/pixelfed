<template>
	<div class="comment-post-component">
		<div class="media media-status align-items-top mt-3">
			<div v-if="commentBorderArrow" class="comment-border-arrow"></div>
			<!-- <a
				v-if="replyChildId == status.id"
				href="#comment-1"
				class="comment-border-link"
				@click.prevent="replyToChild(status)">
				<span class="sr-only">Jump to comment-{{ index }}</span>
			</a> -->

			<a :href="status.account.url">
				<img class="rounded-circle media-avatar border" :src="status.account.avatar" width="32" height="32" onerror="this.onerror=null;this.src='/storage/avatars/default.png?v=2'">
			</a>

			<div class="media-body">
				<div v-if="!status.media_attachments.length" class="media-body-comment">
					<p class="media-body-comment-username">
						<a :href="status.account.url">
							{{status.account.acct}}
						</a>
					</p>
					<read-more :status="status" />
				</div>
				<div v-else>
					<p class="media-body-comment-username">
						<a :href="status.account.url">
							{{status.account.acct}}
						</a>
					</p>
					<div class="bh-comment" @click="lightbox(status)">
						<blur-hash-image
							:width="blurhashWidth(status)"
							:height="blurhashHeight(status)"
							:punch="1"
							class="img-fluid rounded-lg border shadow"
							:hash="status.media_attachments[0].blurhash"
							:src="getMediaSource(status)" />
					</div>
				</div>
				<p class="media-body-reactions">
					<a
						v-if="profile"
						href="#"
						class="font-weight-bold"
						:class="[ status.favourited ? 'text-primary' : 'text-muted' ]"
						@click.prevent="likeComment(status, index, $event)">
							{{ status.favourited ? 'Liked' : 'Like' }}
						</a>
					<!-- <span class="mx-1">·</span> -->
					<!-- <a href="#" class="text-muted font-weight-bold" @click.prevent="replyToChild(status, index)">Reply</a> -->
					<span v-if="profile" class="mx-1">·</span>
					<a
						class="font-weight-bold text-muted"
						:href="status.url"
						v-once>
						{{ shortTimestamp(status.created_at) }}
					</a>
					<span v-if="profile && status.account.id === profile.id">
						<span class="mx-1">·</span>
						<a
							class="font-weight-bold text-lighter"
							href="#"
							@click.prevent="deleteComment(index)">
								Delete
							</a>
					</span>
				</p>
			</div>
		</div>
	</div>
</template>

<script type="text/javascript">
	import ReadMore from './ReadMore.vue';

	export default {
		props: {
			groupId: {
				type: String
			},

			profile: {
				type: Object
			},

			status: {
				type: Object,
			},

			commentBorderArrow: {
				type: Boolean,
				default: false
			}
		},


		components: {
			"read-more": ReadMore
		},

		data() {
			return {
				isLoaded: false,
				hide: false,
				feed: [],
				canLoadMore: false,
				isLoadingMore: false,
				replyContent: null,
				maxReplyId: null,
				readMoreCursor: 200,
				avatar: '/storage/avatars/default.png',
				isUploading: false,
				uploadProgress: 0,
				lightboxStatus: null,
				replyChildId: undefined,
				childReplyContent: null,
				postingChildComment: false
			}
		},

		mounted() {
			console.log(this.status);
			// if(this.permalinkMode && this.permalinkStatus) {
			// 	this.feed.push(this.permalinkStatus);
			// 	this.isLoaded = true;
			// 	this.canLoadMore = false;
			// } else {
			// 	this.fetchComments();
			// }

			// if(this.profile && this.profile.hasOwnProperty('avatar')) {
			// 	this.avatar = this.profile.avatar;
			// }
		},

		methods: {
			fetchComments() {
				axios.get('/api/v0/groups/comments', {
					params: {
						gid: this.groupId,
						sid: this.status.id,
						limit: 3
					}
				}).then(res => {
					this.feed = res.data;
					this.isLoaded = true;
					this.maxReplyId = res.data[(res.data.length - 1)].id;
					if(this.feed.length == 3) {
						this.canLoadMore = true;
					}
				}).catch(err => {
					this.isLoaded = true;
				})
			},

			loadMoreComments() {
				this.isLoadingMore = true;

				axios.get('/api/v0/groups/comments', {
					params: {
						gid: this.groupId,
						sid: this.status.id,
						limit: 3,
						max_id: this.maxReplyId
					}
				}).then(res => {
					if(res.data[res.data.length - 1].id == this.maxReplyId) {
						this.isLoadingMore = false;
						this.canLoadMore = false;
						return;
					}
					this.feed.push(...res.data);
					this.isLoadingMore = false;
					this.maxReplyId = res.data[res.data.length - 1].id;
					if(res.data.length > 0) {
						this.canLoadMore = true;
					} else {
						this.canLoadMore = false;
					}
				}).catch(err => {
					this.isLoadingMore = false;
					this.canLoadMore = false;
				})
			},

			storeComment() {
				axios.post('/api/v0/groups/comment', {
					gid: this.groupId,
					sid: this.status.id,
					content: this.replyContent
				})
				.then(res => {
					this.replyContent = null;
					this.feed.unshift(res.data);
				}).catch(err => {
					if(err.response.status == 422) {
						this.isUploading = false;
						this.uploadProgress = 0;
						swal('Oops!', err.response.data.error, 'error');
					} else {
						this.isUploading = false;
						this.uploadProgress = 0;
						swal('Oops!', 'An error occured while processing your request, please try again later', 'error');
					}
				})
			},

			shortTimestamp(ts) {
				return window.App.util.format.timeAgo(ts);
			},

			readMore() {
				this.readMoreCursor = this.readMoreCursor + 200;
			},

			likeComment(status, index, $event) {
				$event.target.blur();
				let l = status.favourited ? false : true;
				this.feed[index].favourited = l;
				status.favourited = l;
				axios.post('/api/v0/groups/like', {
					sid: status.id,
					gid: this.groupId
				});
			},

			deleteComment(index) {
				if(window.confirm('Are you sure you want to delete this post?') == false) {
					return;
				}

				axios.post('/api/v0/groups/status/delete', {
					gid: this.groupId,
					id: this.feed[index].id
				}).then(res => {
					this.feed.splice(index, 1);
				}).catch(err => {
					console.log(err.response);
					swal('Error', 'Something went wrong. Please try again later.', 'error');
				});
			},

			uploadImage() {
				this.$refs.fileInput.click();
			},

			handleImageUpload() {
				if(!this.$refs.fileInput.files.length) {
					return;
				}
				this.isUploading = true;
				let self = this;
				let data = new FormData();
				data.append('gid', this.groupId);
				data.append('sid', this.status.id);
				data.append('photo', this.$refs.fileInput.files[0]);

				axios.post('/api/v0/groups/comment/photo', data, {
					onUploadProgress: function(progressEvent) {
						self.uploadProgress = Math.floor(progressEvent.loaded / progressEvent.total * 100);
					}
				})
				.then(res => {
					this.isUploading = false;
					this.uploadProgress = 0;
					this.feed.unshift(res.data);
				}).catch(err => {
					if(err.response.status == 422) {
						this.isUploading = false;
						this.uploadProgress = 0;
						swal('Oops!', err.response.data.error, 'error');
					} else {
						this.isUploading = false;
						this.uploadProgress = 0;
						swal('Oops!', 'An error occured while processing your request, please try again later', 'error');
					}
				});
			},

			lightbox(status) {
				this.lightboxStatus = status.media_attachments[0];
				this.$refs.lightboxModal.show();
			},

			hideLightbox() {
				this.lightboxStatus = null;
				this.$refs.lightboxModal.hide();
			},

			blurhashWidth(status) {
				if(!status.media_attachments[0].meta) {
					return 25;
				}
				let aspect = status.media_attachments[0].meta.original.aspect;
				if(aspect == 1) {
					return 25;
				} else if(aspect > 1) {
					return 30;
				} else {
					return 20;
				}
			},

			blurhashHeight(status) {
				if(!status.media_attachments[0].meta) {
					return 25;
				}
				let aspect = status.media_attachments[0].meta.original.aspect;
				if(aspect == 1) {
					return 25;
				} else if(aspect > 1) {
					return 20;
				} else {
					return 30;
				}
			},

			getMediaSource(status) {
				let media = status.media_attachments[0];

				if(media.preview_url.endsWith('storage/no-preview.png')) {
					return media.url;
				}

				return media.preview_url;
			},

			replyToChild(status, index) {
				if(this.replyChildId == status.id) {
					this.replyChildId = null;
					return;
				} else {
					this.childReplyContent = null;
				}

				this.replyChildId = status.id;

				if(!status.hasOwnProperty('replies_loaded') || !status.replies_loaded) {
					this.fetchChildReplies(status, index);
				} else {

				}
			},

			fetchChildReplies(status, index) {
				axios.get('/api/v0/groups/comments', {
					params: {
						gid: this.groupId,
						sid: status.id,
						cid: 1,
						limit: 3
					}
				}).then(res => {
					if(this.feed[index].hasOwnProperty('children')) {
						this.feed[index].children.feed.push(...res.data);
						this.feed[index].children.can_load_more = res.data.length == 3;
					} else {
						this.feed[index].children = {
							feed: res.data,
							can_load_more: res.data.length == 3
						}
					}
					this.feed[index].replies_loaded = true;
				}).catch(err => {
				})
			},

			storeChildComment() {
				this.postingChildComment = true;

				axios.post('/api/v0/groups/comment', {
					gid: this.groupId,
					sid: this.status.id,
					cid: this.replyChildId,
					content: this.childReplyContent
				})
				.then(res => {
					this.childReplyContent = null;
					this.postingChildComment = false;
					// this.feed.unshift(res.data);
				}).catch(err => {
					if(err.response.status == 422) {
						// this.isUploading = false;
						// this.uploadProgress = 0;
						swal('Oops!', err.response.data.error, 'error');
					} else {
						// this.isUploading = false;
						// this.uploadProgress = 0;
						swal('Oops!', 'An error occured while processing your request, please try again later', 'error');
					}
				});

				console.log(this.replyChildId);
			}
		}
	}
</script>

<style lang="scss">
	.comment-post-component {

	}
</style>
