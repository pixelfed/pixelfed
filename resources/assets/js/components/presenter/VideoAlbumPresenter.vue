<template>
	<div v-if="status.sensitive == true">
		<details class="details-animated">
			<summary>
				<p class="mb-0 lead font-weight-bold">{{ status.spoiler_text ? status.spoiler_text : 'CW / NSFW / Hidden Media'}}</p>
				<p class="font-weight-light">(click to show)</p>
			</summary>
			<b-carousel :id="status.id + '-carousel'"
				style="text-shadow: 1px 1px 2px #333; background-color: #000;"
				controls
				img-blank
				background="#ffffff"
				:interval="0"
			>
				<b-carousel-slide v-for="(vid, index) in status.media_attachments" :key="vid.id + '-media'">
					<video slot="img" class="embed-responsive-item" preload="none" controls playsinline loop :alt="vid.description" width="100%" height="100%" :poster="vid.preview_url">
						<source :src="vid.url" :type="vid.mime">
					</video>
				</b-carousel-slide>
			</b-carousel>
		</details>
	</div>
	<div v-else>
		<b-carousel :id="status.id + '-carousel'"
			style="text-shadow: 1px 1px 2px #333; background-color: #000;"
			controls
			img-blank
			background="#ffffff"
			:interval="0"
		>
			<b-carousel-slide v-for="(vid, index) in status.media_attachments" :key="vid.id + '-media'">
				<video slot="img" class="embed-responsive-item" preload="none" controls playsinline loop :alt="vid.description" width="100%" height="100%" :poster="vid.preview_url">
					<source :src="vid.url" :type="vid.mime">
				</video>
			</b-carousel-slide>
		</b-carousel>
	</div>
</template>

<script type="text/javascript">
	export default {
		props: ['status']
	}
</script>
