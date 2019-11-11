<template>
	<div v-if="status.sensitive == true">
		<details class="details-animated">
			<summary>
				<p class="mb-0 lead font-weight-bold">{{ status.spoiler_text ? status.spoiler_text : 'CW / NSFW / Hidden Media'}}</p>
				<p class="font-weight-light">(click to show)</p>
			</summary>
			<div class="embed-responsive embed-responsive-1by1">
				<video class="video" preload="none" loop :poster="status.media_attachments[0].preview_url":data-id="status.id" @click="playOrPause($event)">
					<source :src="status.media_attachments[0].url" :type="status.media_attachments[0].mime">
				</video>
			</div>
		</details>
	</div>
	<div v-else class="embed-responsive embed-responsive-16by9">
		<video class="video" controls preload="metadata" loop :poster="status.media_attachments[0].preview_url" :data-id="status.id">
			<source :src="status.media_attachments[0].url" :type="status.media_attachments[0].mime">
		</video>
	</div>
</template>

<script type="text/javascript">
	export default {
		props: ['status'],

		methods: {
			playOrPause(e) {
				let el = e.target;
				if(el.getAttribute('playing') == 1) {
					el.removeAttribute('playing');
					el.pause();
				} else {
					el.setAttribute('playing', 1);
					el.play();
				}
			}
		}
	}
</script>
