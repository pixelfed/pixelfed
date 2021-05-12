<template>
<div class="w-100 h-100">
	<div v-if="owner && layout == 'moment'">
		<div class="bg-primary shadow">
			<p class="text-center text-white mb-0 py-3 font-weight-bold border-bottom border-info">
				<i class="fas fa-exclamation-triangle fa-lg mr-2"></i> The Moment UI layout has been deprecated and will be removed in a future release.
			</p>
		</div>
	</div>
	<div v-if="isMobile" class="bg-white p-3 border-bottom">
		<div class="d-flex justify-content-between align-items-center">
			<div @click="goBack" class="cursor-pointer">
				<i class="fas fa-chevron-left fa-lg"></i>
			</div>
			<div class="font-weight-bold">
				{{this.profileUsername}}

			</div>
			<div>
				<a class="fas fa-ellipsis-v fa-lg text-muted text-decoration-none" href="#" @click.prevent="visitorMenu"></a>
			</div>
		</div>
	</div>
	<div v-if="relationship && relationship.blocking && warning" class="bg-white pt-3 border-bottom">
		<div class="container">
			<p class="text-center font-weight-bold">You are blocking this account</p>
			<p class="text-center font-weight-bold">Click <a href="#" class="cursor-pointer" @click.prevent="warning = false;">here</a> to view profile</p>
		</div>
	</div>
	<div v-if="loading" style="height: 80vh;" class="d-flex justify-content-center align-items-center">
		<img src="/img/pixelfed-icon-grey.svg" class="">
	</div>
	<div v-if="!loading && !warning">
		<div v-if="layout == 'metro'" class="container">
			<div :class="isMobile ? 'pt-5' : 'pt-5 border-bottom'">
				<div class="container px-0">
					<div class="row">
						<div class="col-12 col-md-4 d-md-flex">
							<div class="profile-avatar mx-md-auto">

								<!-- MOBILE PROFILE PICTURE -->
								<div class="d-block d-md-none mt-n3 mb-3">
									<div class="row">
										<div class="col-4">
											<div v-if="hasStory" class="has-story cursor-pointer shadow-sm" @click="storyRedirect()">
												<img :alt="profileUsername + '\'s profile picture'" class="rounded-circle" :src="profile.avatar" width="77px" height="77px">
											</div>
											<div v-else>
												<img :alt="profileUsername + '\'s profile picture'" class="rounded-circle border" :src="profile.avatar" width="77px" height="77px">
											</div>
										</div>
										<div class="col-8">
											<div class="d-block d-md-none mt-3 py-2">
												<ul class="nav d-flex justify-content-between">
													<li class="nav-item">
														<div class="font-weight-light">
															<span class="text-dark text-center">
																<p class="font-weight-bold mb-0">{{formatCount(profile.statuses_count)}}</p>
																<p class="text-muted mb-0 small">Posts</p>
															</span>
														</div>
													</li>
													<li class="nav-item">
														<div v-if="profileSettings.followers.count" class="font-weight-light">
															<a class="text-dark cursor-pointer text-center" v-on:click="followersModal()">
																<p class="font-weight-bold mb-0">{{formatCount(profile.followers_count)}}</p>
																<p class="text-muted mb-0 small">Followers</p>
															</a>
														</div>
													</li>
													<li class="nav-item">
														<div v-if="profileSettings.following.count" class="font-weight-light">
															<a class="text-dark cursor-pointer text-center" v-on:click="followingModal()">
																<p class="font-weight-bold mb-0">{{formatCount(profile.following_count)}}</p>
																<p class="text-muted mb-0 small">Following</p>
															</a>
														</div>
													</li>
												</ul>
											</div>
										</div>
									</div>
								</div>

								<!-- DESKTOP PROFILE PICTURE -->
								<div class="d-none d-md-block pb-3">
									<div v-if="hasStory" class="has-story-lg cursor-pointer shadow-sm" @click="storyRedirect()">
										<img :alt="profileUsername + '\'s profile picture'" class="rounded-circle box-shadow cursor-pointer" :src="profile.avatar" width="150px" height="150px">
									</div>
									<div v-else>
										<img :alt="profileUsername + '\'s profile picture'" class="rounded-circle box-shadow" :src="profile.avatar" width="150px" height="150px">
									</div>
									<p v-if="sponsorList.patreon || sponsorList.liberapay || sponsorList.opencollective" class="text-center mt-3">
										<button type="button" @click="showSponsorModal" class="btn btn-outline-secondary font-weight-bold py-0">
											<i class="fas fa-heart text-danger"></i>
											Donate
										</button>
									</p>
								</div>
							</div>
						</div>
						<div class="col-12 col-md-8 d-flex align-items-center">
							<div class="profile-details">
								<div class="d-none d-md-flex username-bar pb-3 align-items-center">
									<span class="font-weight-ultralight h3 mb-0">{{profile.username}}</span>
									<span class="pl-1 pb-2 fa-stack" v-if="profile.is_admin" title="Admin Account" data-toggle="tooltip">
										<i class="fas fa-certificate fa-lg text-danger fa-stack-1x"></i>
										<i class="fas fa-crown text-white fa-sm fa-stack-1x" style="font-size:9px;"></i>
									</span>
									<span v-if="profile.id != user.id && user.hasOwnProperty('id')">
										<span class="pl-4" v-if="relationship.following == true">
											<a :href="'/account/direct/t/'+profile.id"  class="btn btn-outline-secondary font-weight-bold btn-sm py-1 text-dark mr-2 px-3 btn-sec-alt" style="border:1px solid #dbdbdb;" data-toggle="tooltip" title="Message">Message</a>
											<button type="button"  class="btn btn-outline-secondary font-weight-bold btn-sm py-1 text-dark btn-sec-alt" style="border:1px solid #dbdbdb;" v-on:click="followProfile" data-toggle="tooltip" title="Unfollow"><i class="fas fa-user-check mx-3"></i></button>
										</span>
										<span class="pl-4" v-if="!relationship.following">
											<button type="button" class="btn btn-primary font-weight-bold btn-sm py-1 px-3" v-on:click="followProfile" data-toggle="tooltip" title="Follow">Follow</button>
										</span>
									</span>
									<span class="pl-4" v-if="owner && user.hasOwnProperty('id')">
										<a class="btn btn-outline-secondary btn-sm" href="/settings/home" style="font-weight: 600;">Edit Profile</a>
									</span>
									<span class="pl-4">
										<a class="fas fa-ellipsis-h fa-lg text-dark text-decoration-none" href="#" @click.prevent="visitorMenu"></a>
									</span>
								</div>
								<div class="font-size-16px">
									<div class="d-none d-md-inline-flex profile-stats pb-3">
										<div class="font-weight-light pr-5">
											<span class="text-dark">
												<span class="font-weight-bold">{{formatCount(profile.statuses_count)}}</span>
												Posts
											</span>
										</div>
										<div v-if="profileSettings.followers.count" class="font-weight-light pr-5">
											<a class="text-dark cursor-pointer" v-on:click="followersModal()">
												<span class="font-weight-bold">{{formatCount(profile.followers_count)}}</span>
												Followers
											</a>
										</div>
										<div v-if="profileSettings.following.count" class="font-weight-light">
											<a class="text-dark cursor-pointer" v-on:click="followingModal()">
												<span class="font-weight-bold">{{formatCount(profile.following_count)}}</span>
												Following
											</a>
										</div>
									</div>
									<p class="mb-0 d-flex align-items-center">
										<span class="font-weight-bold mr-1">{{profile.display_name}}</span>
										<span v-if="profile.pronouns" class="text-muted small">{{profile.pronouns.join('/')}}</span>
									</p>
									<div v-if="profile.note" class="mb-0" v-html="profile.note"></div>
									<p v-if="profile.website" class=""><a :href="profile.website" class="profile-website" rel="me external nofollow noopener" target="_blank" @click.prevent="remoteRedirect(profile.website)">{{truncate(profile.website,24)}}</a></p>
								</div>
							</div>
						</div>
					</div>
				</div>
			</div>
			<div class="d-block d-md-none my-0 pt-3 border-bottom">
				<p v-if="user && user.hasOwnProperty('id')" class="pt-3">
					<button v-if="owner" class="btn btn-outline-secondary bg-white btn-sm py-1 btn-block text-center font-weight-bold text-dark border border-lighter" @click.prevent="redirect('/settings/home')">Edit Profile</button>
					<button v-if="!owner && relationship.following" class="btn btn-outline-secondary bg-white btn-sm py-1 px-5 font-weight-bold text-dark border border-lighter" @click="followProfile">&nbsp;&nbsp; Unfollow &nbsp;&nbsp;</button>
					<button v-if="!owner && !relationship.following" class="btn btn-primary btn-sm py-1 px-5 font-weight-bold" @click="followProfile">{{relationship.followed_by ? 'Follow Back' : '&nbsp;&nbsp;&nbsp;&nbsp; Follow &nbsp;&nbsp;&nbsp;&nbsp;'}}</button>
					<!-- <button v-if="!owner" class="btn btn-outline-secondary bg-white btn-sm py-1 px-5 font-weight-bold text-dark border border-lighter mx-2">Message</button>
					<button v-if="!owner" class="btn btn-outline-secondary bg-white btn-sm py-1 font-weight-bold text-dark border border-lighter"><i class="fas fa-chevron-down fa-sm"></i></button> -->
				</p>
			</div>
			<div class="">
				<ul class="nav nav-topbar d-flex justify-content-center border-0">
					<li class="nav-item border-top">
						<a :class="this.mode == 'grid' ? 'nav-link text-dark' : 'nav-link'" href="#" v-on:click.prevent="switchMode('grid')"><i class="fas fa-th"></i> <span class="d-none d-md-inline-block small pl-1">POSTS</span></a>
					</li>
					<li class="nav-item px-0 border-top">
						<a :class="this.mode == 'collections' ? 'nav-link text-dark' : 'nav-link'" href="#" v-on:click.prevent="switchMode('collections')"><i class="fas fa-images"></i> <span class="d-none d-md-inline-block small pl-1">COLLECTIONS</span></a>
					</li>
					<li v-if="owner" class="nav-item border-top">
						<a :class="this.mode == 'bookmarks' ? 'nav-link text-dark' : 'nav-link'" href="#" v-on:click.prevent="switchMode('bookmarks')"><i class="fas fa-bookmark"></i> <span class="d-none d-md-inline-block small pl-1">SAVED</span></a>
					</li>
				</ul>
			</div>

			<div class="container px-0">
				<div class="profile-timeline mt-md-4">
					<div class="row" v-if="mode == 'grid'">
						<div class="col-4 p-1 p-md-3" v-for="(s, index) in timeline" :key="'tlob:'+index">
							<a class="card info-overlay card-md-border-0" :href="statusUrl(s)" v-once>
								<div class="square">
									<div v-if="s.sensitive" class="square-content">
										<div class="info-overlay-text-label">
											<h5 class="text-white m-auto font-weight-bold">
												<span>
													<span class="far fa-eye-slash fa-lg p-2 d-flex-inline"></span>
												</span>
											</h5>
										</div>
										<blur-hash-canvas
											width="32"
											height="32"
											:hash="s.media_attachments[0].blurhash"
											/>
									</div>
									<div v-else class="square-content">

										<blur-hash-image
											width="32"
											height="32"
											:hash="s.media_attachments[0].blurhash"
											:src="s.media_attachments[0].preview_url"
											/>
									</div>
									<span v-if="s.pf_type == 'photo:album'" class="float-right mr-3 post-icon"><i class="fas fa-images fa-2x"></i></span>
									<span v-if="s.pf_type == 'video'" class="float-right mr-3 post-icon"><i class="fas fa-video fa-2x"></i></span>
									<span v-if="s.pf_type == 'video:album'" class="float-right mr-3 post-icon"><i class="fas fa-film fa-2x"></i></span>
									<div class="info-overlay-text">
										<h5 class="text-white m-auto font-weight-bold">
											<span>
												<span class="far fa-comment fa-lg p-2 d-flex-inline"></span>
												<span class="d-flex-inline">{{formatCount(s.reply_count)}}</span>
											</span>
										</h5>
									</div>
								</div>
							</a>
						</div>
						<div v-if="timeline.length == 0" class="col-12">
							<div class="py-5 text-center text-muted">
								<p><i class="fas fa-camera-retro fa-2x"></i></p>
								<p class="h2 font-weight-light pt-3">No posts yet</p>
							</div>
						</div>
					</div>
					<div v-if="timeline.length && mode == 'grid'">
						<infinite-loading @infinite="infiniteTimeline">
							<div slot="no-more"></div>
							<div slot="no-results"></div>
						</infinite-loading>
					</div>
					<div v-if="mode == 'bookmarks'">
						<div v-if="bookmarksLoading">
							<div class="row">
								<div class="col-12">
									<div class="p-1 p-sm-2 p-md-3 d-flex justify-content-center align-items-center" style="height: 30vh;">
										<img src="/img/pixelfed-icon-grey.svg" class="">
									</div>
								</div>
							</div>
						</div>
						<div v-else>
							<div v-if="bookmarks.length" class="row">
								<div class="col-4 p-1 p-sm-2 p-md-3" v-for="(s, index) in bookmarks">
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
														<span class="fas fa-retweet fa-lg p-2 d-flex-inline"></span>
														<span class="d-flex-inline">{{s.reblogs_count}}</span>
													</span>
												</h5>
											</div>
										</div>
									</a>
								</div>
							</div>
							<div v-else class="col-12">
								<div class="py-5 text-center text-muted">
									<p><i class="fas fa-bookmark fa-2x"></i></p>
									<p class="h2 font-weight-light pt-3">No saved bookmarks</p>
								</div>
							</div>
						</div>
					</div>
					<div v-if="mode == 'collections'">
						<div v-if="collections.length" class="row">
							<div class="col-4 p-1 p-sm-2 p-md-3" v-for="(c, index) in collections">
								<a class="card info-overlay card-md-border-0" :href="c.url">
									<div class="square">
										<div class="square-content" v-bind:style="'background-image: url(' + c.thumb + ');'">
										</div>
									</div>
								</a>
							</div>
						</div>
						<div v-else>
							<div class="py-5 text-center text-muted">
								<p><i class="fas fa-images fa-2x"></i></p>
								<p class="h2 font-weight-light pt-3">No collections yet</p>
							</div>
						</div>
					</div>
				</div>
			</div>
		</div>

		<div v-if="layout == 'moment'" class="mt-3">
			<div :class="momentBackground()" style="width:100%;min-height:274px;">
			</div>
			<div class="bg-white border-bottom">
				<div class="container">
					<div class="row">
						<div class="col-12 row mx-0">
							<div class="col-4 text-left mt-2">
								<span v-if="relationship && relationship.followed_by">
									<span class="bg-light border border-secondary font-weight-bold small py-1 px-2 text-muted rounded">FOLLOWS YOU</span>
								</span>
								<span v-if="profile.is_admin">
									<span class="bg-light border border-danger font-weight-bold small py-1 px-2 text-danger rounded">ADMIN</span>
								</span>
							</div>
							<div class="col-4 text-center">
								<div class="d-block d-md-none">
									<img class="rounded-circle box-shadow" :src="profile.avatar" width="110px" height="110px" style="margin-top:-60px; border: 5px solid #fff">
								</div>
								<div class="d-none d-md-block">
									<img class="rounded-circle box-shadow" :src="profile.avatar" width="172px" height="172px" style="margin-top:-90px; border: 5px solid #fff">
								</div>
							</div>
							<div class="col-4 text-right mt-2">
								<span class="d-none d-md-inline-block pl-4">
									<a :href="'/users/'+profile.username+'.atom'" class="fas fa-rss fa-lg text-muted text-decoration-none"></a>
								</span>
								<span class="pl-md-4 pl-sm-2" v-if="owner">
									<a class="fas fa-cog fa-lg text-muted text-decoration-none" href="/settings/home"></a>
								</span>
								<span class="pl-md-4 pl-sm-2" v-if="profile.id != user.id && user.hasOwnProperty('id')">
									<a class="fas fa-cog fa-lg text-muted text-decoration-none" href="#" @click.prevent="visitorMenu"></a>
								</span>
								<span v-if="profile.id != user.id && user.hasOwnProperty('id')">
									<span class="pl-md-4 pl-sm-2" v-if="relationship.following == true">
										<button type="button"  class="btn btn-outline-secondary font-weight-bold btn-sm" @click.prevent="followProfile()">Unfollow</button>
									</span>
									<span class="pl-md-4 pl-sm-2" v-else>
										<button type="button" class="btn btn-primary font-weight-bold btn-sm" @click.prevent="followProfile()">Follow</button>
									</span>
								</span>
							</div>
						</div>

						<div class="col-12 text-center">
							<div class="profile-details my-3">
								<p class="font-weight-ultralight h2 text-center">{{profile.username}}</p>
								<div v-if="profile.note" class="text-center text-muted p-3" v-html="profile.note"></div>
								<div class="pb-3 text-muted text-center">
									<a class="text-lighter" :href="profile.url">
										<span class="font-weight-bold">{{formatCount(profile.statuses_count)}}</span>
										Posts
									</a>
									<a v-if="profileSettings.followers.count" class="text-lighter cursor-pointer px-3" v-on:click="followersModal()">
										<span class="font-weight-bold">{{formatCount(profile.followers_count)}}</span>
										Followers
									</a>
									<a v-if="profileSettings.following.count" class="text-lighter cursor-pointer" v-on:click="followingModal()">
										<span class="font-weight-bold">{{formatCount(profile.following_count)}}</span>
										Following
									</a>
								</div>
							</div>
						</div>
					</div>
				</div>
			</div>
			<div class="container-fluid">
				<div class="profile-timeline mt-md-4">
					<div class="" v-if="mode == 'grid'">
						<masonry
						  :cols="{default: 3, 700: 2, 400: 1}"
						  :gutter="{default: '5px'}"
						>
							<div class="p-1" v-for="(s, index) in timeline">
								<a :class="[s.sensitive ? 'card info-overlay card-md-border-0' : s.media_attachments[0].filter_class + ' card info-overlay card-md-border-0']" :href="statusUrl(s)">
									<img :src="previewUrl(s)" class="img-fluid w-100">
								</a>
							</div>
						</masonry>
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
	</div>

	<b-modal
		v-if="profile && following"
		ref="followingModal"
		id="following-modal"
		hide-footer
		centered
		scrollable
		title="Following"
		body-class="list-group-flush py-3 px-0"
		dialog-class="follow-modal">
		<div v-if="!loading" class="list-group" style="min-height: 60vh;">
			<div v-if="owner == true" class="list-group-item border-0 pt-0 px-0 mt-n2 mb-3">
				<span class="d-flex px-4 pb-0 align-items-center">
					<i class="fas fa-search text-lighter"></i>
					<input type="text" class="form-control border-0 shadow-0 no-focus" placeholder="Search Following..." v-model="followingModalSearch" v-on:keyup="followingModalSearchHandler">
				</span>
			</div>
			<div v-if="owner == true" class="btn-group rounded-0 mt-n3 mb-3 border-top" role="group" aria-label="Following">
					<!-- <button type="button" :class="[followingModalTab == 'following' ? ' btn btn-light py-3 rounded-0 font-weight-bold modal-tab-active' : 'btn btn-light py-3 rounded-0 font-weight-bold']" style="font-size: 12px;">FOLLOWING</button> -->
					<!-- <button type="button" class="btn btn-light py-3 rounded-0 text-muted font-weight-bold" style="font-size: 12px;">MUTED</button>
					<button type="button" class="btn btn-light py-3 rounded-0 text-muted font-weight-bold" style="font-size: 12px;">BLOCKED</button> -->
			</div>
			<div v-else class="btn-group rounded-0 mt-n3 mb-3" role="group" aria-label="Following">
					<!-- <button type="button" class="btn btn-light py-3 rounded-0 border-primary border-left-0 border-right-0 border-top-0 font-weight-bold" style="font-size: 12px;" @click="followingModalTab = 'following'">FOLLOWING</button>
					<button type="button" class="btn btn-light py-3 rounded-0 text-muted font-weight-bold" style="font-size: 12px;" @click="followingModalTab = 'mutual'">MUTUAL</button>
					<button type="button" class="btn btn-light py-3 rounded-0 text-muted font-weight-bold" style="font-size: 12px;" @click="followingModalTab = 'blocked'">BLOCKED</button> -->
			</div>
			<div class="list-group-item border-0 py-1" v-for="(user, index) in following" :key="'following_'+index">
				<div class="media">
					<a :href="user.url">
						<img class="mr-3 rounded-circle box-shadow" :src="user.avatar" :alt="user.username + '’s avatar'" width="30px" loading="lazy">
					</a>
					<div class="media-body text-truncate">
						<p class="mb-0" style="font-size: 14px">
							<a :href="user.url" class="font-weight-bold text-dark">
								{{user.username}}
							</a>
						</p>
						<p v-if="!user.local" class="text-muted mb-0 text-truncate mr-3" style="font-size: 14px" :title="user.acct" data-toggle="dropdown" data-placement="bottom">
							<span class="font-weight-bold">{{user.acct.split('@')[0]}}</span><span class="text-lighter">&commat;{{user.acct.split('@')[1]}}</span>
						</p>
						<p v-else class="text-muted mb-0 text-truncate" style="font-size: 14px">
							{{user.display_name}}
						</p>
					</div>
					<div v-if="owner">
						<a class="btn btn-outline-dark btn-sm font-weight-bold" href="#" @click.prevent="followModalAction(user.id, index, 'following')">Following</a>
					</div>
				</div>
			</div>
			<div v-if="followingModalSearch && following.length == 0" class="list-group-item border-0">
				<div class="list-group-item border-0 pt-5">
					<p class="p-3 text-center mb-0 lead">No Results Found</p>
				</div>
			</div>
			<div v-if="following.length > 0 && followingMore" class="list-group-item text-center" v-on:click="followingLoadMore()">
				<p class="mb-0 small text-muted font-weight-light cursor-pointer">Load more</p>
			</div>
		</div>
	</b-modal>
	<b-modal ref="followerModal"
		id="follower-modal"
		hide-footer
		centered
		scrollable
		title="Followers"
		body-class="list-group-flush py-3 px-0"
		dialog-class="follow-modal"
		>
		<div class="list-group">
			<div v-if="followers.length == 0" class="list-group-item border-0">
				<p class="text-center mb-0 font-weight-bold text-muted py-5">
					<span class="text-dark">{{profileUsername}}</span> has no followers yet</p>
			</div>
			<div class="list-group-item border-0 py-1" v-for="(user, index) in followers" :key="'follower_'+index">
				<div class="media mb-0">
					<a :href="user.url">
						<img class="mr-3 rounded-circle box-shadow" :src="user.avatar" :alt="user.username + '’s avatar'" width="30px" height="30px" loading="lazy">
					</a>
					<div class="media-body mb-0">
						<p class="mb-0" style="font-size: 14px">
							<a :href="user.url" class="font-weight-bold text-dark">
								{{user.username}}
							</a>
						</p>
						<p class="text-secondary mb-0" style="font-size: 13px">
							{{user.display_name}}
						</p>
					</div>
					<!-- <button class="btn btn-primary font-weight-bold btn-sm py-1">FOLLOW</button> -->
				</div>
			</div>
			<div v-if="followers.length && followerMore" class="list-group-item text-center" v-on:click="followersLoadMore()">
				<p class="mb-0 small text-muted font-weight-light cursor-pointer">Load more</p>
			</div>
		</div>
	</b-modal>
	<b-modal ref="visitorContextMenu"
		id="visitor-context-menu"
		hide-footer
		hide-header
		centered
		size="sm"
		body-class="list-group-flush p-0">
		<div class="list-group" v-if="relationship">
			<div class="list-group-item cursor-pointer text-center rounded text-dark" @click="copyProfileLink">
				Copy Link
			</div>
			<div v-if="profile.locked == false" class="list-group-item cursor-pointer text-center rounded text-dark" @click="showEmbedProfileModal">
				Embed
			</div>
			<div v-if="user && !owner && !relationship.following" class="list-group-item cursor-pointer text-center rounded text-dark" @click="followProfile">
				Follow
			</div>
			<div v-if="user && !owner && relationship.following" class="list-group-item cursor-pointer text-center rounded" @click="followProfile">
				Unfollow
			</div>
			<div v-if="user && !owner && !relationship.muting" class="list-group-item cursor-pointer text-center rounded" @click="muteProfile">
				Mute
			</div>
			<div v-if="user && !owner && relationship.muting" class="list-group-item cursor-pointer text-center rounded" @click="unmuteProfile">
				Unmute
			</div>
			<div v-if="user && !owner" class="list-group-item cursor-pointer text-center rounded text-dark" @click="reportProfile">
				Report User
			</div>
			<div v-if="user && !owner && !relationship.blocking" class="list-group-item cursor-pointer text-center rounded text-dark" @click="blockProfile">
				Block
			</div>
			<div v-if="user && !owner && relationship.blocking" class="list-group-item cursor-pointer text-center rounded text-dark" @click="unblockProfile">
				Unblock
			</div>
			<div v-if="user && owner" class="list-group-item cursor-pointer text-center rounded text-dark" @click="redirect('/settings/home')">
				Settings
			</div>
			<div class="list-group-item cursor-pointer text-center rounded text-dark" @click="redirect('/users/' + profileUsername + '.atom')">
				Atom Feed
			</div>
			<div class="list-group-item cursor-pointer text-center rounded text-muted font-weight-bold" @click="$refs.visitorContextMenu.hide()">
				Close
			</div>
		</div>
	</b-modal>
	<b-modal ref="sponsorModal"
		id="sponsor-modal"
		hide-footer
		:title="'Sponsor ' + profileUsername"
		centered
		size="md"
		body-class="px-5">
		<div>
			<p class="font-weight-bold">External Links</p>
			<p v-if="sponsorList.patreon" class="pt-2">
				<a :href="'https://' + sponsorList.patreon" rel="nofollow" class="font-weight-bold">{{sponsorList.patreon}}</a>
			</p>
			<p v-if="sponsorList.liberapay" class="pt-2">
				<a :href="'https://' + sponsorList.liberapay" rel="nofollow" class="font-weight-bold">{{sponsorList.liberapay}}</a>
			</p>
			<p v-if="sponsorList.opencollective" class="pt-2">
				<a :href="'https://' + sponsorList.opencollective" rel="nofollow" class="font-weight-bold">{{sponsorList.opencollective}}</a>
			</p>
		</div>
	</b-modal>
	<b-modal ref="embedModal"
	id="ctx-embed-modal"
	hide-header
	hide-footer
	centered
	rounded
	size="md"
	body-class="p-2 rounded">
	<div>
		<textarea class="form-control disabled text-monospace" rows="6" style="overflow-y:hidden;border: 1px solid #efefef; font-size: 12px; line-height: 18px; margin: 0 0 7px;resize:none;" v-model="ctxEmbedPayload" disabled=""></textarea>
		<hr>
		<button :class="copiedEmbed ? 'btn btn-primary btn-block btn-sm py-1 font-weight-bold disabed': 'btn btn-primary btn-block btn-sm py-1 font-weight-bold'" @click="ctxCopyEmbed" :disabled="copiedEmbed">{{copiedEmbed ? 'Embed Code Copied!' : 'Copy Embed Code'}}</button>
		<p class="mb-0 px-2 small text-muted">By using this embed, you agree to our <a href="/site/terms">Terms of Use</a></p>
	</div>
</b-modal>
</div>
</template>
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
	.font-size-16px {
		font-size: 16px;
	}
	.profile-website {
		color: #003569;
		text-decoration: none;
		font-weight: 600;
	}
	.nav-topbar .nav-link {
		color: #999;
	}
	.nav-topbar .nav-link .small {
		font-weight: 600;
	}
	.has-story {
		width: 84px;
		height: 84px;
		border-radius: 50%;
		padding: 4px;
		background: radial-gradient(ellipse at 70% 70%, #ee583f 8%, #d92d77 42%, #bd3381 58%);
	}
	.has-story img {
		width: 76px;
		height: 76px;
		border-radius: 50%;
		padding: 6px;
		background: #fff;
	}
	.has-story-lg {
		width: 159px;
		height: 159px;
		border-radius: 50%;
		padding: 4px;
		background: radial-gradient(ellipse at 70% 70%, #ee583f 8%, #d92d77 42%, #bd3381 58%);
	}
	.has-story-lg img {
		width: 150px;
		height: 150px;
		border-radius: 50%;
		padding: 6px;
		background:#fff;
	}
	.no-focus {
		border-color: none;
		outline: 0;
		box-shadow: none;
	}
	.modal-tab-active {
		border-bottom: 1px solid #08d;
	}
	.btn-sec-alt:hover {
		color: #ccc;
		opacity: .7;
		background-color: transparent;
		border-color: #6c757d;
	}
</style>
<script type="text/javascript">
	import VueMasonry from 'vue-masonry-css'


	export default {
		props: [
			'profile-id',
			'profile-layout',
			'profile-settings',
			'profile-username'
		],
		data() {
			return {
				ids: [],
				profile: {},
				user: false,
				timeline: [],
				timelinePage: 2,
				min_id: 0,
				max_id: 0,
				loading: true,
				owner: false,
				layout: this.profileLayout,
				mode: 'grid',
				modes: ['grid', 'collections', 'bookmarks'],
				modalStatus: false,
				relationship: {},
				followers: [],
				followerCursor: 1,
				followerMore: true,
				following: [],
				followingCursor: 1,
				followingMore: true,
				warning: false,
				sponsorList: [],
				bookmarks: [],
				bookmarksPage: 2,
				collections: [],
				collectionsPage: 2,
				isMobile: false,
				ctxEmbedPayload: null,
				copiedEmbed: false,
				hasStory: null,
				followingModalSearch: null,
				followingModalSearchCache: null,
				followingModalTab: 'following',
				bookmarksLoading: true,
			}
		},
		beforeMount() {
			this.fetchRelationships();
			this.fetchProfile();
			let u = new URLSearchParams(window.location.search);
			let forceMetro = localStorage.getItem('pf_metro_ui.exp.forceMetro') == 'true';
			if(forceMetro == true || u.has('ui') && u.get('ui') == 'metro' && this.layout != 'metro') {
				this.layout = 'metro';
			}

			if(this.layout == 'metro' && u.has('t')) {
				if(this.modes.indexOf(u.get('t')) != -1) {
					if(u.get('t') == 'bookmarks') {
						return;
					}
					this.mode = u.get('t');
				}
			}

		},

		mounted() {
			let u = new URLSearchParams(window.location.search);
			if(u.has('md') && u.get('md') == 'followers') {
				this.followersModal();
			}
			if(u.has('md') && u.get('md') == 'following') {
				this.followingModal();
			}
			if(document.querySelectorAll('body')[0].classList.contains('loggedIn') == true) {
				axios.get('/api/pixelfed/v1/accounts/verify_credentials').then(res => {
					this.user = res.data;
					window._sharedData.curUser = res.data;
					window.App.util.navatar();
					if(res.data.id == this.profileId || this.relationship.following == true) {
						axios.get('/api/stories/v0/exists/' + this.profileId)
						.then(res => {
							this.hasStory = res.data == true;
						})
					}
				});
			}
			if(window.outerWidth < 576) {
				$('nav.navbar').hide();
				this.isMobile = true;
			}
		},

		updated() {
			$('[data-toggle="tooltip"]').tooltip();
		},

		methods: {
			fetchProfile() {
				axios.get('/api/pixelfed/v1/accounts/' + this.profileId).then(res => {
					this.profile = res.data;
				}).then(res => {
					this.fetchPosts();

				});
			},

			fetchPosts() {
				let apiUrl = '/api/pixelfed/v1/accounts/' + this.profileId + '/statuses';
				axios.get(apiUrl, {
					params: {
						only_media: true,
						min_id: 1,
					}
				})
				.then(res => {
					let data = res.data.filter(status => status.media_attachments.length > 0);
					let ids = data.map(status => status.id);
					this.ids = ids;
					this.min_id = Math.max(...ids);
					this.max_id = Math.min(...ids);
					this.modalStatus = _.first(res.data);
					this.timeline = data;
					this.ownerCheck();
					this.loading = false;
					//this.loadSponsor();
				}).catch(err => {
					swal('Oops, something went wrong',
						'Please release the page.',
						'error');
				});
			},

			ownerCheck() {
				if($('body').hasClass('loggedIn') == false) {
					this.owner = false;
					return;
				}
				this.owner = this.profile.id === this.user.id;
			},

			infiniteTimeline($state) {
				if(this.loading || this.timeline.length < 9) {
					$state.complete();
					return;
				}
				let apiUrl = '/api/pixelfed/v1/accounts/' + this.profileId + '/statuses';
				axios.get(apiUrl, {
					params: {
						only_media: true,
						max_id: this.max_id
					},
				}).then(res => {
					if (res.data.length && this.loading == false) {
						let data = res.data;
						let self = this;
						data.forEach(d => {
							if(self.ids.indexOf(d.id) == -1) {
								self.timeline.push(d);
								self.ids.push(d.id);
							}
						});
						let max = Math.min(...this.ids);
						if(max == this.max_id) {
							$state.complete();
							return;
						}
						this.min_id = Math.max(...this.ids);
						this.max_id = max;
						$state.loaded();
						this.loading = false;
					} else {
						$state.complete();
					}
				});
			},

			previewUrl(status) {
				return status.sensitive ? '/storage/no-preview.png?v=' + new Date().getTime() : status.media_attachments[0].preview_url;
			},

			previewBackground(status) {
				let preview = this.previewUrl(status);
				return 'background-image: url(' + preview + ');';
			},

			blurhHashMedia(status) {
				return status.sensitive ? null :
					status.media_attachments[0].preview_url;
			},

			switchMode(mode) {
				this.mode = _.indexOf(this.modes, mode) ? mode : 'grid';
				if(this.mode == 'bookmarks' && this.bookmarks.length == 0) {
					axios.get('/api/local/bookmarks')
					.then(res => {
						this.bookmarks = res.data;
						this.bookmarksLoading = false;
					});
				}
				if(this.mode == 'collections' && this.collections.length == 0) {
					axios.get('/api/local/profile/collections/' + this.profileId)
					.then(res => {
						this.collections = res.data
					});
				}
			},

			reportProfile() {
				let id = this.profile.id;
				window.location.href = '/i/report?type=user&id=' + id;
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

			remoteRedirect(url) {
				window.location.href = window.App.config.site.url + '/i/redirect?url=' + encodeURIComponent(url);
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

			fetchRelationships() {
				if(document.querySelectorAll('body')[0].classList.contains('loggedIn') == false) {
					return;
				}
				axios.get('/api/pixelfed/v1/accounts/relationships', {
					params: {
						'id[]': this.profileId
					}
				}).then(res => {
					if(res.data.length) {
						this.relationship = res.data[0];
						if(res.data[0].blocking == true) {
							this.warning = true;
						}
					}
				});
			},

			muteProfile(status = null) {
				if($('body').hasClass('loggedIn') == false) {
					return;
				}
				let id = this.profileId;
				axios.post('/i/mute', {
					type: 'user',
					item: id
				}).then(res => {
					this.fetchRelationships();
					this.$refs.visitorContextMenu.hide();
					swal('Success', 'You have successfully muted ' + this.profile.acct, 'success');
				}).catch(err => {
					swal('Error', 'Something went wrong. Please try again later.', 'error');
				});
			},

			unmuteProfile(status = null) {
				if($('body').hasClass('loggedIn') == false) {
					return;
				}
				let id = this.profileId;
				axios.post('/i/unmute', {
					type: 'user',
					item: id
				}).then(res => {
					this.fetchRelationships();
					this.$refs.visitorContextMenu.hide();
					swal('Success', 'You have successfully unmuted ' + this.profile.acct, 'success');
				}).catch(err => {
					swal('Error', 'Something went wrong. Please try again later.', 'error');
				});
			},

			blockProfile(status = null) {
				if($('body').hasClass('loggedIn') == false) {
					return;
				}
				let id = this.profileId;
				axios.post('/i/block', {
					type: 'user',
					item: id
				}).then(res => {
					this.warning = true;
					this.fetchRelationships();
					this.$refs.visitorContextMenu.hide();
					swal('Success', 'You have successfully blocked ' + this.profile.acct, 'success');
				}).catch(err => {
					swal('Error', 'Something went wrong. Please try again later.', 'error');
				});
			},

			unblockProfile(status = null) {
				if($('body').hasClass('loggedIn') == false) {
					return;
				}
				let id = this.profileId;
				axios.post('/i/unblock', {
					type: 'user',
					item: id
				}).then(res => {
					this.fetchRelationships();
					this.$refs.visitorContextMenu.hide();
					swal('Success', 'You have successfully unblocked ' + this.profile.acct, 'success');
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
					this.timeline.splice(index,1);
					swal('Success', 'You have successfully deleted this post', 'success');
				}).catch(err => {
					swal('Error', 'Something went wrong. Please try again later.', 'error');
				});
			},

			followProfile() {
				if($('body').hasClass('loggedIn') == false) {
					return;
				}
				axios.post('/i/follow', {
					item: this.profileId
				}).then(res => {
					this.$refs.visitorContextMenu.hide();
					if(this.relationship.following) {
						this.profile.followers_count--;
						if(this.profile.locked == true) {
							window.location.href = '/';
						}
					} else {
						this.profile.followers_count++;
					}
					this.relationship.following = !this.relationship.following;
				}).catch(err => {
					if(err.response.data.message) {
						swal('Error', err.response.data.message, 'error');
					}
				});
			},

			followingModal() {
				if($('body').hasClass('loggedIn') == false) {
					window.location.href = encodeURI('/login?next=/' + this.profileUsername + '/');
					return;
				}
				if(this.profileSettings.following.list == false) {
					return;
				}
				if(this.followingCursor > 1) {
					this.$refs.followingModal.show();
					return;
				} else {
					axios.get('/api/pixelfed/v1/accounts/'+this.profileId+'/following', {
						params: {
							page: this.followingCursor
						}
					})
					.then(res => {
						this.following = res.data;
						this.followingModalSearchCache = res.data;
						this.followingCursor++;
						if(res.data.length < 10) {
							this.followingMore = false;
						}
					});
					this.$refs.followingModal.show();
					return;
				}
			},

			followersModal() {
				if($('body').hasClass('loggedIn') == false) {
					window.location.href = encodeURI('/login?next=/' + this.profileUsername + '/');
					return;
				}
				if(this.profileSettings.followers.list == false) {
					return;
				}
				if(this.followerCursor > 1) {
					this.$refs.followerModal.show();
					return;
				} else {
					axios.get('/api/pixelfed/v1/accounts/'+this.profileId+'/followers', {
						params: {
							page: this.followerCursor
						}
					})
					.then(res => {
						this.followers.push(...res.data);
						this.followerCursor++;
						if(res.data.length < 10) {
							this.followerMore = false;
						}
					})
					this.$refs.followerModal.show();
					return;
				}
			},

			followingLoadMore() {
				if($('body').hasClass('loggedIn') == false) {
					window.location.href = encodeURI('/login?next=/' + this.profile.username + '/');
					return;
				}
				axios.get('/api/pixelfed/v1/accounts/'+this.profile.id+'/following', {
					params: {
						page: this.followingCursor,
						fbu: this.followingModalSearch
					}
				})
				.then(res => {
					if(res.data.length > 0) {
						this.following.push(...res.data);
						this.followingCursor++;
						this.followingModalSearchCache = this.following;
					}
					if(res.data.length < 10) {
						this.followingModalSearchCache = this.following;
						this.followingMore = false;
					}
				});
			},


			followersLoadMore() {
				if($('body').hasClass('loggedIn') == false) {
					return;
				}
				axios.get('/api/pixelfed/v1/accounts/'+this.profile.id+'/followers', {
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
			},

			visitorMenu() {
				this.$refs.visitorContextMenu.show();
			},

			followModalAction(id, index, type = 'following') {
				axios.post('/i/follow', {
					item: id
				}).then(res => {
					if(type == 'following') {
						this.following.splice(index, 1);
						this.profile.following_count--;
					}
				}).catch(err => {
					if(err.response.data.message) {
						swal('Error', err.response.data.message, 'error');
					}
				});
			},

			momentBackground() {
				let c = 'w-100 h-100 mt-n3 ';
				if(this.profile.header_bg) {
					c += this.profile.header_bg == 'default' ? 'bg-pixelfed' : 'bg-moment-' + this.profile.header_bg;
				} else {
					c += 'bg-pixelfed';
				}
				return c;
			},

			loadSponsor() {
				axios.get('/api/local/profile/sponsor/' + this.profileId)
				.then(res => {
					this.sponsorList = res.data;
				});
			},

			showSponsorModal() {
				this.$refs.sponsorModal.show();
			},

			goBack() {
				if(window.history.length > 2) {
					window.history.back();
					return;
				} else {
					window.location.href = '/';
					return;
				}
			},

			copyProfileLink() {
				navigator.clipboard.writeText(window.location.href);
				this.$refs.visitorContextMenu.hide();
			},

			formatCount(count) {
				return App.util.format.count(count);
			},

			statusUrl(status) {
				return status.url;

				if(status.local == true) {
					return status.url;
				}

				return '/i/web/post/_/' + status.account.id + '/' + status.id;
			},

			profileUrl(status) {
				return status.url;

				if(status.local == true) {
					return status.account.url;
				}

				return '/i/web/profile/_/' + status.account.id;
			},

			showEmbedProfileModal() {
				this.ctxEmbedPayload = window.App.util.embed.profile(this.profile.url);
				this.$refs.visitorContextMenu.hide();
				this.$refs.embedModal.show();
			},

			ctxCopyEmbed() {
				navigator.clipboard.writeText(this.ctxEmbedPayload);
				this.$refs.embedModal.hide();
				this.$refs.visitorContextMenu.hide();
			},

			storyRedirect() {
				window.location.href = '/stories/' + this.profileUsername;
			},

			followingModalSearchHandler() {
				let self = this;
				let q = this.followingModalSearch;

				if(q.length == 0) {
					this.following = this.followingModalSearchCache;
					this.followingModalSearch = null;
				}
				if(q.length > 0) {
					let url = '/api/pixelfed/v1/accounts/' +
						self.profileId + '/following?page=1&fbu=' +
						q;

					axios.get(url).then(res => {
						this.following = res.data;
					}).catch(err => {
						self.following = self.followingModalSearchCache;
						self.followingModalSearch = null;
					});
				}
			},

			truncate(str, len) {
				return _.truncate(str, {
					length: len
				});
			}
		}
	}
</script>
