<template>
	<div class="feed-media-container bg-black">
		<div class="text-muted" style="max-height: 400px;">
			<div>
				<div v-if="post.pf_type === 'photo'">
					<div v-if="post.sensitive == true" class="content-label-wrapper">
						<div class="text-light content-label">
							<p class="text-center">
								<i class="far fa-eye-slash fa-2x"></i>
							</p>
							<p class="h4 font-weight-bold text-center">
								{{ $t('common.sensitiveContent') }}
							</p>
							<p class="text-center py-2 content-label-text">
								{{ post.spoiler_text ? post.spoiler_text : $t('common.sensitiveContentWarning') }}
							</p>
							<p class="mb-0">
								<button class="btn btn-outline-light btn-block btn-sm font-weight-bold" @click="toggleContentWarning()">See Post</button>
							</p>
						</div>

						<blur-hash-image
						width="32"
						height="32"
						:punch="1"
						class="blurhash-wrapper"
						:hash="post.media_attachments[0].blurhash"
						/>
					</div>

					<div v-else class="content-label-wrapper">
						<blur-hash-image
							:key="key"
							width="32"
							height="32"
							:punch="1"
							:hash="post.media_attachments[0].blurhash"
							:src="post.media_attachments[0].url"
							class="blurhash-wrapper"
							/>

						<p v-if="!post.sensitive && sensitive"
							@click="post.sensitive = true"
							style="
							margin-top: 0;
							padding: 10px;
							color: #000;
							font-size: 10px;
							text-align: right;
							position: absolute;
							top: 0;
							right: 0;
							border-radius: 11px;
							cursor: pointer;
							background: rgba(255, 255, 255,.5);
							">
						<i class="fas fa-eye-slash fa-lg"></i>
						</p>
					</div>
				</div>

				<!-- <div v-else-if="post.pf_type === 'photo:album'">
					<img :src="media[mediaIndex].url" style="width: 100%;height: 500px;object-fit: contain;">

					<div class="d-flex mt-3 justify-content-center">
						<div
							v-for="(thumb, index) in media"
							class="mr-2 border rounded p-1"
							:class="[ index === mediaIndex ? 'border-light' : 'border-dark' ]"
							@click="mediaIndex = index">
							<img :src="thumb.preview_url" width="60" height="40" style="object-fit:cover;">
						</div>
					</div>
				</div> -->
				<!-- <photo-album-presenter :status="post" v-on:togglecw="post.sensitive = false"/> -->

				<!-- <video-presenter v-else-if="post.pf_type === 'video'" :status="post" v-on:togglecw="post.sensitive = false" /> -->

			</div>
		</div>
	</div>
</template>

<script type="text/javascript">
	export default {
		props: {
			post: {
				type: Object
			},

			profile: {
				type: Object
			},

			user: {
				type: Object
			},

			media: {
				type: Array
			},

			showArrows: {
				type: Boolean,
				default: true
			}
		},

		data() {
			return {
				loading: false,
				shortcuts: undefined,
				sensitive: false,
				mediaIndex: 0
			}
		},

		mounted() {
			this.initShortcuts();
		},

		beforeDestroy() {
			document.removeEventListener('keyup', this.shortcuts);
		},

		methods: {
			navPrev() {
				// event.currentTarget.blur();
				if(this.mediaIndex == 0) {
					this.loading = true;
					axios.get('/api/v1/accounts/' + this.profile.id + '/statuses', {
						params: {
							limit: 1,
							max_id: this.post.id
						}
					}).then(res => {
						if(!res.data.length) {
							this.mediaIndex = this.media.length - 1;
							this.loading = false;
							return;
						}
						this.$emit('navigate', res.data[0]);
						this.mediaIndex = 0;
						// this.post = res.data[0];
						// this.media = this.post.media_attachments;
						// this.fetchState(this.post.account.username, this.post.id);
						// this.loading = false;
						let url = window.location.origin + `/@${this.post.account.username}/post/${this.post.id}`;
						history.pushState(null, null, url);
					}).catch(err => {
						this.mediaIndex = this.media.length - 1;
						this.loading = false;
					});
					return;
				}
				this.mediaIndex--;
			},

			navNext() {
				// event.currentTarget.blur();
				if(this.mediaIndex == this.media.length - 1) {
					this.loading = true;
					axios.get('/api/v1/accounts/' + this.profile.id + '/statuses', {
						params: {
							limit: 1,
							min_id: this.post.id
						}
					}).then(res => {
						if(!res.data.length) {
							this.mediaIndex = 0;
							this.loading = false;
							return;
						}
						this.$emit('navigate', res.data[0]);
						this.mediaIndex = 0;
						// this.post = res.data[0];
						// this.media = this.post.media_attachments;
						// this.fetchState(this.post.account.username, this.post.id);
						// this.loading = false;
						let url = window.location.origin + `/@${this.post.account.username}/post/${this.post.id}`;
						history.pushState(null, null, url);
					}).catch(err => {
						this.mediaIndex = 0;
						this.loading = false;
					});
					return;
				}
				this.mediaIndex++;
			},

			initShortcuts() {
				this.shortcuts = document.addEventListener('keyup', event => {
					if (event.key === 'ArrowLeft') {
						this.navPrev();
					}

					if (event.key === 'ArrowRight') {
						this.navNext();
					}
				});
			},
		}
	}
</script>

<style lang="scss">
	.feed-media-container {

		.blurhash-wrapper {
			img {
				border-radius:15px;
				max-height: 400px;
				object-fit: contain;
				background-color: #000;
			}

			canvas {
				border-radius: 15px;
				max-height: 400px;
			}
		}

		.content-label-wrapper {
			position: relative;
		}

		.content-label {
			margin: 0;
			position: absolute;
			top:0;
			left:0;
			display: flex;
			flex-direction: column;
			align-items: center;
			justify-content: center;
			width: 100%;
			height: 400px;
			z-index: 2;
			border-radius: 15px;
			background: rgba(0, 0, 0, 0.2)
		}
	}
</style>
