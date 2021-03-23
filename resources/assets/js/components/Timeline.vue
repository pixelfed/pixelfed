<template>
	<div>
		<div v-if="currentLayout === 'feed'" class="container">
			<div class="row">
				<div v-if="morePostsAvailable == true" class="col-12 mt-5 pt-3 mb-3 fixed-top">
					<p class="text-center">
						<button class="btn btn-dark px-4 rounded-pill font-weight-bold shadow" @click="syncNewPosts">Carregar novos posts</button>
					</p>
				</div>
				<div class="d-none col-12 pl-3 pl-md-0 pt-3 pl-0">
					<div class="d-none d-md-flex justify-content-between align-items-center">
						<p class="lead text-muted mb-0"><i :class="[scope == 'home' ? 'fas fa-home':'fas fa-stream']"></i> &nbsp; {{scope == 'local' ? 'Public' : 'Home'}} Timeline</p>
						<p class="mb-0">
							<span class="btn-group">
								<a href="#" :class="[layout=='feed'?'btn btn-sm btn-outline-primary font-weight-bold text-decoration-none':'btn btn-sm btn-outline-lighter font-weight-light text-decoration-none']" @click.prevent="switchFeedLayout('feed')"><i class="fas fa-list"></i></a>
								<a href="#" :class="[layout!=='feed'?'btn btn-sm btn-outline-primary font-weight-bold text-decoration-none':'btn btn-sm btn-outline-lighter font-weight-light text-decoration-none']" @click.prevent="switchFeedLayout('grid')"><i class="fas fa-th"></i></a>
							</span>
						</p>
						<p class="mb-0 d-none d-md-block">
							<a class="btn btn-block btn-primary btn-sm font-weight-bold" href="/i/compose" data-toggle="modal" data-target="#composeModal">
								Novo Post
							</a>
						</p>
					</div>
					<hr>
				</div>
				<div class="col-md-8 col-lg-8 px-0 mb-sm-3 timeline order-2 order-md-1">
					<div style="margin-top:-2px;">
						<story-component v-if="config.features.stories"></story-component>
					</div>
					<div>
						<div v-if="loading" class="text-center" style="padding-top:10px;">
							<div class="spinner-border" role="status">
								<span class="sr-only">Carregando...</span>
							</div>
						</div>
						<div :data-status-id="status.id" v-for="(status, index) in feed" :key="`${index}-${status.id}`">
							<div v-if="index == 0 && showTips && !loading" class="my-4 card-tips">
								<announcements-card v-on:show-tips="showTips = $event"></announcements-card>
							</div>

							<div v-if="index == 2 && showSuggestions == true && suggestions.length" class="card mb-sm-4 status-card card-md-rounded-0 shadow-none border">
								<div class="card-header d-flex align-items-center justify-content-between bg-white border-0 pb-0">
									<h6 class="text-muted font-weight-bold mb-0">Sugestões</h6>
									<span class="cursor-pointer text-muted" v-on:click="hideSuggestions"><i class="fas fa-times"></i></span>
								</div>
								<div class="card-body row mx-0">
									<div class="col-12 col-md-4 mb-3" v-for="(rec, index) in suggestions">
										<div class="card">
											<div class="card-body text-center pt-3">
												<p class="mb-0">
													<a :href="'/'+rec.username">
														<img :src="rec.avatar" class="img-fluid rounded-circle cursor-pointer" width="45px" height="45px" onerror="this.onerror=null;this.src='/storage/avatars/default.png?v=2'" alt="avatar">
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
													<a class="btn btn-primary btn-block font-weight-bold py-0" href="#" @click.prevent="expRecFollow(rec.id, index)">Seguir</a>
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
												<div v-if="tag.status.sensitive" class="square-content">
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
														:hash="tag.status.media_attachments[0].blurhash"
														/>
												</div>
												<div v-else class="square-content">
													<blur-hash-image
														width="32"
														height="32"
														:hash="tag.status.media_attachments[0].blurhash"
														:src="tag.status.media_attachments[0].preview_url"
														/>
												</div>
												<div class="info-overlay-text">
													<h5 class="text-white m-auto font-weight-bold">
														<span class="pr-4">
															<span class="far fa-heart fa-lg pr-1"></span> {{formatCount(tag.status.favourites_count)}}
														</span>
														<span>
															<span class="far fa-comment fa-lg pr-1"></span> {{formatCount(tag.status.reply_count)}}
														</span>
													</h5>
												</div>
											</div>
										</a>
									</div>
								</div>
							</div>

							<div :class="index == 0 ? 'card mb-sm-4 status-card card-md-rounded-0 shadow-none border mt-md-4' : 'card mb-sm-4 status-card card-md-rounded-0 shadow-none border'">
								<div v-if="status" class="card-header d-inline-flex align-items-center bg-white">
									<!-- <img v-bind:src="status.account.avatar" width="38px" height="38px" class="cursor-pointer" style="border-radius: 38px;" @click="profileUrl(status)" onerror="this.onerror=null;this.src='/storage/avatars/default.png?v=2'"> -->
								<!-- <div v-if="hasStory" class="has-story has-story-sm cursor-pointer shadow-sm" @click="profileUrl(status)">
									<img class="rounded-circle box-shadow" :src="status.account.avatar" width="32px" height="32px" onerror="this.onerror=null;this.src='/storage/avatars/default.png?v=2'">
								</div>
								<div v-else> -->
									<div>
										<img class="rounded-circle box-shadow" :src="status.account.avatar" width="32px" height="32px" onerror="this.onerror=null;this.src='/storage/avatars/default.png?v=2'" alt="avatar">
									</div>
									<div class="pl-2">
										<!-- <a class="d-block username font-weight-bold text-dark" v-bind:href="status.account.url" style="line-height:0.5;"> -->
											<a class="username font-weight-bold text-dark text-decoration-none" v-bind:href="profileUrl(status)" v-html="statusCardUsernameFormat(status)">
												Carregando...
											</a>
											<span v-if="status.account.is_admin" class="fa-stack" title="Admin Account" data-toggle="tooltip" style="height:1em; line-height:1em; max-width:19px;">
												<i class="fas fa-certificate text-danger fa-stack-1x"></i>
												<i class="fas fa-crown text-white fa-sm fa-stack-1x" style="font-size:7px;"></i>
											</span>
									<!-- <span v-if="scope != 'home' && status.account.id != profile.id && status.account.relationship">
										<span class="px-1">•</span>
										<span :class="'font-weight-bold cursor-pointer ' + [status.account.relationship.following == true ? 'text-muted' : 'text-primary']" @click="followAction(status)">{{status.account.relationship.following == true ? 'Following' : 'Follow'}}</span>
									</span> -->
									<!-- <span v-if="status.account.id != profile.id">
										<span class="px-1">•</span>
										<span class="font-weight-bold cursor-pointer text-primary">Follow</span>
									</span> -->
									<div class="d-flex align-items-center">
										<a v-if="status.place" class="small text-decoration-none text-muted" :href="'/discover/places/'+status.place.id+'/'+status.place.slug" title="Location" data-toggle="tooltip"><i class="fas fa-map-marked-alt"></i> {{status.place.name}}, {{status.place.country}}</a>
									</div>
								</div>
								<div class="text-right" style="flex-grow:1;">
									<button class="btn btn-link text-dark py-0" type="button" @click="ctxMenu(status)">
										<span class="fas fa-ellipsis-h text-lighter"></span>
										<span class="sr-only">Post Menu</span>
									</button>
								</div>
							</div>

							<div class="postPresenterContainer" style="background: #000;">

								<div v-if="config.ab.top && status.pf_type === 'text'" class="w-100">
									<div class="w-100 card-img-top border-bottom rounded-0" style="background-image: url(/storage/textimg/bg_1.jpg);background-size: cover;width: 100%;height: 540px;">
											<div class="w-100 h-100 d-flex justify-content-center align-items-center">
												<p class="text-center text-break h3 px-5 font-weight-bold" v-html="status.content"></p>
											</div>
										</div>
								</div>

								<div v-else-if="status.pf_type === 'photo'" class="w-100">
									<photo-presenter :status="status" v-on:lightbox="lightbox" v-on:togglecw="status.sensitive = false"></photo-presenter>
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
									<p class="text-center p-0 font-weight-bold text-white">Erro ao carregar pré visualização.</p>
								</div>

							</div>

							<div v-if="config.features.label.covid.enabled && status.label && status.label.covid == true" class="card-body border-top border-bottom py-2 cursor-pointer pr-2" @click="labelRedirect()">
								<p class="font-weight-bold d-flex justify-content-between align-items-center mb-0">
									<span>
										<i class="fas fa-info-circle mr-2"></i>
										For information about COVID-19, {{config.features.label.covid.org}}
									</span>
									<span>
										<i class="fas fa-chevron-right text-lighter"></i>
									</span>
								</p>
							</div>

							<div class="card-body">
								<div class="reactions my-1 pb-2">
									<h3 v-if="status.favourited" class="fas fa-heart text-danger pr-3 m-0 cursor-pointer" title="Like" v-on:click="likeStatus(status, $event);"></h3>
									<h3 v-else class="far fa-heart pr-3 m-0 like-btn text-dark cursor-pointer" title="Like" v-on:click="likeStatus(status, $event);"></h3>
									<h3 v-if="!status.comments_disabled" class="far fa-comment text-dark pr-3 m-0 cursor-pointer" title="Comment" v-on:click="commentFocus(status, $event)"></h3>
									<!-- <h3 v-if="status.visibility == 'public'" v-bind:class="[status.reblogged ? 'fas fa-retweet pr-3 m-0 text-primary cursor-pointer' : 'fas fa-retweet pr-3 m-0 text-dark share-btn cursor-pointer']" title="Share" v-on:click="shareStatus(status, $event)"></h3> -->
									<!-- <h3 class="fas fa-expand pr-3 m-0 cursor-pointer text-dark" v-on:click="lightbox(status)"></h3> -->
									<span v-if="status.taggedPeople.length" class="float-right">
										<span class="font-weight-light small" style="color:#718096">
											<i class="far fa-user" data-toggle="tooltip" title="Tagged People"></i>
											<span v-for="(tag, index) in status.taggedPeople" class="mr-n2">
												<a :href="'/'+tag.username">
													<img :src="tag.avatar" width="20px" height="20px" class="border rounded-circle" data-toggle="tooltip" :title="'@'+tag.username" alt="Avatar">
												</a>
											</span>
										</span>
									</span>
								</div>

								<div class="likes font-weight-bold" v-if="expLc(status) == true">
									<span class="like-count">{{status.favourites_count}}</span> {{status.favourites_count == 1 ? 'like' : 'likes'}}
								</div>
								<div v-if="status.pf_type != 'text'" class="caption">
									<p v-if="!status.sensitive" class="mb-2 read-more" style="overflow: hidden;">
										<span class="username font-weight-bold">
											<bdi><a class="text-dark" :href="profileUrl(status)">{{status.account.username}}</a></bdi>
										</span>
										<span class="status-content" v-html="status.content"></span>
									</p>
								</div>
								<!-- <div class="comments" v-if="status.id == replyId && !status.comments_disabled">
									<p class="mb-0 d-flex justify-content-between align-items-top read-more mt-2" style="overflow-y: hidden;" v-for="(reply, index) in replies">
										<span>
											<a class="text-dark font-weight-bold mr-1" :href="profileUrl(reply)">{{reply.account.username}}</a>
											<span v-html="reply.content" style="word-break: break-all;" class="comment-body"></span>
										</span>
										<span class="mb-0" style="min-width:38px">
											<span v-on:click="likeStatus(reply, $event);">
												<i v-bind:class="[reply.favourited ? 'fas fa-heart fa-sm text-danger cursor-pointer':'far fa-heart fa-sm text-lighter cursor-pointer']"></i>
											</span>
											<!-- <post-menu :status="reply" :profile="profile" size="sm" :modal="'true'" :feed="feed" class="d-inline-flex pl-2"></post-menu> - ->
											<span class="text-lighter pl-2 cursor-pointer" @click="ctxMenu(reply)">
												<span class="fas fa-ellipsis-v text-lighter"></span>
											</span>
										</span>
									</p>
								</div> -->
								<div class="timestamp mt-2">
									<p class="small text-uppercase mb-0">
										<a :href="statusUrl(status)" class="text-muted">
											<timeago :datetime="status.created_at" :auto-update="60" :converter-options="{includeSeconds:true}" :title="timestampFormat(status.created_at)" v-b-tooltip.hover.bottom></timeago>
										</a>
									</p>
								</div>
							</div>

							<!--<div v-if="status.id == replyId && !status.comments_disabled" class="card-footer bg-white px-2 py-0">
								<ul class="nav align-items-center emoji-reactions" style="overflow-x: scroll;flex-wrap: unset;">
									<li class="nav-item" v-on:click="emojiReaction(status)" v-for="e in emoji">{{e}}</li>
								</ul>
							</div>-->

							<!--<div v-if="status.id == replyId && !status.comments_disabled" class="card-footer bg-white sticky-md-bottom p-0">
								<form class="border-0 rounded-0 align-middle" method="post" action="/i/comment" :data-id="status.id" data-truncate="false">
									<textarea class="form-control border-0 rounded-0" name="comment" placeholder="Add a comment…" autocomplete="off" autocorrect="off" style="height:56px;line-height: 18px;max-height:80px;resize: none; padding-right:4.2rem;" v-model="replyText"></textarea>
									<input type="button" value="Post" class="d-inline-block btn btn-link font-weight-bold reply-btn text-decoration-none" v-on:click.prevent="commentSubmit(status, $event)" :disabled="replyText.length == 0" />
								</form>
							</div>-->
							</div>
						</div>
						<div v-if="!loading && feed.length">
							<div class="card shadow-none">
								<div class="card-body">
									<infinite-loading @infinite="infiniteTimeline" :distance="800">
										<div slot="no-more" class="font-weight-bold">Sem novos posts para exibir</div>
										<div slot="no-results" class="font-weight-bold">Sem novos posts para exibir</div>
									</infinite-loading>
								</div>
							</div>
						</div>
						<div v-if="!loading && scope == 'home' && feed.length == 0">
							<div class="card shadow-none border">
								<div class="card-body text-center">
									<p class="h2 font-weight-lighter p-5">Oi, {{profile.acct}}</p>
									<p class="text-lighter"><i class="fas fa-camera-retro fa-5x"></i></p>
									<p class="h3 font-weight-lighter p-5">Siga novas pessoas para a sua timeline.</p>
									<p><a href="/discover" class="btn btn-primary font-weight-bold py-0">Descobrir</a></p>
								</div>
							</div>
						</div>
					</div>
				</div>
				<div class="col-md-4 col-lg-4 my-4 order-1 order-md-2 d-none d-md-block">
					<div>

						<!-- <div class="mb-4">
							<a class="btn btn-block btn-primary btn-sm font-weight-bold mb-3 border" href="/i/compose" data-toggle="modal" data-target="#composeModal">
								<i class="far fa-plus-square pr-3 fa-lg pt-1"></i> New Post
							</a>
						</div> -->

						<div class="mb-4">
							<div v-show="!loading" class="">
								<div class="pb-2">
									<div class="media d-flex align-items-center">
										<a :href="!userStory ? profile.url : '/stories/' + profile.acct" class="mr-3">
											<!-- <img class="mr-3 rounded-circle box-shadow" :src="profile.avatar || '/storage/avatars/default.png'" alt="avatar" width="64px" height="64px" onerror="this.onerror=null;this.src='/storage/avatars/default.png?v=2'"> -->
											<div v-if="userStory" class="has-story cursor-pointer shadow-sm" @click="storyRedirect()">
												<img class="rounded-circle box-shadow" :src="profile.avatar" width="64px" height="64px" onerror="this.onerror=null;this.src='/storage/avatars/default.png?v=2'" alt="avatar">
											</div>
											<div v-else>
												<img class="rounded-circle box-shadow" :src="profile.avatar" width="64px" height="64px" onerror="this.onerror=null;this.src='/storage/avatars/default.png?v=2'" alt="avatar">
											</div>
										</a>
										<div class="media-body d-flex justify-content-between word-break" >
											<div>
												<p class="mb-0 px-0 font-weight-bold"><a :href="profile.url" class="text-dark">{{profile.username || 'loading...'}}</a></p>
												<p class="my-0 text-muted pb-0">{{profile.display_name || 'loading...'}}</p>
											</div>
											<div class="ml-2">
												<a class="text-muted" href="/settings/home">
													<i class="fas fa-cog fa-lg"></i>
													<span class="sr-only">Configurações</span>
												</a>
											</div>
										</div>
									</div>
								</div>
								<!-- <div class="card-footer bg-transparent border-top mt-2 py-1">
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
								</div> -->
							</div>
						</div>

						<div v-show="modes.notify == true && !loading" class="mb-4">
							<notification-card></notification-card>
						</div>

						<div v-show="showSuggestions == true && suggestions.length && config.ab && config.ab.rec == true" class="mb-4">
							<div class="card shadow-none border">
								<div class="card-header bg-white d-flex align-items-center justify-content-between">
									<a class="small text-muted cursor-pointer" href="#" @click.prevent="refreshSuggestions" ref="suggestionRefresh"><i class="fas fa-sync-alt"></i></a>
									<div class="small text-dark text-uppercase font-weight-bold">Sugestões</div>
									<div class="small text-muted cursor-pointer" v-on:click="hideSuggestions"><i class="fas fa-times"></i></div>
								</div>
								<div class="card-body pt-0">
									<div v-for="(rec, index) in suggestions" class="media align-items-center mt-3">
										<a :href="'/'+rec.username">
											<img :src="rec.avatar" width="32px" height="32px" class="rounded-circle mr-3" onerror="this.onerror=null;this.src='/storage/avatars/default.png?v=2'" alt="avatar">
										</a>
										<div class="media-body">
											<p class="mb-0 font-weight-bold small">
												<a :href="'/'+rec.username" class="text-decoration-none text-dark">
													{{rec.username}}
												</a>
											</p>
											<p class="mb-0 small text-muted">{{rec.message}}</p>
										</div>
										<a class="font-weight-bold small" href="#" @click.prevent="expRecFollow(rec.id, index)">Seguir</a>
									</div>
								</div>
							</div>
						</div>

						<footer>
							<div class="container pb-5">
								<p class="mb-0 text-uppercase font-weight-bold text-muted small">
									<a href="/site/about" class="text-dark pr-2">Sobre</a>
									<a href="/site/help" class="text-dark pr-2">Ajuda</a>
									<!-- <a href="/site/language" class="text-dark pr-2">Language</a> -->
									<a href="/discover/profiles" class="text-dark pr-2">Pessoas</a>
									<!-- <a href="/discover/places" class="text-dark pr-2">Places</a> -->
									<a href="/site/privacy" class="text-dark pr-2">Privacidade</a>
									<!-- <a href="/site/terms" class="text-dark pr-2">Terms</a> -->
								</p>
								<!-- <p class="mb-0 text-uppercase font-weight-bold text-muted small">
									<a href="http://pixelfed.org" class="text-muted" rel="noopener" title="" data-toggle="tooltip">Powered by Pixelfed</a>
								</p> -->
							</div>
						</footer>
					</div>
				</div>
			</div>
		</div>

		<div v-if="currentLayout === 'comments'" class="container p-0 overflow-hidden">
			<div class="row">
				<div class="col-12 col-md-6 offset-md-3">
					<div class="card shadow-none border" style="height:100vh;">
						<div class="card-header d-flex justify-content-between align-items-center">
							<div
								@click="commentNavigateBack(status.id)"
								class="cursor-pointer"
								>
								<i class="fas fa-chevron-left fa-lg px-2"></i>
							</div>
							<div>
								<p class="font-weight-bold mb-0 h5">Commentários</p>
							</div>
							<div>
								<i class="fas fa-cog fa-lg text-white"></i>
							</div>
						</div>
						<div class="card-body" style="overflow-y: auto !important">
							<div class="media">
								<img :src="status.account.avatar" class="rounded-circle border mr-3" width="32px" height="32px">
								<div class="media-body">
									<p class="d-flex justify-content-between align-items-top mb-0" style="overflow-y: hidden;">
										<span class="mr-2" style="font-size: 13px;">
											<a class="text-dark font-weight-bold mr-1 text-break" :href="status.account.url" v-bind:title="status.account.username">{{trimCaption(status.account.username,15)}}</a>
											<span class="text-break comment-body" style="word-break: break-all;" v-html="status.content"></span>
										</span>
									</p>
								</div>
							</div>
							<hr>
							<div class="postCommentsLoader text-center py-2">
								<div class="spinner-border" role="status">
									<span class="sr-only">Carregando...</span>
								</div>
							</div>
							<div class="postCommentsContainer d-none">
								<p v-if="replies.length" class="mb-1 text-center load-more-link my-4">
									<a
										href="#"
										class="text-dark"
										title="Load more comments"
										@click.prevent="loadMoreComments"
									>
										<svg class="bi bi-plus-circle" width="1em" height="1em" viewBox="0 0 16 16" fill="currentColor" xmlns="http://www.w3.org/2000/svg" style="font-size:2em;">  <path fill-rule="evenodd" d="M8 3.5a.5.5 0 01.5.5v4a.5.5 0 01-.5.5H4a.5.5 0 010-1h3.5V4a.5.5 0 01.5-.5z" clip-rule="evenodd"/>  <path fill-rule="evenodd" d="M7.5 8a.5.5 0 01.5-.5h4a.5.5 0 010 1H8.5V12a.5.5 0 01-1 0V8z" clip-rule="evenodd"/>  <path fill-rule="evenodd" d="M8 15A7 7 0 108 1a7 7 0 000 14zm0 1A8 8 0 108 0a8 8 0 000 16z" clip-rule="evenodd"/></svg>
									</a>
								</p>
								<div v-for="(reply, index) in replies" class="pb-3 media" :key="'tl' + reply.id + '_' + index">
									<img :src="reply.account.avatar" class="rounded-circle border mr-3" width="32px" height="32px">
									<div class="media-body">
										<div v-if="reply.sensitive == true">
											<span class="py-3">
												<a class="text-dark font-weight-bold mr-3"  style="font-size: 13px;" :href="reply.account.url" v-bind:title="reply.account.username">{{trimCaption(reply.account.username,15)}}</a>
												<span class="text-break" style="font-size: 13px;">
													<span class="font-italic text-muted">This comment may contain sensitive material</span>
													<span class="text-primary cursor-pointer pl-1" @click="reply.sensitive = false;">Show</span>
												</span>
											</span>
										</div>
										<div v-else>
											<p class="d-flex justify-content-between align-items-top read-more mb-0" style="overflow-y: hidden;">
												<span class="mr-3" style="font-size: 13px;">
													<a class="text-dark font-weight-bold mr-1 text-break" :href="reply.account.url" v-bind:title="reply.account.username">{{trimCaption(reply.account.username,15)}}</a>
													<span class="text-break comment-body" style="word-break: break-all;" v-html="reply.content"></span>
												</span>
												<span class="text-right" style="min-width: 30px;">
													<span v-on:click="likeReply(reply, $event)"><i v-bind:class="[reply.favourited ? 'fas fa-heart fa-sm text-danger':'far fa-heart fa-sm text-lighter']"></i></span>
													<span class="pl-2 text-lighter cursor-pointer" @click="ctxMenu(reply)">
														<span class="fas fa-ellipsis-v text-lighter"></span>
													</span>
													<!-- <post-menu :status="reply" :profile="user" :size="'sm'" :modal="'true'" class="d-inline-block px-2" v-on:deletePost=""></post-menu> -->
												</span>
											</p>
											<p class="mb-0">
												<a v-once class="text-muted mr-3 text-decoration-none small" style="width: 20px;" v-text="timeAgo(reply.created_at)" :href="reply.url"></a>
												<span v-if="reply.favourites_count" class="text-muted comment-reaction font-weight-bold mr-3 small">{{reply.favourites_count == 1 ? '1 like' : reply.favourites_count + ' likes'}}</span>
												<span class="small text-muted comment-reaction font-weight-bold cursor-pointer" v-on:click="replyFocus(reply, index, true)">Reply</span>
											</p>
											<div v-if="reply.reply_count > 0" class="cursor-pointer pb-2" v-on:click="toggleReplies(reply)">
												<span class="show-reply-bar"></span>
												<span class="comment-reaction small font-weight-bold">{{reply.thread ? 'Ocultar' : 'Exibir'}} Respostas ({{reply.reply_count}})</span>
											</div>
											<div v-if="reply.thread == true" class="comment-thread">
												<div v-for="(s, sindex) in reply.replies" class="py-1 media" :key="'cr' + s.id + '_' + index">
													<img :src="s.account.avatar" class="rounded-circle border mr-3" width="25px" height="25px">
													<div class="media-body">
														<p class="d-flex justify-content-between align-items-top read-more mb-0" style="overflow-y: hidden;">
															<span class="mr-2" style="font-size: 13px;">
																<a class="text-dark font-weight-bold mr-1" :href="s.account.url" :title="s.account.username">{{s.account.username}}</a>
																<span class="text-break comment-body" style="word-break: break-all;" v-html="s.content"></span>
															</span>
															<span>
																<span v-on:click="likeReply(s, $event)"><i v-bind:class="[s.favourited ? 'fas fa-heart fa-sm text-danger':'far fa-heart fa-sm text-lighter']"></i></span>
																<!-- <post-menu :status="s" :profile="user" :size="'sm'" :modal="'true'" class="d-inline-block pl-2" v-on:deletePost="deleteCommentReply(s.id, sindex, index) "></post-menu> -->
															</span>
														</p>
														<p class="mb-0">
															<a v-once class="text-muted mr-3 text-decoration-none small" style="width: 20px;" v-text="timeAgo(s.created_at)" :href="s.url"></a>
															<span v-if="s.favourites_count" class="text-muted comment-reaction font-weight-bold mr-3">{{s.favourites_count == 1 ? '1 curtida' : s.favourites_count + ' curtidas'}}</span>
														</p>
													</div>
												</div>
											</div>
										</div>
									</div>
								</div>
								<div v-if="!replies.length">
									<p class="text-center text-muted font-weight-bold small">Nenhum comentário ainda</p>
								</div>
							</div>
						</div>
						<div class="card-footer mb-3">
							<div class="align-middle d-flex">
								<img
									:src="profile.avatar"
									width="36"
									height="36"
									class="rounded-circle border mr-3">
								<textarea
									class="form-control rounded-pill"
									name="comment"
									placeholder="Add a comment…"
									autocomplete="off"
									autocorrect="off"
									rows="1"
									maxlength="0"
									style="resize: none;overflow-y: hidden"
									@click="replyFocus(status)">
								</textarea>
							</div>
						</div>
					</div>
				</div>
			</div>
		</div>

		<div class="modal-stack">
			<b-modal ref="ctxModal"
				id="ctx-modal"
				hide-header
				hide-footer
				centered
				rounded
				size="sm"
				body-class="list-group-flush p-0 rounded">
				<div class="list-group text-center">
					<!-- <div v-if="ctxMenuStatus && ctxMenuStatus.account.id != profile.id && ctxMenuRelationship && ctxMenuRelationship.following" class="list-group-item rounded cursor-pointer font-weight-bold text-danger" @click="ctxMenuUnfollow()">Unfollow</div>
					<div v-if="ctxMenuStatus && ctxMenuStatus.account.id != profile.id && ctxMenuRelationship && !ctxMenuRelationship.following" class="list-group-item rounded cursor-pointer font-weight-bold text-primary" @click="ctxMenuFollow()">Follow</div> -->
					<div class="list-group-item rounded cursor-pointer" @click="ctxMenuGoToPost()">Ver Post</div>
					<!-- <div v-if="ctxMenuStatus && ctxMenuStatus.local == true && !ctxMenuStatus.in_reply_to_id" class="list-group-item rounded cursor-pointer" @click="ctxMenuEmbed()">Embed</div>
					<div class="list-group-item rounded cursor-pointer" @click="ctxMenuCopyLink()">Copy Link</div> -->
					<div class="list-group-item rounded cursor-pointer" @click="ctxMenuShare()">Compartilhar</div>
					<div v-if="ctxMenuStatus && profile && profile.is_admin == true" class="list-group-item rounded cursor-pointer" @click="ctxModMenuShow()">Moderation Tools</div>
					<div v-if="ctxMenuStatus && ctxMenuStatus.account.id != profile.id" class="list-group-item rounded cursor-pointer text-danger" @click="ctxMenuReportPost()">Reportar</div>
					<div v-if="ctxMenuStatus && (profile.is_admin || profile.id == ctxMenuStatus.account.id)" class="list-group-item rounded cursor-pointer text-danger" @click="deletePost(ctxMenuStatus)">Apagar</div>
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
					<p class="py-2 px-3 mb-0">
						<div class="text-center font-weight-bold text-danger">Moderation Tools</div>
						<div class="small text-center text-muted">Select one of the following options</div>
					</p>
					<div class="list-group-item rounded cursor-pointer" @click="moderatePost(ctxMenuStatus, 'unlist')">Unlist from Timelines</div>
					<div v-if="ctxMenuStatus.sensitive" class="list-group-item rounded cursor-pointer" @click="moderatePost(ctxMenuStatus, 'remcw')">Remove Content Warning</div>
					<div v-else class="list-group-item rounded cursor-pointer" @click="moderatePost(ctxMenuStatus, 'addcw')">Add Content Warning</div>
					<!-- <div class="list-group-item rounded cursor-pointer" @click="ctxModOtherMenuShow()">Other</div> -->
					<div class="list-group-item rounded cursor-pointer text-lighter" @click="ctxModMenuClose()">Cancel</div>
				</div>
			</b-modal>
			<b-modal ref="ctxModOtherModal"
				id="ctx-mod-other-modal"
				hide-header
				hide-footer
				centered
				rounded
				size="sm"
				body-class="list-group-flush p-0 rounded">
				<div class="list-group text-center">
					<p class="py-2 px-3 mb-0">
						<div class="text-center font-weight-bold text-danger">Moderation Tools</div>
						<div class="small text-center text-muted">Select one of the following options</div>
					</p>
					<div class="list-group-item rounded cursor-pointer font-weight-bold" @click="confirmModal()">Unlist Posts</div>
					<div class="list-group-item rounded cursor-pointer font-weight-bold" @click="confirmModal()">Moderation Log</div>
					<div class="list-group-item rounded cursor-pointer text-lighter" @click="ctxModOtherMenuClose()">Cancel</div>
				</div>
			</b-modal>
			<b-modal ref="ctxShareModal"
				id="ctx-share-modal"
				title="Share"
				hide-footer
				hide-header
				centered
				rounded
				size="sm"
				body-class="list-group-flush p-0 rounded text-center">
				<div class="list-group-item rounded cursor-pointer" @click="shareStatus(ctxMenuStatus, $event)">{{ctxMenuStatus.reblogged ? 'Cancelar compartilhamento' : 'Compartilhar'}} com seguidores</div>
				<div class="list-group-item rounded cursor-pointer" @click="ctxMenuCopyLink()">Copiar Link</div>
				<!-- <div v-if="ctxMenuStatus && ctxMenuStatus.local == true && !ctxMenuStatus.in_reply_to_id" class="list-group-item rounded cursor-pointer" @click="ctxMenuEmbed()">Embed</div> -->
				<!-- <div class="list-group-item rounded cursor-pointer border-top-0">Email</div>
				<div class="list-group-item rounded cursor-pointer">Facebook</div>
				<div class="list-group-item rounded cursor-pointer">Mastodon</div>
				<div class="list-group-item rounded cursor-pointer">Pinterest</div>
				<div class="list-group-item rounded cursor-pointer">Pixelfed</div>
				<div class="list-group-item rounded cursor-pointer">Twitter</div>
				<div class="list-group-item rounded cursor-pointer">VK</div> -->
				<div class="list-group-item rounded cursor-pointer text-lighter" @click="closeCtxShareMenu()">Cancelar</div>
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
					<div class="form-group">
						<textarea class="form-control disabled text-monospace" rows="8" style="overflow-y:hidden;border: 1px solid #efefef; font-size: 12px; line-height: 18px; margin: 0 0 7px;resize:none;" v-model="ctxEmbedPayload" disabled=""></textarea>
					</div>
					<div class="form-group pl-2 d-flex justify-content-center">
						<div class="form-check mr-3">
							<input class="form-check-input" type="checkbox" v-model="ctxEmbedShowCaption" :disabled="ctxEmbedCompactMode == true">
							<label class="form-check-label font-weight-light">
								Exibir Legenda
							</label>
						</div>
						<div class="form-check mr-3">
							<input class="form-check-input" type="checkbox" v-model="ctxEmbedShowLikes" :disabled="ctxEmbedCompactMode == true">
							<label class="form-check-label font-weight-light">
								Exibir Curtidas
							</label>
						</div>
						<div class="form-check">
							<input class="form-check-input" type="checkbox" v-model="ctxEmbedCompactMode">
							<label class="form-check-label font-weight-light">
								Modo Compacto
							</label>
						</div>
					</div>
					<!-- <hr>
					<button :class="copiedEmbed ? 'btn btn-primary btn-block btn-sm py-1 font-weight-bold disabed': 'btn btn-primary btn-block btn-sm py-1 font-weight-bold'" @click="ctxCopyEmbed" :disabled="copiedEmbed">{{copiedEmbed ? 'Embed Code Copied!' : 'Copy Embed Code'}}</button>
					<p class="mb-0 px-2 small text-muted">By using this embed, you agree to our <a href="/site/terms">Terms of Use</a></p> -->
				</div>
			</b-modal>
			<b-modal ref="ctxReport"
				id="ctx-report"
				hide-header
				hide-footer
				centered
				rounded
				size="sm"
				body-class="list-group-flush p-0 rounded">
				<p class="py-2 px-3 mb-0">
					<div class="text-center font-weight-bold text-danger">Reportar</div>
					<div class="small text-center text-muted">Selecione uma ou mais opções</div>
				</p>
				<div class="list-group text-center">
					<div class="list-group-item rounded cursor-pointer font-weight-bold" @click="sendReport('spam')">Spam</div>
					<div class="list-group-item rounded cursor-pointer font-weight-bold" @click="sendReport('sensitive')">Conteúdo Inapropriado</div>
					<div class="list-group-item rounded cursor-pointer font-weight-bold" @click="sendReport('abusive')">Abusivo ou Nocivo</div>
					<!-- <div class="list-group-item rounded cursor-pointer font-weight-bold" @click="openCtxReportOtherMenu()">Others</div> -->
					<!-- <div class="list-group-item rounded cursor-pointer" @click="ctxReportMenuGoBack()">Go Back</div> -->
					<div class="list-group-item rounded cursor-pointer text-lighter" @click="ctxReportMenuGoBack()">Cancelar</div>
				</div>
			</b-modal>
			<b-modal ref="ctxReportOther"
				id="ctx-report-other"
				hide-header
				hide-footer
				centered
				rounded
				size="sm"
				body-class="list-group-flush p-0 rounded">
				<p class="py-2 px-3 mb-0">
					<div class="text-center font-weight-bold text-danger">Reportar</div>
					<div class="small text-center text-muted">Selecione uma ou mais opções</div>
				</p>
				<div class="list-group text-center">
					<div class="list-group-item rounded cursor-pointer font-weight-bold" @click="sendReport('underage')">Underage Account</div>
					<div class="list-group-item rounded cursor-pointer font-weight-bold" @click="sendReport('copyright')">Copyright Infringement</div>
					<div class="list-group-item rounded cursor-pointer font-weight-bold" @click="sendReport('impersonation')">Impersonation</div>
					<div class="list-group-item rounded cursor-pointer font-weight-bold" @click="sendReport('scam')">Scam or Fraud</div>
					<!-- <div class="list-group-item rounded cursor-pointer font-weight-bold" @click="sendReport('terrorism')">Terrorism Related</div> -->
					<!-- <div class="list-group-item rounded cursor-pointer font-weight-bold" @click="sendReport('other')">Other or Not listed</div> -->
					<!-- <div class="list-group-item rounded cursor-pointer" @click="ctxReportOtherMenuGoBack()">Go Back</div> -->
					<div class="list-group-item rounded cursor-pointer text-lighter" @click="ctxReportOtherMenuGoBack()">Cancel</div>
				</div>
			</b-modal>
			<b-modal ref="ctxConfirm"
				id="ctx-confirm"
				hide-header
				hide-footer
				centered
				rounded
				size="sm"
				body-class="list-group-flush p-0 rounded">
				<div class="d-flex align-items-center justify-content-center py-3">
					<div>{{ this.confirmModalTitle }}</div>
				</div>
				<div class="d-flex border-top btn-group btn-group-block rounded-0" role="group">
					<button type="button" class="btn btn-outline-lighter border-left-0 border-top-0 border-bottom-0 border-right py-2" style="color: rgb(0,122,255) !important;" @click.prevent="confirmModalCancel()">Cancelar</button>
					<button type="button" class="btn btn-outline-lighter border-0" style="color: rgb(0,122,255) !important;" @click.prevent="confirmModalConfirm()">Confirmar</button>
				</div>
			</b-modal>
			<b-modal ref="lightboxModal"
				id="lightbox"
				hide-header
				hide-footer
				centered
				size="lg"
				body-class="p-0"
				>
				<div v-if="lightboxMedia" :class="lightboxMedia.filter_class" class="w-100 h-100">
					<img :src="lightboxMedia.url" style="max-height: 100%; max-width: 100%" alt="lightbox media">
				</div>
			</b-modal>
			<b-modal ref="replyModal"
				id="ctx-reply-modal"
				hide-footer
				centered
				rounded
				:title-html="replyStatus.account ? 'Responder para <span class=text-dark>' + replyStatus.account.username + '</span>' : ''"
				title-tag="p"
				title-class="font-weight-bold text-muted"
				size="md"
				body-class="p-2 rounded">
				<div>
					<vue-tribute :options="tributeSettings">
						<textarea
							class="form-control replyModalTextarea"
							rows="4"
							v-model="replyText">
						</textarea>
					</vue-tribute>

					<div class="border-top border-bottom my-2">
						<ul class="nav align-items-center emoji-reactions" style="overflow-x: scroll;flex-wrap: unset;">
							<li class="nav-item" v-on:click="emojiReaction(status)" v-for="e in emoji">{{e}}</li>
						</ul>
					</div>
					<div class="d-flex justify-content-between align-items-center">
						<div>
							<span class="pl-2 small text-muted font-weight-bold text-monospace">
								<span :class="[replyText.length > config.uploader.max_caption_length ? 'text-danger':'text-dark']">{{replyText.length > config.uploader.max_caption_length ? config.uploader.max_caption_length - replyText.length : replyText.length}}</span>/{{config.uploader.max_caption_length}}
							</span>
						</div>
						<div class="d-flex align-items-center">
							<!-- <div class="custom-control custom-switch mr-3">
								<input type="checkbox" class="custom-control-input" id="replyModalCWSwitch" v-model="replyNsfw">
								<label :class="[replyNsfw ? 'custom-control-label font-weight-bold text-dark':'custom-control-label text-lighter']" for="replyModalCWSwitch">Mark as NSFW</label>
							</div> -->

							<button class="btn btn-primary btn-sm py-2 px-4 lead text-uppercase font-weight-bold" v-on:click.prevent="commentSubmit(status, $event)" :disabled="replyText.length == 0">
								{{replySending == true ? 'POSTING' : 'POST'}}
							</button>
						</div>
					</div>
				</div>
			</b-modal>
			<b-modal ref="ctxStatusModal"
				id="ctx-status-modal"
				hide-header
				hide-footer
				centered
				rounded
				size="xl"
				body-class="list-group-flush p-0 m-0 rounded">
				<!-- <post-component
					v-if="ctxMenuStatus"
					:status-template="ctxMenuStatus.pf_type"
					:status-id="ctxMenuStatus.id"
					:status-username="ctxMenuStatus.account.username"
					:status-url="ctxMenuStatus.url"
					:status-profile-url="ctxMenuStatus.account.url"
					:status-avatar="ctxMenuStatus.account.avatar"
					:status-profile-id="ctxMenuStatus.account.id"
					profile-layout="metro">
				</post-component> -->
			</b-modal>
		</div>
	</div>
</template>

<script type="text/javascript">
	import VueTribute from 'vue-tribute'

	export default {
		props: ['scope', 'layout'],

		components: {
			VueTribute
		},

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
				replyNsfw: false,
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
				replySending: false,
				ctxEmbedShowCaption: true,
				ctxEmbedShowLikes: false,
				ctxEmbedCompactMode: false,
				morePostsAvailable: false,
				mpCount: 0,
				mpData: false,
				mpInterval: 15000,
				mpEnabled: false,
				mpPoller: null,
				confirmModalTitle: 'Are you sure?',
				confirmModalIdentifer: null,
				confirmModalType: false,
				currentLayout: 'feed',
				pagination: {},
				tributeSettings: {
					collection: [
						{
							trigger: '@',
							menuShowMinLength: 2,
							values: (function (text, cb) {
								let url = '/api/compose/v0/search/mention';
								axios.get(url, { params: { q: text }})
								.then(res => {
									cb(res.data);
								})
								.catch(err => {
									console.log(err);
								})
							})
						},
						{
							trigger: '#',
							menuShowMinLength: 2,
							values: (function (text, cb) {
								let url = '/api/compose/v0/search/hashtag';
								axios.get(url, { params: { q: text }})
								.then(res => {
									cb(res.data);
								})
								.catch(err => {
									console.log(err);
								})
							})
						}
					]
				}
			}
		},

		watch: {
			ctxEmbedShowCaption: function (n,o) {
				if(n == true) {
					this.ctxEmbedCompactMode = false;
				}
				let mode = this.ctxEmbedCompactMode ? 'compact' : 'full';
				this.ctxEmbedPayload = window.App.util.embed.post(this.ctxMenuStatus.url, this.ctxEmbedShowCaption, this.ctxEmbedShowLikes, mode);
			},
			ctxEmbedShowLikes: function (n,o) {
				if(n == true) {
					this.ctxEmbedCompactMode = false;
				}
				let mode = this.ctxEmbedCompactMode ? 'compact' : 'full';
				this.ctxEmbedPayload = window.App.util.embed.post(this.ctxMenuStatus.url, this.ctxEmbedShowCaption, this.ctxEmbedShowLikes, mode);
			},
			ctxEmbedCompactMode: function (n,o) {
				if(n == true) {
					this.ctxEmbedShowCaption = false;
					this.ctxEmbedShowLikes = false;
				}
				let mode = this.ctxEmbedCompactMode ? 'compact' : 'full';
				this.ctxEmbedPayload = window.App.util.embed.post(this.ctxMenuStatus.url, this.ctxEmbedShowCaption, this.ctxEmbedShowLikes, mode);
			}
		},

		beforeMount() {
			this.fetchProfile();
			this.fetchTimelineApi();
		},

		mounted() {
			// todo: release after dark mode updates
			/* if(window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches || $('link[data-stylesheet="dark"]').length != 0) {
				this.modes.dark = true;

				let el = document.querySelector('link[data-stylesheet="light"]');
				el.setAttribute('href', '/css/appdark.css?id=' + Date.now());
				el.setAttribute('data-stylesheet', 'dark');
			}*/

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
					window.App.util.navatar();
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
					this.rtw();
					setTimeout(function() {
						document.querySelectorAll('.timeline .card-body .comments .comment-body a').forEach(function(i, e) {
							i.href = App.util.format.rewriteLinks(i);
						});
					}, 500);
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
								axios.post('/api/status/view', {
									'status_id': d.id,
									'profile_id': d.account.id
								});
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
				if(status.comments_disabled) {
					return;
				}

				// if(this.status && this.status.id == status.id) {
				// 	this.$refs.replyModal.show();
				// 	return;
				// }

				this.status = status;
				this.replies = {};
				this.replyStatus = {};
				this.replyText = '';
				this.replyId = status.id;
				this.replyStatus = status;
				// this.$refs.replyModal.show();
				this.fetchStatusComments(status, '');

				$('nav').hide();
				$('footer').hide();
				$('.mobile-footer-spacer').attr('style', 'display:none !important');
				$('.mobile-footer').attr('style', 'display:none !important');
				this.currentLayout = 'comments';
				window.history.pushState({}, '', status.url);
				return;
			},

			commentNavigateBack(id) {
				$('nav').show();
				$('footer').show();
				$('.mobile-footer-spacer').attr('style', 'display:block');
				$('.mobile-footer').attr('style', 'display:block');
				this.currentLayout = 'feed';
				setTimeout(function() {
					$([document.documentElement, document.body]).animate({
						scrollTop: $(`div[data-status-id="${id}"]`).offset().top
					}, 1000);
				}, 500);

				let path = this.scope == 'home' ? '/' : '/timeline/public';
				window.history.pushState({}, '', path);
			},

			likeStatus(status, event) {
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
				window.navigator.vibrate(200);
				if(status.favourited) {
					setTimeout(function() {
						event.target.classList.add('animate__animated', 'animate__bounce');
					},100);
				}
			},

			shareStatus(status, $event) {
				if($('body').hasClass('loggedIn') == false) {
					return;
				}

				this.closeModals();

				axios.post('/i/share', {
					item: status.id
				}).then(res => {
					status.reblogs_count = res.data.count;
					status.reblogged = !status.reblogged;
					if(status.reblogged) {
						swal('Success', 'You shared this post', 'success');
					} else {
						swal('Success', 'You unshared this post', 'success');
					}
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
				// axios.get('/api/v2/status/'+status.id+'/replies',
				// {
				// 	params: {
				// 		limit: 6
				// 	}
				// })
				// .then(res => {
				// 	let data = res.data.filter(res => {
				// 		return res.sensitive == false;
				// 	});
				// 	this.replies = _.reverse(data);
				// 	setTimeout(function() {
				// 		document.querySelectorAll('.timeline .card-body .comments .comment-body a').forEach(function(i, e) {
				// 			i.href = App.util.format.rewriteLinks(i);
				// 		});
				// 	}, 500);
				// }).catch(err => {
				// })
				let url = '/api/v2/comments/'+status.account.id+'/status/'+status.id;
				axios.get(url)
				.then(response => {
					let self = this;
					// this.results = this.layout == 'metro' ?
					// _.reverse(response.data.data) :
					// response.data.data;
					this.replies = _.reverse(response.data.data);
					this.pagination = response.data.meta.pagination;
					if(this.replies.length > 0) {
						$('.load-more-link').removeClass('d-none');
					}
					$('.postCommentsLoader').addClass('d-none');
					$('.postCommentsContainer').removeClass('d-none');
					// setTimeout(function() {
					// 	document.querySelectorAll('.status-comment .postCommentsContainer .comment-body a').forEach(function(i, e) {
					// 		i.href = App.util.format.rewriteLinks(i);
					// 	});
					// }, 500);
				}).catch(error => {
					if(!error.response) {
						$('.postCommentsLoader .lds-ring')
						.attr('style','width:100%')
						.addClass('pt-4 font-weight-bold text-muted')
						.text('An error occurred, cannot fetch comments. Please try again later.');
					} else {
						switch(error.response.status) {
							case 401:
							$('.postCommentsLoader .lds-ring')
							.attr('style','width:100%')
							.addClass('pt-4 font-weight-bold text-muted')
							.text('Please login to view.');
							break;

							default:
							$('.postCommentsLoader .lds-ring')
							.attr('style','width:100%')
							.addClass('pt-4 font-weight-bold text-muted')
							.text('An error occurred, cannot fetch comments. Please try again later.');
							break;
						}
					}
				});
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
					this.closeModals();
				}).catch(err => {
					swal('Error', 'Something went wrong. Please try again later.', 'error');
				});
			},

			commentSubmit(status, $event) {
				this.replySending = true;
				let id = status.id;
				let comment = this.replyText;
				let limit = this.config.uploader.max_caption_length;
				if(comment.length > limit) {
					this.replySending = false;
					swal('Comment Too Long', 'Please make sure your comment is '+limit+' characters or less.', 'error');
					return;
				}
				axios.post('/i/comment', {
					item: id,
					comment: comment,
					sensitive: this.replyNsfw
				}).then(res => {
					this.replyText = '';
					this.replies.push(res.data.entity);
					this.$refs.replyModal.hide();
				});
				this.replySending = false;
			},

			moderatePost(status, action, $event) {
				let username = status.account.username;
				let msg = '';
				let self = this;
				switch(action) {
					case 'addcw':
					msg = 'Are you sure you want to add a content warning to this post?';
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
								swal('Success', 'Successfully added content warning', 'success');
								status.sensitive = true;
								self.ctxModMenuClose();
							}).catch(err => {
								swal(
									'Error',
									'Something went wrong, please try again later.',
									'error'
									);
								self.ctxModMenuClose();
							});
						}
					});
					break;

					case 'remcw':
					msg = 'Are you sure you want to remove the content warning on this post?';
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
								swal('Success', 'Successfully added content warning', 'success');
								status.sensitive = false;
								self.ctxModMenuClose();
							}).catch(err => {
								swal(
									'Error',
									'Something went wrong, please try again later.',
									'error'
									);
								self.ctxModMenuClose();
							});
						}
					});
					break;

					case 'unlist':
					msg = 'Are you sure you want to unlist this post?';
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
								this.feed = this.feed.filter(f => {
									return f.id != status.id;
								});
								swal('Success', 'Successfully unlisted post', 'success');
								self.ctxModMenuClose();
							}).catch(err => {
								self.ctxModMenuClose();
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
				window.location.href = status.media_attachments[0].url;
				return;
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
						swal('Seguindo!', 'Você agora está seguindo ' + username, 'success');
					} else {
						swal('Unfollow!', 'Você deixou de seguir ' + username, 'success');
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
				this.$refs.ctxReport.hide();
				this.$refs.ctxReportOther.hide();
				this.closeModals();
			},

			ctxMenuCopyLink() {
				let status = this.ctxMenuStatus;
				navigator.clipboard.writeText(status.url);
				this.closeModals();
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
						swal('Seguindo!', 'Você agora está seguindo ' + username, 'success');
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
						swal('Unfollow!', 'Você deixou de seguir ' + username, 'success');
					}, 500);
				});
			},

			ctxMenuReportPost() {
				this.$refs.ctxModal.hide();
				this.$refs.ctxReport.show();
				return;
				// window.location.href = '/i/report?type=post&id=' + this.ctxMenuStatus.id;
			},

			ctxMenuEmbed() {
				this.closeModals();
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
				this.ctxEmbedShowCaption = true;
				this.ctxEmbedShowLikes = false;
				this.ctxEmbedCompactMode = false;
				this.$refs.ctxEmbedModal.hide();
			},

			ctxModMenuShow() {
				this.$refs.ctxModal.hide();
				this.$refs.ctxModModal.show();
			},

			ctxModOtherMenuShow() {
				this.$refs.ctxModal.hide();
				this.$refs.ctxModModal.hide();
				this.$refs.ctxModOtherModal.show();
			},

			ctxModMenu() {
				this.$refs.ctxModal.hide();
			},

			ctxModMenuClose() {
				this.closeModals();
				this.$refs.ctxModal.show();
			},

			ctxModOtherMenuClose() {
				this.closeModals();
				this.$refs.ctxModModal.show();
			},

			formatCount(count) {
				return App.util.format.count(count);
			},

			statusUrl(status) {
				if(status.local == true) {
					return status.url;
				}

				return '/i/web/post/_/' + status.account.id + '/' + status.id;
			},

			profileUrl(status) {
				if(status.local == true) {
					return status.account.url;
				}

				return '/i/web/profile/_/' + status.account.id;
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
				axios.get('/api/stories/v0/exists/'+this.profile.id)
				.then(res => {
					this.userStory = res.data;
				})
			},

			// real time watcher
			rtw() {
				this.mpPoller = setInterval(() => {
					let apiUrl = false;
					this.mpCount++;
					if(this.mpCount > 10) {
						this.mpInterval = 30000;
					}
					if(this.mpCount > 50) {
						this.mpInterval = (5 * 60 * 1000);
					}
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
							max_id: 0,
							limit: 20
						}
					}).then(res => {
						let self = this;
						let tids = this.feed.map(status => status.id);
						let data = res.data.filter(d => {
							return d.id > self.min_id && tids.indexOf(d.id) == -1;
						});
						let ids = data.map(status => status.id);
						let max = Math.max(...ids).toString();
						let newer = max > this.min_id;
						if(newer) {
							this.morePostsAvailable = true;
							this.mpData = data;
						}
					});
				}, this.mpInterval);
			},

			syncNewPosts() {
				let self = this;
				let data = this.mpData;
				let ids = data.map(s => s.id);
				this.min_id = Math.max(...ids).toString();
				this.max_id = Math.min(...ids).toString();
				this.feed.unshift(...data);
				this.morePostsAvailable = false;
				this.mpData = null;
			},

			switchFeedLayout(toggle) {
				this.loading = true;
				this.layout = toggle;
				let self = this;
				setTimeout(function() {
					self.loading = false;
				}, 500);
			},

			labelRedirect(type) {
				let url = '/i/redirect?url=' + encodeURI(this.config.features.label.covid.url);
				window.location.href = url;
			},

			openCtxReportOtherMenu() {
				let s = this.ctxMenuStatus;
				this.closeCtxMenu();
				this.ctxMenuStatus = s;
				this.$refs.ctxReportOther.show();
			},

			ctxReportMenuGoBack() {
				this.$refs.ctxReportOther.hide();
				this.$refs.ctxReport.hide();
				this.$refs.ctxModal.show();
			},

			ctxReportOtherMenuGoBack() {
				this.$refs.ctxReportOther.hide();
				this.$refs.ctxModal.hide();
				this.$refs.ctxReport.show();
			},

			sendReport(type) {
				let id = this.ctxMenuStatus.id;

				swal({
					'title': 'Confirmar Report',
					'text': 'Confirma que deseja reportar este post?',
					'icon': 'warning',
					'buttons': true,
					'dangerMode': true
				}).then((res) => {
					if(res) {
						axios.post('/i/report/', {
							'report': type,
							'type': 'post',
							'id': id,
						}).then(res => {
							this.closeCtxMenu();
							swal('Obrigado!', 'Recebemos sua solicitação.', 'success');
						}).catch(err => {
							swal('Oops!', 'There was an issue reporting this post.', 'error');
						})
					} else {
						this.closeCtxMenu();
					}
				});
			},

			closeModals() {
				this.$refs.ctxModal.hide();
				this.$refs.ctxModModal.hide();
				this.$refs.ctxModOtherModal.hide();
				this.$refs.ctxShareModal.hide();
				this.$refs.ctxEmbedModal.hide();
				this.$refs.ctxReport.hide();
				this.$refs.ctxReportOther.hide();
				this.$refs.ctxConfirm.hide();
				this.$refs.lightboxModal.hide();
				this.$refs.replyModal.hide();
				this.$refs.ctxStatusModal.hide();
			},

			openCtxStatusModal() {
				this.closeModals();
				this.$refs.ctxStatusModal.show();
			},

			openConfirmModal() {
				this.closeModals();
				this.$refs.ctxConfirm.show();
			},

			closeConfirmModal() {
				this.closeModals();
				this.confirmModalTitle = 'Confirma?';
				this.confirmModalType = false;
				this.confirmModalIdentifer = null;
			},

			confirmModalConfirm() {
				switch(this.confirmModalType) {
					case 'post.delete':
						axios.post('/i/delete', {
							type: 'status',
							item: this.confirmModalIdentifer
						}).then(res => {
							this.feed = this.feed.filter(s => {
								return s.id != this.confirmModalIdentifer;
							});
							this.closeConfirmModal();
						}).catch(err => {
							this.closeConfirmModal();
							swal('Error', 'Something went wrong. Please try again later.', 'error');
						});
					break;
				}

				this.closeConfirmModal();
			},

			confirmModalCancel() {
				this.closeConfirmModal();
			},

			timeAgo(ts) {
				return App.util.format.timeAgo(ts);
			},

			toggleReplies(reply) {
				if(reply.thread) {
					reply.thread = false;
				} else {
					if(reply.replies.length > 0) {
						reply.thread = true;
						return;
					}
					let url = '/api/v2/comments/'+reply.account.id+'/status/'+reply.id;
					axios.get(url)
					.then(response => {
						reply.replies = _.reverse(response.data.data);
						reply.thread = true;
					});
				}
			},

			likeReply(status, $event) {
				if($('body').hasClass('loggedIn') == false) {
					swal('Login', 'Por favor, efetue login para esta ação.', 'info');
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

			replyFocus(e, index, prependUsername = false) {
				if($('body').hasClass('loggedIn') == false) {
					this.redirect('/login?next=' + encodeURIComponent(window.location.pathname));
					return;
				}
				if(this.status.comments_disabled) {
					return;
				}

				this.replyToIndex = index;
				this.replyingToId = e.id;
				this.replyingToUsername = e.account.username;
				this.reply_to_profile_id = e.account.id;
				let username = e.account.local ? '@' + e.account.username + ' '
				: '@' + e.account.acct + ' ';
				if(prependUsername == true) {
					this.replyText = username;
				}
				this.$refs.replyModal.show();
				setTimeout(function() {
					$('.replyModalTextarea').focus();
				}, 500);
			},

			loadMoreComments() {
				if(this.pagination.total_pages == 1 || this.pagination.current_page == this.pagination.total_pages) {
					$('.load-more-link').addClass('d-none');
					return;
				}
				$('.load-more-link').addClass('d-none');
				$('.postCommentsLoader').removeClass('d-none');
				let next = this.pagination.links.next;
				axios.get(next)
				.then(response => {
					let self = this;
					let res =  response.data.data;
					$('.postCommentsLoader').addClass('d-none');
					for(let i=0; i < res.length; i++) {
						this.replies.unshift(res[i]);
					}
					this.pagination = response.data.meta.pagination;
					$('.load-more-link').removeClass('d-none');
				});
			}
		},

		beforeDestroy () {
			clearInterval(this.mpInterval);
		}
	}
</script>

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
	/*.reply-btn {
		position: absolute;
		bottom: 30px;
		right: 20px;
		width: 60px;
		text-align: center;
		font-size: 13px;
		border-radius: 0 3px 3px 0;
	}*/
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
	.replyModalTextarea {
		border: none;
		font-size: 18px;
		resize: none;
		white-space: pre-wrap;
		outline: none;
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
	#ctx-reply-modal .form-control:focus {
		border: none;
		outline: 0;
		box-shadow: none;
	}
</style>
