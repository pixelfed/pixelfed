<template>
	<div v-if="status.sensitive == true">
		<details class="details-animated">
			<summary>
				<p class="mb-0 lead font-weight-bold">{{ status.spoiler_text ? status.spoiler_text : 'CW / NSFW / Hidden Media'}}</p>
				<p class="font-weight-light">(click to show)</p>
			</summary>
			<div class="max-hide-overflow" :title="status.media_attachments[0].description">
				<img :class="status.media_attachments[0].filter_class + ' card-img-top'" :src="status.media_attachments[0].url" loading="lazy" :alt="altText(status)" onerror="this.onerror=null;this.src='/storage/no-preview.png'">
			</div>
		</details>
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