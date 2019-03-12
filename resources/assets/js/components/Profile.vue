<template>
<div>
	<div class="d-flex justify-content-center py-5 my-5" v-if="loading">
			<img src="/img/pixelfed-icon-grey.svg" class="">
	</div>
	<div v-if="!loading">
		<div class="bg-white py-5 border-bottom">
			<div class="container">
				<div class="row">
					<div class="col-12 col-md-4 d-flex">
						<div class="profile-avatar mx-auto">
							<img class="rounded-circle box-shadow" :src="profile.avatar" width="172px" height="172px">
						</div>
					</div>
					<div class="col-12 col-md-8 d-flex align-items-center">
						<div class="profile-details">
							<div class="username-bar pb-2 d-flex align-items-center">
								<span class="font-weight-ultralight h1">{{profile.username}}</span>
								<span class="pl-4" v-if="profile.is_admin">
									<span class="btn btn-outline-secondary font-weight-bold py-0">ADMIN</span>
								</span>
								<span class="pl-4">
									<a :href="'/users/'+profile.username+'.atom'" class="fas fa-rss fa-lg text-muted"></a>
								</span>	
								<span class="pl-4" v-if="owner">
									<a class="fas fa-cog fa-lg text-muted" href="/settings/home"></a>
								</span>
								<span v-if="profile.id != user.id && user.hasOwnProperty('id')">
									<span class="pl-4" v-if="relationship.following == true">
										<button type="button"  class="btn btn-outline-secondary font-weight-bold px-4 py-0" v-on:click="followProfile()">Unfollow</button>
									</span>
									<span class="pl-4" v-if="!relationship.following">
										<button type="button" class="btn btn-primary font-weight-bold px-4 py-0" v-on:click="followProfile()">Follow</button>
									</span>
								</span>
							</div>
							<div class="profile-stats pb-3 d-inline-flex lead">
								<div class="font-weight-light pr-5">
									<a class="text-dark" :href="profile.url">
										<span class="font-weight-bold">{{profile.statuses_count}}</span>
										Posts
									</a>
								</div>
								<div v-if="profileSettings.followers.count" class="font-weight-light pr-5">
									<a class="text-dark cursor-pointer" v-on:click="followersModal()">
										<span class="font-weight-bold">{{profile.followers_count}}</span>
										Followers
									</a>
								</div>
								<div v-if="profileSettings.following.count" class="font-weight-light">
									<a class="text-dark cursor-pointer" v-on:click="followingModal()">
										<span class="font-weight-bold">{{profile.following_count}}</span>
										Following
									</a>
								</div>
							</div>
							<p class="lead mb-0 d-flex align-items-center">
								<span class="font-weight-bold pr-3">{{profile.display_name}}</span>
							</p>
							<div v-if="profile.note" class="mb-0 lead" v-html="profile.note"></div>
							<p v-if="profile.website" class="mb-0"><a :href="profile.website" class="font-weight-bold" rel="me external nofollow noopener" target="_blank">{{profile.website}}</a></p>
						</div>
					</div>
				</div>
			</div>
		</div>
		<div>
			<ul class="nav nav-topbar d-flex justify-content-center border-0">
				<!-- 			<li class="nav-item">
								<a class="nav-link active font-weight-bold text-uppercase" :href="profile.url">Posts</a>
							</li>
				 -->
				<li class="nav-item">
					<a :class="this.mode == 'grid' ? 'nav-link font-weight-bold text-uppercase active' : 'nav-link font-weight-bold text-uppercase'" href="#" v-on:click.prevent="switchMode('grid')"><i class="fas fa-th"></i></a>
				</li>

				<!-- <li class="nav-item">
					<a :class="this.mode == 'masonry' ? 'nav-link font-weight-bold text-uppercase active' : 'nav-link font-weight-bold text-uppercase'" href="#" v-on:click.prevent="switchMode('masonry')"><i class="fas fa-th-large"></i></a>
				</li> -->

				<li class="nav-item">
					<a :class="this.mode == 'list' ? 'nav-link font-weight-bold text-uppercase active' : 'nav-link font-weight-bold text-uppercase'" href="#" v-on:click.prevent="switchMode('list')"><i class="fas fa-th-list"></i></a>
				</li>

				<li class="nav-item" v-if="owner">
					<a class="nav-link font-weight-bold text-uppercase" :href="profile.url + '/saved'">Saved</a>
				</li>
			</ul>
		</div>

		<div class="container">
			<div class="profile-timeline mt-2 mt-md-4">
				<div class="row" v-if="mode == 'grid'">
					<div class="col-4 p-0 p-sm-2 p-md-3" v-for="(s, index) in timeline">
						<a class="card info-overlay card-md-border-0" :href="s.url">
							<div class="square">
								<span v-if="s.pf_type == 'photo:album'" class="float-right mr-3 post-icon"><i class="fas fa-images fa-2x"></i></span>
								<span v-if="s.pf_type == 'video'" class="float-right mr-3 post-icon"><i class="fas fa-video fa-2x"></i></span>
								<span v-if="s.pf_type == 'video:album'" class="float-right mr-3 post-icon"><i class="fas fa-film fa-2x"></i></span>
								<div class="square-content" v-bind:style="previewBackground(s)">
								</div>
								<div class="info-overlay-text">
									<h5 class="text-white m-auto font-weight-bold">
										<span>
											<span class="far fa-heart fa-lg p-2 d-flex-inline"></span>
											<span class="d-flex-inline">{{s.favourites_count}}</span>
										</span>
										<span>
											<span class="fas fa-retweet fa-lg p-2 d-flex-inline"></span>
											<span class="d-flex-inline">{{s.reblogs_count}}</span>
										</span>
									</h5>
								</div>
							</div>
						</a>
					</div>
				</div>
				<div class="row" v-if="mode == 'list'">
					<div class="col-md-8 col-lg-8 offset-md-2 pt-2 px-0 my-3 timeline">
						<div class="card mb-4 status-card card-md-rounded-0" :data-status-id="status.id" v-for="(status, index) in timeline" :key="status.id">

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
				</div>
				<div class="masonry-grid" v-if="mode == 'masonry'">
					<div class="d-inline p-0 p-sm-2 p-md-3 masonry-item" v-for="(status, index) in timeline">
						<a class="" v-on:click.prevent="statusModal(status)" :href="status.url">
							<img :src="previewUrl(status)" :class="'o-'+masonryOrientation(status)">
						</a>
					</div>
				</div>
				<div v-if="timeline.length">
					<infinite-loading @infinite="infiniteTimeline">
						<div slot="no-more"></div>
						<div slot="no-results"></div>
					</infinite-loading>
				</div>
			</div>
		</div>
	</div>
	<!-- <b-modal id="statusModal" ref="statusModalRef" hide-footer hide-header v-if="modalStatus" size="lg" v-on:hide.native="closeModal()" lazy class="border-0">
			<post-component v-bind:status-template="modalStatus.pf_type" v-bind:status-id="modalStatus.id" v-bind:status-username="modalStatus.account.username" v-bind:status-url="modalStatus.url" v-bind:status-profile-url="modalStatus.account.url" v-bind:status-avatar="modalStatus.account.avatar"></post-component>
	</b-modal> -->
  <b-modal ref="followingModal"
    id="following-modal"
    hide-footer
    centered
    title="Following"
    body-class="list-group-flush p-0">
    <div class="list-group">
      <div class="list-group-item border-0" v-for="(user, index) in following" :key="'following_'+index">
        <div class="media">
          <a :href="user.url">
            <img class="mr-3 rounded-circle box-shadow" :src="user.avatar" :alt="user.username + '’s avatar'" width="30px">
          </a>
          <div class="media-body">
            <p class="mb-0" style="font-size: 14px">
              <a :href="user.url" class="font-weight-bold text-dark">
                {{user.username}}
              </a>
            </p>
            <p class="text-muted mb-0" style="font-size: 14px">
                {{user.display_name}}
            </p>
          </div>
        </div>
      </div>
      <div v-if="followingMore" class="list-group-item text-center" v-on:click="followingLoadMore()">
	  	<p class="mb-0 small text-muted font-weight-light cursor-pointer">Load more</p>
      </div>
    </div>
  </b-modal>
  <b-modal ref="followerModal"
    id="follower-modal"
    hide-footer
    centered
    title="Followers"
    body-class="list-group-flush p-0">
    <div class="list-group">
      <div class="list-group-item border-0" v-for="(user, index) in followers" :key="'follower_'+index">
        <div class="media">
          <a :href="user.url">
            <img class="mr-3 rounded-circle box-shadow" :src="user.avatar" :alt="user.username + '’s avatar'" width="30px">
          </a>
          <div class="media-body">
            <p class="mb-0" style="font-size: 14px">
              <a :href="user.url" class="font-weight-bold text-dark">
                {{user.username}}
              </a>
            </p>
            <p class="text-muted mb-0" style="font-size: 14px">
                {{user.display_name}}
            </p>
          </div>
        </div>
      </div>
      <div v-if="followerMore" class="list-group-item text-center" v-on:click="followersLoadMore()">
	  	<p class="mb-0 small text-muted font-weight-light cursor-pointer">Load more</p>
      </div>
    </div>
  </b-modal>
</div>
</template>
<!-- <style type="text/css" scoped="">
	.modal-body {
		padding: 0;
		margin: 0;
	}
	@media (min-width: 992px) {
		.modal-lg, .modal-xl {
		    max-width: 900px;
		}
	}
</style> -->
<style type="text/css" scoped>
	.o-square {
		max-width: 320px;
	}
	.o-portrait {
		max-width: 320px;
	}
	.o-landscape {
		max-width: 320px;
	}
	.post-icon {
		color: #fff;
		position:relative;
		margin-top: 10px;
		z-index: 9;
		opacity: 0.6;
		text-shadow: 3px 3px 16px #272634;
	}
</style>
<script type="text/javascript">
export default {
	props: [
		'profile-id',
		'profile-settings'
	],
	data() {
		return {
			profile: {},
			user: {},
			timeline: [],
			timelinePage: 2,
			min_id: 0,
			max_id: 0,
			loading: true,
			owner: false,
			mode: 'grid',
			modes: ['grid', 'list', 'masonry'],
			modalStatus: false,
			relationship: {},
			followers: [],
			followerCursor: 1,
			followerMore: true,
			following: [],
			followingCursor: 1,
			followingMore: true
		}
	},
	beforeMount() {
		this.fetchProfile();
	},

	mounted() {
	},

	updated() {
	},

	methods: {
		fetchProfile() {
			axios.get('/api/v1/accounts/' + this.profileId).then(res => {
				this.profile = res.data;
			});
			axios.get('/api/v1/accounts/verify_credentials').then(res => {
				this.user = res.data;
			});
			axios.get('/api/v1/accounts/relationships', {
				params: {
					'id[]': this.profileId
				}
			}).then(res => {
				this.relationship = res.data[0];
			});
			let apiUrl = '/api/v1/accounts/' + this.profileId + '/statuses';
			axios.get(apiUrl, {
				params: {
					only_media: true,
					min_id: 1,
				}
			})
			.then(res => {
				let data = res.data;
				this.timeline = data;
				let ids = data.map(status => status.id);
				this.min_id = Math.max(...ids);
				this.max_id = Math.min(...ids);
				this.modalStatus = _.first(res.data);
				this.loading = false;
				this.ownerCheck();
			}).catch(err => {
				swal(
					'Oops, something went wrong',
					'Please release the page.',
					'error'
				);
			});
		},

		ownerCheck() {
			this.owner = this.profile.id === this.user.id;
		},

		infiniteTimeline($state) {
			let apiUrl = '/api/v1/accounts/' + this.profileId + '/statuses';
			axios.get(apiUrl, {
				params: {
					only_media: true,
					max_id: this.max_id
				},
			}).then(res => {
				if (res.data.length && this.loading == false) {
					let data = res.data;
					this.timeline.push(...data);
					let ids = data.map(status => status.id);
					this.max_id = Math.min(...ids);
					this.loading = false;
					$state.loaded();
				} else {
					$state.complete();
				}
			});
		},

		previewUrl(status) {
			return status.media_attachments[0].preview_url;
		},

		previewBackground(status) {
			let preview = this.previewUrl(status);
			return 'background-image: url(' + preview + ');';
		},

		switchMode(mode) {
			this.mode = _.indexOf(this.modes, mode) ? mode : 'grid';
			if(this.mode == 'masonry') {
				$('.masonry').masonry({
					columnWidth: 200,
					itemSelector: '.masonry-item'
				});
			}
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
				username.setAttribute('href', this.user.url);
				username.textContent = this.user.username;

				let text = document.createElement('span');
				text.innerHTML = comment;

				let wrapper = document.createElement('p');
				wrapper.classList.add('read-more');
				wrapper.classList.add('mb-0');
				wrapper.appendChild(username);
				wrapper.appendChild(text);
				comments.insertBefore(wrapper, comments.firstChild);
			});
		},

		statusModal(status) {
			this.modalStatus = status;
			this.$refs.statusModalRef.show();
		},

		masonryOrientation(status) {
			let o = status.media_attachments[0].orientation;
			if(!o) {
				o = 'square';
			}
			return o;
		},

		followProfile() {
			axios.post('/i/follow', {
				item: this.profileId
			}).then(res => {
				if(this.relationship.following) {
					this.profile.followers_count--;
					if(this.profile.locked == true) {
						window.location.href = '/';
					}
				} else {
					this.profile.followers_count++;
				}
				this.relationship.following = !this.relationship.following;
			});
		},

		followingModal() {
			if(this.profileSettings.following.list == false) {
				return;
			}
			if(this.following.length > 0) {
				this.$refs.followingModal.show();
				return;
			}
			axios.get('/api/v1/accounts/'+this.profile.id+'/following', {
				params: {
					page: this.followingCursor
				}
			})
			.then(res => {
				this.following = res.data;
				this.followingCursor++;
        if(res.data.length < 10) {
					this.followingMore = false;
				}
			});
			this.$refs.followingModal.show();
		},

		followersModal() {
			if(this.profileSettings.followers.list == false) {
				return;
			}
			if(this.followers.length > 0) {
				this.$refs.followerModal.show();
				return;
			}
			axios.get('/api/v1/accounts/'+this.profile.id+'/followers', {
				params: {
					page: this.followerCursor
				}
			})
			.then(res => {
				this.followers = res.data;
				this.followerCursor++;
        if(res.data.length < 10) {
					this.followerMore = false;
				}
			})	
			this.$refs.followerModal.show();
		},

		followingLoadMore() {
			axios.get('/api/v1/accounts/'+this.profile.id+'/following', {
				params: {
					page: this.followingCursor
				}
			})
			.then(res => {
				if(res.data.length > 0) {
					this.following.push(...res.data);
					this.followingCursor++;
				}
        if(res.data.length < 10) {
					this.followingMore = false;
				}
			});
		},


		followersLoadMore() {
			axios.get('/api/v1/accounts/'+this.profile.id+'/followers', {
				params: {
					page: this.followerCursor
				}
			})
			.then(res => {
				if(res.data.length > 0) {
					this.followers.push(...res.data);
					this.followerCursor++;
				}
        if(res.data.length < 10) {
					this.followerMore = false;
				}
			});
		}
	}
}
</script>
