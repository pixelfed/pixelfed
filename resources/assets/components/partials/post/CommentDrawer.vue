<template>
	<div class="post-comment-drawer">
		<input type="file" ref="fileInput" class="d-none" accept="image/jpeg,image/png" @change="handleImageUpload">

		<div class="post-comment-drawer-feed">
			<div v-if="feed.length && feed.length >= 1" class="mb-2 sort-menu">
				<b-dropdown size="sm" variant="link" ref="sortMenu" toggle-class="text-decoration-none text-dark font-weight-bold" no-caret>
					<template #button-content>
						Show {{ sorts[sortIndex] }} comments <i class="far fa-chevron-down ml-1"></i>
					</template>
					<b-dropdown-item href="#" :class="{ active: sortIndex === 0 }" @click="toggleSort(0)">
						<p class="title mb-0">All</p>
						<p class="description">All comments in chronological order</p>
					</b-dropdown-item>
					<b-dropdown-item href="#" :class="{ active: sortIndex === 1 }" @click="toggleSort(1)">
						<p class="title mb-0">Newest</p>
						<p class="description">Newest comments appear first</p>
					</b-dropdown-item>
					<b-dropdown-item href="#" :class="{ active: sortIndex === 2 }" @click="toggleSort(2)">
						<p class="title mb-0">Popular</p>
						<p class="description">The most relevant comments appear first</p>
					</b-dropdown-item>
				</b-dropdown>
			</div>

			<div v-if="feedLoading" class="post-comment-drawer-feed-loader">
				<b-spinner />
			</div>

			<div v-else>
				<transition-group tag="div" enter-active-class="animate__animated animate__fadeIn" leave-active-class="animate__animated animate__fadeOut" mode="out-in">
					<div
						v-for="(post, idx) in feed"
						:key="'cd:' + post.id + ':' + idx"
						class="media media-status align-items-top mb-3"
                        :style="{ opacity: deletingIndex && deletingIndex === idx ? 0.3 : 1 }">

						<a href="#l">
							<img class="shadow-sm media-avatar border" :src="getPostAvatar(post)" width="40" height="40" draggable="false" onerror="this.onerror=null;this.src='/storage/avatars/default.jpg?v=0';">
						</a>

						<div class="media-body">
							<div class="media-body-wrapper">
								<div v-if="!post.media_attachments.length" class="media-body-comment">
									<p class="media-body-comment-username">
										<a :href="post.account.url" :id="'acpop_'+post.id" tabindex="0" @click.prevent="goToProfile(post.account)">
											{{ post.account.acct }}
										</a>

										<b-popover :target="'acpop_'+post.id" triggers="hover" placement="bottom" custom-class="shadow border-0 rounded-px" :delay="750">
											<profile-hover-card
												:profile="post.account"
												v-on:follow="follow(idx)"
												v-on:unfollow="unfollow(idx)" />
										</b-popover>
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
										v-if="post.favourites_count && !hideCounts"
										class="btn btn-link media-body-likes-count shadow-sm"
										@click.prevent="showLikesModal(idx)">
										<i class="far fa-thumbs-up primary"></i>
										<span class="count">{{ prettyCount(post.favourites_count) }}</span>
									</button>
								</div>

								<div v-else>
                                    <div :class="[ post.content && post.content.length || post.media_attachments.length ? 'media-body-comment' : '']">
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
    											<p class="mb-0 small">Tap to view</p>
    										</div>
    									</div>

                                        <read-more :status="post" class="mb-1" />

    									<div v-if="!post.sensitive"
                                            class="bh-comment"
                                            :class="[post.media_attachments.length > 1 ? 'bh-comment-borderless' : '']"
                                            :style="{
                                                'max-width': post.media_attachments.length > 1 ? '100% !important' : '160px',
                                                'max-height': post.media_attachments.length > 1 ? '100% !important' : '260px',
                                            }">
                                            <div v-if="post.media_attachments[0].type == 'image'">
                                                <div v-if="post.media_attachments.length == 1">
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
                                                </div>
                                                <div v-else
                                                    style="
                                                        display: grid;
                                                        grid-auto-flow:column;
                                                        gap:1px;
                                                        grid-template-rows: [row1-start] 50% [row1-end row2-start] 50% [row2-end];
                                                        grid-template-columns: [column1-start] 50% [column1-end column2-start] 50% [column2-end];
                                                        border-radius: 8px;
                                                    ">
                                                    <div v-for="(albumMedia, idx) in post.media_attachments.slice(0, 4)" @click="lightbox(post, idx)">
                                                        <blur-hash-image
                                                            :width="30"
                                                            :height="30"
                                                            :punch="1"
                                                            class="img-fluid shadow"
                                                            :hash="post.media_attachments[idx].blurhash"
                                                            :src="getMediaSource(post, idx)"
                                                         />
                                                    </div>
                                                </div>
                                            </div>

                                            <div v-else="post.media_attachments[0].type == 'vaideo'">
                                                <div @click="lightbox(post)" class="cursor-pointer">
                                                    <div style="position: relative;" class="d-flex align-items-center justify-content-center">
                                                        <div style="position: absolute;width: 40px; height: 40px; background-color: rgba(0, 0, 0, 0.5);border-radius: 40px;" class="d-flex justify-content-center align-items-center">
                                                            <i class="far fa-play pl-1 text-white fa-lg"></i>
                                                        </div>
                                                        <video :src="post.media_attachments[0].url" class="img-fluid" style="max-height: 200px"/>
                                                    </div>
                                                </div>
                                            </div>

                                            <div v-else>
                                                <p>Cannot render commment</p>
                                            </div>

    										<button
    											v-if="post.favourites_count && !hideCounts"
    											class="btn btn-link media-body-likes-count shadow-sm"
    											@click.prevent="showLikesModal(idx)">
    											<i class="far fa-thumbs-up primary"></i>
    											<span class="count">{{ prettyCount(post.favourites_count) }}</span>
    										</button>
                                        </div>
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
								<template v-if="post.visibility != 'public'">
									<span class="mx-1">·</span>
									<span
										v-if="post.visibility === 'unlisted'"
										class="text-lighter"
										v-b-tooltip:hover.bottom
										title="This post is unlisted on timelines">
										<i class="far fa-unlock fa-sm"></i>
									</span>
									<span
										v-else-if="post.visibility === 'private'"
										class="text-muted"
										v-b-tooltip:hover.bottom
										title="This post is only visible to followers of this account">
										<i class="far fa-lock fa-sm"></i>
									</span>
								</template>
								<span class="mx-1">·</span>
								<a class="font-weight-bold text-muted" :href="post.url" @click.prevent="toggleCommentReply(idx)">
									Reply
								</a>
								<span class="mx-1">·</span>
								<a class="font-weight-bold text-muted" :href="post.url" @click.prevent="goToPost(post)" v-once>
									{{ timeago(post.created_at) }}
								</a>
								<span v-if="profile && post.account.id === profile.id || status.account.id === profile.id">
									<span class="mx-1">·</span>
									<a
										class="font-weight-bold"
                                        :class="[deletingIndex && deletingIndex === idx ? 'text-danger' : 'text-muted']"
										href="#"
										@click.prevent="deleteComment(idx)">
                                        {{ deletingIndex && deletingIndex === idx ? 'Deleting...' : 'Delete'}}
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

							<template v-if="post.reply_count">
								<div v-if="!post.replies.replies_show && commentReplyIndex !== idx" class="media-body-show-replies">
									<a href="#" class="font-weight-bold primary" @click.prevent="showCommentReplies(idx)">
										<i class="media-body-show-replies-icon"></i>
										<span class="media-body-show-replies-label">Show {{ prettyCount(post.reply_count) }} replies</span>
									</a>
								</div>

								<div v-else class="media-body-show-replies">
									<a href="#" class="font-weight-bold text-muted" @click.prevent="hideCommentReplies(idx)">
										<i class="media-body-show-replies-icon"></i>
										<span class="media-body-show-replies-label">Hide {{ prettyCount(post.reply_count) }} replies</span>
									</a>
								</div>
							</template>

							<comment-replies
                                :key="`cmr-${post.id}-${feed[idx].reply_count}`"
								v-if="feed[idx].replies_show"
								:status="post"
								:feed="feed[idx].replies"
								v-on:counter-change="replyCounterChange(idx, $event)"
								class="mt-3" />

							<div
								v-if="post.replies_show == true && commentReplyIndex == idx && feed[idx].reply_count > 3">
								<div class="media-body-show-replies mt-n3">
									<a href="#" class="font-weight-bold text-dark" @click.prevent="goToPost(post)">
										<i class="media-body-show-replies-icon"></i>
										<span class="media-body-show-replies-label">View full thread</span>
									</a>
								</div>
							</div>

							<comment-reply-form
								v-if="commentReplyIndex == idx"
								:parent-id="post.id"
								v-on:new-comment="pushCommentReply(idx, $event)"
								v-on:counter-change="replyCounterChange(idx, $event)" />

							<!-- <div v-if="commentReplyIndex != undefined && commentReplyIndex == idx" class="d-flex align-items-top reply-form child-reply-form my-3">
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
				</transition-group>
			</div>
		</div>

		<div v-if="!feedLoading && canLoadMore" class="post-comment-drawer-loadmore">
			<p>
				<a class="font-weight-bold text-dark" href="#" @click.prevent="fetchMore()">Load more comments…</a>
			</p>
		</div>

		<div v-if="showEmptyRepliesRefresh" class="post-comment-drawer-loadmore">
			<p class="text-center mb-4">
				<a class="btn btn-outline-primary font-weight-bold rounded-pill" href="#" @click.prevent="forceRefresh()">
					<i class="far fa-sync mr-2"></i> Refresh
				</a>
			</p>
		</div>

		<div class="d-flex align-items-top reply-form child-reply-form">
			<img class="shadow-sm media-avatar border" :src="profile.avatar" width="40" height="40" draggable="false" onerror="this.onerror=null;this.src='/storage/avatars/default.jpg?v=0';">

			<div v-show="!settings.expanded" class="w-100">
				<vue-tribute :options="tributeSettings">
					<textarea
                        class="form-control bg-light rounded-sm shadow-sm rounded-pill"
                        placeholder="Write a comment...."
                        style="resize: none;padding-right:140px;"
                        rows="1"
                        v-model="replyContent"
                        :disabled="isPostingReply"></textarea>
				</vue-tribute>
			</div>

			<div v-show="settings.expanded" class="w-100">
				<vue-tribute :options="tributeSettings">
					<textarea
						class="form-control bg-light rounded-sm shadow-sm"
						placeholder="Write a comment...."
						style="resize: none;padding-right:140px;"
						rows="5"
						v-model="replyContent"
						:disabled="isPostingReply"></textarea>
				</vue-tribute>
			</div>

			<div class="reply-form-input-actions" :class="{ open: settings.expanded }">
				<button
					@click="replyUpload()"
					class="btn btn-link text-muted px-1 mr-2">
					<i class="far fa-image fa-lg"></i>
				</button>
				<button
					@click="toggleReplyExpand()"
					class="btn btn-link text-muted px-1 mr-2">
					<i class="far fa-text-size fa-lg"></i>
				</button>
				<button
					class="btn btn-link text-muted px-1 small font-weight-bold py-0 rounded-pill text-decoration-none"
					@click="toggleShowReplyOptions">
					<i class="far fa-ellipsis-h"></i>
				</button>
			</div>
		</div>

		<div v-if="showReplyOptions" class="child-reply-form-options mt-2" style="margin-left: 60px;">
			<b-form-checkbox v-model="settings.sensitive" switch>
				{{ $t('common.sensitive') }}
			</b-form-checkbox>
		</div>

		<div v-if="replyContent && replyContent.length" class="text-right mt-2">
			<button class="btn btn-primary btn-sm font-weight-bold primary rounded-pill px-4" @click="storeComment">{{ $t('common.comment') }}</button>
		</div>

		<b-modal ref="lightboxModal"
			id="lightbox"
			:hide-header="true"
			:hide-footer="true"
			centered
			size="lg"
			body-class="p-0"
			content-class="bg-transparent border-0 position-relative"
			>
            <div v-if="lightboxStatus && lightboxStatus.type == 'image'" @click="hideLightbox">
				<img :src="lightboxStatus.url" style="width: 100%;max-height: 90vh;object-fit: contain;">
			</div>
			<div v-else-if="lightboxStatus && lightboxStatus.type == 'video'" style="position: relative" class="d-flex align-items-center justify-content-center">
                <button
                    class="btn btn-dark d-flex align-items-center justify-content-center"
                    style="position: fixed; top: 10px; right: 10px;width: 56px; height: 56px; border-radius: 56px;"
                    @click="hideLightbox">
                    <i class="far fa-times-circle fa-2x text-warning" style="padding-top:2px"></i>
                </button>
                <video :src="lightboxStatus.url" controls style="max-height: 90vh;object-fit: contain;" autoplay @ended="hideLightbox"/>
            </div>
		</b-modal>
	</div>
</template>

<script type="text/javascript">
	import VueTribute from 'vue-tribute'
	import ReadMore from './ReadMore.vue';
	import ProfileHoverCard from './../profile/ProfileHoverCard.vue';
	import CommentReplies from './CommentReplies.vue';
	import CommentReplyForm from './CommentReplyForm.vue';

	export default {
		props: {
			status: {
				type: Object
			}
		},

		components: {
			VueTribute,
			ReadMore,
			ProfileHoverCard,
			CommentReplyForm,
			CommentReplies
		},

		data() {
			return {
				profile: window._sharedData.user,
				ids: [],
				feed: [],
				sortIndex: 0,
				sorts: [
					'all',
					'newest',
					'popular'
				],
				replyContent: undefined,
				nextUrl: undefined,
				canLoadMore: false,
				isPostingReply: false,
				showReplyOptions: false,
				feedLoading: false,
				isUploading: false,
				uploadProgress: 0,
				lightboxStatus: null,
				settings: {
					expanded: false,
					sensitive: false
				},
				tributeSettings: {
					noMatchTemplate: null,
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
									cb();
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
									cb();
									console.log(err);
								})
							})
						}
					]
				},
				showEmptyRepliesRefresh: false,
				commentReplyIndex: undefined,
                deletingIndex: undefined
			}
		},

		mounted() {
			// if(this.status.replies && this.status.replies.length) {
			// 	this.feed.push(this.status.replies);
			// }
			this.fetchContext();
		},

		computed: {
			hideCounts: {
				get() {
					return this.$store.state.hideCounts == true;
				}
			},
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
                if(event) {
                    event.target?.blur();
                }
                if(!this.nextUrl) {
                    return;
                }
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
						if(this.ids && this.ids.indexOf(post.id) == -1) {
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
                    let cmt = res.data;
                    cmt.replies = [];
					this.replyContent = undefined;
					this.isPostingReply = false;
					this.ids.push(res.data.id);
					this.feed.push(cmt);
					this.$emit('counter-change', 'comment-increment');
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

                this.deletingIndex = index;

				axios.post('/i/delete', {
					type: 'status',
					item: this.feed[index].id
				})
				.then(res => {
                    if(this.ids && this.ids.length) {
                        this.ids.splice(index, 1);
                    }
                    if(this.feed && this.feed.length) {
					   this.feed.splice(index, 1);
                    }
					this.$emit('counter-change', 'comment-decrement');
				})
                .then(() => {
                    this.deletingIndex = undefined;
					this.fetchMore(1);
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
                        status: this.replyContent,
						media_ids: [ res.data.id ],
						in_reply_to_id: this.status.id,
						sensitive: this.settings.sensitive
					}).then(res => {
						this.feed.push(res.data);
                        this.replyContent = undefined;
                        this.isPostingReply = false;
                        this.ids.push(res.data.id);
                        this.$emit('counter-change', 'comment-increment');
					})
				});
			},

			lightbox(status, idx = 0) {
				this.lightboxStatus = status.media_attachments[idx];
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

			getMediaSource(status, idx = 0) {
				let media = status.media_attachments[idx];

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
				this.showCommentReplies(index);
			},

			showCommentReplies(index) {
				if(this.feed[index].hasOwnProperty('replies_show') && this.feed[index].replies_show) {
					this.feed[index].replies_show = false;
					this.commentReplyIndex = undefined;
					return;
				}

				this.feed[index].replies_show = true;
				this.commentReplyIndex = index;
				this.fetchCommentReplies(index);
			},

			hideCommentReplies(index) {
				this.commentReplyIndex = undefined;
				this.feed[index].replies_show = false;
			},

			fetchCommentReplies(index) {
				axios.get('/api/v2/statuses/' + this.feed[index].id + '/replies', {
					params: {
						limit: 3
					}
				})
				.then(res => {
					this.feed[index].replies = res.data.data;
				})
			},

			getPostAvatar(post) {
				if(this.profile.id == post.account.id) {
					return window._sharedData.user.avatar;
				}

				return post.account.avatar;
			},

			follow(index) {
				axios.post('/api/v1/accounts/' + this.feed[index].account.id + '/follow')
				.then(res => {
					this.$store.commit('updateRelationship', [res.data]);
					this.feed[index].account.followers_count = this.feed[index].account.followers_count + 1;
					window._sharedData.user.following_count = window._sharedData.user.following_count + 1;
				})
			},

			unfollow(index) {
				axios.post('/api/v1/accounts/' + this.feed[index].account.id + '/unfollow')
				.then(res => {
					this.$store.commit('updateRelationship', [res.data]);
					this.feed[index].account.followers_count = this.feed[index].account.followers_count - 1;
					window._sharedData.user.following_count = window._sharedData.user.following_count - 1;
				})
			},

			handleCounterChange(payload) {
				this.$emit('counter-change', payload);
			},

			pushCommentReply(index, post) {
				if(!this.feed[index].hasOwnProperty('replies')) {
					this.feed[index].replies = [post];
				} else {
					this.feed[index].replies.push(post);
				}
				this.feed[index].reply_count = this.feed[index].reply_count + 1;
                this.feed[index].replies_show = true;
			},

			replyCounterChange(index, type) {
				switch(type) {
					case 'comment-increment':
						this.feed[index].reply_count = this.feed[index].reply_count + 1;
					break;

					case 'comment-decrement':
						this.feed[index].reply_count = this.feed[index].reply_count - 1;
					break;
				}
			}
		}
	}
</script>

<style lang="scss">
	.post-comment-drawer {
		&-feed {
			margin-bottom: 1rem;

			.sort-menu {
				.dropdown {
					border-radius: 18px;
				}

				.dropdown-menu {
					padding: 0;
				}

				.dropdown-item:active {
					background-color: inherit;
				}

				.title {
					color: var(--dropdown-item-color);
				}

				.description {
					margin-bottom: 0;
					color: var(--dropdown-item-color);
					font-size: 12px;
				}

				.active {
					.title {
						font-weight: 600;
						color: var(--dropdown-item-active-color);
					}

					.description {
						color: var(--dropdown-item-active-color);
					}
				}
			}

			&-loader {
				display: flex;
				justify-content: center;
				align-items: center;
				height: 200px;
			}
		}

		.media-body {
			&-comment {
				position: relative;
				min-width: 240px;
			}

			&-wrapper {
				.media-body-comment {
					padding: 0.7rem;
				}

				.media-body-likes-count {
					z-index: 3;
					position: absolute;
					right: -5px;
					bottom: -10px;
					background-color: var(--body-bg);
					padding: 1px 8px;
					font-weight: 600;
					font-size: 12px;
					border-radius: 15px;
					text-decoration: none;
					user-select: none !important;

					i {
						margin-right: 3px;
					}

					.count {
						color: #334155;
					}
				}
			}

			&-show-replies {
				margin-top: -5px;
				margin-bottom: 5px;
				font-size: 13px;

				a {
					display: flex;
					align-items: center;
					text-decoration: none;
				}

				&-icon {
					display: inline-block;
					font-style: normal;
					font-variant: normal;
					text-rendering: auto;
					line-height: 1;
					padding-left: 0.5rem;
					margin-right: 0.25rem;
					transform: rotate(90deg);
					font-family: 'Font Awesome 5 Free';
					font-weight: 400;
					text-decoration: none;

					&:before {
						content: "\F148";
					}
				}

				&-label {
					padding-top: 9px;
				}
			}
		}

		&-loadmore {
			font-size: 0.7875rem;
		}

		.reply-form {
			&-input {
				flex: 1;
				position: relative;

				&-actions {
					position: absolute;
					right: 10px;
					top: 50%;
					transform: translateY(-50%);

					&.open {
						top: 85%;
						transform: translateY(-85%);
					}
				}
			}
		}

		.child-reply-form {
			position: relative;
		}

		.bh-comment {
			position: relative;
			width: 100%;
			height: auto;
			max-width: 160px !important;
			max-height: 260px !important;

    		.img-fluid,
			canvas {
				border-radius: 8px;
			}

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
    			border-radius: 8px;
			}

            &.bh-comment-borderless {
                .img-fluid,
                img,
                canvas {
                    border-radius: 0;
                }

                border-radius: 8px;
                overflow: hidden;
                margin-bottom: 5px;
            }

			.sensitive-warning {
				position: absolute;
				left: 50%;
				top: 50%;
				transform: translate(-50%, -50%);
				text-align: center;
				color: #fff;
				user-select: none;
				cursor: pointer;
				background: rgba(0,0,0,0.4);
				padding: 5px;
				border-radius: 8px;
			}
		}

		.v-tribute {
			width: 100%;
		}
	}
</style>
