<template>
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
				<button @click="toggleContentWarning()" class="btn btn-outline-light btn-block btn-sm font-weight-bold">See Post</button>
			</p>
		</div>
		<blur-hash-image
			width="32"
			height="32"
			:punch="1"
			:hash="status.media_attachments[0].blurhash"
			:alt="altText(status)"/>
	</div>
	<div v-else class="embed-responsive embed-responsive-16by9">
		<video class="video" controls playsinline preload="metadata" loop :data-id="status.id" :poster="poster()">
			<source :src="status.media_attachments[0].url" :type="status.media_attachments[0].mime">
		</video>
	</div>
</template>

<style type="text/css" scoped>
  .content-label-wrapper {
  	position: relative;
  }
  .content-label {
  	margin: 0;
  	position: absolute;
  	top:50%;
  	left:50%;
  	transform: translate(-50%, -50%);
  	display: flex;
  	flex-direction: column;
  	align-items: center;
  	justify-content: center;
  	width: 100%;
  	height: 100%;
  	z-index: 2;
  	background: rgba(0, 0, 0, 0.2)
  }
</style>

<script type="text/javascript">
	export default {
		props: ['status'],

		methods: {
			altText(status) {
				let desc = status.media_attachments[0].description;
				if(desc) {
					return desc;
				}

				return 'Video was not tagged with any alt text.';
			},

			playOrPause(e) {
				let el = e.target;
				if(el.getAttribute('playing') == 1) {
					el.removeAttribute('playing');
					el.pause();
				} else {
					el.setAttribute('playing', 1);
					el.play();
				}
			},

			toggleContentWarning(status) {
				this.$emit('togglecw');
			},

            poster() {
                let url = this.status.media_attachments[0].preview_url;
                if(url.endsWith('no-preview.jpg') || url.endsWith('no-preview.png')) {
                    return;
                }
                return url;
            }
		}
	}
</script>
