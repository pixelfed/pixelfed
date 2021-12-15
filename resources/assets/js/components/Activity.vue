<template>
	<div>
		<!-- <div class="bg-white py-4">
			<div class="container">
				<div class="d-flex justify-content-between align-items-center">
					<div></div>
					<a href="/account/activity" class="cursor-pointer font-weight-bold text-primary">Notifications</a>
					<a href="/account/direct" class="cursor-pointer font-weight-bold text-dark">Direct Messages</a>
					<a href="/account/following" class="cursor-pointer font-weight-bold text-dark">Following</a>
					<div></div>
				</div>
			</div>
		</div> -->
		<div class="container">
			<div class="row my-5">
				<div class="col-12 col-md-8 offset-md-2">
					<div v-if="notifications.length > 0" class="media mb-3 align-items-center px-3 border-bottom pb-3" v-for="(n, index) in notifications">
						<img class="mr-2 rounded-circle" style="border:1px solid #ccc" :src="n.account.avatar" alt="" width="32px" height="32px">
						<div class="media-body font-weight-light">
							<div v-if="n.type == 'favourite'">
								<p class="my-0">
									<a :href="getProfileUrl(n.account)" class="font-weight-bold text-dark word-break" data-placement="bottom" data-toggle="tooltip" :title="n.account.username">{{n.account.local == false ? '@':''}}{{truncate(n.account.username)}}</a> liked your <a class="font-weight-bold" v-bind:href="getPostUrl(n.status)">post</a>.
								</p>
							</div>

							<div v-else-if="n.type == 'comment'">
								<p class="my-0">
									<a :href="getProfileUrl(n.account)" class="font-weight-bold text-dark word-break" data-placement="bottom" data-toggle="tooltip" :title="n.account.username">{{n.account.local == false ? '@':''}}{{truncate(n.account.username)}}</a> commented on your <a class="font-weight-bold" v-bind:href="getPostUrl(n.status)">post</a>.
								</p>
							</div>

							<div v-else-if="n.type == 'group:comment'">
								<p class="my-0">
									<a :href="getProfileUrl(n.account)" class="font-weight-bold text-dark word-break" :title="n.account.username">{{n.account.local == false ? '@':''}}{{truncate(n.account.username)}}</a> commented on your <a class="font-weight-bold" v-bind:href="n.group_post_url">group post</a>.
								</p>
							</div>

							<div v-else-if="n.type == 'story:react'">
								<p class="my-0">
									<a :href="getProfileUrl(n.account)" class="font-weight-bold text-dark word-break" :title="n.account.username">{{n.account.local == false ? '@':''}}{{truncate(n.account.username)}}</a> reacted to your <a class="font-weight-bold" v-bind:href="'/account/direct/t/'+n.account.id">story</a>.
								</p>
							</div>

							<div v-else-if="n.type == 'story:comment'">
								<p class="my-0">
									<a :href="getProfileUrl(n.account)" class="font-weight-bold text-dark word-break" :title="n.account.username">{{n.account.local == false ? '@':''}}{{truncate(n.account.username)}}</a> commented on your <a class="font-weight-bold" v-bind:href="'/account/direct/t/'+n.account.id">story</a>.
								</p>
							</div>

							<div v-else-if="n.type == 'mention'">
								<p class="my-0">
									<a :href="getProfileUrl(n.account)" class="font-weight-bold text-dark word-break" data-placement="bottom" data-toggle="tooltip" :title="n.account.username">{{n.account.local == false ? '@':''}}{{truncate(n.account.username)}}</a> <a class="font-weight-bold" v-bind:href="mentionUrl(n.status)">mentioned</a> you.
								</p>
							</div>

							<div v-else-if="n.type == 'follow'">
								<p class="my-0">
									<a :href="getProfileUrl(n.account)" class="font-weight-bold text-dark word-break" data-placement="bottom" data-toggle="tooltip" :title="n.account.username">{{n.account.local == false ? '@':''}}{{truncate(n.account.username)}}</a> followed you.
								</p>
							</div>

							<div v-else-if="n.type == 'share'">
								<p class="my-0">
									<a :href="getProfileUrl(n.account)" class="font-weight-bold text-dark word-break" data-placement="bottom" data-toggle="tooltip" :title="n.account.username">{{n.account.local == false ? '@':''}}{{truncate(n.account.username)}}</a> shared your <a class="font-weight-bold" v-bind:href="getPostUrl(n.status)">post</a>.
								</p>
							</div>

							<div v-else-if="n.type == 'modlog'">
								<p class="my-0">
									<a :href="getProfileUrl(n.account)" class="font-weight-bold text-dark word-break" :title="n.account.username">{{truncate(n.account.username)}}</a> updated a <a class="font-weight-bold" v-bind:href="n.modlog.url">modlog</a>.
								</p>
							</div>

							<div v-else-if="n.type == 'tagged'">
								<p class="my-0">
									<a :href="getProfileUrl(n.account)" class="font-weight-bold text-dark word-break" :title="n.account.username">{{n.account.local == false ? '@':''}}{{truncate(n.account.username)}}</a> tagged you in a <a class="font-weight-bold" v-bind:href="n.tagged.post_url">post</a>.
								</p>
							</div>

							<div v-else-if="n.type == 'direct'">
								<p class="my-0">
									<a :href="getProfileUrl(n.account)" class="font-weight-bold text-dark word-break" :title="n.account.username">{{n.account.local == false ? '@':''}}{{truncate(n.account.username)}}</a> sent a <a class="font-weight-bold" v-bind:href="'/account/direct/t/'+n.account.id">dm</a>.
								</p>
							</div>

							<div v-else-if="n.type == 'direct'">
								<p class="my-0">
									<a :href="getProfileUrl(n.account)" class="font-weight-bold text-dark word-break" :title="n.account.username">{{n.account.local == false ? '@':''}}{{truncate(n.account.username)}}</a> sent a <a class="font-weight-bold" v-bind:href="'/account/direct/t/'+n.account.id">dm</a>.
								</p>
							</div>

							<div v-else-if="n.type == 'group.join.approved'">
								<p class="my-0">
									Your application to join <a :href="n.group.url" class="font-weight-bold text-dark word-break" :title="n.group.name">{{truncate(n.group.name)}}</a> was approved!
								</p>
							</div>

							<div v-else-if="n.type == 'group.join.rejected'">
								<p class="my-0">
									Your application to join <a :href="n.group.url" class="font-weight-bold text-dark word-break" :title="n.group.name">{{truncate(n.group.name)}}</a> was rejected. You can re-apply to join in 6 months.
								</p>
							</div>

							<div class="align-items-center">
								<span class="small text-muted" data-toggle="tooltip" data-placement="bottom" :title="n.created_at">{{timeAgo(n.created_at)}}</span>
							</div>
						</div>
						<div>
							<div v-if="n.status && n.status && n.status.media_attachments && n.status.media_attachments.length">
								<a :href="getPostUrl(n.status)">
									<img :src="n.status.media_attachments[0].preview_url" width="32px" height="32px">
								</a>
							</div>
							<div v-else-if="n.status && n.status.parent && n.status.parent.media_attachments && n.status.parent.media_attachments.length">
								<a :href="n.status.parent.url">
									<img :src="n.status.parent.media_attachments[0].preview_url" width="32px" height="32px">
								</a>
							</div>
							<!-- <div v-else-if="n.status && n.status.parent && n.status.parent.media_attachments && n.status.parent.media_attachments.length">
								<a :href="n.status.parent.url">
									<img :src="n.status.parent.media_attachments[0].preview_url" width="32px" height="32px">
								</a>
							</div> -->

							<!-- <div v-else-if="n.type == 'follow' && n.relationship.following == false">
								<a href="#" class="btn btn-primary py-0 font-weight-bold" @click.prevent="followProfile(n);">
									Follow
								</a>
							</div> -->

							<!-- <div v-else-if="n.status && n.status.parent && !n.status.parent.media_attachments && n.type == 'like' && n.relationship.following == false">
								<a href="#" class="btn btn-primary py-0 font-weight-bold">
									Follow
								</a>
							</div> -->
							<div v-else>
								<a v-if="viewContext(n) != '/'" class="btn btn-outline-primary py-0 font-weight-bold" :href="viewContext(n)">View</a>
							</div>
						</div>
					</div>
					<div v-if="notifications.length">
						<infinite-loading @infinite="infiniteNotifications">
							<div slot="no-results" class="font-weight-bold"></div>
							<div slot="no-more" class="font-weight-bold"></div>
						</infinite-loading>
					</div>
					<div v-if="notifications.length == 0" class="text-lighter text-center py-3">
						<p class="mb-0"><i class="fas fa-inbox fa-3x"></i></p>
						<p class="mb-0 small font-weight-bold">0 Notifications!</p>
					</div>
				</div>
			</div>
		</div>
	</div>
</template>

<script type="text/javascript">
export default {
	data() {
		return {
			notifications: {},
			notificationCursor: 2,
			notificationMaxId: 0,
		};
	},

	mounted() {
		this.fetchNotifications();
	},

	updated() {
		$('[data-toggle="tooltip"]').tooltip()
	},

	methods: {
		fetchNotifications() {
			axios.get('/api/pixelfed/v1/accounts/verify_credentials').then(res => {
					window._sharedData.curUser = res.data;
					window.App.util.navatar();
			});
			axios.get('/api/pixelfed/v1/notifications?pg=true')
			.then(res => {
				let data = res.data.filter(n => {
					if(n.type == 'share' && !n.status) {
						return false;
					}
					if(n.type == 'comment' && !n.status) {
						return false;
					}
					if(n.type == 'mention' && !n.status) {
						return false;
					}
					if(n.type == 'favourite' && !n.status) {
						return false;
					}
					if(n.type == 'follow' && !n.account) {
						return false;
					}
					return true;
				});
				let ids = res.data.map(n => n.id);
				this.notificationMaxId = Math.max(...ids);
				this.notifications = data;
				$('.notification-card .loader').addClass('d-none');
				$('.notification-card .contents').removeClass('d-none');
			});
		},

		infiniteNotifications($state) {
			if(this.notificationCursor > 10) {
				$state.complete();
				return;
			}
			axios.get('/api/pixelfed/v1/notifications', {
				params: {
					max_id: this.notificationMaxId
				}
			}).then(res => {
				if(res.data.length) {
					let data = res.data.filter(n => {
						if(n.type == 'share' && !n.status) {
							return false;
						}
						if(n.type == 'comment' && !n.status) {
							return false;
						}
						if(n.type == 'mention' && !n.status) {
							return false;
						}
						if(n.type == 'favourite' && !n.status) {
							return false;
						}
						if(n.type == 'follow' && !n.account) {
							return false;
						}
						if(_.find(this.notifications, {id: n.id})) {
							return false;
						}
						return true;
					});

					let ids = data.map(n => n.id);
					this.notificationMaxId = Math.max(...ids);
					this.notifications.push(...data);
					this.notificationCursor++;
					$state.loaded();
				} else {
					$state.complete();
				}
			});
		},

		truncate(text) {
			if(text.length <= 15) {
				return text;
			}

			return text.slice(0,15) + '...'
		},

		timeAgo(ts) {
			let date = Date.parse(ts);
			let seconds = Math.floor((new Date() - date) / 1000);
			let interval = Math.floor(seconds / 31536000);
			if (interval >= 1) {
				return interval + "y";
			}
			interval = Math.floor(seconds / 604800);
			if (interval >= 1) {
				return interval + "w";
			}
			interval = Math.floor(seconds / 86400);
			if (interval >= 1) {
				return interval + "d";
			}
			interval = Math.floor(seconds / 3600);
			if (interval >= 1) {
				return interval + "h";
			}
			interval = Math.floor(seconds / 60);
			if (interval >= 1) {
				return interval + "m";
			}
			return Math.floor(seconds) + "s";
		},

		mentionUrl(status) {
			let username = status.account.username;
			let id = status.id;
			return '/p/' + username + '/' + id;
		},

		followProfile(n) {
			let self = this;
			let id = n.account.id;
			axios.post('/i/follow', {
					item: id
			}).then(res => {
				self.notifications.map(notification => {
					if(notification.account.id === id) {
						notification.relationship.following = true;
					}
				});
			}).catch(err => {
				if(err.response.data.message) {
					swal('Error', err.response.data.message, 'error');
				}
			});
		},

		viewContext(n) {
			switch(n.type) {
				case 'follow':
					return n.account.url;
				break;
				case 'mention':
					return n.status.url;
				break;
				case 'like':
				case 'favourite':
				case 'comment':
					return n.status.url;
				break;
				case 'tagged':
					return n.tagged.post_url;
				break;
				case 'direct':
					return '/account/direct/t/'+n.account.id;
				break
			}
			return '/';
		},

		getProfileUrl(account) {
			if(account.local == true) {
				return account.url;
			}

			return '/i/web/profile/_/' + account.id;
		},

		getPostUrl(status) {
			if(status.local == true) {
				return status.url;
			}

			return '/i/web/post/_/' + status.account.id + '/' + status.id;
		}
	}
}
</script>
