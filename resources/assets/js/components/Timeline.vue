<template>
<div class="container" style="">
	<div v-if="layout === 'feed'" class="row">
		<div :class="[modes.distractionFree ? 'col-md-8 col-lg-8 offset-md-2 px-0 mb-sm-3 timeline order-2 order-md-1':'col-md-8 col-lg-8 px-0 mb-sm-3 timeline order-2 order-md-1']">
			<div v-if="config.features.stories">
				<story-component v-if="config.features.stories"></story-component>
			</div>
			<div>
				<div v-if="loading" class="text-center" style="padding-top:10px;">
					<div class="spinner-border" role="status">
						<span class="sr-only">Loading...</span>
					</div>
				</div>
				<div :data-status-id="status.id" v-for="(status, index) in feed" :key="`${index}-${status.id}`">
					<div v-if="index == 2 && showSuggestions == true && suggestions.length" class="card mb-sm-4 status-card card-md-rounded-0 shadow-none border">
						<div class="card-header d-flex align-items-center justify-content-between bg-white border-0 pb-0">
							<h6 class="text-muted font-weight-bold mb-0">Suggestions For You</h6>
							<span class="cursor-pointer text-muted" v-on:click="hideSuggestions"><i class="fas fa-times"></i></span>
						</div>
						<div class="card-body row mx-0">
							<div class="col-12 col-md-4 mb-3" v-for="(rec, index) in suggestions">
								<div class="card">
									<div class="card-body text-center pt-3">
										<p class="mb-0">
											<a :href="'/'+rec.username">
												<img :src="rec.avatar" class="img-fluid rounded-circle cursor-pointer" width="45px" height="45px" onerror="this.onerror=null;this.src='/storage/avatars/default.png?v=2'">
											</a>
										</p>
										<div class="py-3">
											<p class="font-weight-bold text-dark cursor-pointer mb-0">
												<a :href="'/'+rec.username" class="text-decoration-none text-dark">
													{{rec.username}}
												</a>
											</p>
											<p class="small text-muted mb-0">{{rec.message}}</p>
										</div>
										<p class="mb-0">
											<a class="btn btn-primary btn-block font-weight-bold py-0" href="#" @click.prevent="expRecFollow(rec.id, index)">Follow</a>
										</p>
									</div>
								</div>
							</div>
						</div>
					</div>

					<div v-if="index == 4 && showHashtagPosts && hashtagPosts.length" class="card mb-sm-4 status-card card-md-rounded-0 shadow-none border">
						<div class="card-header d-flex align-items-center justify-content-between bg-white border-0 pb-0">
							<span></span>
							<h6 class="text-muted font-weight-bold mb-0"><a :href="'/discover/tags/'+hashtagPostsName+'?src=tr'">#{{hashtagPostsName}}</a></h6>
							<span class="cursor-pointer text-muted" v-on:click="showHashtagPosts = false"><i class="fas fa-times"></i></span>
						</div>
						<div class="card-body row mx-0">
							<div v-for="(tag, index) in hashtagPosts" class="col-4 p-0 p-sm-2 p-md-3 hashtag-post-square">
								<a class="card info-overlay card-md-border-0" :href="tag.status.url">
									<div :class="[tag.status.filter ? 'square ' + tag.status.filter : 'square']">
										<div class="square-content" :style="'background-image: url('+tag.status.thumb+')'"></div>
										<div class="info-overlay-text">
											<h5 class="text-white m-auto font-weight-bold">
												<span class="pr-4">
													<span class="far fa-heart fa-lg pr-1"></span> {{tag.status.like_count}}
												</span>
												<span>
													<span class="fas fa-retweet fa-lg pr-1"></span> {{tag.status.share_count}}
												</span>
											</h5>
										</div>
									</div>
								</a>
							</div>
						</div>
					</div>

					<div class="card mb-sm-4 status-card card-md-rounded-0 shadow-none border">
						<div v-if="!modes.distractionFree" class="card-header d-inline-flex align-items-center bg-white">
							<img v-bind:src="status.account.avatar" width="38px" height="38px" class="cursor-pointer" style="border-radius: 38px;" @click="profileUrl(status)" onerror="this.onerror=null;this.src='/storage/avatars/default.png?v=2'">
							<!-- <div v-if="hasStory" class="has-story has-story-sm cursor-pointer shadow-sm" @click="profileUrl(status)">
								<img class="rounded-circle box-shadow" :src="status.account.avatar" width="32px" height="32px" onerror="this.onerror=null;this.src='/storage/avatars/default.png?v=2'">
							</div>
							<div v-else>
								<img class="rounded-circle box-shadow" :src="status.account.avatar" width="32px" height="32px" onerror="this.onerror=null;this.src='/storage/avatars/default.png?v=2'">
							</div> -->
							<div class="pl-2">
								<!-- <a class="d-block username font-weight-bold text-dark" v-bind:href="status.account.url" style="line-height:0.5;"> -->
								<a class="username font-weight-bold text-dark text-decoration-none" v-bind:href="profileUrl(status)" v-html="statusCardUsernameFormat(status)">
									Loading...
								</a>
								<span v-if="status.account.is_admin" class="fa-stack" title="Admin Account" data-toggle="tooltip" style="height:1em; line-height:1em; max-width:19px;">
									<i class="fas fa-certificate text-danger fa-stack-1x"></i>
									<i class="fas fa-crown text-white fa-sm fa-stack-1x" style="font-size:7px;"></i>
								</span>
								<span v-if="scope != 'home' && status.account.id != profile.id && status.account.relationship">
									<span class="px-1">•</span>
									<span :class="'font-weight-bold cursor-pointer ' + [status.account.relationship.following == true ? 'text-muted' : 'text-primary']" @click="followAction(status)">{{status.account.relationship.following == true ? 'Following' : 'Follow'}}</span>
								</span>
								<a v-if="status.place" class="d-block small text-decoration-none" :href="'/discover/places/'+status.place.id+'/'+status.place.slug" style="color:#718096">{{status.place.name}}, {{status.place.country}}</a>
							</div>
							<div class="text-right" style="flex-grow:1;">
								<button class="btn btn-link text-dark py-0" type="button" @click="ctxMenu(status)">
									<span class="fas fa-ellipsis-h text-lighter"></span>
								</button>
							</div>
						</div>

						<div class="postPresenterContainer" style="background: #000;">
							<div v-if="status.pf_type === 'photo'" class="w-100">
								<photo-presenter :status="status" v-on:lightbox="lightbox"></photo-presenter>
							</div>

							<div v-else-if="status.pf_type === 'video'" class="w-100">
								<video-presenter :status="status"></video-presenter>
							</div>

							<div v-else-if="status.pf_type === 'photo:album'" class="w-100">
								<photo-album-presenter :status="status" v-on:lightbox="lightbox"></photo-album-presenter>
							</div>

							<div v-else-if="status.pf_type === 'video:album'" class="w-100">
								<video-album-presenter :status="status"></video-album-presenter>
							</div>

							<div v-else-if="status.pf_type === 'photo:video:album'" class="w-100">
								<mixed-album-presenter :status="status" v-on:lightbox="lightbox"></mixed-album-presenter>
							</div>

							<div v-else class="w-100">
								<p class="text-center p-0 font-weight-bold text-white">Error: Problem rendering preview.</p>
							</div>
						</div>

						<div class="card-body">
							<div v-if="!modes.distractionFree" class="reactions my-1 pb-2">
								<h3 v-bind:class="[status.favourited ? 'fas fa-heart text-danger pr-3 m-0 cursor-pointer' : 'far fa-heart pr-3 m-0 like-btn text-lighter cursor-pointer']" title="Like" v-on:click="likeStatus(status, $event)"></h3>
								<h3 v-if="!status.comments_disabled" class="far fa-comment text-lighter pr-3 m-0 cursor-pointer" title="Comment" v-on:click="commentFocus(status, $event)"></h3>
								<h3 v-if="status.visibility == 'public'" v-bind:class="[status.reblogged ? 'fas fa-retweet pr-3 m-0 text-primary cursor-pointer' : 'fas fa-retweet pr-3 m-0 text-lighter share-btn cursor-pointer']" title="Share" v-on:click="shareStatus(status, $event)"></h3>
								<span v-if="status.pf_type == 'photo'" class="float-right">
									<h3 class="fas fa-expand pr-3 m-0 cursor-pointer text-lighter" v-on:click="lightbox(status)"></h3>
								</span>
							</div>

							<div class="likes font-weight-bold" v-if="expLc(status) == true && !modes.distractionFree">
								<span class="like-count">{{status.favourites_count}}</span> {{status.favourites_count == 1 ? 'like' : 'likes'}}
							</div>
							<div class="caption">
								<p class="mb-2 read-more" style="overflow: hidden;">
									<span class="username font-weight-bold">
										<bdi><a class="text-dark" :href="profileUrl(status)">{{status.account.username}}</a></bdi>
									</span>
									<span class="status-content" v-html="status.content"></span>
								</p>
							</div>
							<div class="comments" v-if="status.id == replyId && !status.comments_disabled">
								<p class="mb-0 d-flex justify-content-between align-items-top read-more" style="overflow-y: hidden;" v-for="(reply, index) in replies">
										<span>
											<a class="text-dark font-weight-bold mr-1" :href="profileUrl(reply)">{{reply.account.username}}</a>
											<span v-html="reply.content"></span>
										</span>
										<span class="mb-0" style="min-width:38px">
											<span v-on:click="likeStatus(reply, $event)">
												<i v-bind:class="[reply.favourited ? 'fas fa-heart fa-sm text-danger cursor-pointer':'far fa-heart fa-sm text-lighter cursor-pointer']"></i>
											</span>
											<!-- <post-menu :status="reply" :profile="profile" size="sm" :modal="'true'" :feed="feed" class="d-inline-flex pl-2"></post-menu> -->
											<span class="text-lighter pl-2 cursor-pointer" @click="ctxMenu(reply)">
												<span class="fas fa-ellipsis-v text-lighter"></span>
											</span>
										</span>
								</p>
							</div>
							<div class="timestamp mt-2">
								<p class="small text-uppercase mb-0">
									<a :href="statusUrl(status)" class="text-muted">
										<timeago :datetime="status.created_at" :auto-update="60" :converter-options="{includeSeconds:true}" :title="timestampFormat(status.created_at)" v-b-tooltip.hover.bottom></timeago>
									</a>
									<a v-if="modes.distractionFree" class="float-right" :href="status.url">
										<i class="fas fa-ellipsis-h fa-lg text-muted"></i>
									</a>
								</p>
							</div>
						</div>

						<div v-if="status.id == replyId && !status.comments_disabled" class="card-footer bg-white px-2 py-0">
							<ul class="nav align-items-center emoji-reactions" style="overflow-x: scroll;flex-wrap: unset;">
								<li class="nav-item" v-on:click="emojiReaction(status)" v-for="e in emoji">{{e}}</li>
							</ul>
						</div>

						<div v-if="status.id == replyId && !status.comments_disabled" class="card-footer bg-white sticky-md-bottom p-0">
							<form class="border-0 rounded-0 align-middle" method="post" action="/i/comment" :data-id="status.id" data-truncate="false">
								<textarea class="form-control border-0 rounded-0" name="comment" placeholder="Add a comment…" autocomplete="off" autocorrect="off" style="height:56px;line-height: 18px;max-height:80px;resize: none; padding-right:4.2rem;" v-model="replyText"></textarea>
								<input type="button" value="Post" class="d-inline-block btn btn-link font-weight-bold reply-btn text-decoration-none" v-on:click.prevent="commentSubmit(status, $event)" :disabled="replyText.length == 0" />
							</form>
						</div>
					</div>
				</div>
				<div v-if="!loading && feed.length">
					<div class="card shadow-none">
						<div class="card-body">
							<infinite-loading @infinite="infiniteTimeline" :distance="800">
							<div slot="no-more" class="font-weight-bold">No more posts to load</div>
							<div slot="no-results" class="font-weight-bold">No more posts to load</div>
							</infinite-loading>
						</div>
					</div>
				</div>
				<div v-if="!loading && scope == 'home' && feed.length == 0">
					<div class="card shadow-none border">
						<div class="card-body text-center">
							<p class="h2 font-weight-lighter p-5">Hello, {{profile.acct}}</p>
							<p class="text-lighter"><i class="fas fa-camera-retro fa-5x"></i></p>
							<p class="h3 font-weight-lighter p-5">Start following people to build your timeline.</p>
							<p><a href="/discover" class="btn btn-primary font-weight-bold py-0">Discover new people and posts</a></p>
						</div>
					</div>
				</div>
			</div>
		</div>

		<div v-if="!modes.distractionFree" class="col-md-4 col-lg-4 my-3 order-1 order-md-2 d-none d-md-block">
			<div class="position-sticky" style="top:83px;">
				<div class="mb-4">
					<div class="card shadow-none border">
						<div class="card-body pb-2">
							<div class="media d-flex align-items-center">
								<a :href="!userStory ? profile.url : '/stories/' + profile.acct" class="mr-3">
									<!-- <img class="mr-3 rounded-circle box-shadow" :src="profile.avatar || '/storage/avatars/default.png'" alt="avatar" width="64px" height="64px" onerror="this.onerror=null;this.src='/storage/avatars/default.png?v=2'"> -->
									<div v-if="userStory" class="has-story cursor-pointer shadow-sm" @click="storyRedirect()">
										<img class="rounded-circle box-shadow" :src="profile.avatar" width="64px" height="64px" onerror="this.onerror=null;this.src='/storage/avatars/default.png?v=2'">
									</div>
									<div v-else>
										<img class="rounded-circle box-shadow" :src="profile.avatar" width="64px" height="64px" onerror="this.onerror=null;this.src='/storage/avatars/default.png?v=2'">
									</div>
								</a>
								<div class="media-body d-flex justify-content-between word-break" >
									<div>
										<p class="mb-0 px-0 font-weight-bold"><a :href="profile.url" class="text-dark">{{profile.username || 'loading...'}}</a></p>
										<p class="my-0 text-muted pb-0">{{profile.display_name || 'loading...'}}</p>
									</div>
									<div class="ml-2">
										<a class="text-muted" href="/settings/home"><i class="fas fa-cog fa-lg"></i></a>
									</div>
								</div>
							</div>
						</div>
						<div class="card-footer bg-transparent border-top mt-2 py-1">
							<div class="d-flex justify-content-between text-center">
								<span class="cursor-pointer" @click="redirect(profile.url)">
									<p class="mb-0 font-weight-bold">{{formatCount(profile.statuses_count)}}</p>
									<p class="mb-0 small text-muted">Posts</p>
								</span>
								<span class="cursor-pointer" @click="redirect(profile.url+'?md=followers')">
									<p class="mb-0 font-weight-bold">{{formatCount(profile.followers_count)}}</p>
									<p class="mb-0 small text-muted">Followers</p>
								</span>
								<span class="cursor-pointer" @click="redirect(profile.url+'?md=following')">
									<p class="mb-0 font-weight-bold">{{formatCount(profile.following_count)}}</p>
									<p class="mb-0 small text-muted">Following</p>
								</span>
							</div>
						</div>
					</div>
				</div>

				<div class="mb-4">
					<a class="btn btn-light btn-block btn-sm font-weight-bold text-dark mb-3 border bg-white" href="/i/compose" data-toggle="modal" data-target="#composeModal">
						<i class="far fa-plus-square pr-3 fa-lg pt-1"></i> Compose Post
					</a>
				</div>

				<div v-if="showTips && !loading" class="mb-4 card-tips">
					<announcements-card v-on:show-tips="showTips = $event"></announcements-card>
				</div>

				<div v-show="modes.notify == true && !loading" class="mb-4">
					<notification-card></notification-card>
				</div>

				<div v-show="showSuggestions == true && suggestions.length && config.ab && config.ab.rec == true" class="mb-4">
					<div class="card shadow-none border">
						<div class="card-header bg-white d-flex align-items-center justify-content-between">
							<a class="small text-muted cursor-pointer" href="#" @click.prevent="refreshSuggestions" ref="suggestionRefresh"><i class="fas fa-sync-alt"></i></a>
							<div class="small text-dark text-uppercase font-weight-bold">Suggestions</div>
							<div class="small text-muted cursor-pointer" v-on:click="hideSuggestions"><i class="fas fa-times"></i></div>
						</div>
						<div class="card-body pt-0">
							<div v-for="(rec, index) in suggestions" class="media align-items-center mt-3">
								<a :href="'/'+rec.username">
									<img :src="rec.avatar" width="32px" height="32px" class="rounded-circle mr-3" onerror="this.onerror=null;this.src='/storage/avatars/default.png?v=2'">
								</a>
								<div class="media-body">
									<p class="mb-0 font-weight-bold small">
										<a :href="'/'+rec.username" class="text-decoration-none text-dark">
											{{rec.username}}
										</a>
									</p>
									<p class="mb-0 small text-muted">{{rec.message}}</p>
								</div>
								<a class="font-weight-bold small" href="#" @click.prevent="expRecFollow(rec.id, index)">Follow</a>
							</div>
						</div>
					</div>
				</div>

				<footer>
					<div class="container pb-5">
						<p class="mb-0 text-uppercase font-weight-bold text-muted small">
							<a href="/site/about" class="text-dark pr-2">About Us</a>
							<a href="/site/help" class="text-dark pr-2">Help</a>
							<a href="/site/language" class="text-dark pr-2">Language</a>
							<a href="/discover/profiles" class="text-dark pr-2">Profiles</a>
							<a href="/discover/places" class="text-dark pr-2">Places</a>
							<a href="/site/privacy" class="text-dark pr-2">Privacy</a>
							<a href="/site/terms" class="text-dark pr-2">Terms</a>
						</p>
						<p class="mb-0 text-uppercase font-weight-bold text-muted small">
							<a href="http://pixelfed.org" class="text-muted" rel="noopener" title="" data-toggle="tooltip">Powered by Pixelfed</a>
						</p>
					</div>
				</footer>
			</div>
		</div>
	</div>
	<div v-else class="row pt-2">
		<div class="col-12">
			<div v-if="loading" class="text-center">
				<div class="spinner-border" role="status">
					<span class="sr-only">Loading...</span>
				</div>
			</div>
			<div v-else class="row">
				<div class="col-12 col-md-4 p-1 p-md-3 mb-3" v-for="(s, index) in feed" :key="`${index}-${s.id}`">
					<div class="card info-overlay card-md-border-0 shadow-sm border border-light" :href="statusUrl(s)">
						<div :class="[s.sensitive ? 'square' : 'square ' + s.media_attachments[0].filter_class]">
							<span v-if="s.pf_type == 'photo:album'" class="float-right mr-3 post-icon"><i class="fas fa-images fa-2x"></i></span>
							<span v-if="s.pf_type == 'video'" class="float-right mr-3 post-icon"><i class="fas fa-video fa-2x"></i></span>
							<span v-if="s.pf_type == 'video:album'" class="float-right mr-3 post-icon"><i class="fas fa-film fa-2x"></i></span>
							<div class="square-content" v-bind:style="previewBackground(s)">
							</div>
							<div class="info-overlay-text px-4">
								<p class="text-white m-auto text-center">
									{{trimCaption(s.content_text)}}
								</p>
							</div>
						</div>
					</div>
					<div class="py-3 media align-items-center">
						<img :src="s.account.avatar" class="mr-3 rounded-circle shadow-sm" :alt="s.account.username + ' \'s avatar'" width="30px" height="30px" onerror="this.onerror=null;this.src='/storage/avatars/default.png?v=2'">
						<div class="media-body">
							<p class="mb-0 font-weight-bold small">{{s.account.username}}</p>
							<p class="mb-0" style="line-height: 0.7;">
								<a :href="statusUrl(s)" class="small text-lighter">
									<timeago :datetime="s.created_at" :auto-update="60" :converter-options="{includeSeconds:true}" :title="timestampFormat(s.created_at)" v-b-tooltip.hover.bottom></timeago>
								</a>
							</p>
						</div>
						<div class="ml-3">
							<p class="mb-0">
								<span class="font-weight-bold small">{{s.favourites_count == 1 ? '1 like' : s.favourites_count+' likes'}}</span>
								<span class="px-2"><i v-bind:class="[s.favourited ? 'fas fa-heart text-danger cursor-pointer' : 'far fa-heart like-btn text-lighter cursor-pointer']" v-on:click="likeStatus(s, $event)"></i></span>
								<span class="mr-2 cursor-pointer"><i class="fas fa-ellipsis-v" @click="ctxMenu(s)"></i></span>
							</p>
						</div>
					</div>
				</div>
			</div>
			<div v-if="!loading && feed.length">
					<infinite-loading @infinite="infiniteTimeline" :distance="800">
					<div slot="no-more" class="font-weight-bold">No more posts to load</div>
					<div slot="no-results" class="font-weight-bold">No more posts to load</div>
					</infinite-loading>
			</div>
		</div>
	</div>
<b-modal ref="ctxModal"
	id="ctx-modal"
	hide-header
	hide-footer
	centered
	rounded
	size="sm"
	body-class="list-group-flush p-0 rounded">
	<div class="list-group text-center">
		<div v-if="ctxMenuStatus && ctxMenuStatus.account.id != profile.id" class="list-group-item rounded cursor-pointer font-weight-bold text-danger" @click="ctxMenuReportPost()">Report inappropriate</div>
		<div v-if="ctxMenuStatus && ctxMenuStatus.account.id != profile.id && ctxMenuRelationship && ctxMenuRelationship.following" class="list-group-item rounded cursor-pointer font-weight-bold text-danger" @click="ctxMenuUnfollow()">Unfollow</div>
		<div v-if="ctxMenuStatus && ctxMenuStatus.account.id != profile.id && ctxMenuRelationship && !ctxMenuRelationship.following" class="list-group-item rounded cursor-pointer font-weight-bold text-primary" @click="ctxMenuFollow()">Follow</div>
		<div class="list-group-item rounded cursor-pointer" @click="ctxMenuGoToPost()">Go to post</div>
		<div v-if="ctxMenuStatus && ctxMenuStatus.local == true" class="list-group-item rounded cursor-pointer" @click="ctxMenuEmbed()">Embed</div>
		<!-- <div class="list-group-item rounded cursor-pointer" @click="ctxMenuShare()">Share</div> -->
		<div class="list-group-item rounded cursor-pointer" @click="ctxMenuCopyLink()">Copy Link</div>
		<div v-if="profile && profile.is_admin == true" class="list-group-item rounded cursor-pointer" @click="ctxModMenuShow()">Moderation Tools</div>
		<div v-if="ctxMenuStatus && (profile.is_admin || profile.id == ctxMenuStatus.account.id)" class="list-group-item rounded cursor-pointer" @click="deletePost(ctxMenuStatus)">Delete</div>
		<div class="list-group-item rounded cursor-pointer text-lighter" @click="closeCtxMenu()">Cancel</div>
	</div>
</b-modal>
<b-modal ref="ctxModModal"
	id="ctx-mod-modal"
	hide-header
	hide-footer
	centered
	rounded
	size="sm"
	body-class="list-group-flush p-0 rounded">
	<div class="list-group text-center">
		<div class="list-group-item rounded cursor-pointer" @click="moderatePost(ctxMenuStatus, 'unlist')">Unlist from Timelines</div>
		<div class="list-group-item rounded cursor-pointer" @click="">Add Content Warning</div>
		<div class="list-group-item rounded cursor-pointer text-lighter" @click="ctxModMenuClose()">Cancel</div>
	</div>
 </b-modal>
 <b-modal ref="ctxShareModal"
    id="ctx-share-modal"
    title="Share"
    hide-footer
    centered
    rounded
    size="sm"
    body-class="list-group-flush p-0 rounded text-center">
      <div class="list-group-item rounded cursor-pointer border-top-0">Email</div>
      <div class="list-group-item rounded cursor-pointer">Facebook</div>
      <div class="list-group-item rounded cursor-pointer">Mastodon</div>
      <div class="list-group-item rounded cursor-pointer">Pinterest</div>
      <div class="list-group-item rounded cursor-pointer">Pixelfed</div>
      <div class="list-group-item rounded cursor-pointer">Twitter</div>
      <div class="list-group-item rounded cursor-pointer">VK</div>
      <div class="list-group-item rounded cursor-pointer text-lighter" @click="closeCtxShareMenu()">Cancel</div>
 </b-modal>
 <b-modal ref="ctxEmbedModal"
    id="ctx-embed-modal"
    hide-header
    hide-footer
    centered
    rounded
    size="md"
    body-class="p-2 rounded">
	<div>
		<textarea class="form-control disabled" rows="1" style="border: 1px solid #efefef; font-size: 14px; line-height: 12px; height: 37px; margin: 0 0 7px; resize: none; white-space: nowrap;" v-model="ctxEmbedPayload"></textarea>
		<hr>
		<button :class="copiedEmbed ? 'btn btn-primary btn-block btn-sm py-1 font-weight-bold disabed': 'btn btn-primary btn-block btn-sm py-1 font-weight-bold'" @click="ctxCopyEmbed" :disabled="copiedEmbed">{{copiedEmbed ? 'Embed Code Copied!' : 'Copy Embed Code'}}</button>
		<p class="mb-0 px-2 small text-muted">By using this embed, you agree to our <a href="/site/terms">Terms of Use</a></p>
	</div>
  </b-modal>
  <b-modal
  	id="lightbox"
  	ref="lightboxModal"
  	hide-header
  	hide-footer
  	centered
  	size="lg"
  	body-class="p-0"
  	>
  	<div v-if="lightboxMedia" :class="lightboxMedia.filter_class" class="w-100 h-100">
  		<img :src="lightboxMedia.url" style="max-height: 100%; max-width: 100%">
  	</div>
  </b-modal>
</div>
</template>

<style type="text/css" scoped>
	.postPresenterContainer {
		display: flex;
		align-items: center;
		background: #fff;
	}
	.word-break {
		word-break: break-all;
	}
	.small .custom-control-label {
		padding-top: 3px;
	}
	.reply-btn {
		position: absolute;
		bottom: 12px;
		right: 20px;
		width: 60px;
		text-align: center;
		border-radius: 0 3px 3px 0;
	}
	.emoji-reactions .nav-item {
		font-size: 1.2rem;
		padding: 9px;
		cursor: pointer;
	}
	.emoji-reactions::-webkit-scrollbar {
		width: 0px;
		height: 0px;
		background: transparent;
	}
	.reply-btn[disabled] {
		opacity: .3;
		color: #3897f0;
	}
	.has-story {
		width: 64px;
		height: 64px;
		border-radius: 50%;
		padding: 2px;
		background: radial-gradient(ellipse at 70% 70%, #ee583f 8%, #d92d77 42%, #bd3381 58%);
	}
	.has-story img {
		width: 60px;
		height: 60px;
		border-radius: 50%;
		padding: 3px;
		background: #fff;
	}
	.has-story.has-story-sm {
		width: 32px;
		height: 32px;
		border-radius: 50%;
		padding: 2px;
		background: radial-gradient(ellipse at 70% 70%, #ee583f 8%, #d92d77 42%, #bd3381 58%);
	}
	.has-story.has-story-sm img {
		width: 28px;
		height: 28px;
		border-radius: 50%;
		padding: 3px;
		background: #fff;
	}
</style>

<script type="text/javascript">
	export default {
		props: ['scope', 'layout'],
		data() {
			return {
				ids: [],
				config: window.App.config,
				page: 2,
				feed: [],
				profile: {},
				min_id: 0,
				max_id: 0,
				suggestions: {},
				loading: true,
				replies: [],
				replyId: null,
				modes: {
					'mod': false,
					'dark': false,
					'notify': true,
					'distractionFree': false
				},
				followers: [],
				followerCursor: 1,
				followerMore: true,
				following: [],
				followingCursor: 1,
				followingMore: true,
				lightboxMedia: false,
				showSuggestions: true,
				showReadMore: true,
				replyStatus: {},
				replyText: '',
				emoji: window.App.util.emoji,
				showHashtagPosts: false,
				hashtagPosts: [],
				hashtagPostsName: '',
				ctxMenuStatus: false,
				ctxMenuRelationship: false,
				ctxEmbedPayload: false,
				copiedEmbed: false,
				showTips: true,
				userStory: false,
			}
		},

		beforeMount() {
			this.fetchProfile();
			this.fetchTimelineApi();
		},

		mounted() {
			if(window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches || $('link[data-stylesheet="dark"]').length != 0) {
				this.modes.dark = true;

				// todo: release after dark mode updates
				/* let el = document.querySelector('link[data-stylesheet="light"]');
				el.setAttribute('href', '/css/appdark.css?id=' + Date.now());
				el.setAttribute('data-stylesheet', 'dark'); */
			}

			if(localStorage.getItem('pf_metro_ui.exp.rec') == 'false') {
				this.showSuggestions = false;
			} else {
				this.showSuggestions = true;
			}

			if(localStorage.getItem('pf_metro_ui.exp.rm') == 'false') {
				this.showReadMore = false;
			} else {
				this.showReadMore = true;
			}

			if(localStorage.getItem('pf_metro_ui.exp.df') == 'true') {
				this.modes.distractionFree = true;
			} else {
				this.modes.distractionFree = false;
			}

			if(localStorage.getItem('metro-tips') == 'false') {
				this.showTips = false;
			}

			this.$nextTick(function () {
				$('[data-toggle="tooltip"]').tooltip();
				let u = new URLSearchParams(window.location.search);
				if(u.has('a') && u.get('a') == 'co') {
					$('#composeModal').modal('show');
				}
			});
		},

		updated() {
			if(this.showReadMore == true) {
				pixelfed.readmore();
			}
		},

		methods: {
			fetchProfile() {
				axios.get('/api/pixelfed/v1/accounts/verify_credentials').then(res => {
					this.profile = res.data;
					if(this.profile.is_admin == true) {
						this.modes.mod = true;
					}
					window._sharedData.curUser = res.data;
					this.hasStory();
					// this.expRec();
				}).catch(err => {
					swal(
						'Oops, something went wrong',
						'Please reload the page.',
						'error'
					);
				});
			},

			fetchTimelineApi() {
				let apiUrl = false;
				switch(this.scope) {
					case 'home':
					apiUrl = '/api/pixelfed/v1/timelines/home';
					break;

					case 'local':
					apiUrl = '/api/pixelfed/v1/timelines/public';
					break;

					case 'network':
					apiUrl = '/api/pixelfed/v1/timelines/network';
					break;
				}
				axios.get(apiUrl, {
					params: {
						max_id: this.max_id,
						limit: 3
					}
				}).then(res => {
					let data = res.data;
					this.feed.push(...data);
					let ids = data.map(status => status.id);
					this.ids = ids;
					this.min_id = Math.max(...ids).toString();
					this.max_id = Math.min(...ids).toString();
					this.loading = false;
					$('.timeline .pagination').removeClass('d-none');
					// if(this.feed.length == 4) {
					// 	this.fetchTimelineApi();
					// } 
					if(this.hashtagPosts.length == 0) {
						this.fetchHashtagPosts();
					}
					// this.fetchStories();
				}).catch(err => {
					swal(
						'Oops, something went wrong',
						'Please reload the page.',
						'error'
					);
				});
			},

			infiniteTimeline($state) {
				if(this.loading) {
					$state.complete();
					return;
				}
				if(this.page > 40) {
					this.loading = false;
					$state.complete();
				}
				let apiUrl = false;
				switch(this.scope) {
					case 'home':
					apiUrl = '/api/pixelfed/v1/timelines/home';
					break;

					case 'local':
					apiUrl = '/api/pixelfed/v1/timelines/public';
					break;

					case 'network':
					apiUrl = '/api/pixelfed/v1/timelines/network';
					break;
				}
				axios.get(apiUrl, {
					params: {
						max_id: this.max_id,
						limit: 6
					},
				}).then(res => {
					if (res.data.length && this.loading == false) {
						let data = res.data;
						let self = this;
						data.forEach((d, index) => {
							if(self.ids.indexOf(d.id) == -1) {
								self.feed.push(d);
								self.ids.push(d.id);
							} 
						});
						this.min_id = Math.max(...this.ids).toString();
						this.max_id = Math.min(...this.ids).toString();
						this.page += 1;
						$state.loaded();
						this.loading = false;
					} else {
						$state.complete();
					}
				}).catch(err => {
					this.loading = false;
					$state.complete();
				});
			},

			reportUrl(status) {
				let type = status.in_reply_to ? 'comment' : 'post';
				let id = status.id;
				return '/i/report?type=' + type + '&id=' + id;
			},

			commentFocus(status, $event) {
				if(this.replyId == status.id || status.comments_disabled) {
					return;
				}
				this.replies = {};
				this.replyStatus = {};
				this.replyText = '';
				this.replyId = status.id;
				this.replyStatus = status;
				this.fetchStatusComments(status, '');
			},

			likeStatus(status) {
				if($('body').hasClass('loggedIn') == false) {
					return;
				}
				let count = status.favourites_count;
				status.favourited = !status.favourited;
				axios.post('/i/like', {
					item: status.id
				}).then(res => {
					status.favourites_count = res.data.count;
				}).catch(err => {
					status.favourited = !status.favourited;
					status.favourites_count = count;
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
					status.reblogged = !status.reblogged;
				}).catch(err => {
					swal('Error', 'Something went wrong, please try again later.', 'error');
				});
			},

			timestampFormat(timestamp) {
				let ts = new Date(timestamp);
				return ts.toDateString() + ' ' + ts.toLocaleTimeString();
			},

			redirect(url) {
				window.location.href = url;
				return;
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
					let data = res.data.filter(res => {
						return res.sensitive == false;
					});
					this.replies = _.reverse(data);
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

			deletePost(status) {
				if($('body').hasClass('loggedIn') == false || this.ownerOrAdmin(status) == false) {
					return;
				}

				if(window.confirm('Are you sure you want to delete this post?') == false) {
					return;
				}

				axios.post('/i/delete', {
					type: 'status',
					item: status.id
				}).then(res => {
					this.feed = this.feed.filter(s => {
						return s.id != status.id;
					});
					this.$refs.ctxModal.hide();
				}).catch(err => {
					swal('Error', 'Something went wrong. Please try again later.', 'error');
				});
			},

			commentSubmit(status, $event) {
				let id = status.id;
				let comment = this.replyText;
				axios.post('/i/comment', {
					item: id,
					comment: comment
				}).then(res => {
					this.replyText = '';
					this.replies.push(res.data.entity);
				});
			},

			moderatePost(status, action, $event) {
				let username = status.account.username;
				switch(action) {
					case 'autocw':
						let msg = 'Are you sure you want to enforce CW for ' + username + ' ?';
						swal({
							title: 'Confirm',
							text: msg,
							icon: 'warning',
							buttons: true,
							dangerMode: true
						}).then(res =>  {
							if(res) {
								axios.post('/api/v2/moderator/action', {
									action: action,
									item_id: status.id,
									item_type: 'status'
								}).then(res => {
									swal('Success', 'Successfully enforced CW for ' + username, 'success');
								}).catch(err => {
									swal(
										'Error',
										'Something went wrong, please try again later.',
										'error'
									);
								});
							}
						});
					break;

					case 'noautolink':
						msg = 'Are you sure you want to disable auto linking for ' + username + ' ?';
						swal({
							title: 'Confirm',
							text: msg,
							icon: 'warning',
							buttons: true,
							dangerMode: true
						}).then(res =>  {
							if(res) {
								axios.post('/api/v2/moderator/action', {
									action: action,
									item_id: status.id,
									item_type: 'status'
								}).then(res => {
									swal('Success', 'Successfully disabled autolinking for ' + username, 'success');
								}).catch(err => {
									swal(
										'Error',
										'Something went wrong, please try again later.',
										'error'
									);
								});
							}
						});
					break;

					case 'unlisted':
						msg = 'Are you sure you want to unlist from timelines for ' + username + ' ?';
						swal({
							title: 'Confirm',
							text: msg,
							icon: 'warning',
							buttons: true,
							dangerMode: true
						}).then(res =>  {
							if(res) {
								axios.post('/api/v2/moderator/action', {
									action: action,
									item_id: status.id,
									item_type: 'status'
								}).then(res => {
									swal('Success', 'Successfully unlisted for ' + username, 'success');
								}).catch(err => {
									swal(
										'Error',
										'Something went wrong, please try again later.',
										'error'
									);
								});
							}
						});
					break;

					case 'disable':
						msg = 'Are you sure you want to disable ' + username + '’s account ?';
						swal({
							title: 'Confirm',
							text: msg,
							icon: 'warning',
							buttons: true,
							dangerMode: true
						}).then(res =>  {
							if(res) {
								axios.post('/api/v2/moderator/action', {
									action: action,
									item_id: status.id,
									item_type: 'status'
								}).then(res => {
									swal('Success', 'Successfully disabled ' + username + '’s account', 'success');
								}).catch(err => {
									swal(
										'Error',
										'Something went wrong, please try again later.',
										'error'
									);
								});
							}
						});
					break;

					case 'suspend':
						msg = 'Are you sure you want to suspend ' + username + '’s account ?';
						swal({
							title: 'Confirm',
							text: msg,
							icon: 'warning',
							buttons: true,
							dangerMode: true
						}).then(res =>  {
							if(res) {
								axios.post('/api/v2/moderator/action', {
									action: action,
									item_id: status.id,
									item_type: 'status'
								}).then(res => {
									swal('Success', 'Successfully suspend ' + username + '’s account', 'success');
								}).catch(err => {
									swal(
										'Error',
										'Something went wrong, please try again later.',
										'error'
									);
								});
							}
						});
					break;
				}
			},

			followingModal() {
				if(this.following.length > 0) {
					this.$refs.followingModal.show();
					return;
				}
				axios.get('/api/pixelfed/v1/accounts/'+this.profile.id+'/following', {
					params: {
						page: this.followingCursor
					}
				})
				.then(res => {
					this.following = res.data;
					this.followingCursor++;
				});
        		if(res.data.length < 10) {
					this.followingMore = false;
				}
				this.$refs.followingModal.show();
			},

			followersModal() {
				if(this.followers.length > 0) {
					this.$refs.followerModal.show();
					return;
				}
				axios.get('/api/pixelfed/v1/accounts/'+this.profile.id+'/followers', {
					params: {
						page: this.followerCursor
					}
				})
				.then(res => {
					this.followers = res.data;
					this.followerCursor++;
				})
        		if(res.data.length < 10) {
					this.followerMore = false;
				}
				this.$refs.followerModal.show();
			},

			followingLoadMore() {
				axios.get('/api/pixelfed/v1/accounts/'+this.profile.id+'/following', {
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

			lightbox(status) {
				this.lightboxMedia = status.media_attachments[0];
				this.$refs.lightboxModal.show();
			},

			expLc(status) {
				if(this.config.ab.lc == false) {
					return true;
				}
				if(this.statusOwner(status) == true) {
					return true;
				}
				return false;
			},

			expRec() {
				//return;

				if(this.config.ab.rec == false) {
					return;
				}
				axios.get('/api/local/exp/rec')
				.then(res => {
					this.suggestions = res.data;
				})
			},

			expRecFollow(id, index) {
				return;

				if(this.config.ab.rec == false) {
					return;
				}

				axios.post('/i/follow', {
						item: id
				}).then(res => {
					this.suggestions.splice(index, 1);
				}).catch(err => {
					if(err.response.data.message) {
						swal('Error', err.response.data.message, 'error');
					}
				});
			},

			followAction(status) {
				let id = status.account.id;

				axios.post('/i/follow', {
						item: id
				}).then(res => {
					this.feed.forEach(s => {
						if(s.account.id == id) {
							s.account.relationship.following = !s.account.relationship.following;
						}
					});

					let username = status.account.acct;

					if(status.account.relationship.following) {
						swal('Follow successful!', 'You are now following ' + username, 'success');
					} else {
						swal('Unfollow successful!', 'You are no longer following ' + username, 'success');
					}

				}).catch(err => {
					if(err.response.data.message) {
						swal('Error', err.response.data.message, 'error');
					}
				});
			},

			owner(status) {
				return this.profile.id === status.account.id;
			},

			admin() {
				return this.profile.is_admin == true;
			},

			ownerOrAdmin(status) {
				return this.owner(status) || this.admin();
			},

			hideSuggestions() {
				localStorage.setItem('pf_metro_ui.exp.rec', false);
				this.showSuggestions = false;
			},

			emojiReaction(status) {
				let em = event.target.innerText;
				if(this.replyText.length == 0) {
					this.replyText = em + ' ';
					$('textarea[name="comment"]').focus();
				} else {
					this.replyText += em + ' ';
					$('textarea[name="comment"]').focus();
				}
			}, 

			refreshSuggestions() {
				return;

				let el = event.target.parentNode;
				if(el.classList.contains('disabled') == true) {
					return;
				}
				axios.get('/api/local/exp/rec', {
					params: {
						refresh: true
					}
				})
				.then(res => {
					this.suggestions = res.data;

					if (el.classList) {
						el.classList.add('disabled');
						el.classList.add('text-light');
					}
					else {
						el.className += ' ' + 'disabled text-light';
					}
					setTimeout(function() {
						el.setAttribute('href', '#');
						if (el.classList) {
							el.classList.remove('disabled');
							el.classList.remove('text-light');
						}
						else {
							el.className = el.className.replace(new RegExp('(^|\\b)' + className.split(' ').join('|') + '(\\b|$)', 'gi'), 'disabled text-light');
						}
					}, 10000);
				});
			},

			fetchHashtagPosts() {
				axios.get('/api/local/discover/tag/list')
				.then(res => {
					let tags = res.data;
					if(tags.length == 0) {
						return;
					}
					let hashtag = tags[Math.floor(Math.random(), tags.length)];
					this.hashtagPostsName = hashtag;
					axios.get('/api/v2/discover/tag', {
						params: {
							hashtag: hashtag
						}
					}).then(res => {
						if(res.data.tags.length > 3) {
							this.showHashtagPosts = true;
							this.hashtagPosts = res.data.tags.splice(0,3);
						}
					})
				})
			},

			ctxMenu(status) {
				this.ctxMenuStatus = status;
				this.ctxEmbedPayload = window.App.util.embed.post(status.url);
				if(status.account.id == this.profile.id) {
					this.ctxMenuRelationship = false;
					this.$refs.ctxModal.show();
				} else {
					axios.get('/api/pixelfed/v1/accounts/relationships', {
						params: {
							'id[]': status.account.id
						}
					}).then(res => {
						this.ctxMenuRelationship = res.data[0];
						this.$refs.ctxModal.show();
					});
				}
			},

			closeCtxMenu(truncate) {
				this.copiedEmbed = false;
				this.ctxMenuStatus = false;
				this.ctxMenuRelationship = false;
				this.$refs.ctxModal.hide();
			},

			ctxMenuCopyLink() {
				let status = this.ctxMenuStatus;
				navigator.clipboard.writeText(status.url);
				this.closeCtxMenu();
				return;
			},

			ctxMenuGoToPost() {
				let status = this.ctxMenuStatus;
				window.location.href = this.statusUrl(status);
				this.closeCtxMenu();
				return;
			},

			ctxMenuFollow() {
				let id = this.ctxMenuStatus.account.id;
				axios.post('/i/follow', {
					item: id
				}).then(res => {
					let username = this.ctxMenuStatus.account.acct;
					this.closeCtxMenu();
					setTimeout(function() {
						swal('Follow successful!', 'You are now following ' + username, 'success');
					}, 500);
				});
			},

			ctxMenuUnfollow() {
				let id = this.ctxMenuStatus.account.id;
				axios.post('/i/follow', {
					item: id
				}).then(res => {
					let username = this.ctxMenuStatus.account.acct;
					if(this.scope == 'home') {
						this.feed = this.feed.filter(s => {
							return s.account.id != this.ctxMenuStatus.account.id;
						});
					}
					this.closeCtxMenu();
					setTimeout(function() {
						swal('Unfollow successful!', 'You are no longer following ' + username, 'success');
					}, 500);
				});
			},

			ctxMenuReportPost() {
				window.location.href = '/i/report?type=post&id=' + this.ctxMenuStatus.id;
			},

			ctxMenuEmbed() {
				this.$refs.ctxModal.hide();
				this.$refs.ctxEmbedModal.show();
			},

			ctxMenuShare() {
				this.$refs.ctxModal.hide();
				this.$refs.ctxShareModal.show();
			},

			closeCtxShareMenu() {
				this.$refs.ctxShareModal.hide();
				this.$refs.ctxModal.show();
			},

			ctxCopyEmbed() {
				navigator.clipboard.writeText(this.ctxEmbedPayload);
				this.$refs.ctxEmbedModal.hide();
			},

			ctxModMenuShow() {
				this.$refs.ctxModal.hide();
				this.$refs.ctxModModal.show();
			},
			
			ctxModMenu() {
				this.$refs.ctxModal.hide();
			},

			ctxModMenuClose() {
				this.$refs.ctxModal.hide();
				this.$refs.ctxModModal.hide();
			},

			formatCount(count) {
				return App.util.format.count(count);
			},

			statusUrl(status) {
				return status.url;

				// if(status.local == true) {
				// 	return status.url;
				// }

				// return '/i/web/post/_/' + status.account.id + '/' + status.id;
			},

			profileUrl(status) {
				return status.account.url;
				// if(status.local == true) {
				// 	return status.account.url;
				// }

				// return '/i/web/profile/_/' + status.account.id;
			},

			statusCardUsernameFormat(status) {
				if(status.account.local == true) {
					return status.account.username;
				}

				let fmt = window.App.config.username.remote.format;
				let txt = window.App.config.username.remote.custom;
				let usr = status.account.username;
				let dom = document.createElement('a');
				dom.href = status.account.url;
				dom = dom.hostname;

				switch(fmt) {
					case '@':
						return usr + '<span class="text-lighter font-weight-bold">@' + dom + '</span>';
					break;

					case 'from':
						return usr + '<span class="text-lighter font-weight-bold"> <span class="font-weight-normal">from</span> ' + dom + '</span>';
					break;

					case 'custom':
						return usr + '<span class="text-lighter font-weight-bold"> ' + txt + ' ' + dom + '</span>';
					break;

					default: 
						return usr + '<span class="text-lighter font-weight-bold">@' + dom + '</span>';
					break;
				}
			},

			previewUrl(status) {
				return status.sensitive ? '/storage/no-preview.png?v=' + new Date().getTime() : status.media_attachments[0].preview_url;
			},

			previewBackground(status) {
				let preview = this.previewUrl(status);
				return 'background-image: url(' + preview + ');';
			},

			trimCaption(caption, len = 60) {
				return _.truncate(caption, {
					length: len
				});
			},

			hasStory() {
				axios.get('/api/stories/v1/exists/'+this.profile.id)
				.then(res => {
					this.userStory = res.data;
				})
			}
		}
	}
</script>