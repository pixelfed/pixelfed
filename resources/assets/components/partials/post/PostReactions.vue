<template>
	<div class="px-3 my-3" style="z-index:3;">
		<div v-if="(status.favourites_count || status.reblogs_count) && ((status.hasOwnProperty('liked_by') && status.liked_by.url) || (status.hasOwnProperty('reblogs_count') && status.reblogs_count))" class="mb-0 d-flex justify-content-between">
			<p v-if="!hideCounts && status.favourites_count" class="mb-2 reaction-liked-by">
				Liked by
				<span v-if="status.favourites_count == 1 && status.favourited == true" class="font-weight-bold">me</span>
				<span v-else>
					<router-link :to="'/i/web/profile/' + status.liked_by.id" class="primary font-weight-bold">{{ status.liked_by.username}}</router-link>
					<span v-if="status.liked_by.others || status.favourites_count > 1">
						and <a href="#" class="primary font-weight-bold" @click.prevent="showLikes()">{{ count(status.favourites_count - 1) }} others</a>
					</span>
				</span>
			</p>

			<p v-if="!hideCounts && status.reblogs_count" class="mb-2 reaction-liked-by">
				Shared by
				<span v-if="status.reblogs_count == 1 && status.reblogged == true" class="font-weight-bold">me</span>
				<a v-else class="primary font-weight-bold" href="#" @click.prevent="showShares()">
					{{ count(status.reblogs_count) }} {{ status.reblogs_count > 1 ? 'others' : 'other' }}
				</a>
			</p>
		</div>

		<div class="d-flex justify-content-between" style="font-size: 14px !important;">
			<div>
				<button type="button" class="btn btn-light font-weight-bold rounded-pill mr-2" @click.prevent="like()">
					<span v-if="status.favourited" class="primary">
						<i class="fas fa-heart mr-md-1 text-danger fa-lg"></i>
					</span>
					<span v-else>
						<i class="far fa-heart mr-md-2"></i>
					</span>
					<span v-if="likesCount && !hideCounts">
						{{ count(likesCount)}}
						<span class="d-none d-md-inline">{{ likesCount == 1 ? $t('common.like') : $t('common.likes') }}</span>
					</span>
					<span v-else>
						<span class="d-none d-md-inline">{{ $t('common.like') }}</span>
					</span>
				</button>

				<button v-if="!status.comments_disabled" type="button" class="btn btn-light font-weight-bold rounded-pill mr-2 px-3" @click="showComments()">
					<i class="far fa-comment mr-md-2"></i>
					<span v-if="replyCount && !hideCounts">
						{{ count(replyCount) }}
						<span class="d-none d-md-inline">{{ replyCount == 1 ? $t('common.comment') : $t('common.comments') }}</span>
					</span>
					<span v-else>
						<span class="d-none d-md-inline">{{ $t('common.comment') }}</span>
					</span>
				</button>

			</div>
			<div>
				<button
					type="button"
					class="btn btn-light font-weight-bold rounded-pill"
					:disabled="isReblogging"
					@click="handleReblog()">
					<span v-if="isReblogging">
						<b-spinner variant="warning" small />
					</span>
					<span v-else>
						<i v-if="status.reblogged == true" class="fas fa-retweet fa-lg text-warning"></i>
						<i v-else class="far fa-retweet"></i>

						<span v-if="status.reblogs_count && !hideCounts" class="ml-md-2">
							{{ count(status.reblogs_count) }}
						</span>
					</span>
				</button>

				<button
					v-if="!status.in_reply_to_id && !status.reblog_of_id"
					type="button"
					class="btn btn-light font-weight-bold rounded-pill ml-3"
					:disabled="isBookmarking"
					@click="handleBookmark()">
					<span v-if="isBookmarking">
						<b-spinner variant="warning" small />
					</span>
					<span v-else>
						<i v-if="status.hasOwnProperty('bookmarked_at') || (status.hasOwnProperty('bookmarked') && status.bookmarked == true)" class="fas fa-bookmark fa-lg text-warning"></i>
						<i v-else class="far fa-bookmark"></i>
					</span>
				</button>

				<button v-if="admin" type="button" class="ml-3 btn btn-light font-weight-bold rounded-pill" v-b-tooltip.hover title="Moderation Tools" @click="openModTools()">
					<i class="far fa-user-crown"></i>
				</button>
			</div>
		</div>
	</div>
</template>

<script type="text/javascript">
	import CommentDrawer from './CommentDrawer.vue';
	import ProfileHoverCard from './../profile/ProfileHoverCard.vue';

	export default {
		props: {
			status: {
				type: Object
			},

			profile: {
				type: Object
			},

			admin: {
				type: Boolean,
				default: false
			}
		},

		components: {
			"comment-drawer": CommentDrawer,
			"profile-hover-card": ProfileHoverCard
		},

		data() {
			return {
				key: 1,
				menuLoading: true,
				sensitive: false,
				isReblogging: false,
				isBookmarking: false,
				owner: false,
				license: false
			}
		},

		computed: {
			hideCounts: {
				get() {
					return this.$store.state.hideCounts == true;
				}
			},

			autoloadComments: {
				get() {
					return this.$store.state.autoloadComments == true;
				}
			},

			newReactions: {
				get() {
					return this.$store.state.newReactions;
				},
			},

			likesCount: function() {
				return this.status.favourites_count;
			},

			replyCount: function() {
				return this.status.reply_count;
			}
		},

		methods: {
			count(val) {
				return App.util.format.count(val);
			},

			like() {
				event.currentTarget.blur();
				if(this.status.favourited) {
					this.$emit('unlike');
				} else {
					this.$emit('like');
				}
			},

			showLikes() {
				event.currentTarget.blur();
				this.$emit('likes-modal');
			},

			showShares() {
				event.currentTarget.blur();
				this.$emit('shares-modal');
			},

			showComments() {
				event.currentTarget.blur();
				this.$emit('toggle-comments');
			},

			copyLink() {
				event.currentTarget.blur();
				App.util.clipboard(this.status.url);
			},

			shareToOther() {
				if (navigator.canShare) {
					navigator.share({
						url: this.status.url
					})
					.then(() => console.log('Share was successful.'))
					.catch((error) => console.log('Sharing failed', error));
				} else {
					swal('Not supported', 'Your current device does not support native sharing.', 'error');
				}
			},

			counterChange(type) {
				this.$emit('counter-change', type);
			},

			showCommentLikes(post) {
				this.$emit('comment-likes-modal', post);
			},

			handleReblog() {
				this.isReblogging = true;
				if(this.status.reblogged) {
					this.$emit('unshare');
				} else {
					this.$emit('share');
				}

				setTimeout(() => {
					this.isReblogging = false;
				}, 5000);
			},

			handleBookmark() {
				event.currentTarget.blur();
				this.isBookmarking = true;
				this.$emit('bookmark');

				setTimeout(() => {
					this.isBookmarking = false;
				}, 2000);
			},

			getStatusAvatar() {
				if(window._sharedData.user.id == this.status.account.id) {
					return window._sharedData.user.avatar;
				}

				return this.status.account.avatar;
			},

			openModTools() {
				this.$emit('mod-tools');
			}
		}
	}
</script>
