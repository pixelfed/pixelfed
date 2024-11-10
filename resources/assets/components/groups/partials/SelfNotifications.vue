<template>
	<div class="group-notification-component col-12 col-md-9" style="height: 100vh - 51px !important;overflow:hidden">
		<div class="row h-100 bg-white">
			<div class="col-12 col-md-8 border-left">
                <div class="px-5">
					<div class="my-4">
						<p class="h1 font-weight-bold mb-1">Group Notifications</p>
						<!-- <p class="lead text-muted mb-0">Latest notifications from your groups</p> -->
					</div>
					<!-- <div class="p-4 mb-4">
						<p class="font-weight-bold text-center text-muted">You don't have any notifications</p>
					</div> -->

					<div v-if="notifications.length > 0" v-for="(n, index) in notifications" class="nitem card card-body shadow-none mb-3 py-2 px-0 rounded-pill" style="background-color: #F3F4F6">
						<div class="media align-items-center px-3">
							<img class="mr-3 rounded-circle" style="border:1px solid #ccc" :src="n.account.avatar" alt="" width="32px" height="32px">
							<div class="media-body">
								<div v-if="n.type == 'group:like'">
									<p class="my-0">
										<a :href="getProfileUrl(n.account)" data-placement="bottom" data-toggle="tooltip" :title="n.account.username">{{n.account.local == false ? '@':''}}{{truncate(n.account.username)}}</a> liked your <a v-bind:href="getPostUrl(n.status)">post</a> in <a :href="n.group.url">{{n.group.name}}</a>
									</p>
								</div>

								<div v-else-if="n.type == 'group:comment'">
									<p class="my-0">
										<a :href="getProfileUrl(n.account)" class="font-weight-bold text-dark word-break" :title="n.account.username">{{n.account.local == false ? '@':''}}{{truncate(n.account.username)}}</a> commented on your <a class="font-weight-bold" v-bind:href="n.status.url">post</a> in <a :href="n.group.url">{{n.group.name}}</a>
									</p>
								</div>

								<div v-else-if="n.type == 'mention'">
									<p class="my-0">
										<a :href="getProfileUrl(n.account)" data-placement="bottom" data-toggle="tooltip" :title="n.account.username">{{n.account.local == false ? '@':''}}{{truncate(n.account.username)}}</a> <a v-bind:href="mentionUrl(n.status)">mentioned</a> you.
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

								<div v-else>
									<p class="my-0">Cannot display notification</p>
								</div>

								<!-- <div class="align-items-center">
									<span class="small text-muted" data-toggle="tooltip" data-placement="bottom" :title="n.created_at">{{timeAgo(n.created_at)}}</span>
								</div> -->
							</div>
							<div>
								<!-- <div v-if="n.status && n.status && n.status.media_attachments && n.status.media_attachments.length">
									<a :href="getPostUrl(n.status)">
										<img :src="n.status.media_attachments[0].preview_url" width="32px" height="32px">
									</a>
								</div>
								<div v-else-if="n.status && n.status.parent && n.status.parent.media_attachments && n.status.parent.media_attachments.length">
									<a :href="n.status.parent.url">
										<img :src="n.status.parent.media_attachments[0].preview_url" width="32px" height="32px">
									</a>
								</div>

								<div v-else>
									<a v-if="viewContext(n) != '/'" class="btn btn-outline-primary py-0 font-weight-bold" :href="viewContext(n)">View</a>
								</div> -->

								<div class="align-items-center text-muted">
									<span class="small" data-toggle="tooltip" data-placement="bottom" :title="n.created_at">{{timeAgo(n.created_at)}}</span>
									<span>·</span>
									<div class="dropdown d-inline">
										<a class="dropdown-toggle text-lighter" href="#" role="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
											<i class="far fa-cog fa-sm"></i>
										</a>

										<div class="dropdown-menu dropdown-menu-right">
											<a class="dropdown-item font-weight-bold" href="#">Dismiss</a>
											<div class="dropdown-divider"></div>
											<a class="dropdown-item font-weight-bold" href="#">Help</a>
											<!-- <a class="dropdown-item font-weight-bold" href="#">Ignore</a> -->
											<a class="dropdown-item font-weight-bold" href="#">Report</a>
										</div>
									</div>
								</div>
							</div>
						</div>
					</div>
				</div>
			</div>
			<div class="col-12 col-md-4 border-left bg-light">
			</div>
		</div>
	</div>
</template>

<script type="text/javascript">
	export default {
		data() {
			return {
				notifications: [],
				initialLoad: false,
				loading: true,
				page: 1
			}
		},

		mounted() {
			this.fetchNotifications();
		},

		methods: {
			fetchNotifications() {
				axios.get('/api/pixelfed/v1/accounts/verify_credentials').then(res => {
						window._sharedData.curUser = res.data;
						window.App.util.navatar();
				});
				axios.get('/api/v0/groups/self/notifications')
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
					// let ids = res.data.map(n => n.id);
					// this.notificationMaxId = Math.max(...ids);
					this.notifications = data;
					// $('.notification-card .loader').addClass('d-none');
					// $('.notification-card .contents').removeClass('d-none');
				});
			},

			// infiniteNotifications($state) {
			// 	if(this.notificationCursor > 10) {
			// 		$state.complete();
			// 		return;
			// 	}
			// 	axios.get('/api/pixelfed/v1/notifications', {
			// 		params: {
			// 			max_id: this.notificationMaxId
			// 		}
			// 	}).then(res => {
			// 		if(res.data.length) {
			// 			let data = res.data.filter(n => {
			// 				if(n.type == 'share' && !n.status) {
			// 					return false;
			// 				}
			// 				if(n.type == 'comment' && !n.status) {
			// 					return false;
			// 				}
			// 				if(n.type == 'mention' && !n.status) {
			// 					return false;
			// 				}
			// 				if(n.type == 'favourite' && !n.status) {
			// 					return false;
			// 				}
			// 				if(n.type == 'follow' && !n.account) {
			// 					return false;
			// 				}
			// 				if(_.find(this.notifications, {id: n.id})) {
			// 					return false;
			// 				}
			// 				return true;
			// 			});
			// 			this.notifications.push(...data);
			// 			this.notificationCursor++;
			// 			$state.loaded();
			// 		} else {
			// 			$state.complete();
			// 		}
			// 	});
			// },

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

<style lang="scss">
	.group-notification-component {
		.dropdown-toggle::after {
			content: '';
			display: none;
		}

		.nitem {
			a {
				color: #000;
				font-weight: 700 !important;

				&:hover,
				&:focus {
    				color: #121416 !important;
    			}
			}
		}
	}
</style>
