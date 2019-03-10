<template>
	<div v-if="status.sensitive == true">
		<details class="details-animated">
			<summary>
				<p class="mb-0 lead font-weight-bold">{{ status.spoiler_text ? status.spoiler_text : 'CW / NSFW / Hidden Media'}}</p>
				<p class="font-weight-light">(click to show)</p>
			</summary>
			<div class="max-hide-overflow" v-on:click="$emit('lightbox', status.media_attachments[0])" :class="status.media_attachments[0].filter_class">
				<img class="card-img-top" :src="status.media_attachments[0].url" :alt="status.media_attachments[0].description" :title="status.media_attachments[0].description">
			</div>
		</details>
	</div>
	<div v-else>
		<div :class="status.media_attachments[0].filter_class" v-on:click="$emit('lightbox', status.media_attachments[0])">
			<img class="card-img-top" :src="status.media_attachments[0].url" :alt="status.media_attachments[0].description" :title="status.media_attachments[0].description">
		</div>
	</div>
</template>

<script type="text/javascript">
	export default {
		props: ['status']
	}
</script>