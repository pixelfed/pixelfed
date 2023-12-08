<template>
	<div class="notifications-component">
		<div class="card shadow-sm mb-3" style="overflow: hidden;border-radius: 15px !important;">
			<div class="card-body pb-0">
				<div class="d-flex justify-content-between align-items-center mb-3">
					<span class="text-muted font-weight-bold">Notifications</span>
					<div v-if="feed && feed.length">
						<router-link to="/i/web/notifications" class="btn btn-outline-light btn-sm mr-2" style="color: #B8C2CC !important">
							<i class="far fa-filter"></i>
						</router-link>
						<button
							v-if="hasLoaded && feed.length"
							class="btn btn-light btn-sm"
							:class="{ 'text-lighter': isRefreshing }"
							:disabled="isRefreshing"
							@click="refreshNotifications">
							<i class="fal fa-redo"></i>
						</button>
					</div>
				</div>

				<div v-if="!hasLoaded" class="notifications-component-feed">
					<div class="d-flex align-items-center justify-content-center flex-column bg-light rounded-lg p-3 mb-3" style="min-height: 100px;">
						<b-spinner variant="grow" />
					</div>
				</div>

				<div v-else class="notifications-component-feed">
					<template v-if="isEmpty">
						<div class="d-flex align-items-center justify-content-center flex-column bg-light rounded-lg p-3 mb-3" style="min-height: 100px;">
							<i class="fal fa-bell fa-2x text-lighter"></i>
							<p class="mt-2 small font-weight-bold text-center mb-0">{{ $t('notifications.noneFound') }}</p>
						</div>
					</template>

					<template v-else>
						<div v-for="(n, index) in feed" class="mb-2">
							<div class="media align-items-center">
								<img
									v-if="n.type === 'autospam.warning'"
									class="mr-2 rounded-circle shadow-sm p-1"
									style="border: 2px solid var(--danger)"
									src="/img/pixelfed-icon-color.svg"
									width="32"
									height="32"
									/>
								<img
									v-else
									class="mr-2 rounded-circle shadow-sm"
									:src="n.account.avatar"
									width="32"
									height="32"
									onerror="this.onerror=null;this.src='/storage/avatars/default.png';">

								<div class="media-body font-weight-light small">
									<div v-if="n.type == 'favourite'">
										<p class="my-0">
											<a :href="getProfileUrl(n.account)" class="font-weight-bold text-dark word-break" :title="n.account.acct">{{n.account.local == false ? '@':''}}{{truncate(n.account.username)}}</a> liked your
											<span v-if="n.status && n.status.hasOwnProperty('media_attachments')">
												<a class="font-weight-bold" v-bind:href="getPostUrl(n.status)" :id="'fvn-' + n.id" @click.prevent="goToPost(n.status)">post</a>.
												<b-popover :target="'fvn-' + n.id" title="" triggers="hover" placement="top" boundary="window">
													<img :src="notificationPreview(n)" width="100px" height="100px" style="object-fit: cover;">
												</b-popover>
											</span>
											<span v-else>
												<a class="font-weight-bold" :href="getPostUrl(n.status)" @click.prevent="goToPost(n.status)">post</a>.
											</span>
										</p>
									</div>
									<div v-else-if="n.type == 'autospam.warning'">
										<p class="my-0">
											Your recent <a :href="getPostUrl(n.status)" @click.prevent="goToPost(n.status)" class="font-weight-bold">post</a> has been unlisted.
										</p>
										<p class="mt-n1 mb-0">
											<span class="small text-muted"><a href="#" class="font-weight-bold" @click.prevent="showAutospamInfo(n.status)">Click here</a> for more info.</span>
										</p>
									</div>
									<div v-else-if="n.type == 'comment'">
										<p class="my-0">
											<a :href="getProfileUrl(n.account)" class="font-weight-bold text-dark word-break" :title="n.account.acct">{{n.account.local == false ? '@':''}}{{truncate(n.account.username)}}</a> commented on your <a class="font-weight-bold" :href="getPostUrl(n.status)" @click.prevent="goToPost(n.status)">post</a>.
										</p>
									</div>
									<div v-else-if="n.type == 'group:comment'">
										<p class="my-0">
											<a :href="getProfileUrl(n.account)" class="font-weight-bold text-dark word-break" :title="n.account.acct">{{n.account.local == false ? '@':''}}{{truncate(n.account.username)}}</a> commented on your <a class="font-weight-bold" :href="n.group_post_url">group post</a>.
										</p>
									</div>
									<div v-else-if="n.type == 'story:react'">
										<p class="my-0">
											<a :href="getProfileUrl(n.account)" class="font-weight-bold text-dark word-break" :title="n.account.acct">{{n.account.local == false ? '@':''}}{{truncate(n.account.username)}}</a> reacted to your <a class="font-weight-bold" v-bind:href="'/i/web/direct/thread/'+n.account.id">story</a>.
										</p>
									</div>
									<div v-else-if="n.type == 'story:comment'">
										<p class="my-0">
											<a :href="getProfileUrl(n.account)" class="font-weight-bold text-dark word-break" :title="n.account.acct">{{n.account.local == false ? '@':''}}{{truncate(n.account.username)}}</a> commented on your <a class="font-weight-bold" v-bind:href="'/i/web/direct/thread/'+n.account.id">story</a>.
										</p>
									</div>
									<div v-else-if="n.type == 'mention'">
										<p class="my-0">
											<a :href="getProfileUrl(n.account)" class="font-weight-bold text-dark word-break" :title="n.account.acct">{{n.account.local == false ? '@':''}}{{truncate(n.account.username)}}</a> <a class="font-weight-bold" v-bind:href="mentionUrl(n.status)" @click.prevent="goToPost(n.status)">mentioned</a> you.
										</p>
									</div>
									<div v-else-if="n.type == 'follow'">
										<p class="my-0">
											<a :href="getProfileUrl(n.account)" class="font-weight-bold text-dark word-break" :title="n.account.acct">{{n.account.local == false ? '@':''}}{{truncate(n.account.username)}}</a> followed you.
										</p>
									</div>
									<div v-else-if="n.type == 'share'">
										<p class="my-0">
											<a :href="getProfileUrl(n.account)" class="font-weight-bold text-dark word-break" :title="n.account.acct">{{n.account.local == false ? '@':''}}{{truncate(n.account.username)}}</a> shared your <a class="font-weight-bold" :href="getPostUrl(n.status)" @click.prevent="goToPost(n.status)">post</a>.
										</p>
									</div>
									<div v-else-if="n.type == 'modlog'">
										<p class="my-0">
											<a :href="getProfileUrl(n.account)" class="font-weight-bold text-dark word-break" :title="n.account.acct">{{truncate(n.account.username)}}</a> updated a <a class="font-weight-bold" v-bind:href="n.modlog.url">modlog</a>.
										</p>
									</div>
									<div v-else-if="n.type == 'tagged'">
										<p class="my-0">
											<a :href="getProfileUrl(n.account)" class="font-weight-bold text-dark word-break" :title="n.account.acct">{{n.account.local == false ? '@':''}}{{truncate(n.account.username)}}</a> tagged you in a <a class="font-weight-bold" v-bind:href="n.tagged.post_url">post</a>.
										</p>
									</div>
									<div v-else-if="n.type == 'direct'">
										<p class="my-0">
											<a :href="getProfileUrl(n.account)" class="font-weight-bold text-dark word-break" :title="n.account.acct">{{n.account.local == false ? '@':''}}{{truncate(n.account.username)}}</a> sent a <router-link class="font-weight-bold" :to="'/i/web/direct/thread/'+n.account.id">dm</router-link>.
										</p>
									</div>

									<div v-else-if="n.type == 'group.join.approved'">
										<p class="my-0">
											Your application to join the <a :href="n.group.url" class="font-weight-bold text-dark word-break" :title="n.group.name">{{truncate(n.group.name)}}</a> group was approved!
										</p>
									</div>

									<div v-else-if="n.type == 'group.join.rejected'">
										<p class="my-0">
											Your application to join <a :href="n.group.url" class="font-weight-bold text-dark word-break" :title="n.group.name">{{truncate(n.group.name)}}</a> was rejected.
										</p>
									</div>

									<div v-else-if="n.type == 'group:invite'">
										<p class="my-0">
											<a :href="getProfileUrl(n.account)" class="font-weight-bold text-dark word-break" :title="n.account.acct">{{n.account.local == false ? '@':''}}{{truncate(n.account.username)}}</a> invited you to join <a :href="n.group.url + '/invite/claim'" class="font-weight-bold text-dark word-break" :title="n.group.name">{{n.group.name}}</a>.
										</p>
									</div>

									<div v-else>
										<p class="my-0">
											We cannot display this notification at this time.
										</p>
									</div>
								</div>
								<div class="small text-muted font-weight-bold" :title="n.created_at">{{timeAgo(n.created_at)}}</div>
							</div>
						</div>

						<div v-if="hasLoaded && feed.length == 0">
							<p class="small font-weight-bold text-center mb-0">{{ $t('notifications.noneFound') }}</p>
						</div>

						<div v-else>
							<intersect v-if="hasLoaded && canLoadMore" @enter="enterIntersect">
								<placeholder small style="margin-top: -6px" />
								<placeholder small/>
								<placeholder small/>
								<placeholder small/>
							</intersect>

							<div v-else class="d-block" style="height: 10px;">
							</div>
						</div>
					</template>
				</div>
			</div>
		</div>
	</div>
</template>

<script type="text/javascript">
	import Placeholder from './../partials/placeholders/NotificationPlaceholder.vue';
	import Intersect from 'vue-intersect';

	export default {
		props: {
			profile: {
				type: Object
			}
		},

		components: {
			"intersect": Intersect,
			"placeholder": Placeholder
		},

		data() {
			return {
				feed: {},
				maxId: undefined,
				isIntersecting: false,
				canLoadMore: false,
				isRefreshing: false,
				hasLoaded: false,
				isEmpty: false,
				retryTimeout: undefined,
				retryAttempts: 0
			}
		},

		mounted() {
			this.init();
		},

		destroyed() {
			clearTimeout(this.retryTimeout);
		},

		methods: {
			init() {
				if(this.retryAttempts == 1) {
					this.hasLoaded = true;
					this.isEmpty = true;
					clearTimeout(this.retryTimeout);
					return;
				}
				axios.get('/api/pixelfed/v1/notifications', {
					params: {
						limit: 9,
					}
				})
				.then(res => {
					if(!res || !res.data || !res.data.length) {
						this.retryAttempts = this.retryAttempts + 1;
						this.retryTimeout = setTimeout(() => this.init(), this.retryAttempts * 1500);
						return;
					}
					let data = res.data.filter(n => {
						if(n.type == 'share' && (!n.status || !n.account)) {
							return false;
						}
						if(n.type == 'comment' && (!n.status || !n.account)) {
							return false;
						}
						if(n.type == 'mention' && (!n.status || !n.account)) {
							return false;
						}
						if(n.type == 'favourite' && (!n.status || !n.account)) {
							return false;
						}
						if(n.type == 'follow' && !n.account) {
							return false;
						}
						if(n.type == 'modlog' && !n.modlog) {
							return false;
						}
						return true;
					});

					if(!res.data.length) {
						this.canLoadMore = false;
					} else {
						this.canLoadMore = true;
					}

					if(this.retryTimeout || this.retryAttempts) {
						this.retryAttempts = 0;
						clearTimeout(this.retryTimeout);
					}
					this.maxId = res.data[res.data.length - 1].id;
					this.feed = data;

					this.hasLoaded = true;
					setTimeout(() => {
						this.isRefreshing = false;
					}, 15000);
				});
			},

			refreshNotifications() {
				event.currentTarget.blur();
				this.isRefreshing = true;
				this.init();
			},

			enterIntersect() {
				if(this.isIntersecting || !this.canLoadMore) {
					return;
				}

				this.isIntersecting = true;

				axios.get('/api/pixelfed/v1/notifications', {
					params: {
						limit: 9,
						max_id: this.maxId
					}
				})
				.then(res => {
					if(!res.data || !res.data.length) {
						this.canLoadMore = false;
						this.isIntersecting = false;
						return;
					}
					let data = res.data.filter(n => {
						if(n.type == 'share' && (!n.status || !n.account)) {
							return false;
						}
						if(n.type == 'comment' && (!n.status || !n.account)) {
							return false;
						}
						if(n.type == 'mention' && (!n.status || !n.account)) {
							return false;
						}
						if(n.type == 'favourite' && (!n.status || !n.account)) {
							return false;
						}
						if(n.type == 'follow' && !n.account) {
							return false;
						}
						if(n.type == 'modlog' && !n.modlog) {
							return false;
						}
						return true;
					});

					if(!res.data.length) {
						this.canLoadMore = false;
						return;
					}

					this.maxId = res.data[res.data.length - 1].id;
					this.feed.push(...data);

					this.$nextTick(() => {
					   this.isIntersecting = false;
					})
				});
			},

			truncate(text) {
				if(text.length <= 15) {
					return text;
				}

				return text.slice(0,15) + '...'
			},

			timeAgo(ts) {
				return window.App.util.format.timeAgo(ts);
			},

			mentionUrl(status) {
				let username = status.account.username;
				let id = status.id;
				return '/p/' + username + '/' + id;
			},

			redirect(url) {
				window.location.href = url;
			},

			notificationPreview(n) {
				if(!n.status || !n.status.hasOwnProperty('media_attachments') || !n.status.media_attachments.length) {
					return '/storage/no-preview.png';
				}
				return n.status.media_attachments[0].preview_url;
			},

			getProfileUrl(account) {
				return '/i/web/profile/' + account.id;
			},

			getPostUrl(status) {
				if(!status) {
					return;
				}

				return '/i/web/post/' + status.id;
			},

			goToPost(status) {
				this.$router.push({
					name: 'post',
					path: `/i/web/post/${status.id}`,
					params: {
						id: status.id,
						cachedStatus: status,
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

			showAutospamInfo(status) {
				let el = document.createElement('p');
				el.classList.add('text-left');
				el.classList.add('mb-0');
				el.innerHTML = '<p class="">We use automated systems to help detect potential abuse and spam. Your recent <a href="/i/web/post/' + status.id + '" class="font-weight-bold">post</a> was flagged for review. <br /> <p class=""><span class="font-weight-bold">Don\'t worry! Your post will be reviewed by a human</span>, and they will restore your post if they determine it appropriate.</p><p style="font-size:12px">Once a human approves your post, any posts you create after will not be marked as unlisted. If you delete this post and share more posts before a human can approve any of them, you will need to wait for at least one unlisted post to be reviewed by a human.';
				let wrapper = document.createElement('div');
				wrapper.appendChild(el);
				swal({
					title: 'Why was my post unlisted?',
					content: wrapper,
					icon: 'warning'
				})
			}
		}
	}
</script>

<style lang="scss">
	.notifications-component {
		&-feed {
			min-height: 50px;
			max-height: 300px;
			overflow-y: auto;

			-ms-overflow-style: none;
			scrollbar-width: none;
			overflow-y: scroll;

			&::-webkit-scrollbar {
				display: none;
			}

		}
		.card {
			width: 100%;
			position: relative;
		}

		.card-body {
			width: 100%;
		}
	}
</style>
