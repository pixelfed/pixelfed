<template>
<div class="container" style="">
	<div class="row">
		<div class="col-md-8 col-lg-8 pt-2 px-0 my-3 timeline order-2 order-md-1">
			<div class="loader text-center">
				<div class="spinner-border" role="status">
				  <span class="sr-only">Loading...</span>
				</div>
			</div>
			<div class="card mb-4 status-card card-md-rounded-0" :data-status-id="status.id" v-for="(status, index) in feed" :key="status.id">

				<div class="card-header d-inline-flex align-items-center bg-white">
					<img v-bind:src="status.account.avatar" width="32px" height="32px" style="border-radius: 32px;">
					<a class="username font-weight-bold pl-2 text-dark" v-bind:href="status.account.url">
						{{status.account.username}}
					</a>
					<div class="text-right" style="flex-grow:1;">
						<div class="dropdown">
							<button class="btn btn-link text-dark no-caret dropdown-toggle" type="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false" title="Post options">
								<span class="fas fa-ellipsis-v fa-lg text-muted"></span>
							</button>
							<div class="dropdown-menu dropdown-menu-right" aria-labelledby="dropdownMenuButton">
								<a class="dropdown-item font-weight-bold" :href="status.url">Go to post</a>
								<span v-bind:class="[statusOwner(status) ? 'd-none' : '']">
									<a class="dropdown-item font-weight-bold" :href="reportUrl(status)">Report</a>
									<a class="dropdown-item font-weight-bold" v-on:click="muteProfile(status)">Mute Profile</a>
									<a class="dropdown-item font-weight-bold" v-on:click="blockProfile(status)">Block Profile</a>
								</span>
								<span  v-bind:class="[statusOwner(status) ? '' : 'd-none']">
									<a class="dropdown-item font-weight-bold" :href="editUrl(status)">Edit</a>
									<a class="dropdown-item font-weight-bold text-danger" v-on:click="deletePost(status)">Delete</a>
								</span>
							</div>
						</div>
					</div>
				</div>

				<div class="postPresenterContainer">
					<div v-if="status.pf_type === 'photo'" class="w-100">
						<photo-presenter :status="status"></photo-presenter>	
					</div>

					<div v-else-if="status.pf_type === 'video'" class="w-100">
						<video-presenter :status="status"></video-presenter>
					</div>

					<div v-else-if="status.pf_type === 'photo:album'" class="w-100">
						<photo-album-presenter :status="status"></photo-album-presenter>
					</div>

					<div v-else-if="status.pf_type === 'video:album'" class="w-100">
						<video-album-presenter :status="status"></video-album-presenter>
					</div>

					<div v-else-if="status.pf_type === 'photo:video:album'" class="w-100">
						<mixed-album-presenter :status="status"></mixed-album-presenter>
					</div>

					<div v-else class="w-100">
						<p class="text-center p-0 font-weight-bold text-white">Error: Problem rendering preview.</p>
					</div>
				</div>

				<div class="card-body">
					<div class="reactions my-1">
						<h3 v-bind:class="[status.favourited ? 'fas fa-heart text-danger pr-3 m-0 cursor-pointer' : 'far fa-heart pr-3 m-0 like-btn cursor-pointer']" title="Like" v-on:click="likeStatus(status, $event)"></h3>
						<h3 class="far fa-comment pr-3 m-0 cursor-pointer" title="Comment" v-on:click="commentFocus(status, $event)"></h3>
						<h3 v-bind:class="[status.reblogged ? 'far fa-share-square pr-3 m-0 text-primary cursor-pointer' : 'far fa-share-square pr-3 m-0 share-btn cursor-pointer']" title="Share" v-on:click="shareStatus(status, $event)"></h3>
					</div>

					<div class="likes font-weight-bold">
						<span class="like-count">{{status.favourites_count}}</span> {{status.favourites_count == 1 ? 'like' : 'likes'}}
					</div>
					<div class="caption">
						<p class="mb-2 read-more" style="overflow: hidden;">
							<span class="username font-weight-bold">
								<bdi><a class="text-dark" :href="status.account.url">{{status.account.username}}</a></bdi>
							</span>
							<span v-html="status.content"></span>
						</p>
					</div>
					<div class="comments">
					</div>
					<div class="timestamp pt-1">
						<p class="small text-uppercase mb-0">
							<a :href="status.url" class="text-muted">
								<timeago :datetime="status.created_at" :auto-update="60" :converter-options="{includeSeconds:true}" :title="timestampFormat(status.created_at)" v-b-tooltip.hover.bottom></timeago>
							</a>
						</p>
					</div>
				</div>

				<div class="card-footer bg-white d-none">
					<form class="" v-on:submit.prevent="commentSubmit(status, $event)">
						<input type="hidden" name="item" value="">
						<input class="form-control status-reply-input" name="comment" placeholder="Add a comment…" autocomplete="off">
					</form>
				</div>
			</div>
		</div>

		<div class="col-md-4 col-lg-4 pt-2 my-3 order-1 order-md-2">
			<div class="mb-4">
				<div class="card profile-card">
					<div class="card-body loader text-center">
						<div class="spinner-border" role="status">
							<span class="sr-only">Loading...</span>
						</div>
					</div>
					<div class="card-body contents d-none">
						<div class="media d-flex align-items-center">
							<a :href="profile.url">
								<img class="mr-3 rounded-circle box-shadow" :src="profile.avatar || '/storage/avatars/default.png'" alt="avatar" width="64px" height="64px">
							</a>
							<div class="media-body">
								<p class="mb-0 px-0 font-weight-bold"><a :href="profile.url" class="text-dark">&commat;{{profile.username}}</a></p>
								<p class="my-0 text-muted text-truncate pb-0">{{profile.display_name}}</p>
							</div>
						</div>
					</div>
					<div class="card-footer bg-white py-1 d-none">
						<div class="d-flex justify-content-between text-center">
							<span class="pl-3 cursor-pointer" v-on:click="redirect(profile.url)">
								<p class="mb-0 font-weight-bold">{{profile.statuses_count}}</p>
								<p class="mb-0 small text-muted">Posts</p>
							</span>
							<span class="cursor-pointer" v-on:click="redirect(profile.url + '/followers')">
								<p class="mb-0 font-weight-bold">{{profile.followers_count}}</p>
								<p class="mb-0 small text-muted">Followers</p>
							</span>
							<span class="pr-3 cursor-pointer" v-on:click="redirect(profile.url + '/following')">
								<p class="mb-0 font-weight-bold">{{profile.following_count}}</p>
								<p class="mb-0 small text-muted">Following</p>
							</span>
						</div>
					</div>
				</div>
			</div>

			<div class="mb-4">
				<div class="card notification-card">
					<div class="card-header bg-white">
						<p class="mb-0 d-flex align-items-center justify-content-between">
							<span class="text-muted font-weight-bold">Notifications</span>
							<a class="text-dark small" href="/account/activity">See All</a>
						</p>
					</div>
					<div class="card-body loader text-center" style="height: 300px;">
						<div class="spinner-border" role="status">
							<span class="sr-only">Loading...</span>
						</div>
					</div>
					<div class="card-body pt-2 contents" style="max-height: 300px; overflow-y: scroll;">
						<div class="media mb-3 align-items-center" v-for="(n, index) in notifications">
							<img class="mr-2 rounded-circle" style="border:1px solid #ccc" :src="n.account.avatar" alt="" width="32px" height="32px">
							<div class="media-body font-weight-light small">
								<div v-if="n.type == 'favourite'">
									<p class="my-0">
										<a :href="n.account.url" class="font-weight-bold text-dark word-break">{{n.account.username}}</a> liked your <a class="font-weight-bold" v-bind:href="replyUrl(n.status)">post</a>.
									</p>
								</div>
								<div v-else-if="n.type == 'comment'">
									<p class="my-0">
										<a :href="n.account.url" class="font-weight-bold text-dark word-break">{{n.account.username}}</a> commented on your <a class="font-weight-bold" v-bind:href="replyUrl(n.status)">post</a>.
									</p>
								</div>
								<div v-else-if="n.type == 'mention'">
									<p class="my-0">
										<a :href="n.account.url" class="font-weight-bold text-dark word-break">{{n.account.username}}</a> <a class="font-weight-bold" v-bind:href="mentionUrl(n.status)">mentioned</a> you.
									</p>
								</div>
								<div v-else-if="n.type == 'follow'">
									<p class="my-0">
										<a :href="n.account.url" class="font-weight-bold text-dark word-break">{{n.account.username}}</a> followed you.
									</p>	
								</div>
							</div>
							
						</div>
					</div>
				</div>
			</div>

			<footer>
				<div class="container pb-5">
					<p class="mb-0 text-uppercase font-weight-bold text-muted small">
						<a href="/site/about" class="text-dark pr-2">About Us</a>
						<a href="/site/help" class="text-dark pr-2">Support</a>
						<a href="/site/open-source" class="text-dark pr-2">Open Source</a>
						<a href="/site/language" class="text-dark pr-2">Language</a>
						<a href="/site/terms" class="text-dark pr-2">Terms</a>
						<a href="/site/privacy" class="text-dark pr-2">Privacy</a>
						<a href="/site/platform" class="text-dark pr-2">API</a>
					</p>
					<p class="mb-0 text-uppercase font-weight-bold text-muted small">
						<a href="http://pixelfed.org" class="text-muted" rel="noopener" title="" data-toggle="tooltip">Powered by PixelFed</a>
					</p>
				</div>
			</footer>
		</div>
	</div>
</div>
</template>

<style type="text/css">
	.postPresenterContainer {
		display: flex;
		align-items: center;
		background: #fff;
	}
	.cursor-pointer {
		cursor: pointer;
	}
	.word-break {
		word-break: break-all;
	}
</style>

<script type="text/javascript">
	export default {
		data() {
			return {
				page: 1,
				feed: [],
				profile: {},
				scope: window.location.pathname,
				min_id: 0,
				max_id: 0,
				notifications: {},
				stories: {},
				suggestions: {},
			}
		},

		beforeMount() {
			this.fetchTimelineApi();
			this.fetchProfile();
		},

		mounted() {
		},

		updated() {
			this.scroll();
		},

		methods: {
			fetchProfile() {
				axios.get('/api/v1/accounts/verify_credentials').then(res => {
					this.profile = res.data;
					$('.profile-card .loader').addClass('d-none');
					$('.profile-card .contents').removeClass('d-none');
					$('.profile-card .card-footer').removeClass('d-none');
					this.fetchNotifications();
				}).catch(err => {
					swal(
						'Oops, something went wrong',
						'Please reload the page.',
						'error'
					);
				});
			},

			fetchTimelineApi() {
				let homeTimeline = '/api/v1/timelines/home?page=' + this.page;
				let localTimeline = '/api/v1/timelines/public?page=' + this.page;
				let apiUrl = this.scope == '/' ? homeTimeline : localTimeline;
				axios.get(apiUrl).then(res => {
					$('.timeline .loader').addClass('d-none');
					let data = res.data;
					this.feed.push(...data);
					let ids = data.map(status => status.id);
					this.min_id = Math.min(...ids);
					if(this.page == 1) {
						this.max_id = Math.max(...ids);
					}
					this.page++;
				}).catch(err => {
				});
			},

			fetchNotifications() {
				axios.get('/api/v1/notifications')
				.then(res => {
					this.notifications = res.data;
					$('.notification-card .loader').addClass('d-none');
					$('.notification-card .contents').removeClass('d-none');
				});
			},

			scroll() {
				window.onscroll = () => {
				  let bottomOfWindow = document.documentElement.scrollTop + window.innerHeight == document.documentElement.offsetHeight;

				  if (bottomOfWindow) {
				  	this.fetchTimelineApi();
				  }
				};
			},

			reportUrl(status) {
				let type = status.in_reply_to ? 'comment' : 'post';
				let id = status.id;
				return '/i/report?type=' + type + '&id=' + id;
			},

			commentFocus(status, $event) {
				let el = event.target;
				let card = el.parentElement.parentElement.parentElement;
				let comments = card.getElementsByClassName('comments')[0];
				if(comments.children.length == 0) {
					comments.classList.add('mb-2');
					this.fetchStatusComments(status, card);
				}
				let footer = card.querySelectorAll('.card-footer')[0];
				let input = card.querySelectorAll('.status-reply-input')[0];
				if(footer.classList.contains('d-none') == true) {
					footer.classList.remove('d-none');
					input.focus();
				} else {
					footer.classList.add('d-none');
					input.blur();
				}
			},

			likeStatus(status, $event) {
				if($('body').hasClass('loggedIn') == false) {
					return;
				}
				
				axios.post('/i/like', {
					item: status.id
				}).then(res => {
					status.favourites_count = res.data.count;
					if(status.favourited == true) {
						status.favourited = false;
					} else {
						status.favourited = true;
					}
				}).catch(err => {
					swal('Error', 'Something went wrong, please try again later.', 'error');
				});
			},

			shareStatus(status, $event) {
				if($('body').hasClass('loggedIn') == false) {
					return;
				}

				axios.post('/i/share', {
					item: status.id
				}).then(res => {
					status.reblogs_count = res.data.count;
					if(status.reblogged == true) {
						status.reblogged = false;
					} else {
						status.reblogged = true;
					}
				}).catch(err => {
					swal('Error', 'Something went wrong, please try again later.', 'error');
				});
			},

			timestampFormat(timestamp) {
				let ts = new Date(timestamp);
				return ts.toDateString() + ' ' + ts.toLocaleTimeString();
			},

			editUrl(status) {
				return status.url + '/edit';
			},

			redirect(url) {
				window.location.href = url;
				return;
			},

			replyUrl(status) {
				let username = this.profile.username;
				let id = status.account.id == this.profile.id ? status.id : status.in_reply_to_id;
				return '/p/' + username + '/' + id;
			},

			mentionUrl(status) {
				let username = status.account.username;
				let id = status.id;
				return '/p/' + username + '/' + id;
			},

			statusOwner(status) {
				let sid = status.account.id;
				let uid = this.profile.id;
				if(sid == uid) {
					return true;
				} else {
					return false;
				}
			},

			fetchStatusComments(status, card) {
				axios.get('/api/v2/status/'+status.id+'/replies')
				.then(res => {
					let comments = card.querySelectorAll('.comments')[0];
					let data = res.data;
					data.forEach(function(i, k) {
						let username = document.createElement('a');
						username.classList.add('font-weight-bold');
						username.classList.add('text-dark');
						username.classList.add('mr-2');
						username.setAttribute('href', i.account.url);
						username.textContent = i.account.username;

						let text = document.createElement('span');
						text.innerHTML = i.content;

						let comment = document.createElement('p');
						comment.classList.add('read-more');
						comment.classList.add('mb-0');
						comment.appendChild(username);
						comment.appendChild(text);
						comments.appendChild(comment);
					});
				}).catch(err => {
				})
			},

			muteProfile(status) {
				if($('body').hasClass('loggedIn') == false) {
					return;
				}
				axios.post('/i/mute', {
					type: 'user',
					item: status.account.id
				}).then(res => {
					this.feed = this.feed.filter(s => s.account.id !== status.account.id);
					swal('Success', 'You have successfully muted ' + status.account.acct, 'success');
				}).catch(err => {
					swal('Error', 'Something went wrong. Please try again later.', 'error');
				});
			},

			blockProfile(status) {
				if($('body').hasClass('loggedIn') == false) {
					return;
				}

				axios.post('/i/block', {
					type: 'user',
					item: status.account.id
				}).then(res => {
					this.feed = this.feed.filter(s => s.account.id !== status.account.id);
					swal('Success', 'You have successfully blocked ' + status.account.acct, 'success');
				}).catch(err => {
					swal('Error', 'Something went wrong. Please try again later.', 'error');
				});
			},

			deletePost(status, index) {
				if($('body').hasClass('loggedIn') == false || status.account.id !== this.profile.id) {
					return;
				}

				axios.post('/i/delete', {
					type: 'status',
					item: status.id
				}).then(res => {
					this.feed.splice(index,1);
					swal('Success', 'You have successfully deleted this post', 'success');
				}).catch(err => {
					swal('Error', 'Something went wrong. Please try again later.', 'error');
				});
			},

			commentSubmit(status, $event) {
				let id = status.id;
				let form = $event.target;
				let input = $(form).find('input[name="comment"]');
				let comment = input.val();
				let comments = form.parentElement.parentElement.getElementsByClassName('comments')[0];
				axios.post('/i/comment', {
					item: id,
					comment: comment
				}).then(res => {
					input.val('');
					input.blur();

					let username = document.createElement('a');
					username.classList.add('font-weight-bold');
					username.classList.add('text-dark');
					username.classList.add('mr-2');
					username.setAttribute('href', this.profile.url);
					username.textContent = this.profile.username;

					let text = document.createElement('span');
					text.innerHTML = comment;

					let wrapper = document.createElement('p');
					wrapper.classList.add('read-more');
					wrapper.classList.add('mb-0');
					wrapper.appendChild(username);
					wrapper.appendChild(text);
					comments.insertBefore(wrapper, comments.firstChild);
				});
			}

		}
	}
</script>