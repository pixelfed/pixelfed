<template>
	<div>
		<div class="card rounded-0 border-top-0 status-card card-md-rounded-0 shadow-none border">
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
						<a :href="statusUrl(status)" class="text-muted text-uppercase">
							<timeago :datetime="status.created_at" :auto-update="60" :converter-options="{includeSeconds:true}" :title="timestampFormat(status.created_at)" v-b-tooltip.hover.bottom></timeago>
						</a>
						<span v-if="recommended">
							<span class="px-1">&middot;</span>
							<span class="text-muted">Based on popular and trending content</span>
						</span>
					</p>
				</div>
			</div>
		</div>

		<context-menu
			ref="contextMenu"
			:status="status"
			:profile="profile"
			v-on:status-delete="statusDeleted"
		/>
	</div>
</template>

<script type="text/javascript">
	import ContextMenu from './ContextMenu.vue';

	export default {
		props: {
			status: {
				type: Object
			},

			recommended: {
				type: Boolean,
				default: false
			},

			reactionBar: {
				type: Boolean,
				default: true
			}
		},

		components: {
			"context-menu": ContextMenu
		},

		data() {
			return {
				config: window.App.config,
				profile: {},
				loading: true,
				replies: [],
				replyId: null,
				lightboxMedia: false,
				showSuggestions: true,
				showReadMore: true,
				replyStatus: {},
				replyText: '',
				replyNsfw: false,
				emoji: window.App.util.emoji,
			}
		},

		mounted() {
			this.profile = window._sharedData.curUser;
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

			commentFocus(status, $event) {
				this.$emit('comment-focus', status);
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
				this.$emit('status-delete', status);
			}
		}
	}
</script>
