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
				<b-carousel-slide v-for="(media, index) in status.media_attachments" :key="media.id + '-media'">

					<video v-if="media.type == 'Video'" slot="img" class="embed-responsive-item" preload="none" controls loop :alt="media.description" width="100%" height="100%">
						<source :src="media.url" :type="media.mime">
					</video>

					<div v-else-if="media.type == 'Image'" slot="img" :class="media.filter_class" v-on:click="$emit('lightbox', media)">
						<img class="d-block img-fluid w-100" :src="media.url" :alt="media.description" :title="media.description">
					</div>

					<p v-else class="text-center p-0 font-weight-bold text-white">Error: Problem rendering preview.</p>

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
			<b-carousel-slide v-for="(media, index) in status.media_attachments" :key="media.id + '-media'">

				<video v-if="media.type == 'Video'" slot="img" class="embed-responsive-item" preload="none" controls loop :alt="media.description" width="100%" height="100%">
					<source :src="media.url" :type="media.mime">
				</video>

				<div v-else-if="media.type == 'Image'" slot="img" :class="media.filter_class" v-on:click="$emit('lightbox', media)">
					<img class="d-block img-fluid w-100" :src="media.url" :alt="media.description" :title="media.description">
				</div>

				<p v-else class="text-center p-0 font-weight-bold text-white">Error: Problem rendering preview.</p>

			</b-carousel-slide>
		</b-carousel>
	</div>
</template>

<script type="text/javascript">
	export default {
		props: ['status']
	}
</script>