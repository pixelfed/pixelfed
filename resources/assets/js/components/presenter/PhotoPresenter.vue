<template>
	<div v-if="status.sensitive == true">
		<div class="text-light content-label">
			<p class="text-center">
				<i class="far fa-eye-slash fa-2x"></i>
			</p>
			<p class="h4 font-weight-bold text-center">
				Sensitive Content
			</p>
			<p class="text-center py-2">
				This photo contains sensitive content which <br/>
				some people may find offsensive or disturbing.
			</p>
			<p class="mb-0">
				<button @click="status.sensitive = false" class="btn btn-outline-light btn-block btn-sm font-weight-bold">See Photo</button>
			</p>
		</div>
		<blur-hash-image
			width="32"
			height="32"
			punch="1"
			:hash="status.media_attachments[0].blurhash"
			:alt="altText(status)"/>
	</div>
	<div v-else>
		<div :title="status.media_attachments[0].description">
			<img :class="status.media_attachments[0].filter_class + ' card-img-top'" :src="status.media_attachments[0].url" loading="lazy" :alt="altText(status)" onerror="this.onerror=null;this.src='/storage/no-preview.png'">
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
  	top:45%;
  	left:50%;
  	z-index: 999;
  	transform: translate(-50%, -50%);
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
			}
		}
	}
</script>