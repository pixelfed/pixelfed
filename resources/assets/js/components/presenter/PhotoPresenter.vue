<template>
	<div v-if="status.sensitive == true" class="content-label-wrapper">
		<div class="text-light content-label">
			<p class="text-center">
				<i class="far fa-eye-slash fa-2x"></i>
			</p>
			<p class="h4 font-weight-bold text-center">
				Sensitive Content
			</p>
			<p class="text-center py-2">
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
	<div v-else>
		<div :title="status.media_attachments[0].description">
			<img class="card-img-top" 
				:src="status.media_attachments[0].url" 
				loading="lazy" 
				:alt="altText(status)"
				:width="width()"
				:height="height()"
				onerror="this.onerror=null;this.src='/storage/no-preview.png'">
		</div>
	</div>
</template>

<style type="text/css" scoped>
  .card-img-top {
    border-top-left-radius: 0 !important;
    border-top-right-radius: 0 !important;
  }
  .content-label {
  	margin: 0;
  	position: absolute;
  	top:50%;
  	left:50%;
  	z-index: 2;
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

				return 'Photo was not tagged with any alt text.';
			},

			toggleContentWarning(status) {
				this.$emit('togglecw');
			},

			width() {
				if( !this.status.media_attachments[0].meta || 
					!this.status.media_attachments[0].meta.original ||
					!this.status.media_attachments[0].meta.original.width ) {
					return;
				}
				return this.status.media_attachments[0].meta.original.width;
			},

			height() {
				if( !this.status.media_attachments[0].meta || 
					!this.status.media_attachments[0].meta.original ||
					!this.status.media_attachments[0].meta.original.height ) {
					return;
				}
				return this.status.media_attachments[0].meta.original.height;
			}
		}
	}
</script>