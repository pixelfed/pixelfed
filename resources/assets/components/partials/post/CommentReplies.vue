<template>
	<div class="comment-replies-component">
		<div v-if="loading" class="mt-n2">
			<div class="ph-item border-0 mb-0 p-0 bg-transparent" style="border-radius:15px;margin-left:-14px;">
				<div class="ph-col-12 mb-0">
					<div class="ph-row align-items-center mt-0">
						<div class="ph-avatar mr-3 d-flex" style="min-width: 40px;width:40px!important;height:40px!important;border-radius: 8px;"></div>
						<div class="ph-col-6"></div>
					</div>
				</div>
			</div>
		</div>
		<template v-else>
			<transition-group tag="div" enter-active-class="animate__animated animate__fadeIn" leave-active-class="animate__animated animate__fadeOut" mode="out-in">
				<div
					v-for="(post, idx) in feed"
					:key="'cd:' + post.id + ':' + idx">
					<div class="media media-status align-items-top mb-3">
						<a href="#l">
							<img class="shadow-sm media-avatar border" :src="post.account.avatar" width="40" height="40" draggable="false" onerror="this.onerror=null;this.src='/storage/avatars/default.jpg?v=0';">
						</a>

						<div class="media-body">
							<div class="media-body-wrapper">
								<div v-if="!post.media_attachments.length" class="media-body-comment">
									<p class="media-body-comment-username">
										<a :href="post.account.url" @click.prevent="goToProfile(post.account)">
											{{ post.account.acct }}
										</a>
									</p>

									<span v-if="post.sensitive">
										<p class="mb-0">
											{{ $t('common.sensitiveContentWarning') }}
										</p>
										<a href="#" class="small font-weight-bold primary" @click.prevent="post.sensitive = false">Show</a>
									</span>

									<!-- <span v-else v-html="post.content"></span> -->
									<read-more v-else :status="post" />

									<button
										v-if="post.favourites_count"
										class="btn btn-link media-body-likes-count shadow-sm"
										@click.prevent="showLikesModal(idx)">
										<i class="far fa-thumbs-up primary"></i>
										<span class="count">{{ prettyCount(post.favourites_count) }}</span>
									</button>
								</div>

								<div v-else>
									<p class="media-body-comment-username">
										<a :href="post.account.url" @click.prevent="goToProfile(post.account)">
											{{ post.account.acct }}
										</a>
									</p>
									<div v-if="post.sensitive" class="bh-comment" @click="post.sensitive = false">
										<blur-hash-image
											:width="blurhashWidth(post)"
											:height="blurhashHeight(post)"
											:punch="1"
											class="img-fluid border shadow"
											:hash="post.media_attachments[0].blurhash"
											 />

										<div class="sensitive-warning">
											<p class="mb-0"><i class="far fa-eye-slash fa-lg"></i></p>
											<p class="mb-0 small">Click to view</p>
										</div>
									</div>

									<div v-else class="bh-comment">
										<div @click="lightbox(post)">
											<blur-hash-image
												:width="blurhashWidth(post)"
												:height="blurhashHeight(post)"
												:punch="1"
												class="img-fluid border shadow"
												:hash="post.media_attachments[0].blurhash"
												:src="getMediaSource(post)"
											 />
										</div>

										<button
											v-if="post.favourites_count"
											class="btn btn-link media-body-likes-count shadow-sm"
											@click.prevent="showLikesModal(idx)">
											<i class="far fa-thumbs-up primary"></i>
											<span class="count">{{ prettyCount(post.favourites_count) }}</span>
										</button>
									</div>
								</div>
							</div>

							<p class="media-body-reactions">
								<button
									class="btn btn-link font-weight-bold btn-sm p-0"
									:class="[ post.favourited ? 'primary' : 'text-muted' ]"
									@click="likeComment(idx)">
									{{ post.favourited ? 'Liked' : 'Like' }}
								</button>
								<!-- <span class="mx-1">·</span>
								<a class="font-weight-bold text-muted" :href="post.url" @click.prevent="toggleCommentReply(idx)">
									Reply
								</a> -->
								<span class="mx-1">·</span>
								<a class="font-weight-bold text-muted" :href="post.url" @click.prevent="goToPost(post)" v-once>
									{{ timeago(post.created_at) }}
								</a>
								<span v-if="profile && post.account.id === profile.id">
									<span class="mx-1">·</span>
									<a
										class="font-weight-bold text-muted"
										href="#"
										@click.prevent="deleteComment(idx)">
										Delete
									</a>
								</span>

								<span v-else>
									<span class="mx-1">·</span>
									<a
										class="font-weight-bold text-muted"
										href="#"
										@click.prevent="reportComment(idx)">
										Report
									</a>
								</span>
							</p>

							<!-- <div class="d-flex align-items-top reply-form child-reply-form my-3">
								<img class="shadow-sm media-avatar border" :src="profile.avatar" width="40" height="40">

								<input
									class="form-control bg-light rounded-pill shadow-sm" style="border-color: #e2e8f0 !important;"
									placeholder="Write a comment...."
									v-model="replyContent"
									v-on:keyup.enter="storeComment"
									:disabled="isPostingReply" />

								<div class="reply-form-input-actions">
									<button
										class="btn btn-link text-muted px-1 mr-2">
										<i class="far fa-image fa-lg"></i>
									</button>
									<button
										class="btn btn-link text-muted px-1 small font-weight-bold py-0 rounded-pill text-decoration-none"
										@click="toggleShowReplyOptions">
										<i class="far fa-ellipsis-h"></i>
									</button>
								</div>
							</div> -->
						</div>
					</div>
				</div>
			</transition-group>
		</template>
	</div>
</template>

<script type="text/javascript">
	import ReadMore from './ReadMore.vue';

	export default {
		props: {
			status: {
				type: Object
			},

			feed: {
				type: Array
			}
		},

		components: {
			ReadMore
		},

		data() {
			return {
				loading: true,
				profile: window._sharedData.user,
				ids: [],
				nextUrl: undefined,
				canLoadMore: false,
			}
		},

		watch: {
			feed: {
				deep: true,
				immediate: true,
				handler(o, n) {
					this.loading = false;
				}
			}
		},

		methods: {
			fetchContext() {
				axios.get('/api/v2/statuses/' + this.status.id + '/replies', {
					params: {
						limit: 3
					}
				})
				.then(res => {
					if(res.data.next) {
						this.nextUrl = res.data.next;
						this.canLoadMore = true;
					}
					res.data.data.forEach(post => {
						this.ids.push(post.id);
						this.feed.push(post);
					});

					if(!res.data || !res.data.data || !res.data.data.length && this.status.reply_count) {
						this.showEmptyRepliesRefresh = true;
					}
				})
			},

			fetchMore(limit = 3) {
				axios.get(this.nextUrl, {
					params: {
						limit: limit,
						sort: this.sorts[this.sortIndex]
					}
				}).then(res => {
					this.feedLoading = false;
					if(!res.data.next) {
						this.canLoadMore = false;
					}

					this.nextUrl = res.data.next;

					res.data.data.forEach(post => {
						if(this.ids.indexOf(post.id) == -1) {
							this.ids.push(post.id);
							this.feed.push(post);
						}
					});
				})
			},

			fetchSortedFeed() {
				axios.get('/api/v2/statuses/' + this.status.id + '/replies', {
					params: {
						limit: 3,
						sort: this.sorts[this.sortIndex]
					}
				})
				.then(res => {
					this.feed = res.data.data;
					this.nextUrl = res.data.next;
					this.feedLoading = false;
				});
			},

			forceRefresh() {
				axios.get('/api/v2/statuses/' + this.status.id + '/replies', {
					params: {
						limit: 3,
						refresh_cache: true
					}
				})
				.then(res => {
					if(res.data.next) {
						this.nextUrl = res.data.next;
						this.canLoadMore = true;
					}
					res.data.data.forEach(post => {
						this.ids.push(post.id);
						this.feed.push(post);
					});

					this.showEmptyRepliesRefresh = false;
				})
			},

			timeago(ts) {
				return App.util.format.timeAgo(ts);
			},

			prettyCount(val) {
				return App.util.format.count(val);
			},

			goToPost(post) {
				this.$router.push({
					name: 'post',
					path: `/i/web/post/${post.id}`,
					params: {
						id: post.id,
						cachedStatus: post,
						cachedProfile: this.profile
					}
				})
			},

			goToProfile(account) {
				this.$router.push({
					name: 'profile',
					path: `/i/web/profile/${account.id}`,
					params: {
						id: account.id,
						cachedProfile: account,
						cachedUser: this.profile
					}
				})
			},

			storeComment() {
				this.isPostingReply = true;

				axios.post('/api/v1/statuses', {
					status: this.replyContent,
					in_reply_to_id: this.status.id,
					sensitive: this.settings.sensitive
				})
				.then(res => {
					this.replyContent = undefined;
					this.isPostingReply = false;
					this.ids.push(res.data.id);
					this.feed.push(res.data);
                    this.$emit('new-comment', res.data);
				})
			},

			toggleSort(index) {
				this.$refs.sortMenu.hide();
				this.feedLoading = true;
				this.sortIndex = index;
				this.fetchSortedFeed();
			},

			deleteComment(index) {
				event.currentTarget.blur();

				if(!window.confirm(this.$t('menu.deletePostConfirm'))) {
					return;
				}

				axios.post('/i/delete', {
					type: 'status',
					item: this.feed[index].id
				})
				.then(res => {
					this.feed.splice(index, 1);
					this.$emit('counter-change', 'comment-decrement');
					this.fetchMore(1);
				})
				.catch(err => {

				})
			},

			showLikesModal(index) {
				this.$emit('show-likes', this.feed[index]);
			},

			reportComment(index) {
				// location.href = '/i/report?type=post&id=' + this.feed[index].id;
				this.$emit('handle-report', this.feed[index]);
			},

			likeComment(index) {
				event.currentTarget.blur();
				let post = this.feed[index];
				let count = post.favourites_count;
				let state = post.favourited;
				this.feed[index].favourited = !this.feed[index].favourited;
				this.feed[index].favourites_count = state ? count - 1 : count + 1;

				axios.post('/api/v1/statuses/' + post.id + '/' + (state ? 'unfavourite' : 'favourite'))
				.then(res => {

				})
			},

			toggleShowReplyOptions() {
				event.currentTarget.blur();
				this.showReplyOptions = !this.showReplyOptions;
			},

			replyUpload() {
				event.currentTarget.blur();
				this.$refs.fileInput.click();
			},

			handleImageUpload() {
				if(!this.$refs.fileInput.files.length) {
					return;
				}

				this.isUploading = true;
				let self = this;
				let data = new FormData();
				data.append('file', this.$refs.fileInput.files[0]);

				axios.post('/api/v1/media', data)
				.then(res => {
					axios.post('/api/v1/statuses', {
						media_ids: [ res.data.id ],
						in_reply_to_id: this.status.id,
						sensitive: this.settings.sensitive
					}).then(res => {
						this.feed.push(res.data)
					})
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

			toggleReplyExpand() {
				event.currentTarget.blur();
				this.settings.expanded = !this.settings.expanded;
			},

			toggleCommentReply(index) {
				this.commentReplyIndex = index;
			}
		}
	}
</script>
