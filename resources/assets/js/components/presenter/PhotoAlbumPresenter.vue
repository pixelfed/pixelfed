<template>
	<div v-if="status.sensitive == true">
		<details class="details-animated">
			<summary>
				<p class="mb-0 lead font-weight-bold">{{ status.spoiler_text ? status.spoiler_text : 'CW / NSFW / Hidden Media'}}</p>
				<p class="font-weight-light">(click to show)</p>
			</summary>
			<b-carousel :id="status.id + '-carousel'"
				v-model="cursor"
				style="text-shadow: 1px 1px 2px #333;min-height: 330px;display: flex;align-items: center;"
				controls
				background="#ffffff"
				:interval="0"
			>
				<b-carousel-slide v-for="(img, index) in status.media_attachments" :key="img.id">
					<div slot="img" class="d-block mx-auto text-center" style="max-height: 600px;" :title="img.description">
						<img :class="img.filter_class + ' img-fluid'" style="max-height: 600px;" :src="img.url" :alt="img.description" loading="lazy">
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
			style="text-shadow: 1px 1px 2px #333;min-height: 330px;display: flex;align-items: center;"
			controls
			background="#ffffff"
			:interval="0"
		>
			<b-carousel-slide v-for="(img, index) in status.media_attachments" :key="img.id" :title="img.description">
				<div slot="img" class="d-block mx-auto text-center" style="max-height: 600px;">
					<img :class="img.filter_class + ' img-fluid'" style="max-height: 600px;" :src="img.url" loading="lazy" :alt="img.description">
				</div>
			</b-carousel-slide>
			<span class="badge badge-dark box-shadow" style="position: absolute;top:10px;right:10px;">
				{{cursor + 1}} / {{status.media_attachments.length}}
			</span>
		</b-carousel>
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

		data() {
			return {
				cursor: 0
			}
		}
	}
</script>