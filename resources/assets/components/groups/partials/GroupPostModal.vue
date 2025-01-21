<template>
	<div class="group-post-modal">
		<b-modal
			ref="modal"
			size="xl"
			hide-footer
			hide-header
			centered
			body-class="gpm p-0">
			<div class="d-flex">
				<div class="gpm-media">
					<img :src="status.media_attachments[0].preview_url">
				</div>
				<div class="p-3" style="width: 30%;">
					<div class="media align-items-center mb-2">
						<a :href="status.account.url">
							<img class="rounded-circle media-avatar border mr-2" :src="status.account.avatar" width="32" height="32">
						</a>
						<div class="media-body">
							<div class="media-body-comment">
								<p class="media-body-comment-username mb-n1">
									<a :href="status.account.url" class="text-dark text-decoration-none font-weight-bold">
										{{status.account.acct}}
									</a>
								</p>
								<p class="media-body-comment-timestamp mb-0">
									<a class="font-weight-light text-muted small" :href="status.url">
										{{shortTimestamp(status.created_at)}}
									</a>
									<span class="text-lighter" style="padding-left:2px;padding-right:2px;">·</span>
									<span class="text-muted small"><i class="fas fa-globe"></i></span>
								</p>
							</div>
						</div>
						<div class="text-right" style="flex-grow:1;">
							<button class="btn btn-link text-dark py-0" type="button">
								<span class="fas fa-ellipsis-h text-lighter"></span>
								<span class="sr-only">Post Menu</span>
							</button>
						</div>
					</div>

					<read-more :status="status" />

					<div class="border-top border-bottom mt-3">
						<div class="d-flex justify-content-between" style="padding: 8px 5px">
							<button class="btn btn-link py-0 text-decoration-none btn-sm" :class="{ 'font-weight-bold': status.favourited, 'text-primary': status.favourited, 'text-muted': !status.favourited }">
								<i class="far fa-heart mr-1"></i>
								{{ status.favourited ? 'Liked' : 'Like' }}
							</button>

							<button class="btn btn-link py-0 text-decoration-none btn-sm text-muted">
								<i class="far fa-comment cursor-pointer text-muted mr-1"></i>
								Comment
							</button>

							<button class="btn btn-link py-0 text-decoration-none btn-sm" disabled>
								<i class="fas fa-external-link-alt cursor-pointer text-muted mr-1"></i>
								Share
							</button>
						</div>
					</div>

					<!-- <comment-drawer
						:profile="profile"
						:status="status"
						:group-id="groupId" /> -->
				</div>
			</div>
		</b-modal>
	</div>
</template>

<script type="text/javascript">
	import ReadMore from './ReadMore.vue';
	import CommentDrawer from './CommentDrawer.vue';

	export default {
		props: {
			groupId: {
				type: String
			},

			status: {
				type: Object
			},

			profile: {
				type: Object
			}
		},

		components: {
			"read-more": ReadMore,
			"comment-drawer": CommentDrawer
		},

		data() {
			return {
				loaded: false
			}
		},

		mounted() {
			this.init();
		},

		methods: {
			init() {
				this.loaded = true;
				this.$refs.modal.show();
			},

			shortTimestamp(ts) {
				return window.App.util.format.timeAgo(ts);
			},
		}
	}
</script>

<style lang="scss">
	.gpm {
		&-media {
			display: flex;
			width: 70%;

			img {
				width: 100%;
				height: auto;
				max-height: 70vh;
				object-fit: contain;
				background-color: #000;
			}

		}

		.comment-drawer-component {
			.my-3 {
				max-height: 46vh;
				overflow: auto;
			}
		}

		.cdrawer-reply-form {
			position: absolute;
			bottom: 0;
			margin-bottom: 1rem !important;
			min-width: 310px;
		}
	}

</style>
