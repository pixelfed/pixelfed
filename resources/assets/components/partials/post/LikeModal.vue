<template>
	<div>
		<b-modal
			ref="likesModal"
			centered
			size="md"
			:scrollable="true"
			hide-footer
			header-class="py-2"
			body-class="p-0"
			title-class="w-100 text-center pl-4 font-weight-bold"
			title-tag="p"
			:title="$t('common.likes')">
			<div v-if="isLoading" class="likes-loader list-group border-top-0" style="max-height: 500px;">
				<like-placeholder />
			</div>

			<div v-else>
				<div v-if="!likes.length" class="d-flex justify-content-center align-items-center" style="height: 140px;">
					<p class="font-weight-bold mb-0">{{ $t('post.noLikes') }}</p>
				</div>

				<div v-else class="list-group" style="max-height: 500px;">
					<div v-for="(account, index) in likes" class="list-group-item border-left-0 border-right-0 px-3" :class="[ index === 0 ? 'border-top-0' : '']">
						<div class="media align-items-center">
							<img :src="account.avatar" width="40" height="40" style="border-radius: 8px;" class="mr-3 shadow-sm" onerror="this.src='/storage/avatars/default.jpg?v=0';this.onerror=null;">
							<div class="media-body">
								<p class="mb-0 text-truncate"><a :href="account.url" class="text-dark font-weight-bold text-decoration-none" @click.prevent="goToProfile(account)">{{ getUsername(account) }}</a></p>
								<p class="mb-0 mt-n1 text-dark font-weight-bold small text-break">&commat;{{ account.acct }}</p>
							</div>

							<div>
								<button
									v-if="account.follows == null || account.id == user.id"
									class="btn btn-outline-muted rounded-pill btn-sm font-weight-bold"
									@click="goToProfile(profile)"
									style="width:110px;">
									View Profile
								</button>
								<button
									v-else-if="account.follows"
									class="btn btn-outline-muted rounded-pill btn-sm font-weight-bold"
									:disabled="isUpdatingFollowState"
									@click="handleUnfollow(index)"
									style="width:110px;">
									<span v-if="isUpdatingFollowState && followStateIndex === index">
										<b-spinner small />
									</span>
									<span v-else>Following</span>
								</button>
								<button
									v-else-if="!account.follows"
									class="btn btn-primary rounded-pill btn-sm font-weight-bold"
									:disabled="isUpdatingFollowState"
									@click="handleFollow(index)"
									style="width:110px;">
									<span v-if="isUpdatingFollowState && followStateIndex === index">
										<b-spinner small />
									</span>
									<span v-else>Follow</span>
								</button>
							</div>
						</div>
					</div>

					<div v-if="canLoadMore">
						<intersect @enter="enterIntersect">
							<like-placeholder class="border-top-0" />
						</intersect>
						<like-placeholder />
					</div>
				</div>
			</div>
		</b-modal>
	</div>
</template>

<script type="text/javascript">
	import Intersect from 'vue-intersect'
	import LikePlaceholder from './LikeListPlaceholder.vue';
	import { parseLinkHeader } from '@web3-storage/parse-link-header';

	export default {
		props: {
			status: {
				type: Object
			},

			profile: {
				type: Object
			}
		},

		components: {
			"intersect": Intersect,
			"like-placeholder": LikePlaceholder
		},

		data() {
			return {
				isOpen: false,
				isLoading: true,
				canLoadMore: false,
				isFetchingMore: false,
				likes: [],
				ids: [],
				page: undefined,
				isUpdatingFollowState: false,
				followStateIndex: undefined,
				user: window._sharedData.user
			}
		},

		methods: {
			clear() {
				this.isOpen = false;
				this.isLoading = true;
				this.canLoadMore = false;
				this.isFetchingMore = false;
				this.likes = [];
				this.ids = [];
				this.page = undefined;
			},

			fetchLikes() {
				axios.get('/api/v1/statuses/'+this.status.id+'/favourited_by', {
					params: {
						limit: 40
					}
				})
				.then(res => {
					this.ids = res.data.map(a => a.id);
					this.likes = res.data;
					if(res.headers && res.headers.link) {
						const links = parseLinkHeader(res.headers.link);
						if(links.next) {
							this.page = links.next.cursor;
							this.canLoadMore = true;
						} else {
							this.canLoadMore = false;
						}
					}
					this.isLoading = false;
				});
			},

			open() {
				if(this.page) {
					this.clear();
				}
				this.isOpen = true;
				this.fetchLikes();
				this.$refs.likesModal.show();
			},

			enterIntersect() {
				if(this.isFetchingMore) {
					return;
				}

				this.isFetchingMore = true;

				axios.get('/api/v1/statuses/'+this.status.id+'/favourited_by', {
					params: {
						limit: 10,
						cursor: this.page
					}
				}).then(res => {
					if(!res.data || !res.data.length) {
						this.canLoadMore = false;
						this.isFetchingMore = false;
						return;
					}
					res.data.forEach(user => {
						if(this.ids.indexOf(user.id) == -1) {
							this.ids.push(user.id);
							this.likes.push(user);
						}
					})
					if(res.headers && res.headers.link) {
						const links = parseLinkHeader(res.headers.link);
						if(links.next) {
							this.page = links.next.cursor;
						} else {
							this.canLoadMore = false;
						}
					}
					this.isFetchingMore = false;
				})
			},

			getUsername(account) {
				return account.display_name ? account.display_name : account.username;
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

			handleFollow(index) {
				event.currentTarget.blur();

				this.followStateIndex = index;
				this.isUpdatingFollowState = true;

				let account = this.likes[index];
				axios.post('/api/v1/accounts/' + account.id + '/follow')
				.then(res => {
					this.likes[index].follows = true;
					this.followStateIndex = undefined;
					this.isUpdatingFollowState = false;
				});
			},

			handleUnfollow(index) {
				event.currentTarget.blur();

				this.followStateIndex = index;
				this.isUpdatingFollowState = true;

				let account = this.likes[index];
				axios.post('/api/v1/accounts/' + account.id + '/unfollow')
				.then(res => {
					this.likes[index].follows = false;
					this.followStateIndex = undefined;
					this.isUpdatingFollowState = false;
				});
			}
		}
	}
</script>
