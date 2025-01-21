<template>
	<div class="discover-my-hashtags-component">
		<div v-if="isLoaded" class="container-fluid mt-3">

			<div class="row">
				<div class="col-md-4 col-lg-3">
					<sidebar :user="profile" />
				</div>

				<div class="col-md-6 col-lg-6">
					<b-breadcrumb class="font-default" :items="breadcrumbItems"></b-breadcrumb>

					<h1 class="font-default">My Hashtags</h1>
					<p class="font-default lead">Posts from hashtags you follow</p>

					<hr>

					<b-spinner v-if="isLoading" />

					<status-card
						v-if="!isLoading"
						v-for="(post, index) in feed"
						:key="'ti1:'+index+':'+post.id"
						:profile="profile"
						:status="post"
						@like="likeStatus(index)"
						@unlike="unlikeStatus(index)"
						@share="shareStatus(index)"
						@unshare="unshareStatus(index)"
						@menu="openContextMenu(index)"
						@mod-tools="handleModTools(index)"
						@likes-modal="openLikesModal(index)"
						@shares-modal="openSharesModal(index)"
						@bookmark="handleBookmark(index)"
						/>

					<p v-if="!isLoading && tagsLoaded && feed.length == 0" class="lead">No hashtags found :(</p>
				</div>

				<div class="col-md-2 col-lg-3">
					<div class="nav flex-column nav-pills font-default">
						<a
							v-for="(tag, idx) in tags"
							class="nav-link"
							:class="{ active: tagIndex == idx }"
							href="#"
							@click.prevent="toggleTag(idx)">
							{{ tag }}
						</a>
					</div>
				</div>
			</div>
		</div>

		<context-menu
			v-if="showMenu"
			ref="contextMenu"
			:status="feed[postIndex]"
			:profile="profile"
			@moderate="commitModeration"
			@delete="deletePost"
			@report-modal="handleReport"
		/>

		<likes-modal
			v-if="showLikesModal"
			ref="likesModal"
			:status="likesModalPost"
			:profile="profile"
		/>

		<shares-modal
			v-if="showSharesModal"
			ref="sharesModal"
			:status="sharesModalPost"
			:profile="profile"
		/>

		<report-modal
			ref="reportModal"
			:key="reportedStatusId"
			:status="reportedStatus"
		/>
	</div>
</template>

<script type="text/javascript">
import Drawer from './../partials/drawer.vue';
import Sidebar from './../partials/sidebar.vue';
import StatusCard from './../partials/TimelineStatus.vue';
import ContextMenu from './../partials/post/ContextMenu.vue';
import LikesModal from './../partials/post/LikeModal.vue';
import SharesModal from './../partials/post/ShareModal.vue';
import ReportModal from './../partials/modal/ReportPost.vue';

export default {
	components: {
		"drawer": Drawer,
		"sidebar": Sidebar,
		"context-menu": ContextMenu,
        "likes-modal": LikesModal,
        "shares-modal": SharesModal,
        "report-modal": ReportModal,
		"status-card": StatusCard
	},

	data() {
		return {
			isLoaded: true,
			isLoading: true,
			profile: window._sharedData.user,
			tagIndex: 0,
			tags: [],
			feed: [],
			tagsLoaded: false,
			breadcrumbItems: [
				{
					text: 'Discover',
					href: '/i/web/discover'
				},
				{
					text: 'My Hashtags',
					active: true
				}
			],
				canLoadMore: true,
				isFetchingMore: false,
				endFeedReached: false,
			postIndex: 0,
			showMenu: false,
			showLikesModal: false,
			likesModalPost: {},
			showReportModal: false,
			reportedStatus: {},
			reportedStatusId: 0,
			showSharesModal: false,
			sharesModalPost: {},
		}
	},

	mounted() {
		this.fetchHashtags();
	},

	methods: {
		fetchHashtags() {
			axios.get('/api/local/discover/tag/list')
			.then(res => {
				this.tags = res.data;
				this.tagsLoaded = true;
				if(this.tags.length) {
					this.fetchTagFeed(this.tags[0]);
				} else {
					this.isLoading = false;
				}
			})
			.catch(e => {
				this.isLoading = false;
			})
		},

		fetchTagFeed(hashtag) {
			this.isLoading = true;
			axios.get('/api/v2/discover/tag', {
				params: {
					hashtag: hashtag
				}
			})
			.then(res => {
				this.feed = res.data.tags.map(p => p.status);
				this.isLoading = false;
			})
			.catch(e => {
				this.isLoading = false;
			})
		},

		toggleTag(tag) {
			this.tagIndex = tag;
			this.fetchTagFeed(this.tags[tag]);
		},

		likeStatus(index) {
			let status = this.feed[index];
			let state = status.favourited;
			let count = status.favourites_count;
			this.feed[index].favourites_count = count + 1;
			this.feed[index].favourited = !status.favourited;

			axios.post('/api/v1/statuses/' + status.id + '/favourite')
			.then(res => {
				//
			}).catch(err => {
				this.feed[index].favourites_count = count;
				this.feed[index].favourited = false;

				let el = document.createElement('p');
				el.classList.add('text-left');
				el.classList.add('mb-0');
				el.innerHTML = '<span class="lead">We limit certain interactions to keep our community healthy and it appears that you have reached that limit. <span class="font-weight-bold">Please try again later.</span></span>';
				let wrapper = document.createElement('div');
				wrapper.appendChild(el);

				if(err.response.status === 429) {
					swal({
						title: 'Too many requests',
						content: wrapper,
						icon: 'warning',
						buttons: {
							// moreInfo: {
							// 	text: "Contact a human",
							// 	visible: true,
							// 	value: "more",
							// 	className: "text-lighter bg-transparent border"
							// },
							confirm: {
								text: "OK",
								value: false,
								visible: true,
								className: "bg-transparent primary",
								closeModal: true
							}
						}
					})
					.then((val) => {
						if(val == 'more') {
							location.href = '/site/contact'
						}
						return;
					});
				}
			})
		},

		unlikeStatus(index) {
			let status = this.feed[index];
			let state = status.favourited;
			let count = status.favourites_count;
			this.feed[index].favourites_count = count - 1;
			this.feed[index].favourited = !status.favourited;

			axios.post('/api/v1/statuses/' + status.id + '/unfavourite')
			.then(res => {
				//
			}).catch(err => {
				this.feed[index].favourites_count = count;
				this.feed[index].favourited = false;
			})
		},

		shareStatus(index) {
			let status = this.feed[index];
			let state = status.reblogged;
			let count = status.reblogs_count;
			this.feed[index].reblogs_count = count + 1;
			this.feed[index].reblogged = !status.reblogged;

			axios.post('/api/v1/statuses/' + status.id + '/reblog')
			.then(res => {
				//
			}).catch(err => {
				this.feed[index].reblogs_count = count;
				this.feed[index].reblogged = false;
			})
		},

		unshareStatus(index) {
			let status = this.feed[index];
			let state = status.reblogged;
			let count = status.reblogs_count;
			this.feed[index].reblogs_count = count - 1;
			this.feed[index].reblogged = !status.reblogged;

			axios.post('/api/v1/statuses/' + status.id + '/unreblog')
			.then(res => {
				//
			}).catch(err => {
				this.feed[index].reblogs_count = count;
				this.feed[index].reblogged = false;
			})
		},

		openContextMenu(idx) {
			this.postIndex = idx;
			this.showMenu = true;
			this.$nextTick(() => {
				this.$refs.contextMenu.open();
			});
		},

		commitModeration(type) {
			let idx = this.postIndex;

			switch(type) {
				case 'addcw':
					this.feed[idx].sensitive = true;
				break;

				case 'remcw':
					this.feed[idx].sensitive = false;
				break;

				case 'unlist':
					this.feed.splice(idx, 1);
				break;

				case 'spammer':
					let id = this.feed[idx].account.id;

					this.feed = this.feed.filter(post => {
						return post.account.id != id;
					});
				break;
			}
		},

		deletePost() {
			this.feed.splice(this.postIndex, 1);
		},

		handleReport(post) {
			this.reportedStatusId = post.id;
			this.$nextTick(() => {
				this.reportedStatus = post;
				this.$refs.reportModal.open();
			});
		},

		openLikesModal(idx) {
			this.postIndex = idx;
			this.likesModalPost = this.feed[this.postIndex];
			this.showLikesModal = true;
			this.$nextTick(() => {
				this.$refs.likesModal.open();
			});
		},

		openSharesModal(idx) {
			this.postIndex = idx;
			this.sharesModalPost = this.feed[this.postIndex];
			this.showSharesModal = true;
			this.$nextTick(() => {
				this.$refs.sharesModal.open();
			});
		},

		handleBookmark(index) {
			let p = this.feed[index];

			axios.post('/i/bookmark', {
				item: p.id
			})
			.then(res => {
				this.feed[index].bookmarked = !p.bookmarked;
			})
			.catch(err => {
				this.$bvToast.toast('Cannot bookmark post at this time.', {
					title: 'Bookmark Error',
					variant: 'danger',
					autoHideDelay: 5000
				});
			});
		},
	}
}
</script>

<style lang="scss" scoped>
	.discover-my-hashtags-component {
		.bg-stellar {
			background: #7474BF;
			background: -webkit-linear-gradient(to right, #348AC7, #7474BF);
			background: linear-gradient(to right, #348AC7, #7474BF);
		}
		.font-default {
			font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
			letter-spacing: -0.7px;
		}

		.active {
			font-weight: 700;
		}
	}
</style>
