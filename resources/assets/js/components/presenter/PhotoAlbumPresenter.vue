<template>
	<div v-if="status.sensitive == true">
		<details class="details-animated">
			<summary>
				<p class="mb-0 lead font-weight-bold">{{ status.spoiler_text ? status.spoiler_text : 'CW / NSFW / Hidden Media'}}</p>
				<p class="font-weight-light">(click to show)</p>
			</summary>
			<b-carousel :id="status.id + '-carousel'"
				v-model="cursor"
				style="text-shadow: 1px 1px 2px #333;"
				controls
				background="#ffffff"
				:interval="0"
			>
				<b-carousel-slide v-for="(img, index) in status.media_attachments" :key="img.id">
					<div slot="img" :class="img.filter_class + ' d-block mx-auto text-center'" style="max-height: 600px;" v-on:click="$emit('lightbox', img)">
						<img class="img-fluid" style="max-height: 600px;" :src="img.url" :alt="img.description" :title="img.description">
					</div>
				</b-carousel-slide>
				<span class="badge badge-dark box-shadow" style="position: absolute;top:10px;right:10px;">
					{{cursor + 1}} / {{status.media_attachments.length}}
				</span>
			</b-carousel>
		</details>
	</div>
	<div v-else>
		<b-carousel :id="status.id + '-carousel'"
			v-model="cursor"
			style="text-shadow: 1px 1px 2px #333;"
			controls
			background="#ffffff"
			:interval="0"
		>
			<b-carousel-slide v-for="(img, index) in status.media_attachments" :key="img.id">
				<div slot="img" :class="img.filter_class + ' d-block mx-auto text-center'" style="max-height: 600px;" v-on:click="$emit('lightbox', img)">
					<img class="img-fluid" style="max-height: 600px;" :src="img.url" :alt="img.description" :title="img.description">
				</div>
			</b-carousel-slide>
			<span class="badge badge-dark box-shadow" style="position: absolute;top:10px;right:10px;">
				{{cursor + 1}} / {{status.media_attachments.length}}
			</span>
		</b-carousel>
	</div>
</template>

<script type="text/javascript">
	export default {
		props: ['status'],

		data() {
			return {
				cursor: 0
			}
		}
	}
</script>