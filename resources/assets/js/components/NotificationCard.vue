<template>
	<div>
		<div class="card notification-card">
			<div class="card-header bg-white">
				<p class="mb-0 d-flex align-items-center justify-content-between">
					<span><i class="far fa-bell fa-lg text-muted"></i></span>
					<span class="small text-dark text-uppercase font-weight-bold">Alerts</span>
					<a class="text-decoration-none text-muted" href="/account/activity"><i class="fas fa-inbox fa-lg"></i></a>
				</p>
			</div>
			<div class="card-body loader text-center" style="height: 230px;">
				<div class="spinner-border" role="status">
					<span class="sr-only">Loading...</span>
				</div>
			</div>
			<div class="card-body pt-2 px-0 contents" style="max-height: 230px; overflow-y: scroll;">
				<div v-if="notifications.length > 0" class="media mb-4 align-items-center px-3" v-for="(n, index) in notifications">
					<img class="mr-2 rounded-circle" style="border:1px solid #ccc" :src="n.account.avatar" alt="" width="32px" height="32px">
					<div class="media-body font-weight-light small">
						<div v-if="n.type == 'favourite'">
							<p class="my-0">
								<a :href="n.account.url" class="font-weight-bold text-dark word-break" data-placement="bottom" data-toggle="tooltip" :title="n.account.username">{{truncate(n.account.username)}}</a> liked your <a class="font-weight-bold" v-bind:href="n.status.url">post</a>.
							</p>
						</div>
						<div v-else-if="n.type == 'comment'">
							<p class="my-0">
								<a :href="n.account.url" class="font-weight-bold text-dark word-break" data-placement="bottom" data-toggle="tooltip" :title="n.account.username">{{truncate(n.account.username)}}</a> commented on your <a class="font-weight-bold" v-bind:href="n.status.url">post</a>.
							</p>
						</div>
						<div v-else-if="n.type == 'mention'">
							<p class="my-0">
								<a :href="n.account.url" class="font-weight-bold text-dark word-break" data-placement="bottom" data-toggle="tooltip" :title="n.account.username">{{truncate(n.account.username)}}</a> <a class="font-weight-bold" v-bind:href="mentionUrl(n.status)">mentioned</a> you.
							</p>
						</div>
						<div v-else-if="n.type == 'follow'">
							<p class="my-0">
								<a :href="n.account.url" class="font-weight-bold text-dark word-break" data-placement="bottom" data-toggle="tooltip" :title="n.account.username">{{truncate(n.account.username)}}</a> followed you.
							</p>
						</div>
						<div v-else-if="n.type == 'share'">
							<p class="my-0">
								<a :href="n.account.url" class="font-weight-bold text-dark word-break" data-placement="bottom" data-toggle="tooltip" :title="n.account.username">{{truncate(n.account.username)}}</a> shared your <a class="font-weight-bold" v-bind:href="n.status.reblog.url">post</a>.
							</p>
						</div>
					</div>
					<div class="small text-muted" data-toggle="tooltip" data-placement="bottom" :title="n.created_at">{{timeAgo(n.created_at)}}</div>
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
</template>

<style type="text/css" scoped></style>

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
			if(window.outerWidth > 767) {
				this.fetchNotifications();
			}
		},

		updated() {
			$('[data-toggle="tooltip"]').tooltip()
		},

		methods: {
			fetchNotifications() {
				axios.get('/api/v1/notifications')
				.then(res => {
					let data = res.data.filter(n => {
						if(n.type == 'share' && !status) {
							return false;
						}
						return true;
					});
					let ids = res.data.map(n => n.id);
					this.notificationMaxId = Math.max(...ids);
					this.notifications = data;
					$('.notification-card .loader').addClass('d-none');
					$('.notification-card .contents').removeClass('d-none');
					this.notificationPoll();
				});
			},

			infiniteNotifications($state) {
				if(this.notificationCursor > 10) {
					$state.complete();
					return;
				}
				axios.get('/api/v1/notifications', {
					params: {
						page: this.notificationCursor
					}
				}).then(res => {
					if(res.data.length) {
						let data = res.data.filter(n => {
							if(n.type == 'share' && !status) {
								return false;
							}
							return true;
						});
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

            webNotify(notification) {

                const title = `${notification.account.username} ${{
                    "favourite": "liked your post"
                    "comment": "commented on your post"
                    "mention": "mentioned you"
                    "follow": "followed you"
                    "share": "shared your post"
                }[notification.type]}`;

                const options = {image: notification.account.avatar}

                let n;

                if (!("Notification" in window)) return
                else if (Notification.permission === 'granted') {
                    n = new Notification(title, options);
                }
                else if (Notification.permission !== 'denied') {
                    Notification.requestPermission(function (permission) {
                        if (permission === "granted") {
                            n = new Notification(title, options);
                        }
                    });
                }

                n.onclick(e => {
                    e.preventDefault();
                    window.open(notification.account.url, '_blank');
                })
            },

			notificationPoll() {
				let interval = this.notifications.length > 5 ? 15000 : 120000;
				let self = this;
				setInterval(function() {
					axios.get('/api/v1/notifications')
					.then(res => {
						let data = res.data.filter(n => {
							if(n.type == 'share' || self.notificationMaxId >= n.id) {
								return false;
							}
							return true;
						});
						if(notification.length) {
							let ids = notification.map(n => n.id);
							self.notificationMaxId = Math.max(...ids);

							self.notifications.unshift(...notification);
							let beep = new Audio('/static/beep.mp3');
							beep.volume = 0.7;
							beep.play();
                            webNotify(notification)
							$('.notification-card .far.fa-bell').addClass('fas text-danger').removeClass('far text-muted');
						}
					});
				}, interval);
			}
		}
	}
</script>
