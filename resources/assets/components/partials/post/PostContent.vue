<template>
	<div class="timeline-status-component-content">
		<div v-if="status.pf_type === 'poll'" class="postPresenterContainer" style="background: #000;">
		</div>

		<div v-else-if="!fixedHeight" class="postPresenterContainer" style="background: #000;">
			<div v-if="status.pf_type === 'photo'" class="w-100">
				<photo-presenter
					:status="status"
					v-on:lightbox="toggleLightbox"
					v-on:togglecw="status.sensitive = false"/>
			</div>

			<div v-else-if="status.pf_type === 'video'" class="w-100">
				<video-presenter :status="status" v-on:togglecw="status.sensitive = false"></video-presenter>
			</div>

			<div v-else-if="status.pf_type === 'photo:album'" class="w-100">
				<photo-album-presenter :status="status" v-on:lightbox="toggleLightbox" v-on:togglecw="status.sensitive = false"></photo-album-presenter>
			</div>

			<div v-else-if="status.pf_type === 'video:album'" class="w-100">
				<video-album-presenter :status="status" v-on:togglecw="status.sensitive = false"></video-album-presenter>
			</div>

			<div v-else-if="status.pf_type === 'photo:video:album'" class="w-100">
				<mixed-album-presenter :status="status" v-on:lightbox="toggleLightbox" v-on:togglecw="status.sensitive = false"></mixed-album-presenter>
			</div>
		</div>

		<div v-else class="card-body p-0">
			<div v-if="status.pf_type === 'photo'" :class="{ fixedHeight: fixedHeight }">
				<div v-if="status.sensitive == true" class="content-label-wrapper">
					<div class="text-light content-label">
						<p class="text-center">
							<i class="far fa-eye-slash fa-2x"></i>
						</p>
						<p class="h4 font-weight-bold text-center">
							{{ $t('common.sensitiveContent') }}
						</p>
						<p class="text-center py-2 content-label-text">
							{{ status.spoiler_text ? status.spoiler_text : $t('common.sensitiveContentWarning') }}
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
						:hash="status.media_attachments[0].blurhash"
						/>
				</div>
				<div
					v-else
					@click.prevent="toggleLightbox"
					class="content-label-wrapper"
					style="position: relative;width:100%;height: 400px;overflow: hidden;z-index:1"
					>

					<img
                        :src="status.media_attachments[0].url"
                        style="position: absolute;width: 105%;height: 410px;object-fit: cover;z-index: 1;top:0;left:0;filter: brightness(0.35) blur(6px);margin:-5px;">

					<!-- <blur-hash-canvas
						v-if="status.media_attachments[0].blurhash && status.media_attachments[0].blurhash != 'U4Rfzst8?bt7ogayj[j[~pfQ9Goe%Mj[WBay'"
						:key="key"
						width="32"
						height="32"
						:punch="1"
						:hash="status.media_attachments[0].blurhash"
						style="position: absolute;width: 105%;height: 410px;object-fit: cover;z-index: 1;top:0;left:0;filter: brightness(0.35);"
						/> -->

					<blur-hash-image
						:key="key"
						width="32"
						height="32"
						:punch="1"
						:hash="status.media_attachments[0].blurhash"
						:src="status.media_attachments[0].url"
						class="blurhash-wrapper"
                        :alt="status.media_attachments[0].description"
                        :title="status.media_attachments[0].description"
						style="width: 100%;position: absolute;z-index:9;top:0:left:0"
						/>

					<p v-if="!status.sensitive && sensitive"
						@click="status.sensitive = true"
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

			<template v-else-if="status.pf_type === 'video'">
				<div v-if="status.sensitive == true" class="content-label-wrapper">
					<div class="text-light content-label">
						<p class="text-center">
							<i class="far fa-eye-slash fa-2x"></i>
						</p>
						<p class="h4 font-weight-bold text-center">
							Sensitive Content
						</p>
						<p class="text-center py-2 content-label-text">
							{{ status.spoiler_text ? status.spoiler_text : 'This post may contain sensitive content.'}}
						</p>
						<p class="mb-0">
							<button @click="status.sensitive = false" class="btn btn-outline-light btn-block btn-sm font-weight-bold">See Post</button>
						</p>
					</div>
				</div>
				<video v-else class="card-img-top shadow" :class="{ fixedHeight: fixedHeight }" style="border-radius:15px;object-fit: contain;background-color: #000;" controls :poster="getPoster(status)">
					<source :src="status.media_attachments[0].url" :type="status.media_attachments[0].mime">
				</video>
			</template>

			<div v-else-if="status.pf_type === 'photo:album'" class="card-img-top shadow" style="border-radius: 15px;">
				<photo-album-presenter :status="status" v-on:lightbox="toggleLightbox" v-on:togglecw="toggleContentWarning()" style="border-radius:15px !important;object-fit: contain;background-color: #000;overflow: hidden;" :class="{ fixedHeight: fixedHeight }"/>
			</div>

			<div v-else-if="status.pf_type === 'photo:video:album'" class="card-img-top shadow" style="border-radius: 15px;">
				<mixed-album-presenter :status="status" v-on:lightbox="toggleLightbox" v-on:togglecw="status.sensitive = false" style="border-radius:15px !important;object-fit: contain;background-color: #000;overflow: hidden;align-items:center" :class="{ fixedHeight: fixedHeight }"></mixed-album-presenter>
			</div>

			<div v-else-if="status.pf_type === 'text'">
                <div v-if="status.sensitive" class="border m-3 p-5 rounded-lg">
                    <p class="text-center">
                        <i class="far fa-eye-slash fa-2x"></i>
                    </p>
                    <p class="text-center lead font-weight-bold mb-0">Sensitive Content</p>
                    <p class="text-center">{{ status.spoiler_text && status.spoiler_text.length ? status.spoiler_text : 'This post may contain sensitive content' }}</p>
                    <p class="text-center mb-0">
                        <button class="btn btn-primary btn-sm font-weight-bold" @click="status.sensitive = false">See post</button>
                    </p>
                </div>
            </div>

			<div v-else class="bg-light rounded-lg d-flex align-items-center justify-content-center" style="height: 400px;">
				<div>
					<p class="text-center">
						<i class="fas fa-exclamation-triangle fa-4x"></i>
					</p>

					<p class="lead text-center mb-0">
						Cannot display post
					</p>

					<p class="small text-center mb-0">
						<!-- <a class="font-weight-bold primary" href="#">Report issue</a> -->
						{{status.pf_type}}:{{status.id}}
					</p>
				</div>
			</div>
		</div>

		<div
			v-if="status.content && !status.sensitive"
			class="card-body status-text"
			:class="[ status.pf_type === 'text' ? 'py-0' : 'pb-0']">
			<p>
				<read-more :status="status" :cursor-limit="300"/>
			</p>
			<!-- <p v-html="status.content_text || status.content">
			</p> -->
		</div>
	</div>
</template>

<script type="text/javascript">
	import BigPicture from 'bigpicture';
	import ReadMore from './ReadMore.vue';

	export default {
		props: ['status'],

		components: {
			"read-more": ReadMore,
		},

		data() {
			return {
				key: 1,
				sensitive: false,
			};
		},

		computed: {
			fixedHeight: {
				get() {
					return this.$store.state.fixedHeight == true;
				}
			}
		},

		methods: {
			toggleLightbox(e) {
				BigPicture({
					el: e.target
				})
			},

			toggleContentWarning() {
				this.key++;
				this.sensitive = true;
				this.status.sensitive = !this.status.sensitive;
			},

            getPoster(status) {
                let url = status.media_attachments[0].preview_url;
                if(url.endsWith('no-preview.jpg') || url.endsWith('no-preview.png')) {
                    return;
                }
                return url;
            }
		}
	}
</script>
