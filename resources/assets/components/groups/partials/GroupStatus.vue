<template>
	<div class="status-card-component" :class="{ 'status-card-sm': loaded && size === 'small' }">
		 <div v-if="loaded" class="shadow-none mb-3">
			<div v-if="status.pf_type !== 'poll'" :class="{ 'border-top-0': !hasTopBorder }" class="card shadow-sm" style="border-radius: 18px !important;">
                <parent-unavailable
                    v-if="parentUnavailable == true"
                    :permalink-mode="permalinkMode"
                    :permalink-status="childContext"
                    :status="status"
                    :profile="profile"
                    :group-id="groupId"
                />
				<div v-else class="card-body pb-0">
                    <group-post-header
                        :group="group"
                        :status="status"
                        :profile="profile"
                        :showGroupHeader="showGroupHeader"
                        :showGroupChevron="showGroupChevron"
                        v-on:delete="statusDeleted"
                    />

					<div>
						<div>
							<div class="pl-2">

								<div v-if="status.sensitive && status.content.length" class="card card-body shadow-none border bg-light py-2 my-2 text-center user-select-none cursor-pointer" @click="status.sensitive = false">
									<div class="media justify-content-center align-items-center">
										<div class="mx-3">
											<i class="far fa-exclamation-triangle fa-2x text-lighter"></i>
										</div>
										<div class="media-body">
											<p class="font-weight-bold mb-0">Warning, may contain sensitive content. </p>
											<p class="mb-0 text-lighter small text-center font-weight-bold">Click to view</p>
										</div>
									</div>
								</div>

                                <template v-else>
								    <p v-html="renderedCaption" class="pt-2 text-break" style="font-size:15px;"></p>
                                </template>

								<photo-presenter
									v-if="status.pf_type === 'photo'"
									class="col px-0 border mb-4 rounded"
									:status="status"
									v-on:lightbox="showPostModal"
									v-on:togglecw="status.sensitive = false"
									@click="showPostModal"/>

								<video-presenter
									v-else-if="status.pf_type === 'video'"
									class="col px-0 border mb-4 rounded"
									:status="status"
									v-on:togglecw="status.sensitive = false" />

								<photo-album-presenter
									v-else-if="status.pf_type === 'photo:album'"
									class="col px-0 border mb-4 rounded"
									:status="status" v-on:lightbox="lightbox"
									v-on:togglecw="status.sensitive = false" />

								<video-album-presenter
									v-else-if="status.pf_type === 'video:album'"
									class="col px-0 border mb-4 rounded"
									:status="status"
									v-on:togglecw="status.sensitive = false" />

								<mixed-album-presenter
									v-else-if="status.pf_type === 'photo:video:album'"
									:status="status"
									class="col px-0 border mb-4 rounded"
									v-on:lightbox="lightbox"
									v-on:togglecw="status.sensitive = false" />

								<div v-if="status.favourites_count || status.reply_count" class="border-top my-0">
									<div class="d-flex justify-content-between py-2" style="font-size: 14px;">
										<button v-if="status.favourites_count" class="btn btn-light py-0 text-decoration-none text-dark" style="font-size: 12px; font-weight: 600;" @click="showLikesModal($event)">
											{{ status.favourites_count }} {{ status.favourites_count == 1 ? 'Like' : 'Likes' }}
										</button>

										<button v-if="status.reply_count" class="btn btn-light py-0 text-decoration-none text-dark" style="font-size: 12px; font-weight: 600;" @click="commentFocus($event)">
											{{ status.reply_count }} {{ status.reply_count == 1 ? 'Comment' : 'Comments' }}
										</button>
									</div>
								</div>

								<div v-if="profile" class="border-top my-0">
									<div class="d-flex justify-content-between py-2 px-4">
										<!-- <button class="btn btn-link py-0 text-decoration-none" :class="{ 'font-weight-bold': status.favourited, 'text-primary': status.favourited, 'text-muted': !status.favourited }"
												@click="likeStatus(status, $event);">
											<i class="far fa-heart mr-1">
											</i>
											{{ status.favourited ? 'Liked' : 'Like' }}
										</button> -->
										<div>
											<button :id="'lr__'+status.id" class="btn btn-link py-0 text-decoration-none" :class="{ 'font-weight-bold': status.favourited, 'text-primary': status.favourited, 'text-muted': !status.favourited }" @click="likeStatus(status, $event);">
												<i class="far fa-heart mr-1"></i>
												{{ status.favourited ? 'Liked' : 'Like' }}
											</button>
										</div>

										<button class="btn btn-link py-0 text-decoration-none text-muted" @click="commentFocus($event)">
											<i class="far fa-comment cursor-pointer text-muted mr-1">
											</i>
											Comment
										</button>

										<button class="btn btn-link py-0 text-decoration-none" disabled>
											<i class="fas fa-external-link-alt cursor-pointer text-muted mr-1">
											</i>
											Share
										</button>
									</div>
								</div>

								<comment-drawer
									v-if="showCommentDrawer"
									:permalink-mode="permalinkMode"
									:permalink-status="childContext"
									:status="status"
									:profile="profile"
									:group-id="groupId" />
							</div>
						</div>
					</div>
				</div>
			</div>

			<div v-else class="border">
				<poll-card :status="status" :profile="profile" v-on:status-delete="statusDeleted" :showBorder="false" />

				<div class="bg-white" style="padding: 0 1.25rem">
					<div v-if="profile" class="border-top my-0">
						<div class="d-flex justify-content-between py-2 px-4">
							<!-- <button class="btn btn-link py-0 text-decoration-none" :class="{ 'font-weight-bold': status.favourited, 'text-primary': status.favourited, 'text-muted': !status.favourited }" @click="likeStatus(status, $event);"> -->
							<div>
								<button :id="'lr__'+status.id" class="btn btn-link py-0 text-decoration-none" :class="{ 'font-weight-bold': status.favourited, 'text-primary': status.favourited, 'text-muted': !status.favourited }" @click="likeStatus(status, $event);">
									<i class="far fa-heart mr-1"></i>
									{{ status.favourited ? 'Liked' : 'Like' }}
								</button>

								<b-popover :target="'lr__'+status.id" triggers="hover" placement="top">
									<template #title>Popover Title</template>
									I am popover <b>component</b> content!
								</b-popover>
							</div>

							<button class="btn btn-link py-0 text-decoration-none text-muted" @click="commentFocus($event)">
								<i class="far fa-comment cursor-pointer text-muted mr-1"></i>
								Comment
							</button>

							<button class="btn btn-link py-0 text-decoration-none" disabled>
								<i class="fas fa-external-link-alt cursor-pointer text-muted mr-1">
								</i>
								Share
							</button>
						</div>
					</div>

					<comment-drawer
						v-if="showCommentDrawer"
						:permalink-mode="permalinkMode"
						:permalink-status="childContext"
						:profile="profile"
						:status="status"
						:group-id="groupId" />
				</div>
			</div>

			<!-- <div v-else class="card rounded-0 border-top-0 status-card card-md-rounded-0 shadow-none border">
				<div v-if="status" class="card-header d-inline-flex align-items-center bg-white">
					<div>
						<img class="rounded-circle box-shadow" :src="status.account.avatar" width="32px" height="32px" onerror="this.onerror=null;this.src='/storage/avatars/default.png?v=2'" alt="avatar">
					</div>
					<div class="pl-2">
						<a class="username font-weight-bold text-dark text-decoration-none text-break" v-bind:href="profileUrl(status)" v-html="statusCardUsernameFormat(status)">
							Loading...
						</a>
						<span v-if="status.account.is_admin" class="fa-stack" title="Admin Account" data-toggle="tooltip" style="height:1em; line-height:1em; max-width:19px;">
							<i class="fas fa-certificate text-danger fa-stack-1x"></i>
							<i class="fas fa-crown text-white fa-sm fa-stack-1x" style="font-size:7px;"></i>
						</span>
						<div class="d-flex align-items-center">
							<a v-if="status.place" class="small text-decoration-none text-muted" :href="'/discover/places/'+status.place.id+'/'+status.place.slug" title="Location" data-toggle="tooltip"><i class="fas fa-map-marked-alt"></i> {{status.place.name}}, {{status.place.country}}</a>
						</div>
					</div>
					<div class="text-right" style="flex-grow:1;">
						<button class="btn btn-link text-dark py-0" type="button" @click="ctxMenu()">
							<span class="fas fa-ellipsis-h text-lighter"></span>
							<span class="sr-only">Post Menu</span>
						</button>
					</div>
				</div>

				<div class="postPresenterContainer" style="background: #000;">

					<div v-if="status.pf_type === 'photo'" class="w-100">
						<photo-presenter
							:status="status"
							v-on:lightbox="lightbox"
							v-on:togglecw="status.sensitive = false"/>
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
					<div v-if="reactionBar" class="reactions my-1 pb-2">
						<h3 v-if="status.favourited" class="fas fa-heart text-danger pr-3 m-0 cursor-pointer" title="Like" v-on:click="likeStatus(status, $event);"></h3>
						<h3 v-else class="far fa-heart pr-3 m-0 like-btn text-dark cursor-pointer" title="Like" v-on:click="likeStatus(status, $event);"></h3>
						<h3 v-if="!status.comments_disabled" class="far fa-comment text-dark pr-3 m-0 cursor-pointer" title="Comment" v-on:click="commentFocus(status, $event)"></h3>
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

					<div v-if="status.liked_by.username && status.liked_by.username !== profile.username" class="likes mb-1">
						<span class="like-count">Liked by
							<a class="font-weight-bold text-dark" :href="status.liked_by.url">{{status.liked_by.username}}</a>
							<span v-if="status.liked_by.others == true">
								and <span class="font-weight-bold" v-if="status.liked_by.total_count_pretty">{{status.liked_by.total_count_pretty}}</span> <span class="font-weight-bold">others</span>
							</span>
						</span>
					</div>
					<div v-if="status.pf_type != 'text'" class="caption">
						<p v-if="!status.sensitive" class="mb-2 read-more" style="overflow: hidden;">
							<span class="username font-weight-bold">
								<bdi><a class="text-dark" :href="profileUrl(status)">{{status.account.username}}</a></bdi>
							</span>
							<span class="status-content" v-html="status.content"></span>
						</p>
					</div>
					<div class="timestamp mt-2">
						<p class="small mb-0">
							<a v-if="status.visibility != 'archived'" :href="statusUrl(status)" class="text-muted text-uppercase">
								<timeago :datetime="status.created_at" :auto-update="60" :converter-options="{includeSeconds:true}" :title="timestampFormat(status.created_at)" v-b-tooltip.hover.bottom></timeago>
							</a>
							<span v-else class="text-muted text-uppercase">
								Posted <timeago :datetime="status.created_at" :auto-update="60" :converter-options="{includeSeconds:true}" :title="timestampFormat(status.created_at)" v-b-tooltip.hover.bottom></timeago>
							</span>

							<span v-if="recommended">
								<span class="px-1">&middot;</span>
								<span class="text-muted">Based on popular and trending content</span>
							</span>
						</p>
					</div>
				</div>
			</div> -->

			<!-- <div v-else :class="{ 'border-top-0': !hasTopBorder }" class="card shadow-none border rounded-0">
				<div class="card-body pb-0">
					<div class="media">
						<img class="rounded-circle box-shadow mr-2" :src="status.account.avatar" width="42px" height="42px" onerror="this.onerror=null;this.src='/storage/avatars/default.png?v=2'" alt="avatar">
						<div class="media-body">
							<div class="pl-2 d-flex align-items-top">
								<div>
									<p class="mb-0">
										<a class="username font-weight-bold text-dark text-decoration-none text-break" v-bind:href="profileUrl(status)" v-html="statusCardUsernameFormat(status)">
											Loading...
										</a>
									</p>
									<p class="mb-0">
										<a class="font-weight-light text-muted small"
										   :href="statusUrl(status)">{{shortTimestamp(status.created_at)}}</a>
										<span class="text-lighter" style="padding-left:2px;padding-right:2px;">·</span>
										<span class="text-muted small"><i class="fas fa-globe"></i></span>
									</p>
								</div>
								<span class="text-right" style="flex-grow:1;">
									<button class="btn btn-link text-dark py-0" type="button" @click="ctxMenu()">
										<span class="fas fa-ellipsis-h text-lighter"></span>
										<span class="sr-only">Post Menu</span>
									</button>
								</span>
							</div>
						</div>
					</div>
					<div>
						<div>
							<div class="pl-2">

								<details v-if="status.sensitive">
									<summary class="mb-2 font-weight-bold text-muted">Content Warning</summary>
									<p v-html="status.content" class="pt-2 text-break status-content"></p>
								</details>

								<p v-else v-html="status.content" class="pt-2 text-break" style="font-size:15px;"></p>

								<div class="mb-1 row px-3">
									<photo-presenter
										v-if="status.pf_type === 'photo'"
										class="col px-0 border mb-4 rounded"
										:status="status"
										v-on:lightbox="lightbox"
										v-on:togglecw="status.sensitive = false"/>

									<video-presenter
										v-else-if="status.pf_type === 'video'"
										class="col px-0 border mb-4 rounded"
										:status="status"
										v-on:togglecw="status.sensitive = false" />

									<photo-album-presenter
										v-else-if="status.pf_type === 'photo:album'"
										class="col px-0 border mb-4 rounded"
										:status="status" v-on:lightbox="lightbox"
										v-on:togglecw="status.sensitive = false" />

									<video-album-presenter
										v-else-if="status.pf_type === 'video:album'"
										class="col px-0 border mb-4 rounded"
										:status="status"
										v-on:togglecw="status.sensitive = false" />

									<mixed-album-presenter
										v-else-if="status.pf_type === 'photo:video:album'"
										:status="status"
										class="col px-0 border mb-4 rounded"
										v-on:lightbox="lightbox"
										v-on:togglecw="status.sensitive = false" />
								</div>

								<div>
									<div class="pl-2">

										<div v-if="status.favourites_count || status.reply_count" class="border-top my-0">
											<div class="d-flex justify-content-between py-2" style="font-size: 14px;font-weight: 400;">
												<div v-if="status.favourites_count">
													{{ status.favourites_count }} Likes
												</div>

												<button v-if="status.reply_count" class="btn btn-link py-0 text-decoration-none text-dark" @click="commentFocus($event)">
													{{ status.reply_count }} Comments
												</button>
											</div>
										</div>

										<div class="border-top my-0">
											<div class="d-flex justify-content-between py-2 px-4">
												<button class="btn btn-link py-0 text-decoration-none" :class="{ 'font-weight-bold': status.favourited, 'text-primary': status.favourited, 'text-muted': !status.favourited }"
														@click="likeStatus(status, $event);">
													<i class="far fa-heart mr-1">
													</i>
													{{ status.favourited ? 'Liked' : 'Like' }}
												</button>

												<button class="btn btn-link py-0 text-decoration-none text-muted" @click="commentFocus($event)">
													<i class="far fa-comment cursor-pointer text-muted mr-1">
													</i>
													Comment
												</button>

												<button class="btn btn-link py-0 text-decoration-none" disabled>
													<i class="fas fa-external-link-alt cursor-pointer text-muted mr-1">
													</i>
													Share
												</button>
											</div>
										</div>

										<comment-drawer
											v-if="showCommentDrawer"
											:status="permalinkMode ? parentContext : status"
											:profile="profile"
											:permalink-mode="permalinkMode"
											:permalink-status="childContext"
											:group-id="groupId" />
									</div>
								</div>
							</div>
						</div>
					</div>
				</div>
			</div> -->

			<context-menu
				v-if="profile"
				ref="contextMenu"
				:status="status"
				:profile="profile"
				:group-id="groupId"
				v-on:status-delete="statusDeleted"
			/>

			<post-modal
				v-if="showModal"
				ref="modal"
				:status="status"
				:profile="profile"
				:groupId="groupId"
				/>

		</div>

		<div v-else class="card card-body shadow-none border mb-3" style="height: 200px;">
			<div class="w-100 h-100 d-flex justify-content-center align-items-center">
				<div class="spinner-border text-primary" role="status">
					<span class="sr-only">Loading...</span>
				</div>
			</div>
		</div>

		<!-- <b-modal
			v-if="likes && likes.length"
			ref="likeBox"
			title="Liked by"
			size="sm"
			centered
			hide-footer
			title="Likes"
			body-class="list-group-flush py-3 px-0">
			<div class="list-group">
				<div class="list-group-item border-0 py-1" v-for="(user, index) in likes" :key="'modal_likes_'+index+status.id">
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
				<infinite-loading v-if="likes && likes.length" @infinite="infiniteLikesHandler" spinner="spiral">
					<div slot="no-more"></div>
					<div slot="no-results"></div>
				</infinite-loading>
			</div>
		</b-modal> -->
	</div>
</template>

<script type="text/javascript">
	import CommentDrawer from './CommentDrawer.vue';
	import ContextMenu from './ContextMenu.vue';
	import PollCard from '~/partials/PollCard.vue';
	import MixedAlbumPresenter from '@/presenter/MixedAlbumPresenter.vue';
	import PhotoAlbumPresenter from '@/presenter/PhotoAlbumPresenter.vue';
	import PhotoPresenter from '@/presenter/PhotoPresenter.vue';
	import VideoAlbumPresenter from '@/presenter/VideoAlbumPresenter.vue';
	import VideoPresenter from '@/presenter/VideoPresenter.vue';
	import GroupPostModal from './GroupPostModal.vue';
    import { autoLink } from 'twitter-text';
    import ParentUnavailable from '@/groups/partials/Status/ParentUnavailable.vue';
    import GroupPostHeader from '@/groups/partials/Status/GroupHeader.vue';

	export default {
		props: {
			groupId: {
				type: String
			},

			group: {
				type: Object
			},

			profile: {
				type: Object
			},

			prestatus: {
				type: Object
			},

			recommended: {
				type: Boolean,
				default: false
			},

			reactionBar: {
				type: Boolean,
				default: true
			},

			hasTopBorder: {
				type: Boolean,
				default: true
			},

			size: {
				type: String,
				validator: (val) => ['regular', 'small'].includes(val),
				default: 'regular'
			},

			permalinkMode: {
				type: Boolean,
				default: false
			},

			showGroupChevron: {
				type: Boolean,
				default: false
			},

			showGroupHeader: {
				type: Boolean,
				default: false
			}
		},

		components: {
			"comment-drawer": CommentDrawer,
			"context-menu": ContextMenu,
			"poll-card": PollCard,
			"mixed-album-presenter": MixedAlbumPresenter,
			"photo-album-presenter": PhotoAlbumPresenter,
			"photo-presenter": PhotoPresenter,
			"video-album-presenter": VideoAlbumPresenter,
			"video-presenter": VideoPresenter,
			"post-modal": GroupPostModal,
            "parent-unavailable": ParentUnavailable,
            "group-post-header": GroupPostHeader
		},

		data() {
			return {
				config: window.App.config,
				status: {},
				loaded: false,
				replies: [],
				replyId: null,
				lightboxMedia: false,
				showSuggestions: true,
				showReadMore: true,
				replyStatus: {},
				replyText: '',
				replyNsfw: false,
				emoji: window.App.util.emoji,
				commentDrawerKey: 0,
				showCommentDrawer: false,
				parentContext: undefined,
				childContext: undefined,
				parentUnavailable: undefined,
				showModal: false,
				likes: [],
				likesPage: 1,
				openLikesModal: false,
			}
		},

        computed: {
            renderedCaption: {
                get() {
                    if(this.prestatus) {
                        const gid = this.prestatus.gid;
                        if(this.prestatus.content == null) {
                            return ""
                        }
                        return autoLink(
                            this.prestatus.content,
                            {
                                hashtagUrlBase: App.config.site.url + `/groups/${gid}/topics/`,
                                usernameUrlBase: App.config.site.url + `/groups/${gid}/username/`
                            }
                        )
                    }

                    return this.prestatus.content;
                }
            }
        },

		mounted() {
			this.status = this.prestatus;
			let self = this;
			setTimeout(() => {
				if(this.permalinkMode == true && this.prestatus.in_reply_to_id) {
					self.childContext = self.status;
					axios.get('/api/v0/groups/status', {
						params: {
							gid: self.groupId,
							sid: self.status.in_reply_to_id
						}
					}).then(res => {
						self.status = res.data;
						self.parentUnavailable = false;
						self.showCommentDrawer = true;
						self.parentContext = res.data;
						self.loaded = true;
					}).catch(err => {
						self.status = this.prestatus;
						self.parentUnavailable = true;
						self.showCommentDrawer = true;
						self.parentContext = this.prestatus;
						self.loaded = true;
					});
				} else {
					self.parentUnavailable = false;
					self.showCommentDrawer = false;
					self.loaded = true;
				}
			}, 100);
		},

		methods: {
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

			timestampFormat(timestamp) {
				let ts = new Date(timestamp);
				return ts.toDateString() + ' ' + ts.toLocaleTimeString();
			},

			shortTimestamp(ts) {
				return window.App.util.format.timeAgo(ts);
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

			lightbox(status) {
				window.location.href = status.media_attachments[0].url;
			},

			labelRedirect(type) {
				let url = '/i/redirect?url=' + encodeURI(this.config.features.label.covid.url);
				window.location.href = url;
			},

			likeStatus(status, event) {
				event.currentTarget.blur();
				let count = status.favourites_count;
                let state = status.favourited ? 'unlike' : 'like';
				axios.post('/api/v0/groups/status/' + state, {
					sid: status.id,
					gid: this.groupId
				}).then(res => {
					status.favourited = state;
					status.favourites_count = state? count + 1 : count - 1;
					status.favourited = state;
					if(status.favourited) {
						setTimeout(function() {
							event.target.classList.add('animate__animated', 'animate__bounce');
						},100);
					}
				}).catch(err => {
					if(err.response.status == 422) {
						swal('Error', err.response.data.error, 'error');
					} else {
						swal('Error', 'Something went wrong, please try again later.', 'error');
					}
				});
				window.navigator.vibrate(200);
			},

			commentFocus($event) {
				$event.target.blur();
				this.showCommentDrawer = !this.showCommentDrawer;
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

			owner(status) {
				return this.profile.id === status.account.id;
			},

			admin() {
				return this.profile.is_admin == true;
			},

			ownerOrAdmin(status) {
				return this.owner(status) || this.admin();
			},

			ctxMenu() {
				this.$refs.contextMenu.open();
			},

			timeAgo(ts) {
				return App.util.format.timeAgo(ts);
			},

			statusDeleted(status) {
				this.$emit('status-delete');
			},

			showPostModal() {
				this.showModal = true;
				this.$refs.modal.init();
			},

			showLikesModal(event) {
				if(event && event.hasOwnProperty('currentTarget')) {
					event.currentTarget().blur();
				}

				this.$emit('likes-modal');
				return;

				if(this.likes.length) {
					this.$refs.likeBox.show();
					return;
				}

				axios.get('/api/v0/groups/'+this.groupId+'/likes/'+this.status.id)
				.then(res => {
					this.likes = res.data.data;
					this.$refs.likeBox.show();
				});
			},

			infiniteLikesHandler($state) {
				axios.get('/api/v0/groups/'+this.groupId+'/likes/'+this.status.id, {
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
		}
	}
</script>

<style lang="scss">
	.status-card-component {
		.status-content {
			font-size: 17px;
		}

		&.status-card-sm {
			.status-content {
				font-size: 14px;
			}

			.fa-lg {
				font-size: unset;
				line-height: unset;
				vertical-align: unset;
			}
		}
	}

	.reaction-bar {
		width: auto;
		max-width: unset;
		left: -50px !important;
		border: 1px solid #F3F4F6 !important;

		.popover-body {
			padding: 2px;
		}

		.arrow {
			display: none;
		}

		img {
			width: 48px;
		}
	}
</style>
