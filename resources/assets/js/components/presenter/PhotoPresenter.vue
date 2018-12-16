<template>
	<div v-if="status.sensitive == true">
		<details class="details-animated">
			<summary>
				<p class="mb-0 lead font-weight-bold">{{ status.spoiler_text ? status.spoiler_text : 'CW / NSFW / Hidden Media'}}</p>
				<p class="font-weight-light">(click to show)</p>
			</summary>
			<a class="max-hide-overflow" :href="status.url" :class="status.media_attachments[0].filter_class">
				<img class="card-img-top" :src="status.media_attachments[0].url">
			</a>
		</details>
	</div>
	<div v-else>
		<div :class="status.media_attachments[0].filter_class">
			<img class="card-img-top" :src="status.media_attachments[0].url">
		</div>
	</div>
</template>

<script type="text/javascript">
	export default {
		props: ['status']
	}
</script>