<template>
	<div v-if="status.sensitive == true">
		<details class="details-animated">
			<summary>
				<p class="mb-0 lead font-weight-bold">{{ status.spoiler_text ? status.spoiler_text : 'CW / NSFW / Hidden Media'}}</p>
				<p class="font-weight-light">(click to show)</p>
			</summary>
			<div class="embed-responsive embed-responsive-16by9">
				<video class="video" preload="none" controls loop>
					<source :src="status.media_attachments[0].url" :type="status.media_attachments[0].mime">
				</video>
			</div>
		</details>
	</div>
	<div v-else class="embed-responsive embed-responsive-16by9">
		<video class="video" preload="none" controls loop>
			<source :src="status.media_attachments[0].url" :type="status.media_attachments[0].mime">
		</video>
	</div>
</template>

<script type="text/javascript">
	export default {
		props: ['status']
	}
</script>