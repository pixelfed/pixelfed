<template>
<div>
	<div v-if="!loaded" style="height: 80vh;" class="d-flex justify-content-center align-items-center">
			<img src="/img/pixelfed-icon-grey.svg" class="">
	</div>
	<div v-if="loaded && warning" class="bg-white mt-n4 pt-3 border-bottom">
		<div class="container">
			<p class="text-center font-weight-bold">You are blocking this account</p>
			<p class="text-center font-weight-bold"><a href="#" class="btn btn-primary font-weight-bold px-5" @click.prevent="warning = false; fetchData()">View Status</a></p>
		</div>
	</div>
	<div v-if="loaded && warning == false" class="postComponent">
		<div v-if="layout == 'metro'" class="container px-0">
			<div class="card card-md-rounded-0 status-container orientation-unknown shadow-none border">
				<div class="row px-0 mx-0">
				<div class="d-flex d-md-none align-items-center justify-content-between card-header bg-white w-100">
					<div class="d-flex">
						<div class="status-avatar mr-2" @click="redirect(statusProfileUrl)">
							<img :src="statusAvatar" width="24px" height="24px" style="border-radius:12px;" class="cursor-pointer">
						</div>
						<div class="username">
							<span class="username-link font-weight-bold text-dark cursor-pointer" @click="redirect(statusProfileUrl)">{{ statusUsername }}</span>
							<span v-if="status.account.is_admin" class="fa-stack" title="Admin Account" data-toggle="tooltip" style="height:1em; line-height:1em; max-width:19px;">
								<i class="fas fa-certificate text-danger fa-stack-1x"></i>
								<i class="fas fa-crown text-white fa-sm fa-stack-1x" style="font-size:7px;"></i>
							</span>
							<p class="mb-0" style="font-size: 10px;">
										<span v-if="loaded && status.taggedPeople.length" class="mb-0">
											<span class="font-weight-light cursor-pointer" style="color:#718096" title="Tagged People" data-toggle="tooltip" data-placement="bottom" @click="showTaggedPeopleModal()"><i class="fas fa-tag text-lighter"></i> <span class="font-weight-bold">{{status.taggedPeople.length}} Tagged People</span></span>
										</span>
										<span v-if="loaded && status.place != null && status.taggedPeople.length" class="px-2 font-weight-bold text-lighter">&#8226;</span>
										<span v-if="loaded && status.place != null" class="mb-0 cursor-pointer text-truncate" style="color:#718096" @click="redirect('/discover/places/' + status.place.id + '/' + status.place.slug)"><i class="fas fa-map-marked-alt text-lighter"></i> <span class="font-weight-bold">{{status.place.name}}, {{status.place.country}}</span></span>
									</p>
						</div>
					</div>
					<div v-if="user != false" class="float-right">
						<div class="post-actions">
						<div>
							<button class="btn btn-link text-dark no-caret" title="Post options" @click="ctxMenu()">
								<span class="fas fa-ellipsis-v text-muted"></span>
							</button>
							</div>
						</div>
					</div>
				 </div>
					<div class="col-12 col-md-8 px-0 mx-0">
							<div class="postPresenterContainer d-none d-flex justify-content-center align-items-center" style="background: #000;">
								<div v-if="status.pf_type === 'text'" class="w-100">
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
									<video-presenter :status="status" v-on:togglecw="status.sensitive = false"></video-presenter>
								</div>

								<div v-else-if="status.pf_type === 'photo:album'" class="w-100">
									<photo-album-presenter :status="status" v-on:lightbox="lightbox" v-on:togglecw="status.sensitive = false"></photo-album-presenter>
								</div>

								<div v-else-if="status.pf_type === 'video:album'" class="w-100">
									<video-album-presenter :status="status" v-on:togglecw="status.sensitive = false"></video-album-presenter>
								</div>

								<div v-else-if="status.pf_type === 'photo:video:album'" class="w-100">
									<mixed-album-presenter :status="status" v-on:lightbox="lightbox" v-on:togglecw="status.sensitive = false"></mixed-album-presenter>
								</div>

								<div v-else class="w-100">
									<p class="text-center p-0 font-weight-bold text-white">Error: Problem rendering preview.</p>
								</div>
							</div>
					</div>

					<div class="col-12 col-md-4 px-0 d-flex flex-column border-left border-md-left-0">
						<div class="d-md-flex d-none align-items-center justify-content-between card-header py-3 bg-white">
							<div class="d-flex align-items-center status-username text-truncate">
								<div class="status-avatar mr-2" @click="redirect(statusProfileUrl)">
									<img :src="statusAvatar" width="24px" height="24px" style="border-radius:12px;" class="cursor-pointer">
								</div>
								<div class="username">
									<span class="username-link font-weight-bold text-dark cursor-pointer" @click="redirect(statusProfileUrl)">{{ statusUsername }}</span>
									<span v-if="status.account.is_admin" class="fa-stack" title="Admin Account" data-toggle="tooltip" style="height:1em; line-height:1em; max-width:19px;">
										<i class="fas fa-certificate text-danger fa-stack-1x"></i>
										<i class="fas fa-crown text-white fa-sm fa-stack-1x" style="font-size:7px;"></i>
									</span>
									<p class="mb-0" style="font-size: 10px;">
										<span v-if="loaded && status.taggedPeople.length" class="mb-0">
											<span class="font-weight-light cursor-pointer" style="color:#718096" title="Tagged People" data-toggle="tooltip" data-placement="bottom" @click="showTaggedPeopleModal()"><i class="fas fa-tag text-lighter"></i> <span class="font-weight-bold">{{status.taggedPeople.length}} Tagged People</span></span>
										</span>
										<span v-if="loaded && status.place != null && status.taggedPeople.length" class="px-2 font-weight-bold text-lighter">&#8226;</span>
										<span v-if="loaded && status.place != null" class="mb-0 cursor-pointer text-truncate" style="color:#718096" @click="redirect('/discover/places/' + status.place.id + '/' + status.place.slug)"><i class="fas fa-map-marked-alt text-lighter"></i> <span class="font-weight-bold">{{status.place.name}}, {{status.place.country}}</span></span>
									</p>
								</div>
							</div>
							<div class="float-right">
								<div class="post-actions">
									<div v-if="user != false">
										<button class="btn btn-link text-dark no-caret" title="Post options" @click="ctxMenu()">
											<span class="fas fa-ellipsis-v text-muted"></span>
										</button>
									</div>
								</div>
							</div>
						</div>
						<div class="d-flex flex-md-column flex-column-reverse h-100" style="overflow-y: auto;">
							<div class="card-body status-comments pt-0">
								<div v-if="status.pf_type != 'text'" class="status-comment">
									<div v-if="status.content.length" class="pt-3">
										<div v-if="status.sensitive">
											<span class="py-3">
												<a class="text-dark font-weight-bold mr-1" :href="status.account.url" v-bind:title="status.account.username">{{truncate(status.account.username,15)}}</a>
												<span class="text-break">
													<span class="font-italic text-muted">This comment may contain sensitive material</span>
													<span class="text-primary cursor-pointer pl-1" @click="status.sensitive = false">Show</span>
												</span>
											</span>
										</div>
										<div v-else>
											<p :class="[status.content.length > 620 ? 'mb-1 read-more' : 'mb-1']" style="overflow: hidden;">
												<a class="font-weight-bold pr-1 text-dark text-decoration-none" :href="statusProfileUrl">{{statusUsername}}</a>
												<span class="comment-text" :id="status.id + '-status-readmore'" v-html="content"></span>
											</p>
										</div>
										<hr>
									</div>

									<div v-if="showComments">
										<div class="postCommentsLoader text-center py-2">
											<div class="spinner-border" role="status">
												<span class="sr-only">Loading...</span>
											</div>
										</div>
										<div class="postCommentsContainer d-none">
											<p class="mb-1 text-center load-more-link d-none my-4">
												<a href="#" class="text-dark" v-on:click="loadMore" title="Load more comments" data-toggle="tooltip" data-placement="bottom">
													<svg class="bi bi-plus-circle" width="1em" height="1em" viewBox="0 0 16 16" fill="currentColor" xmlns="http://www.w3.org/2000/svg" style="font-size:2em;">  <path fill-rule="evenodd" d="M8 3.5a.5.5 0 01.5.5v4a.5.5 0 01-.5.5H4a.5.5 0 010-1h3.5V4a.5.5 0 01.5-.5z" clip-rule="evenodd"/>  <path fill-rule="evenodd" d="M7.5 8a.5.5 0 01.5-.5h4a.5.5 0 010 1H8.5V12a.5.5 0 01-1 0V8z" clip-rule="evenodd"/>  <path fill-rule="evenodd" d="M8 15A7 7 0 108 1a7 7 0 000 14zm0 1A8 8 0 108 0a8 8 0 000 16z" clip-rule="evenodd"/></svg>
												</a>
											</p>
											<div class="comments mt-3">
												<div v-for="(reply, index) in results" class="pb-4 media" :key="'tl' + reply.id + '_' + index">
													<img :src="reply.account.avatar" class="rounded-circle border mr-3" width="42px" height="42px">
													<div class="media-body">
														<div v-if="reply.sensitive == true">
															<span class="py-3">
																<a class="text-dark font-weight-bold mr-1" :href="profileUrl(reply)" v-bind:title="reply.account.username">{{!reply.account.local ? '@' : '' }}{{truncate(reply.account.username,15)}}</a>
																<span class="text-break">
																	<span class="font-italic text-muted">This comment may contain sensitive material</span>
																	<span class="text-primary cursor-pointer pl-1" @click="reply.sensitive = false;">Show</span>
																</span>
															</span>
														</div>
														<div v-else>
															<p class="d-flex justify-content-between align-items-top read-more" style="overflow-y: hidden;">
																<span>
																	<a class="text-dark font-weight-bold mr-1 text-break" :href="profileUrl(reply)" v-bind:title="reply.account.username">{{!reply.account.local ? '@' : '' }}{{truncate(reply.account.username,15)}}</a>
																	<span class="text-break comment-body" style="word-break: break-all;" v-html="reply.content"></span>
																</span>
																<span style="min-width:38px;">
																	 <span v-on:click="likeReply(reply, $event)"><i v-bind:class="[reply.favourited ? 'fas fa-heart fa-sm text-danger':'far fa-heart fa-sm text-lighter']"></i></span>
																		<post-menu :status="reply" :profile="user" :size="'sm'" :modal="'true'" class="d-inline-block px-2" v-on:deletePost="deleteComment(reply.id, index)"></post-menu>
																</span>
															</p>
															<p class="">
																<a v-once class="text-muted mr-3 text-decoration-none small" style="width: 20px;" v-text="timeAgo(reply.created_at)" :href="permalinkUrl(reply)"></a>
																<span v-if="reply.favourites_count" class="text-muted comment-reaction font-weight-bold mr-3">{{reply.favourites_count == 1 ? '1 like' : reply.favourites_count + ' likes'}}</span>
																<span class="text-muted comment-reaction font-weight-bold cursor-pointer" v-on:click="replyFocus(reply, index, true)">Reply</span>
															</p>
															<div v-if="reply.reply_count > 0" class="cursor-pointer" v-on:click="toggleReplies(reply)">
																 <span class="show-reply-bar"></span>
																 <span class="comment-reaction font-weight-bold text-muted">{{reply.thread ? 'Hide' : 'View'}} Replies ({{reply.reply_count}})</span>
															</div>
															<div v-if="reply.thread == true" class="comment-thread">
																<div v-for="(s, sindex) in reply.replies" class="pb-3 media" :key="'cr' + s.id + '_' + index">
																	<img :src="s.account.avatar" class="rounded-circle border mr-3" width="25px" height="25px">
																	<div class="media-body">
																		<p class="d-flex justify-content-between align-items-top read-more" style="overflow-y: hidden;">
																			<span>
																				<a class="text-dark font-weight-bold mr-1" :href="profileUrl(s)" :title="s.account.username">{{!s.account.local ? '@' : '' }}{{s.account.username}}</a>
																				<span class="text-break comment-body" style="word-break: break-all;" v-html="s.content"></span>
																			</span>
																			<span class="pl-2" style="min-width:38px">
																				<span v-on:click="likeReply(s, $event)"><i v-bind:class="[s.favourited ? 'fas fa-heart fa-sm text-danger':'far fa-heart fa-sm text-lighter']"></i></span>
																					<post-menu :status="s" :profile="user" :size="'sm'" :modal="'true'" class="d-inline-block pl-2" v-on:deletePost="deleteCommentReply(s.id, sindex, index) "></post-menu>
																			</span>
																		</p>
																		<p class="">
																			<a v-once class="text-muted mr-3 text-decoration-none small" style="width: 20px;" v-text="timeAgo(s.created_at)" :href="s.url"></a>
																			<span v-if="s.favourites_count" class="text-muted comment-reaction font-weight-bold mr-3">{{s.favourites_count == 1 ? '1 like' : s.favourites_count + ' likes'}}</span>
																		</p>
																	</div>
																</div>
															</div>
														</div>
													</div>
												</div>
											</div>
										</div>
									</div>

								</div>
							</div>
							<div v-if="reactionBarLoading" class="card-body flex-grow-0 py-4 text-center">
								<div class="spinner-border" role="status">
									<span class="sr-only">Loading...</span>
								</div>
							</div>
							<div v-else class="card-body flex-grow-0 py-1">
								<div v-if="loaded && user.hasOwnProperty('id')" class="reactions my-2 pb-1 d-flex justify-content-between">
									<h3 v-bind:class="[reactions.liked ? 'fas fa-heart text-danger mr-3 m-0 cursor-pointer' : 'far fa-heart pr-3 m-0 like-btn cursor-pointer']" title="Like" v-on:click="likeStatus"></h3>
									<h3 v-if="!status.comments_disabled" class="far fa-comment mr-3 m-0 cursor-pointer" title="Comment" v-on:click="replyFocus(status)"></h3>
								 <h3 @click="redirect(status.media_attachments[0].url)" class="fas fa-expand m-0 mr-3 cursor-pointer"></h3>
									 <!-- <h3 v-if="status.visibility == 'public'" v-bind:class="[reactions.bookmarked ? 'fas fa-bookmark text-warning m-0 float-right cursor-pointer' : 'far fa-bookmark m-0 float-right cursor-pointer']" title="Bookmark" v-on:click="bookmarkStatus"></h3> -->
									<h3 v-if="status.visibility == 'public'" v-bind:class="[reactions.bookmarked ? 'fas fa-bookmark text-warning m-0 mr-3 cursor-pointer' : 'far fa-bookmark m-0 mr-3 cursor-pointer']" title="Bookmark" v-on:click="bookmarkStatus"></h3>
									<h3 v-if="status.visibility == 'public'" v-bind:class="[reactions.shared ? 'fas fa-retweet m-0 text-primary cursor-pointer' : 'fas fa-retweet m-0 share-btn cursor-pointer']" title="Share" v-on:click="shareStatus"></h3>
								</div>
								<div class="reaction-counts mb-0">
									<div v-if="status.liked_by.username && status.liked_by.username !== user.username" class="likes mb-1">
										<span class="like-count">Liked by
											<a class="font-weight-bold text-dark" :href="status.liked_by.url">{{status.liked_by.username}}</a>
											<span v-if="status.liked_by.others == true">
												and <span class="font-weight-bold text-dark cursor-pointer" @click="likesModal"><span v-if="status.liked_by.total_count_pretty">{{status.liked_by.total_count_pretty}}</span> others</span>
											</span>
										</span>
									</div>
								</div>
								<div class="timestamp d-flex align-items-bottom justify-content-between">
									<a v-bind:href="statusUrl" class="small text-muted" :title="status.created_at">
										{{timestampFormat()}}
									</a>
									<span class="small text-muted text-capitalize cursor-pointer" v-on:click="visibilityModal">{{status.visibility}}</span>
								</div>
							</div>
						</div>
						<div v-if="showComments" class="card-footer bg-white sticky-md-bottom p-0">
							<div v-if="user.length == 0" class="comment-form-guest p-3">
								<a href="/login">Login</a> to like or comment.
							</div>
							<form v-else class="border-0 rounded-0 align-middle" method="post" action="/i/comment" :data-id="statusId" data-truncate="false">
								<textarea class="form-control border-0 rounded-0" name="comment" placeholder="Add a comment…" autocomplete="off" autocorrect="off" style="height:56px;line-height: 18px;max-height:80px;resize: none; padding-right:4.2rem;" @click="replyFocus(status)"></textarea>
								<input type="button" value="Post" class="d-inline-block btn btn-link font-weight-bold reply-btn text-decoration-none" disabled/>
							</form>
						</div>
					</div>

				</div>
			</div>
			<div class="container" v-if="showProfileMorePosts">
				<p class="text-lighter px-3 mt-5" style="font-weight: 600;font-size: 15px;">More posts from <a :href="'/'+statusUsername" class="text-dark">{{this.statusUsername}}</a></p>
				<div class="profile-timeline mt-md-4">
					<div class="row">
						<div class="col-4 p-1 p-md-3" v-for="(s, index) in profileMorePosts" :key="'tlob:'+index">
							<a class="card info-overlay card-md-border-0" :href="getStatusUrl(s)" v-once>
								<div :class="[s.sensitive ? 'square' : 'square ' + s.media_attachments[0].filter_class]">
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
				</div>
			</div>
		</div>

		<div v-if="layout == 'moment'" class="momentui">
			<div class="bg-dark mt-md-n4">
				<div class="container" style="max-width: 700px;">
							<div class="postPresenterContainer d-none d-flex justify-content-center align-items-center bg-dark">
								<div v-if="status.pf_type === 'photo'" class="w-100">
									<photo-presenter :status="status" v-on:lightbox="lightbox" v-on:togglecw="status.sensitive = false"></photo-presenter>
								</div>

								<div v-else-if="status.pf_type === 'video'" class="w-100">
									<video-presenter :status="status" v-on:togglecw="status.sensitive = false"></video-presenter>
								</div>

								<div v-else-if="status.pf_type === 'photo:album'" class="w-100">
									<photo-album-presenter :status="status" v-on:lightbox="lightbox" v-on:togglecw="status.sensitive = false"></photo-album-presenter>
								</div>

								<div v-else-if="status.pf_type === 'video:album'" class="w-100">
									<video-album-presenter :status="status" v-on:togglecw="status.sensitive = false"></video-album-presenter>
								</div>

								<div v-else-if="status.pf_type === 'photo:video:album'" class="w-100">
									<mixed-album-presenter :status="status" v-on:lightbox="lightbox" v-on:togglecw="status.sensitive = false"></mixed-album-presenter>
								</div>

								<div v-else class="w-100">
									<p class="text-center p-0 font-weight-bold text-white">Error: Problem rendering preview.</p>
								</div>
							</div>
				</div>
			</div>
			<div class="bg-white">
				<div class="container">
					<div class="row pb-5">
						<div class="col-12 col-md-8 py-4">
							<div class="reactions d-flex align-items-center">
								<div class="text-center mr-5">
									<div v-bind:class="[reactions.liked ? 'fas fa-heart text-danger m-0 cursor-pointer' : 'far fa-heart m-0 like-btn cursor-pointer']" title="Like" v-on:click="likeStatus" style="font-size:1.575rem">
									</div>
									<div class="like-count font-weight-bold mt-2 rounded border" style="cursor:pointer;" v-on:click="likesModal">

										{{ownerOrAdmin == true ? (status.liked_by.total_count + 1) : 0}}
									</div>
								</div>
								<div class="text-center">
									<div v-if="status.visibility == 'public'" v-bind:class="[reactions.shared ? 'h3 far fa-share-square m-0 text-primary cursor-pointer' : 'h3 far fa-share-square m-0 share-btn cursor-pointer']" title="Share" v-on:click="shareStatus">
									</div>
									<div class="share-count font-weight-bold mt-2 rounded border" v-if="status.visibility == 'public'" style="cursor:pointer;" v-on:click="sharesModal">{{status.reblogs_count || 0}}</div>
								</div>
							</div>
							<div v-if="status.length && status.content_text.includes('#') || status.content_text.includes('https://') || status.content_text.includes('@') || status.content_text.length > 45" class="media align-items-center mt-3">
								<div class="media-body">
									<p class="lead mr-2" v-html="status.content">
									</p>
									<p class="lead mb-0">
										by <a :href="statusProfileUrl">{{statusUsername}}</a>
										<span v-if="relationship && profile && user && !relationship.following && profile.id != user.id">
											<span class="px-1 text-lighter">•</span>
											<a class="font-weight-bold small" href="#">Follow</a>
										</span>
									</p>
								</div>
								<a :href="statusProfileUrl" :title="statusUsername"><img :src="statusAvatar" class="rounded-circle border mr-3" alt="avatar" width="72px" height="72px"></a>
							</div>
							<div v-else class="media align-items-center mt-3">
								<div class="media-body">
									<h2 class="font-weight-bold mr-2">
										{{status.content_text.length ? status.content_text : 'Untitled Post'}}
									</h2>
									<p class="lead mb-0">
										by <a :href="statusProfileUrl">{{statusUsername}}</a>
										<!-- <span class="px-1 text-lighter">•</span>
										<a class="font-weight-bold small" href="#">Follow</a> -->
									</p>
								</div>
								<a :href="statusProfileUrl" :title="statusUsername"><img :src="statusAvatar" class="rounded-circle border mr-3" alt="avatar" width="72px" height="72px"></a>
							</div>
							<hr>
							<div>
								<p class="lead">
									<span v-if="status.place" class="text-truncate">
										<i class="fas fa-map-marker-alt text-lighter mr-3"></i> {{status.place.name}}, {{status.place.country}}
									</span>
									<span v-once class="float-right">
										<i class="far fa-clock text-lighter mr-3"></i> {{timeAgo(status.created_at)}} ago
									</span>
								</p>
								<!-- <div class="">
									<p class="lead">
										<span class="mr-3"><i class="fas fa-camera text-lighter"></i></span>
										<span>Nikon D850</span>
									</p>
									<p class="lead">
										<span class="mr-3"><i class="fas fa-ruler-horizontal text-lighter"></i></span>
										<span>200-500mm</span>
									</p>
									<p class="lead">
										<span class="mr-3"><i class="fas fa-stream text-lighter"></i></span>
										<span>500mm <span class="px-1"></span> ƒ/7.1 <span class="px-1"></span> 1/1600s <span class="px-1"></span> ISO 800</span>
									</p>
								</div> -->
								<div v-if="status.tags" class="pt-4">
									<p class="lead">
										<a v-for="(tag, index) in status.tags" class="btn btn-outline-dark mr-1 mb-1" :href="tag.url + '?src=mp'">{{tag.name}}</a>
									</p>
								</div>
							</div>
						</div>
						<div class="col-12 col-md-4 pt-4 pl-md-3">
							<p class="lead font-weight-bold">Comments</p>
							<div v-if="user != false" class="moment-comments">
								<div class="form-group">
									<textarea class="form-control" rows="3" placeholder="Add a comment ..." v-model="replyText"></textarea>
									<p class="d-flex justify-content-between align-items-center mt-3">
										<span class="small text-lighter font-weight-bold">
											{{replyText.length}}/{{config.uploader.max_caption_length}}
										</span>
										<span v-if="replyText.length > 2">
											<div class="custom-control custom-switch">
												<input type="checkbox" class="custom-control-input" @click="!!replySensitive" v-model="replySensitive" id="sensitiveReply">
												<label class="custom-control-label small font-weight-bold text-muted" style="padding-top: 3px" for="sensitiveReply">Add Content Warning</label>
											</div>
										</span>
										<button class="btn btn-sm font-weight-bold btn-outline-primary py-1"
										v-if="replyText.length > 2" @click="postReply">Post</button>
									</p>
								</div>
							</div>
							<div class="comment mt-4" style="max-height: 500px; overflow-y: auto;">
								<div v-for="(reply, index) in results" :key="'tl' + reply.id + '_' + index" class="media mb-3 mt-2">
									<a :href="reply.account.url" :title="reply.account.username"><img :src="reply.account.avatar" class="rounded-circle border mr-3" alt="avatar" width="32px" height="32px"></a>
									<div class="media-body">
										<div class="d-flex justify-content-between">
											<span>
												<a class="font-weight-bold text-dark" :href="reply.account.url">{{reply.account.username}}</a>
											</span>
											<span class="text-lighter">
												<span v-if="reply.favourited" class="cursor-pointer mr-2" @click="likeReply(reply)">
													<i class="fas fa-heart text-danger"></i>
												</span>
												<span v-else class="cursor-pointer mr-2" @click="likeReply(reply)">
													<i class="far fa-heart"></i>
												</span>
												<span class="">
													<post-menu :status="reply" :profile="user" :size="'sm'" :modal="'true'" class="d-inline-block px-2" v-on:deletePost="deleteComment(reply.id, index)"></post-menu>
												</span>
											</span>
										</div>
										<div v-if="reply.sensitive == true">
											<span class="py-3">
												<span class="text-break">
													<span class="font-italic text-muted">This comment may contain sensitive material</span>
													<span class="badge badge-primary cursor-pointer ml-2 py-1" @click="reply.sensitive = false;">Show</span>
												</span>
											</span>
										</div>
										<div v-else class="read-more" style="overflow-y: hidden;">
											<p v-html="reply.content" class="mb-0">loading ...</p>
										</div>
										<p>
											<span class="small">
												<a class="text-lighter text-decoration-none" :href="reply.url">{{timeAgo(reply.created_at)}}</a>
											</span>
										</p>
									</div>
								</div>
							</div>
						</div>
					</div>
				</div>
			</div>
		</div>

		<div v-if="layout == 'poll'" class="container px-0">
			<div class="row justify-content-center">
				<div class="col-12 col-md-6">

					<div v-if="loading || !user || !reactions" class="text-center">
						<div class="spinner-border" role="status">
						  <span class="sr-only">Loading...</span>
						</div>
					</div>
					<div v-else>
						<poll-card
								:status="status"
								:profile="user"
								:showBorderTop="true"
								:fetch-state="true"
								:reactions="reactions"
								v-on:likeStatus="likeStatus" />

						<comment-feed :status="status" class="mt-3" />
						<!-- <div v-if="user.hasOwnProperty('id')" class="card card-body shadow-none border border-top-0 bg-light">
								<div class="media">
									<img src="/storage/avatars/default.png" class="rounded-circle mr-2" width="40" height="40">
									<div class="media-body">
											<div class="form-group mb-0" style="position:relative;">
												  <input class="form-control rounded-pill" placeholder="Add a comment..." style="padding-right: 90px;">
												  <div class="btn btn-primary btn-sm rounded-pill font-weight-bold px-3" style="position:absolute;top: 5px;right:6px;">Post</div>
											</div>
									</div>
								</div>
						</div>

						<div v-if="user.hasOwnProperty('id')" v-for="(reply, index) in results" :key="'replies:'+index" class="card card-body shadow-none border border-top-0">
							<div class="media">
								<img :src="reply.account.avatar" class="rounded-circle border mr-3" width="32px" height="32px">
									<div class="media-body">
										<div v-if="reply.sensitive == true">
											<span class="py-3">
												<a class="text-dark font-weight-bold mr-3"  style="font-size: 13px;" :href="profileUrl(reply)" v-bind:title="reply.account.username">{{trimCaption(reply.account.username,15)}}</a>
												<span class="text-break" style="font-size: 13px;">
													<span class="font-italic text-muted">This comment may contain sensitive material</span>
													<span class="text-primary cursor-pointer pl-1" @click="reply.sensitive = false;">Show</span>
												</span>
											</span>
										</div>
										<div v-else>
											<p class="d-flex justify-content-between align-items-top read-more mb-0" style="overflow-y: hidden;">
												<span class="mr-3" style="font-size: 13px;">
													<a class="text-dark font-weight-bold mr-1 text-break" :href="profileUrl(reply)" v-bind:title="reply.account.username">{{trimCaption(reply.account.username,15)}}</a>
													<span class="text-break comment-body" style="word-break: break-all;" v-html="reply.content"></span>
												</span>
												<span class="text-right" style="min-width: 30px;">
													<span v-on:click="likeReply(reply, $event)"><i v-bind:class="[reply.favourited ? 'fas fa-heart fa-sm text-danger':'far fa-heart fa-sm text-lighter']"></i></span>
													<span class="pl-2 text-lighter cursor-pointer" @click="ctxMenu(reply)">
														<span class="fas fa-ellipsis-v text-lighter"></span>
													</span>
												</span>
											</p>
											<p class="mb-0">
												<a v-once class="text-muted mr-3 text-decoration-none small" style="width: 20px;" v-text="timeAgo(reply.created_at)" :href="getStatusUrl(reply)"></a>
												<span v-if="reply.favourites_count" class="text-muted comment-reaction font-weight-bold mr-3 small">{{reply.favourites_count == 1 ? '1 like' : reply.favourites_count + ' likes'}}</span>
												<span class="small text-muted comment-reaction font-weight-bold cursor-pointer" v-on:click="replyFocus(reply, index, true)">Reply</span>
											</p>
										</div>
									</div>
							</div>
						</div> -->
					</div>
				</div>
			</div>
		</div>

		<div v-if="layout == 'text'" class="container px-0">
			<div class="row justify-content-center">
				<div class="col-12 col-md-6">
					<status-card :status="status" :hasTopBorder="true" />
					<comment-feed :status="status" class="mt-3" />
				</div>
			</div>
		</div>
	</div>

	<div class="modal-stack">
		<b-modal ref="likesModal"
			id="l-modal"
			hide-footer
			centered
			title="Likes"
			body-class="list-group-flush py-3 px-0">
			<div class="list-group">
				<div class="list-group-item border-0 py-1" v-for="(user, index) in likes" :key="'modal_likes_'+index">
					<div class="media">
						<a :href="user.url">
							<img class="mr-3 rounded-circle box-shadow" :src="user.avatar" :alt="user.username + '’s avatar'" width="30px" onerror="this.onerror=null;this.src='/storage/avatars/default.jpg';">
						</a>
						<div class="media-body">
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
					</div>
				</div>
				<infinite-loading @infinite="infiniteLikesHandler" spinner="spiral">
					<div slot="no-more"></div>
					<div slot="no-results"></div>
				</infinite-loading>
			</div>
		</b-modal>
		<b-modal ref="sharesModal"
			id="s-modal"
			hide-footer
			centered
			title="Shares"
			body-class="list-group-flush py-3 px-0">
			<div class="list-group">
				<div class="list-group-item border-0 py-1" v-for="(user, index) in shares" :key="'modal_shares_'+index">
					<div class="media">
						<a :href="user.url">
							<img class="mr-3 rounded-circle box-shadow" :src="user.avatar" :alt="user.username + '’s avatar'" width="30px">
						</a>
						<div class="media-body">
							<div class="d-inline-block">
								<p class="mb-0" style="font-size: 14px">
									<a :href="user.url" class="font-weight-bold text-dark">
										{{user.username}}
									</a>
								</p>
								<p class="text-muted mb-0" style="font-size: 14px">
										{{user.display_name}}
									</a>
								</p>
							</div>
							<p class="float-right"><!-- <a class="btn btn-primary font-weight-bold py-1" href="#">Follow</a> --></p>
						</div>
					</div>
				</div>
				<infinite-loading @infinite="infiniteSharesHandler" spinner="spiral">
					<div slot="no-more"></div>
					<div slot="no-results"></div>
				</infinite-loading>
			</div>
		</b-modal>
		<b-modal ref="lightboxModal"
			id="lightbox"
			:hide-header="true"
			:hide-footer="true"
			centered
			size="lg"
			body-class="p-0"
			>
			<div v-if="lightboxMedia" >
				<img :src="lightboxMedia.url" :class="lightboxMedia.filter_class + ' img-fluid'" style="min-height: 100%; min-width: 100%">
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
				<div class="form-group">
					<textarea class="form-control disabled text-monospace" rows="8" style="overflow-y:hidden;border: 1px solid #efefef; font-size: 12px; line-height: 18px; margin: 0 0 7px;resize:none;" v-model="ctxEmbedPayload" disabled=""></textarea>
				</div>
				<div class="form-group pl-2 d-flex justify-content-center">
					<div class="form-check mr-3">
						<input class="form-check-input" type="checkbox" v-model="ctxEmbedShowCaption" :disabled="ctxEmbedCompactMode == true">
						<label class="form-check-label font-weight-light">
							Show Caption
						</label>
					</div>
					<div class="form-check mr-3">
						<input class="form-check-input" type="checkbox" v-model="ctxEmbedShowLikes" :disabled="ctxEmbedCompactMode == true">
						<label class="form-check-label font-weight-light">
							Show Likes
						</label>
					</div>
					<div class="form-check">
						<input class="form-check-input" type="checkbox" v-model="ctxEmbedCompactMode">
						<label class="form-check-label font-weight-light">
							Compact Mode
						</label>
					</div>
				</div>
				<hr>
				<button :class="copiedEmbed ? 'btn btn-primary btn-block btn-sm py-1 font-weight-bold disabed': 'btn btn-primary btn-block btn-sm py-1 font-weight-bold'" @click="ctxCopyEmbed" :disabled="copiedEmbed">{{copiedEmbed ? 'Embed Code Copied!' : 'Copy Embed Code'}}</button>
				<p class="mb-0 px-2 small text-muted">By using this embed, you agree to our <a href="/site/terms">Terms of Use</a></p>
			</div>
		</b-modal>
		<b-modal ref="taggedModal"
			id="tagged-modal"
			hide-footer
			centered
			title="Tagged People"
			body-class="list-group-flush py-3 px-0">
			<div class="list-group">
				<div class="list-group-item border-0 py-1" v-for="(taguser, index) in status.taggedPeople" :key="'modal_taggedpeople_'+index">
					<div class="media">
						<a :href="'/'+taguser.username">
							<img class="mr-3 rounded-circle box-shadow" :src="taguser.avatar" :alt="taguser.username + '’s avatar'" width="30px">
						</a>
						<div class="media-body">
							<p class="pt-1 d-flex justify-content-between" style="font-size: 14px">
								<a :href="'/'+taguser.username" class="font-weight-bold text-dark">
									{{taguser.username}}
								</a>
								<button v-if="taguser.id == user.id" class="btn btn-outline-primary btn-sm py-1 px-3" @click="untagMe()">Untag Me</button>
							</p>
						</div>
					</div>
				</div>
			</div>
			<p class="mb-0 text-center small text-muted font-weight-bold"><a href="/site/kb/tagging-people">Learn more</a> about Tagging People.</p>
		</b-modal>
		<b-modal ref="ctxModal"
			id="ctx-modal"
			hide-header
			hide-footer
			centered
			rounded
			size="sm"
			body-class="list-group-flush p-0 rounded">
			<div class="list-group text-center">
				<!-- <div v-if="user && user.id != status.account.id && relationship && relationship.following" class="list-group-item rounded cursor-pointer font-weight-bold text-danger" @click="ctxMenuUnfollow()">Unfollow</div>
				<div v-if="user && user.id != status.account.id && relationship && !relationship.following" class="list-group-item rounded cursor-pointer font-weight-bold text-primary" @click="ctxMenuFollow()">Follow</div> -->
				<div v-if="status && status.local == true" class="list-group-item rounded cursor-pointer" @click="showEmbedPostModal()">Embed</div>
				<div class="list-group-item rounded cursor-pointer" @click="ctxMenuCopyLink()">Copy Link</div>
				<div v-if="status && user.id == status.account.id" class="list-group-item rounded cursor-pointer" @click="toggleCommentVisibility">{{ showComments ? 'Disable' : 'Enable'}} Comments</div>
				<a v-if="status && user.id == status.account.id" class="list-group-item rounded cursor-pointer text-dark text-decoration-none" :href="editUrl()">Edit</a>
				<div v-if="user && user.is_admin == true" class="list-group-item rounded cursor-pointer" @click="ctxModMenu()">Moderation Tools</div>
				<div v-if="status && user.id != status.account.id && !relationship.blocking && !user.is_admin" class="list-group-item rounded cursor-pointer text-danger" @click="blockProfile()">Block</div>
				<div v-if="status && user.id != status.account.id && relationship.blocking && !user.is_admin" class="list-group-item rounded cursor-pointer text-danger" @click="unblockProfile()">Unblock</div>
				<a v-if="user && user.id != status.account.id && !user.is_admin" class="list-group-item rounded cursor-pointer text-danger text-decoration-none" :href="reportUrl()">Report</a>
				<div v-if="status && user.id == status.account.id && status.visibility != 'archived'" class="list-group-item rounded cursor-pointer text-danger" @click="archivePost(status)">Archive</div>
				<div v-if="status && user.id == status.account.id && status.visibility == 'archived'" class="list-group-item rounded cursor-pointer text-danger" @click="unarchivePost(status)">Unarchive</div>
				<div v-if="status && (user.is_admin || user.id == status.account.id)" class="list-group-item rounded cursor-pointer text-danger" @click="deletePost(ctxMenuStatus)">Delete</div>
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
				<div class="list-group-item rounded cursor-pointer" @click="toggleCommentVisibility">{{ showComments ? 'Disable' : 'Enable'}} Comments</div>

				<div class="list-group-item rounded cursor-pointer" @click="moderatePost('unlist')">Unlist from Timelines</div>
				<div v-if="status.sensitive" class="list-group-item rounded cursor-pointer" @click="moderatePost('remcw')">Remove Content Warning</div>
				<div v-else class="list-group-item rounded cursor-pointer" @click="moderatePost('addcw')">Add Content Warning</div>
				<div class="list-group-item rounded cursor-pointer text-lighter" @click="ctxModMenuClose()">Cancel</div>
			</div>
		</b-modal>
		<b-modal ref="replyModal"
			id="ctx-reply-modal"
			hide-footer
			centered
			rounded
			:title-html="replyingToUsername ? 'Reply to <span class=text-dark>' + replyingToUsername + '</span>' : ''"
			title-tag="p"
			title-class="font-weight-bold text-muted"
			size="md"
			body-class="p-2 rounded">
			<div>
				<vue-tribute :options="tributeSettings">
					<textarea class="form-control" rows="4" style="border: none; font-size: 18px; resize: none; white-space: pre-wrap;outline: none;" placeholder="Reply here ..." v-model="replyText">
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
						<div class="custom-control custom-switch mr-3">
							<input type="checkbox" class="custom-control-input" id="replyModalCWSwitch" v-model="replySensitive">
							<label :class="[replySensitive ? 'custom-control-label font-weight-bold text-dark':'custom-control-label text-lighter']" for="replyModalCWSwitch">Mark as NSFW</label>
						</div>
						<!-- <select class="custom-select custom-select-sm my-0 mr-2">
							<option value="public" selected="">Public</option>
							<option value="unlisted">Unlisted</option>
							<option value="followers">Followers Only</option>
						</select> -->
						<button class="btn btn-primary btn-sm py-2 px-4 lead text-uppercase font-weight-bold" v-on:click.prevent="postReply()" :disabled="replyText.length == 0">
						{{replySending == true ? 'POSTING' : 'POST'}}
					</button>
					</div>
				</div>
			</div>
		</b-modal>
	</div>
</div>
</template>

<style type="text/css" scoped>
	.status-comments,
	.reactions {
		background: #fff;
	}
	.postPresenterContainer {
		background: #fff;
	}
	@media(min-width: 720px) {
		.postPresenterContainer {
			min-height: 600px;
		}
	}
	::-webkit-scrollbar {
			width: 0px;
			background: transparent;
	}
	.reply-btn {
		position: absolute;
		bottom: 12px;
		right: 20px;
		width: 60px;
		text-align: center;
		border-radius: 0 3px 3px 0;
	}
	.text-lighter {
		color:#B8C2CC !important;
	}
	.text-break {
		overflow-wrap: break-word;
	}
	.comments p {
		margin-bottom: 0;
	}
	.comment-reaction {
		font-size: 80%;
	}
	.show-reply-bar {
		display: inline-block;
		border-bottom: 1px solid #999;
		height: 0;
		margin-right: 16px;
		vertical-align: middle;
		width: 24px;
	}
	.comment-thread {
		margin-top: 1rem;
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
	@media (min-width: 1200px) {
		.container {
			max-width: 1100px;
		}
	}

</style>
<style type="text/css" scoped>
	.momentui .bg-dark {
		background: #000 !important;
	}
	.momentui .carousel.slide,
	.momentui .carousel-item {
		background: #000 !important;
	}
	.reply-btn[disabled] {
		opacity: .3;
		color: #3897f0;
	}
</style>

<script>
import VueTribute from 'vue-tribute';
import PollCard from './partials/PollCard.vue';
import CommentFeed from './partials/CommentFeed.vue';
import StatusCard from './partials/StatusCard.vue';

pixelfed.postComponent = {};

export default {

		props: [
			'status-id',
			'status-username',
			'status-template',
			'status-url',
			'status-profile-url',
			'status-avatar',
			'status-profile-id',
			'profile-layout',
			'profile-recent'
		],

		components: {
				VueTribute,
				PollCard,
				CommentFeed,
				StatusCard
		},

		data() {
				return {
						config: window.App.config,
						status: false,
						media: {},
						user: false,
						reactions: {
							liked: false,
							shared: false
						},
						likes: [],
						likesPage: 1,
						shares: [],
						sharesPage: 1,
						lightboxMedia: false,
						replyText: '',
						replyStatus: {},
						replySensitive: false,
						relationship: {},
						results: [],
						pagination: {},
						min_id: 0,
						max_id: 0,
						reply_to_profile_id: 0,
						thread: false,
						showComments: false,
						warning: false,
						loaded: false,
						loading: null,
						replyingToId: this.statusId,
						replyingToUsername: this.statusUsername,
						replyToIndex: 0,
						replySending: false,
						emoji: window.App.util.emoji,
						showReadMore: true,
						showCaption: true,
						ctxEmbedPayload: false,
						copiedEmbed: false,
						ctxEmbedShowCaption: true,
						ctxEmbedShowLikes: false,
						ctxEmbedCompactMode: false,
						layout: this.profileLayout,
						showProfileMorePosts: false,
						profileMorePosts: [],
						replySending: false,
						reactionBarLoading: true,
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
						},
						content: undefined
					}
		},
		watch: {
			ctxEmbedShowCaption: function (n,o) {
				if(n == true) {
					this.ctxEmbedCompactMode = false;
				}
				let mode = this.ctxEmbedCompactMode ? 'compact' : 'full';
				this.ctxEmbedPayload = window.App.util.embed.post(this.status.url, this.ctxEmbedShowCaption, this.ctxEmbedShowLikes, mode);
			},
			ctxEmbedShowLikes: function (n,o) {
				if(n == true) {
					this.ctxEmbedCompactMode = false;
				}
				let mode = this.ctxEmbedCompactMode ? 'compact' : 'full';
				this.ctxEmbedPayload = window.App.util.embed.post(this.status.url, this.ctxEmbedShowCaption, this.ctxEmbedShowLikes, mode);
			},
			ctxEmbedCompactMode: function (n,o) {
				if(n == true) {
					this.ctxEmbedShowCaption = false;
					this.ctxEmbedShowLikes = false;
				}
				let mode = this.ctxEmbedCompactMode ? 'compact' : 'full';
				this.ctxEmbedPayload = window.App.util.embed.post(this.status.url, this.ctxEmbedShowCaption, this.ctxEmbedShowLikes, mode);
			}
		},
		beforeMount() {
			this.layout = 'metro';
		},

		mounted() {
			this.fetchRelationships();
			if(localStorage.getItem('pf_metro_ui.exp.rm') == 'false') {
				this.showReadMore = false;
			} else {
				this.showReadMore = true;
			}
		},

		updated() {
			$('.carousel').carousel();
			$('[data-toggle="tooltip"]').tooltip();
			if(this.showReadMore == true) {
				window.pixelfed.readmore();
			}
		},

		methods: {
			reportUrl() {
				return '/i/report?type=post&id=' + this.status.id;
			},

			editUrl() {
				return this.status.url + '/edit';
			},

			timestampFormat() {
					let ts = new Date(this.status.created_at);
					return ts.toDateString() + ' · ' + ts.toLocaleTimeString();
			},

			fetchData() {
					let self = this;
					axios.get('/api/v2/profile/'+this.statusUsername+'/status/'+this.statusId)
						.then(response => {
								if(response.data.status.pf_type == 'poll') {
									self.layout = 'poll';
								}
								if(response.data.status.pf_type == 'text') {
									self.layout = 'text';
								}
								self.status = response.data.status;
								self.media = self.status.media_attachments;
								self.content = response.data.status.content;
								self.status.emojis.forEach(function(emoji) {
									let img = `<img draggable="false" class="emojione custom-emoji" alt="${emoji.shortcode}" title="${emoji.shortcode}" src="${emoji.url}" data-original="${emoji.url}" data-static="${emoji.static_url}" width="18" height="18" />`;
									self.content = self.content.replace(`:${emoji.shortcode}:`, img);
								});
								self.likesPage = 2;
								self.sharesPage = 2;
								self.showCaption = !response.data.status.sensitive;
								if(self.status.comments_disabled == false) {
									self.showComments = true;
									this.fetchComments();
								}
								this.loaded = true;

								if(this.profileRecent !== false) {
									setTimeout(function() {
										self.fetchProfilePosts();
									}, 3000);
								}
								setTimeout(function() {
									self.fetchState();
									document.querySelectorAll('.status-comment .postCommentsContainer .comment-body a').forEach(function(i, e) {
										i.href = App.util.format.rewriteLinks(i);
									});
								}, 500);
						}).catch(error => {
							swal('Oops!', 'An error occured, please try refreshing the page.', 'error');
						});
			},

			fetchState() {
				let self = this;
				axios.get('/api/v2/profile/'+this.statusUsername+'/status/'+this.statusId+'/state')
				.then(res => {
					self.user = res.data.user;
					window._sharedData.curUser = self.user;
					window.App.util.navatar();
					self.likes = res.data.likes;
					self.shares = res.data.shares;
					self.reactions = res.data.reactions;
					self.reactionBarLoading = false;
				});
			},

			likesModal() {
				if($('body').hasClass('loggedIn') == false) {
					window.location.href = '/login?next=' + encodeURIComponent('/p/' + this.status.shortcode);
					return;
				}
				if(this.likes.length) {
					this.$refs.likesModal.show();
					return;
				}
				axios.get('/api/v2/likes/profile/'+this.statusUsername+'/status/'+this.statusId)
				.then(res => {
					this.likes = res.data.data;
					this.$refs.likesModal.show();
				});
			},

			sharesModal() {
				if(this.status.reblogs_count == 0 || $('body').hasClass('loggedIn') == false) {
					window.location.href = '/login?next=' + encodeURIComponent('/p/' + this.status.shortcode);
					return;
				}
				if(this.shares.length) {
					this.$refs.sharesModal.show();
					return;
				}
				axios.get('/api/v2/shares/profile/'+this.statusUsername+'/status/'+this.statusId)
				.then(res => {
					this.shares = res.data.data;
					this.$refs.sharesModal.show();
				});
			},

			infiniteLikesHandler($state) {
				let api = '/api/v2/likes/profile/'+this.statusUsername+'/status/'+this.statusId;
				axios.get(api, {
					params: {
						page: this.likesPage,
					},
				}).then(({ data }) => {
					if (data.data.length > 0) {
						this.likes.push(...data.data);
						this.likesPage++;
						$state.loaded();
					} else {
						$state.complete();
					}
				});
			},

			infiniteSharesHandler($state) {
				axios.get('/api/v2/shares/profile/'+this.statusUsername+'/status/'+this.statusId, {
					params: {
						page: this.sharesPage,
					},
				}).then(({ data }) => {
					if (data.data.length > 0) {
						this.shares.push(...data.data);
						this.sharesPage++;
						$state.loaded();
					} else {
						$state.complete();
					}
				});
			},

			likeStatus(event) {
				if($('body').hasClass('loggedIn') == false) {
					window.location.href = '/login?next=' + encodeURIComponent(window.location.pathname);
					return;
				}

				axios.post('/i/like', {
					item: this.status.id
				}).then(res => {
					this.status.favourites_count = res.data.count;
					if(this.reactions.liked == true) {
						this.reactions.liked = false;
						let user = this.user.id;
						this.likes = this.likes.filter(function(like) {
							return like.id !== user;
						});
					} else {
						this.reactions.liked = true;
						let user = this.user;
						this.likes.unshift(user);
						setTimeout(function() {
							event.target.classList.add('animate__animated', 'animate__bounce');
						},100);
					}
				}).catch(err => {
					console.error(err);
					swal('Error', 'Something went wrong, please try again later.', 'error');
				});
				window.navigator.vibrate(200);
			},

			shareStatus() {
				if($('body').hasClass('loggedIn') == false) {
					window.location.href = '/login?next=' + encodeURIComponent(window.location.pathname);
					return;
				}

				axios.post('/i/share', {
					item: this.status.id
				}).then(res => {
					this.status.reblogs_count = res.data.count;
					if(this.reactions.shared == true) {
						this.reactions.shared = false;
						let user = this.user.id;
						this.shares = this.shares.filter(function(reaction) {
							return reaction.id !== user;
						});
					} else {
						this.reactions.shared = true;
						let user = this.user;
						this.shares.push(user);
					}
				}).catch(err => {
					console.error(err);
					swal('Error', 'Something went wrong, please try again later.', 'error');
				});
			},

			bookmarkStatus() {
				if($('body').hasClass('loggedIn') == false) {
					window.location.href = '/login?next=' + encodeURIComponent(window.location.pathname);
					return;
				}

				axios.post('/i/bookmark', {
					item: this.status.id
				}).then(res => {
					if(this.reactions.bookmarked == true) {
						this.reactions.bookmarked = false;
					} else {
						this.reactions.bookmarked = true;
					}
				}).catch(err => {
					swal('Error', 'Something went wrong, please try again later.', 'error');
				});
			},

			blockProfile() {
				if($('body').hasClass('loggedIn') == false) {
					return;
				}

				axios.post('/i/block', {
					type: 'user',
					item: this.status.account.id
				}).then(res => {
					this.$refs.ctxModal.hide();
					this.relationship.blocking = true;
					swal('Success', 'You have successfully blocked ' + this.status.account.acct, 'success');
				}).catch(err => {
					swal('Error', 'Something went wrong. Please try again later.', 'error');
				});
			},

			unblockProfile() {
				if($('body').hasClass('loggedIn') == false) {
					return;
				}

				axios.post('/i/unblock', {
					type: 'user',
					item: this.status.account.id
				}).then(res => {
					this.relationship.blocking = false;
					this.$refs.ctxModal.hide();
					swal('Success', 'You have successfully unblocked ' + this.status.account.acct, 'success');
				}).catch(err => {
					swal('Error', 'Something went wrong. Please try again later.', 'error');
				});
			},

			deletePost(status) {
				if(!this.ownerOrAdmin()) {
					return;
				}
				var result = confirm('Are you sure you want to delete this post?');
				if (result) {
						if($('body').hasClass('loggedIn') == false) {
						return;
						}
						axios.post('/i/delete', {
							type: 'status',
							item: this.status.id
						}).then(res => {
							swal('Success', 'You have successfully deleted this post', 'success');
							setTimeout(function() {
								window.location.href = '/';
							}, 3000);
						}).catch(err => {
							swal('Error', 'Something went wrong. Please try again later.', 'error');
						});
				}
			},

			owner() {
				return this.user.id === this.status.account.id;
			},

			admin() {
				return this.user.is_admin == true;
			},

			ownerOrAdmin() {
				return this.owner() || this.admin();
			},

			lightbox(src) {
				this.lightboxMedia = src;
				this.$refs.lightboxModal.show();
			},

			postReply() {
				let self = this;
				this.replySending = true;
				if(this.replyText.length == 0 ||
					this.replyText.trim() == '@'+this.status.account.acct) {
					self.replyText = null;
					$('textarea[name="comment"]').blur();
					return;
				}
				let data = {
					item: this.replyingToId,
					comment: this.replyText,
					sensitive: this.replySensitive
				}

				this.replyText = '';

				axios.post('/i/comment', data)
				.then(function(res) {
					let entity = res.data.entity;
					if(entity.in_reply_to_id == self.status.id) {
						if(self.layout == 'metro') {
							self.results.push(entity);
						} else {
							self.results.unshift(entity);
						}
						let elem = $('.status-comments')[0];
						elem.scrollTop = elem.clientHeight * 2;
					} else {
						if(self.replyToIndex >= 0) {
							let el = self.results[self.replyToIndex];
							el.replies.push(entity);
							el.reply_count = el.reply_count + 1;
						}
					}
					self.$refs.replyModal.hide();
					self.replySending = false;
				});
			},

			deleteComment(id, i) {
				axios.post('/i/delete', {
					type: 'comment',
					item: id
				}).then(res => {
					this.results.splice(i, 1);
				}).catch(err => {
					swal('Something went wrong!', 'Please try again later', 'error');
				});
			},

			deleteCommentReply(id, i, pi) {
				axios.post('/i/delete', {
					type: 'comment',
					item: id
				}).then(res => {
					this.results[pi].replies.splice(i, 1);
					--this.results[pi].reply_count;
				}).catch(err => {
					swal('Something went wrong!', 'Please try again later', 'error');
				});
			},

			l(e) {
				let len = e.length;
				if(len < 10) { return e; }
				return e.substr(0, 10)+'...';
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
			},

			fetchComments() {
					let url = '/api/v2/comments/'+this.statusProfileId+'/status/'+this.statusId;
					axios.get(url)
						.then(response => {
								let self = this;
								this.results = this.layout == 'metro' ?
									_.reverse(response.data.data) :
									response.data.data;
								this.pagination = response.data.meta.pagination;
								if(this.results.length > 0) {
									$('.load-more-link').removeClass('d-none');
								}
								$('.postCommentsLoader').addClass('d-none');
								$('.postCommentsContainer').removeClass('d-none');
								setTimeout(function() {
									document.querySelectorAll('.status-comment .postCommentsContainer .comment-body a').forEach(function(i, e) {
										i.href = App.util.format.rewriteLinks(i);
									});
								}, 500);
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

			loadMore(e) {
					e.preventDefault();
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
									this.results.unshift(res[i]);
								}
								this.pagination = response.data.meta.pagination;
								$('.load-more-link').removeClass('d-none');
						});
			},

			likeReply(status, $event) {
				if($('body').hasClass('loggedIn') == false) {
					swal('Login', 'Please login to perform this action.', 'info');
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

			truncate(str,lim) {
				return _.truncate(str,{
					length: lim
				});
			},

			timeAgo(ts) {
				return App.util.format.timeAgo(ts);
			},

			emojiReaction() {
				let em = event.target.innerText;
				if(this.replyText.length == 0) {
					this.reply_to_profile_id = this.status.account.id;
					this.replyText = em + ' ';
					$('textarea[name="comment"]').focus();
				} else {
					this.reply_to_profile_id = this.status.account.id;
					this.replyText += em + ' ';
					$('textarea[name="comment"]').focus();
				}
			},

			toggleCommentVisibility() {
				if(this.ownerOrAdmin() == false) {
					return;
				}

				let state = this.status.comments_disabled;
				let self = this;

				if(state == true) {
					// re-enable comments
					axios.post('/i/visibility', {
						item: self.status.id,
						disableComments: false
					}).then(function(res) {
							self.status.comments_disabled = false;
							self.$refs.ctxModal.hide();
							window.location.reload();
					}).catch(function(err) {
						return;
					});
				} else {
					// disable comments
					axios.post('/i/visibility', {
						item: self.status.id,
						disableComments: true
					}).then(function(res) {
						self.status.comments_disabled = true;
						self.showComments = false;
						self.$refs.ctxModal.hide();
					}).catch(function(err) {
						return;
					});
				}
			},

			fetchRelationships() {
				if(document.querySelectorAll('body')[0].classList.contains('loggedIn') == false) {
					this.fetchData();
					return;
				} else {
					axios.get('/api/pixelfed/v1/accounts/relationships', {
						params: {
							'id[]': this.statusProfileId
						}
					}).then(res => {
						if(res.data[0] == null) {
							this.fetchData();
							return;
						}
						this.relationship = res.data[0];
						if(res.data[0].blocking == true) {
							this.loaded = true;
							this.warning = true;
							return;
						} else {
							this.fetchData();
							return;
						}
					});
				}
			},

			visibilityModal() {
				switch(this.status.visibility) {
					case 'public':
						swal('Public Post', 'This post is visible to everyone.', 'info');
					break;

					case 'unlisted':
						swal('Unlisted Post', 'This post is visible on profiles and with a direct links. It is not displayed on timelines.', 'info');
					break;

					case 'private':
						swal('Private Post', 'This post is only visible to followers.', 'info');
					break;
				}
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

			redirect(url) {
				window.location.href = url;
			},

			showEmbedPostModal() {
				let mode = this.ctxEmbedCompactMode ? 'compact' : 'full';
				this.ctxEmbedPayload = window.App.util.embed.post(this.status.url, this.ctxEmbedShowCaption, this.ctxEmbedShowLikes, mode);
				this.$refs.ctxModal.hide();
				this.$refs.embedModal.show();
			},

			ctxCopyEmbed() {
				navigator.clipboard.writeText(this.ctxEmbedPayload);
				this.$refs.embedModal.hide();
			},

			permalinkUrl(reply, showOrigin = false) {
				let profile = reply.account;
				if(profile.local == true) {
					return reply.url;
				} else {
					return showOrigin ?
						reply.url :
						'/i/web/post/_/' + profile.id + '/' + reply.id;
				}
			},

			fetchProfilePosts() {
				if(!$('body').hasClass('loggedIn') && this.loaded) {
					return;
				}
				let self = this;
				let apiUrl = '/api/pixelfed/v1/accounts/' + this.statusProfileId + '/statuses';
				axios.get(apiUrl, {
					params: {
						only_media: true,
						min_id: 1,
						limit: 9
					}
				})
				.then(res => {
					let data = res.data.filter(function(status) {
						return status.media_attachments.length > 0 &&
						status.id != self.statusId &&
						status.sensitive == false
					});
					let ids = data.map(status => status.id);
					if(data.length >= 3) {
						self.showProfileMorePosts = true;
					}
					self.profileMorePosts = data.slice(0,6);
				})
			},

			previewUrl(status) {
				return status.sensitive ? '/storage/no-preview.png?v=' + new Date().getTime() : status.media_attachments[0].preview_url;
			},

			previewBackground(status) {
				let preview = this.previewUrl(status);
				return 'background-image: url(' + preview + ');';
			},

			getStatusUrl(status) {

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

			showTaggedPeopleModal() {
				if(!$('body').hasClass('loggedIn') && this.loaded) {
					return;
				}
				this.$refs.taggedModal.show();
			},

			untagMe() {
				this.$refs.taggedModal.hide();
				let id = this.user.id;
				axios.post('/api/local/compose/tag/untagme', {
					status_id: this.statusId,
					profile_id: id
				}).then(res => {
						this.status.taggedPeople = this.status.taggedPeople.filter(t => {
								return t.id != id;
						});
						swal('Untagged', 'You have been untagged from this post.', 'success');
				}).catch(err => {
						swal('An Error Occurred', 'Please try again later.', 'error');
				});
			},

			copyPostUrl() {
				navigator.clipboard.writeText(this.statusUrl);
				return;
			},

			moderatePost(action, $event) {
				let status = this.status;
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
									// this.feed = this.feed.filter(f => {
									//   return f.id != status.id;
									// });
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

			ctxMenu() {
				this.$refs.ctxModal.show();
				return;
			},

			closeCtxMenu(truncate) {
				this.$refs.ctxModal.hide();
			},

			ctxModMenu() {
				this.$refs.ctxModal.hide();
				this.$refs.ctxModModal.show();
			},

			ctxModMenuClose() {
				this.$refs.ctxModal.hide();
				this.$refs.ctxModModal.hide();
			},

			ctxMenuCopyLink() {
				let status = this.status;
				navigator.clipboard.writeText(status.url);
				this.closeCtxMenu();
				return;
			},

			ctxMenuFollow() {
				let id = this.status.account.id;
				axios.post('/i/follow', {
					item: id
				}).then(res => {
					let username = this.status.account.acct;
					this.relationship.following = true;
					this.$refs.ctxModal.hide();
					setTimeout(function() {
						swal('Follow successful!', 'You are now following ' + username, 'success');
					}, 500);
				});
			},

			ctxMenuUnfollow() {
				let id = this.status.account.id;
				axios.post('/i/follow', {
					item: id
				}).then(res => {
					let username = this.status.account.acct;
					this.relationship.following = false;
					this.$refs.ctxModal.hide();
					setTimeout(function() {
						swal('Unfollow successful!', 'You are no longer following ' + username, 'success');
					}, 500);
				});
			},

			archivePost(status) {
				if(window.confirm('Are you sure you want to archive this post?') == false) {
					return;
				}

				axios.post('/api/pixelfed/v2/status/' + status.id + '/archive')
				.then(res => {
					this.$refs.ctxModal.hide();
					window.location.href = '/';
				});
			},

			unarchivePost(status) {
				if(window.confirm('Are you sure you want to unarchive this post?') == false) {
					return;
				}

				axios.post('/api/pixelfed/v2/status/' + status.id + '/unarchive')
				.then(res => {
					this.$refs.ctxModal.hide();
				});
			},

			statusLike(s) {
				this.reactions.liked = !!this.reactions.liked;
			},

			trimCaption(caption, len = 60) {
				return _.truncate(caption, {
					length: len
				});
			},
		},
}
</script>
