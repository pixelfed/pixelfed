<template>
	<div class="comment-drawer-component">
		<input type="file" ref="fileInput" class="d-none" accept="image/jpeg,image/png" @change="handleImageUpload">
		<div v-if="hide"></div>
		<div v-else-if="!isLoaded" class="border-top d-flex justify-content-center py-3">
			<div class="text-center">
				<div
					class="spinner-border text-lighter"
					role="status">
					<span class="sr-only">Loading...</span>
				</div>
				<p class="text-muted">Loading Comments ...</p>
			</div>
		</div>
		<div v-else class="border-top">
			<!-- <div v-if="profile && canReply" class="my-3">
				<div class="d-flex align-items-top reply-form">
					<img class="rounded-circle mr-2 border" :src="avatar" width="38" height="38">
					<div v-if="isUploading" class="w-100">
						<p class="font-weight-light mb-1">Uploading image ...</p>
						<div class="progress rounded-pill" style="height:4px">
							<div class="progress-bar" role="progressbar" :aria-valuenow="uploadProgress" aria-valuemin="0" aria-valuemax="100" :style="{ width: uploadProgress + '%'}"></div>
						</div>
					</div>
					<div v-else class="reply-form-input">
						<input
							class="form-control bg-light border-lighter rounded-pill"
							placeholder="Write a comment...."
							v-model="replyContent"
							v-on:keyup.enter="storeComment">

						<div class="reply-form-input-actions">
							<button
								class="btn btn-link text-muted px-1 mr-2"
								@click="uploadImage">
								<i class="far fa-image fa-lg"></i>
							</button>
							<button class="btn btn-link text-muted px-1 small font-weight-bold border py-0 rounded-pill text-decoration-none">
								GIF
							</button>
						</div>
					</div>
				</div>
			</div> -->
			<div class="my-3">
				<div
					v-for="(status, index) in feed"
					:key="'cdf' + index + status.id"
					class="media media-status align-items-top">

					<a
						v-if="replyChildId == status.id"
						href="#comment-1"
						class="comment-border-link"
						@click.prevent="replyToChild(status)">
						<span class="sr-only">Jump to comment-{{ index }}</span>
					</a>

					<a :href="status.account.url">
						<img class="rounded-circle media-avatar border" :src="status.account.avatar" width="32" height="32" onerror="this.onerror=null;this.src='/storage/avatars/default.png?v=2'" />
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
							<span class="mx-1">·</span>
							<a href="#" class="text-muted font-weight-bold" @click.prevent="replyToChild(status, index)">Reply</a>
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

						<!-- <div v-if="replyChildId == status.id && status.reply_count" class="media media-status align-items-top mt-3">
							<div class="comment-border-arrow"></div>
							<a href="https://pixelfed.test/groups/328821658771132416/user/321493203255693312"><img src="https://pixelfed.test/storage/avatars/321493203255693312/5a6nqo.jpg?v=2" width="32" height="32" class="rounded-circle media-avatar border"></a>
							<div class="media-body"><div class="media-body-comment"><p class="media-body-comment-username"><a href="https://pixelfed.test/groups/328821658771132416/user/321493203255693312">
								dansup
							</a></p> <div class="read-more-component" style="word-break: break-all;"><div>test</div></div></div> <p class="media-body-reactions"><a href="#" class="font-weight-bold text-muted">
								Like
							</a> <span class="mx-1">·</span> <a href="https://pixelfed.test/groups/328821658771132416/p/358529382599041029" class="font-weight-bold text-muted">
							1h
						</a> <span><span class="mx-1">·</span> <a href="#" class="font-weight-bold text-lighter">
									Delete
								</a></span></p>
							</div>
						</div> -->

						<div v-if="replyChildIndex == index && status.hasOwnProperty('children') && status.children.hasOwnProperty('feed') && status.children.feed.length">
							<comment-post
								v-for="(s, index) in status.children.feed"
								:status="s"
								:profile="profile"
								:commentBorderArrow="true"
								:key="'scp_'+index+'_'+s.id"
								/>
						</div>

						<a
							v-if="replyChildIndex == index &&
								status.hasOwnProperty('children') &&
								status.children.hasOwnProperty('can_load_more') &&
								status.children.can_load_more == true"
							class="text-muted font-weight-bold mt-1 mb-0"
							style="font-size: 13px;"
							href="#"
							:disabled="loadingChildComments"
							@click.prevent="loadMoreChildComments(status, index)">
							<div class="comment-border-arrow"></div>
							<i class="far fa-long-arrow-right mr-1"></i>
							{{ loadingChildComments ? 'Loading' : 'Load' }} more comments
						</a>

						<a
							v-else-if="replyChildIndex !== index &&
								status.hasOwnProperty('children') &&
								status.children.hasOwnProperty('can_load_more') &&
								status.children.can_load_more == true &&
								status.reply_count > 0 &&
								!loadingChildComments"
							class="text-muted font-weight-bold mt-1 mb-0"
							style="font-size: 13px;"
							href="#"
							:disabled="loadingChildComments"
							@click.prevent="replyToChild(status, index)">
							<i class="far fa-long-arrow-right mr-1"></i>
							{{ loadingChildComments ? 'Loading' : 'Load' }} more comments
						</a>

						<div v-if="replyChildId == status.id" class="mt-3 mb-3 d-flex align-items-top reply-form child-reply-form">
							<div class="comment-border-arrow"></div>
							<img class="rounded-circle mr-2 border" :src="avatar" width="38" height="38" onerror="this.onerror=null;this.src='/storage/avatars/default.png?v=2'">
							<div v-if="isUploading" class="w-100">
								<p class="font-weight-light mb-1">Uploading image ...</p>
								<div class="progress rounded-pill" style="height:10px">
									<div class="progress-bar progress-bar-striped progress-bar-animated" role="progressbar" :aria-valuenow="uploadProgress" aria-valuemin="0" aria-valuemax="100" :style="{ width: uploadProgress + '%'}"></div>
								</div>
							</div>
							<div v-else class="reply-form-input">
								<input
									class="form-control bg-light border-lighter rounded-pill"
									placeholder="Write a comment...."
									v-model="childReplyContent"
									:disabled="postingChildComment"
									v-on:keyup.enter="storeChildComment(index)">

								<!-- <div class="reply-form-input-actions">
									<button
										class="btn btn-link text-muted px-1 mr-2"
										@click="uploadImage">
										<i class="far fa-image fa-lg"></i>
									</button>
									<button class="btn btn-link text-muted px-1 small font-weight-bold border py-0 rounded-pill text-decoration-none">
										GIF
									</button>
								</div> -->
							</div>
						</div>
					</div>

				</div>
				<!-- <a v-if="permalinkMode && canLoadMore" class="text-muted mb-n1" style="font-size: 13px;font-weight: 600;" href="#">Load more comments ...</a> -->
			</div>
			<button
				v-if="canLoadMore"
				class="btn btn-link btn-sm text-muted mb-2"
				@click="loadMoreComments"
				:disabled="isLoadingMore">

				<span v-if="!isLoadingMore">
					Load more comments ...
				</span>
				<div
					v-else
					class="spinner-border spinner-border-sm text-muted"
					role="status">
					<span class="sr-only">Loading...</span>
				</div>
			</button>

			<div v-if="profile && canReply" class="mt-3 mb-n3">
				<div class="d-flex align-items-top reply-form cdrawer-reply-form">
					<img class="rounded-circle mr-2 border" :src="avatar" width="38" height="38" onerror="this.onerror=null;this.src='/storage/avatars/default.png?v=2'">
					<div v-if="isUploading" class="w-100">
						<p class="font-weight-light small text-muted mb-1">Uploading image ...</p>
						<div class="progress rounded-pill" style="height:10px">
							<div class="progress-bar progress-bar-striped progress-bar-animated" role="progressbar" :aria-valuenow="uploadProgress" aria-valuemin="0" aria-valuemax="100" :style="{ width: uploadProgress + '%'}"></div>
						</div>
					</div>
					<div v-else class="w-100">
                        <div class="reply-form-input">
                            <textarea
                                class="form-control bg-light border-lighter"
                                placeholder="Write a comment...."
                                :rows="replyContent && replyContent.length > 40 ? 4 : 1"
                                v-model="replyContent"></textarea>

    						<div class="reply-form-input-actions">
    							<button
    								class="btn btn-link text-muted px-1 mr-2"
    								@click="uploadImage">
    								<i class="far fa-image fa-lg"></i>
    							</button>
    							<!-- <button class="btn btn-link text-muted px-1 small font-weight-bold border py-0 rounded-pill text-decoration-none">
    								GIF
    							</button> -->
    						</div>
                        </div>
                        <div class="d-flex justify-content-between reply-form-menu">
                            <div class="char-counter">
                                <span>{{ replyContent?.length ?? 0 }}</span>
                                <span>/</span>
                                <span>500</span>
                            </div>

                        </div>
					</div>
                    <button
                        class="btn btn-link btn-sm font-weight-bold align-self-center ml-3 mb-3"
                        @click="storeComment">Post</button>
				</div>
			</div>
		</div>

		<b-modal ref="lightboxModal"
			id="lightbox"
			:hide-header="true"
			:hide-footer="true"
			centered
			size="lg"
			body-class="p-0"
			content-class="bg-transparent border-0"
			>
			<div v-if="lightboxStatus" @click="hideLightbox">
				<img :src="lightboxStatus.url" style="width: 100%;max-height: 90vh;object-fit: contain;">
			</div>
		</b-modal>
	</div>
</template>

<script type="text/javascript">
	import ReadMore from './ReadMore.vue';
	import CommentPost from './CommentPost.vue';

	export default {
		props: {
			groupId: {
				type: String
			},

			profile: {
				type: Object
			},

			status: {
				type: Object
			},

			show: {
				type: Boolean,
				default: false
			},

			permalinkMode: {
				type: Boolean,
				default: false
			},

			permalinkStatus: {
				type: Object
			},

			canReply: {
				type: Boolean,
				default: true
			}
		},

		components: {
			"read-more": ReadMore,
			"comment-post": CommentPost
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
				replyChildIndex: undefined,
				childReplyContent: null,
				postingChildComment: false,
				loadingChildComments: false,
				replyChildMinId: undefined,
                replyCursorId: null
			}
		},

		mounted() {
			if(this.permalinkMode && this.permalinkStatus) {
				let status = this.permalinkStatus;
				if(status.reply_count) {
					status.children = {
						feed: [],
						can_load_more: true
					}
				}
				this.feed.push(status);
				this.isLoaded = true;
				this.canLoadMore = false;
			} else {
				this.fetchComments();
			}

			if(this.profile && this.profile.hasOwnProperty('avatar')) {
				this.avatar = this.profile.avatar;
			}
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
					let data = res.data.map(function(c) {
						if(c.reply_count && c.reply_count > 0) {
							c.children = {
								feed: [],
								can_load_more: true
							}
						}
						return c;
					})
					this.feed = data;
					this.isLoaded = true;
					this.maxReplyId = res.data[(res.data.length - 1)].id;
					if(this.feed.length == 3) {
						this.canLoadMore = true;
					} else {
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
                    setTimeout(() => {
					   this.isLoadingMore = false;
                    }, 500);
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

			storeComment($event) {
                $event.currentTarget?.blur();
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
				axios.post(`/api/v0/groups/comment/${l ? 'like' : 'unlike'}`, {
					sid: status.id,
					gid: this.groupId
				});
			},

			deleteComment(index) {
				if(window.confirm('Are you sure you want to delete this post?') == false) {
					return;
				}

				axios.post('/api/v0/groups/comment/delete', {
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
					this.replyChildIndex = null;
					return;
				} else {
					this.childReplyContent = null;
				}

				this.replyChildId = status.id;
                this.replyCursorId = status.id
				this.replyChildIndex = index;

				if(!status.hasOwnProperty('replies_loaded') || !status.replies_loaded) {
					this.$nextTick(() => {
						this.fetchChildReplies(status, index);
					});
				} else {
                    this.$nextTick(() => {
                        this.fetchChildReplies(status, index);
                    });
				}
			},

			fetchChildReplies(status, index) {
				axios.get('/api/v0/groups/comments', {
					params: {
						gid: this.groupId,
						sid: status.id,
						cid: this.replyCursorId,
						limit: 3
					}
				}).then(res => {
					if(this.feed[index].hasOwnProperty('children')) {
						this.feed[index].children.feed.push(res.data);
						this.feed[index].children.can_load_more = res.data.length == 3;
					} else {
						this.feed[index].children = {
							feed: res.data,
							can_load_more: res.data.length == 3
						}
					}
					this.replyChildMinId = res.data[res.data.length - 1].id;
					this.$nextTick(() => {
						this.feed[index].replies_loaded = true;
					});
				}).catch(err => {
					this.feed[index].children.can_load_more = false;
				})
			},

			storeChildComment(index) {
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
					this.feed[index].children.feed.push(res.data);
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
			},

			loadMoreChildComments(status, index) {
				this.loadingChildComments = true;

				axios.get('/api/v0/groups/comments', {
					params: {
						gid: this.groupId,
						sid: status.id,
						max_id: this.replyChildMinId,
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
					this.replyChildMinId = res.data[res.data.length - 1].id;
					this.feed[index].replies_loaded = true;
					this.loadingChildComments = false;
				}).catch(err => {
				})
			}
		}
	}
</script>

<style lang="scss">
	.comment-drawer-component {
		.media {
			position: relative;

			.comment-border-link {
				display: block;
				position: absolute;
				top: 40px;
				left: 11px;
				width: 10px;
				height: calc(100% - 100px);
				border-left: 4px solid transparent;
				border-right: 4px solid transparent;
				background-color: #E5E7EB;
				background-clip: padding-box;

				&:hover {
					background-color: #BFDBFE;
				}
			}

			.child-reply-form {
				position: relative;
			}

			.comment-border-arrow {
				display: block;
				position: absolute;
				top: -6px;
				left: -33px;
				width: 10px;
				height: 29px;
				border-left: 4px solid transparent;
				border-right: 4px solid transparent;
				background-color: #E5E7EB;
				background-clip: padding-box;
				border-bottom: 2px solid transparent;

				&:after {
					content: '';
					display: block;
					position: absolute;
					top: 25px;
					left: 2px;
					width: 15px;
					height: 2px;
					background-color: #E5E7EB;
				}
			}

			&-status {
				margin-bottom: 1.3rem;
			}

			&-avatar {
				margin-right: 12px;
			}

			&-body {
				&-comment {
					width: fit-content;
					padding: 0.4rem 0.7rem;
					background-color: var(--comment-bg);
					border-radius: 0.9rem;

					&-username {
						margin-bottom: 0.25rem !important;
						font-size: 14px;
						font-weight: 700 !important;
						color: #000;

						a {
							color: #000;
							text-decoration: none;
						}
					}

					&-content {
						margin-bottom: 0;
						font-size: 16px;
					}
				}

				&-reactions {
					margin-top: 0.25rem !important;
					margin-bottom: 0 !important;
					color: #B8C2CC !important;
					font-size: 12px;
				}
			}
		}

		.load-more-comments {
			font-weight: 500;
		}

		.reply-form {
			margin-bottom: 2rem;

			&-input {
				flex: 1;
				position: relative;

                textarea {
                    border-radius: 10px;
                }

                .form-control {
                    resize: none;
                    padding-right: 100px;
                }

				&-actions {
					position: absolute;
					right: 10px;
					top: 50%;
					transform: translateY(-50%);
				}
			}

            .btn {
                text-decoration: none;
            }

            &-menu {
                margin-top: 5px;

                .char-counter {
                    color: var(--muted);
                    font-size: 10px;
                }
            }

		}

		.bh-comment {
			width: 100%;
			height: auto;
			max-width: 160px !important;
			max-height: 260px !important;

			span {
				width: 100%;
				height: auto;
				max-width: 160px !important;
				max-height: 260px !important;
			}

			img {
				width: 100%;
				height: auto;
				max-width: 160px !important;
				max-height: 260px !important;
				object-fit: cover;
			}
		}
	}
</style>
