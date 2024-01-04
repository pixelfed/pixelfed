<template>
	<div class="post-timeline-component web-wrapper">
		<div v-if="isLoaded" class="container-fluid mt-3">
			<div class="row">
				<div class="col-md-4 col-lg-3 d-md-block">
					<sidebar :user="user" />
				</div>

				<div class="col-md-8 col-lg-6">
					<div v-if="isReply" class="p-3 rounded-top mb-n3" style="background-color: var(--card-header-accent)">
						<p>
							<i class="fal fa-reply mr-1"></i> In reply to

							<a
								:href="'/i/web/profile/' + reply.account.id"
								class="font-weight-bold primary"
								@click.prevent="goToProfile(reply.account)">
								&commat;{{ reply.account.acct }}
							</a>

							<button
								@click.prevent="goToPost(reply)"
								class="btn btn-primary font-weight-bold btn-sm px-3 float-right rounded-pill">
								View Post
							</button>
						</p>
					</div>
					<status
						:key="post.id + ':fui:' + forceUpdateIdx"
						:status="post"
						:profile="user"
						v-on:menu="openContextMenu()"
						v-on:like="likeStatus()"
						v-on:unlike="unlikeStatus()"
						v-on:likes-modal="openLikesModal()"
						v-on:shares-modal="openSharesModal()"
						v-on:bookmark="handleBookmark()"
						v-on:share="shareStatus()"
						v-on:unshare="unshareStatus()"
						v-on:follow="follow()"
						v-on:unfollow="unfollow()"
						v-on:counter-change="counterChange"
						/>
				</div>

				<div class="d-none d-lg-block col-lg-3">
					<rightbar />
				</div>
			</div>
		</div>

		<div v-if="postStateError" class="container-fluid mt-3">
			<div class="row">
				<div class="col-md-4 col-lg-3 d-md-block">
					<sidebar :user="user" />
				</div>
				<div class="col-md-8 col-lg-6">
					<div class="card card-body shadow-none border">
						<div class="d-flex align-self-center flex-column" style="max-width: 500px;">
							<p class="text-center">
								<i class="far fa-exclamation-triangle fa-3x text-lighter"></i>
							</p>
							<p class="text-center lead font-weight-bold">Error displaying post</p>
							<p class="mb-0">This can happen for a few reasons:</p>
							<ul class="text-lighter">
								<li>The url is invalid or has a typo</li>
								<li>The page has been flagged for review by our automated abuse detection systems</li>
								<li>The content may have been deleted</li>
								<li>You do not have permission to view this content</li>
							</ul>
						</div>
					</div>
				</div>

				<div class="d-none d-lg-block col-lg-3">
					<rightbar />
				</div>
			</div>
		</div>

		<context-menu
			v-if="isLoaded"
			ref="contextMenu"
			:status="post"
			:profile="user"
			@report-modal="handleReport()"
			@delete="deletePost()"
			v-on:edit="handleEdit"
		/>

		<likes-modal
			v-if="showLikesModal"
			ref="likesModal"
			:status="post"
			:profile="user"
		/>

		<shares-modal
			v-if="showSharesModal"
			ref="sharesModal"
			:status="post"
			:profile="profile"
		/>

		<report-modal
			v-if="post"
			ref="reportModal"
			:status="post"
		/>

		<post-edit-modal
			ref="editModal"
			v-on:update="mergeUpdatedPost"
		/>

		<drawer />
	</div>
</template>

<script type="text/javascript">
	import Drawer from './partials/drawer.vue';
	import Rightbar from './partials/rightbar.vue';
	import Sidebar from './partials/sidebar.vue';
	import Status from './partials/TimelineStatus.vue';
	import ContextMenu from './partials/post/ContextMenu.vue';
	import MediaContainer from './partials/post/MediaContainer.vue';
	import LikesModal from './partials/post/LikeModal.vue';
	import SharesModal from './partials/post/ShareModal.vue';
	import ReportModal from './partials/modal/ReportPost.vue';
	import PostEditModal from './partials/post/PostEditModal.vue';

	export default {
		props: {
			cachedStatus: {
				type: Object
			},

			cachedProfile: {
				type: Object
			}
		},

		components: {
			"drawer": Drawer,
			"sidebar": Sidebar,
			"status": Status,
			"context-menu": ContextMenu,
			"media-container": MediaContainer,
			"likes-modal": LikesModal,
			"shares-modal": SharesModal,
			"rightbar": Rightbar,
			"report-modal": ReportModal,
            "post-edit-modal": PostEditModal
		},

		data() {
			return {
				isLoaded: false,
				user: undefined,
				profile: undefined,
				post: undefined,
				relationship: {},
				media: undefined,
				mediaIndex: 0,
				showLikesModal: false,
				isReply: false,
				reply: {},
				showSharesModal: false,
				postStateError: false,
				forceUpdateIdx: 0
			}
		},

		beforeMount() {
			this.init();
		},

		watch: {
			'$route': 'init'
		},

		methods: {
			init() {
				if(this.cachedStatus && this.cachedProfile) {
					this.post = this.cachedStatus;
					this.media = this.post.media_attachments;
					this.profile = this.post.account;
					this.user = this.cachedProfile;
					if(this.post.in_reply_to_id) {
						this.fetchReply();
					} else {
						this.isReply = false;
						this.fetchRelationship();
					}
				} else {
					this.fetchSelf();
				}
			},

			fetchSelf() {
				this.user = window._sharedData.user;
				this.fetchPost();
			},

			fetchPost() {
				axios.get('/api/pixelfed/v1/statuses/'+this.$route.params.id)
				.then(res => {
					if(!res.data || !res.data.hasOwnProperty('id')) {
						this.$router.push('/i/web/404');
					}
					if(!res.data.hasOwnProperty('account') || !res.data.account) {
						this.postStateError = true;
						return;
					}
					this.post = res.data;
					this.media = this.post.media_attachments;
					this.profile = this.post.account;
					if(this.post.in_reply_to_id) {
						this.fetchReply();
					} else {
						this.fetchRelationship();
					}
				}).catch(err => {
					switch(err.response.status) {
						case 403:
						case 404:
							this.$router.push('/i/web/404');
						break;
					}
				})
			},

			fetchReply() {
				axios.get('/api/pixelfed/v1/statuses/' + this.post.in_reply_to_id)
				.then(res => {
					this.reply = res.data;
					this.isReply = true;
					this.fetchRelationship();
				})
				.catch(err => {
					this.fetchRelationship();
				})
			},

			fetchRelationship() {
				if(this.profile.id == this.user.id) {
					this.relationship = {};
					this.fetchState();
					return;
				}

				axios.get('/api/pixelfed/v1/accounts/relationships', {
					params: {
						'id[]': this.profile.id
					}
				}).then(res => {
					this.relationship = res.data[0];
					this.fetchState();
				});
			},

			fetchState() {
				axios.get('/api/v2/statuses/'+this.post.id+'/state')
				.then(res => {
					this.post.favourited = res.data.liked;
					this.post.reblogged = res.data.shared;
					this.post.bookmarked = res.data.bookmarked;
					if(!this.post.favourites_count && this.post.favourited) {
						this.post.favourites_count = 1;
					}
					this.isLoaded = true;
				}).catch(err => {
					this.isLoaded = false;
					this.postStateError = true;
				})
			},

			goBack() {
				this.$router.push('/i/web');
			},

			likeStatus() {
				let count = this.post.favourites_count;
				this.post.favourites_count = count + 1;
				this.post.favourited = !this.post.favourited;

				axios.post('/api/v1/statuses/' + this.post.id + '/favourite')
				.then(res => {
					//
				}).catch(err => {
					this.post.favourites_count = count;
					this.post.favourited = false;
				})
			},

			unlikeStatus() {
				let count = this.post.favourites_count;
				this.post.favourites_count = count - 1;
				this.post.favourited = !this.post.favourited;

				axios.post('/api/v1/statuses/' + this.post.id + '/unfavourite')
				.then(res => {
					//
				}).catch(err => {
					this.post.favourites_count = count;
					this.post.favourited = false;
				})
			},

			shareStatus() {
				let count = this.post.reblogs_count;
				this.post.reblogs_count = count + 1;
				this.post.reblogged = !this.post.reblogged;

				axios.post('/api/v1/statuses/' + this.post.id + '/reblog')
				.then(res => {
					//
				}).catch(err => {
					this.post.reblogs_count = count;
					this.post.reblogged = false;
				})
			},

			unshareStatus() {
				let count = this.post.reblogs_count;
				this.post.reblogs_count = count - 1;
				this.post.reblogged = !this.post.reblogged;

				axios.post('/api/v1/statuses/' + this.post.id + '/unreblog')
				.then(res => {
					//
				}).catch(err => {
					this.post.reblogs_count = count;
					this.post.reblogged = false;
				})
			},

			follow() {
				axios.post('/api/v1/accounts/' + this.post.account.id + '/follow')
				.then(res => {
					this.$store.commit('updateRelationship', [res.data]);
					this.user.following_count++;
					this.post.account.followers_count++;
				}).catch(err => {
					swal('Oops!', 'An error occurred when attempting to follow this account.', 'error');
					this.post.relationship.following = false;
				});
			},

			unfollow() {
				axios.post('/api/v1/accounts/' + this.post.account.id + '/unfollow')
				.then(res => {
					this.$store.commit('updateRelationship', [res.data]);
					this.user.following_count--;
					this.post.account.followers_count--;
				}).catch(err => {
					swal('Oops!', 'An error occurred when attempting to unfollow this account.', 'error');
					this.post.relationship.following = true;
				});
			},

			openContextMenu() {
				this.$nextTick(() => {
					this.$refs.contextMenu.open();
				});
			},

			openLikesModal() {
				this.showLikesModal = true;
				this.$nextTick(() => {
					this.$refs.likesModal.open();
				});
			},

			openSharesModal() {
				this.showSharesModal = true;
				this.$nextTick(() => {
					this.$refs.sharesModal.open();
				});
			},

			deletePost() {
				this.$router.push('/i/web');
			},

			goToPost(post) {
				this.$router.push({
					name: 'post',
					path: `/i/web/post/${post.id}`,
					params: {
						id: post.id,
						cachedStatus: post,
						cachedProfile: this.user
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
						cachedUser: this.user
					}
				})
			},

			handleBookmark() {
				axios.post('/i/bookmark', {
					item: this.post.id
				})
				.then(res => {
					this.post.bookmarked = !this.post.bookmarked;
				})
				.catch(err => {
					this.$bvToast.toast('Cannot bookmark post at this time.', {
						title: 'Bookmark Error',
						variant: 'danger',
						autoHideDelay: 5000
					});
				});
			},

			handleReport() {
				this.$nextTick(() => {
					this.$refs.reportModal.open();
				});
			},

			counterChange(type) {
				switch(type) {
					case 'comment-increment':
						this.post.reply_count = this.post.reply_count + 1;
					break;

					case 'comment-decrement':
						this.post.reply_count = this.post.reply_count - 1;
					break;
				}
			},

			handleEdit(status) {
            	this.$refs.editModal.show(status);
            },

            mergeUpdatedPost(post) {
            	this.post = post;
            	this.$nextTick(() => {
            		this.forceUpdateIdx++;
            	});
            }
		}
	}
</script>
