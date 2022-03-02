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

					<video v-if="media.type == 'video'" slot="img" class="embed-responsive-item" preload="none" controls playsinline loop :alt="media.description" width="100%" height="100%" :poster="media.preview_url">
						<source :src="media.url" :type="media.mime">
					</video>

					<div v-else-if="media.type == 'image'" slot="img" :title="media.description">
						<img :class="media.filter_class + ' d-block img-fluid w-100'" :src="media.url" :alt="media.description" loading="lazy" onerror="this.onerror=null;this.src='/storage/no-preview.png'">
					</div>

					<p v-else class="text-center p-0 font-weight-bold text-white">Error: Problem rendering preview.</p>

				</b-carousel-slide>
			</b-carousel>
		</details>
	</div>
	<div v-else class="w-100 h-100 p-0">
		<!-- <b-carousel :id="status.id + '-carousel'"
					style="text-shadow: 1px 1px 2px #333; background-color: #000;"
					controls
					img-blank
					background="#ffffff"
					:interval="0"
				>
			<b-carousel-slide v-for="(media, index) in status.media_attachments" :key="media.id + '-media'">

				<video v-if="media.type == 'Video'" slot="img" class="embed-responsive-item" preload="none" controls loop :title="media.description" width="100%" height="100%" :poster="media.preview_url">
					<source :src="media.url" :type="media.mime">
				</video>

				<div v-else-if="media.type == 'Image'" slot="img" :title="media.description">
					<img :class="media.filter_class + ' d-block img-fluid w-100'" :src="media.url" :alt="media.description" loading="lazy">
				</div>

				<p v-else class="text-center p-0 font-weight-bold text-white">Error: Problem rendering preview.</p>

			</b-carousel-slide>
		</b-carousel> -->
		<carousel ref="carousel" :centerMode="true" :loop="false" :per-page="1" :paginationPosition="'bottom-overlay'" paginationActiveColor="#3897f0" paginationColor="#dbdbdb" class="p-0 m-0">
			<slide v-for="(media, index) in status.media_attachments" :key="'px-carousel-'+media.id + '-' + index" class="w-100 h-100 d-block mx-auto text-center" style="background: #000; display: flex;align-items: center;">

				<video v-if="media.type == 'video'" class="embed-responsive-item" preload="none" controls loop :title="media.description" width="100%" height="100%" :poster="media.preview_url">
					<source :src="media.url" :type="media.mime">
				</video>

				<div v-else-if="media.type == 'image'" :title="media.description">
					<img :class="media.filter_class + ' img-fluid w-100'" :src="media.url" :alt="media.description" loading="lazy"  onerror="this.onerror=null;this.src='/storage/no-preview.png'">
				</div>

				<p v-else class="text-center p-0 font-weight-bold text-white">Error: Problem rendering preview.</p>

			</slide>
		</carousel>
	</div>
</template>

<script type="text/javascript">
	export default {
		props: ['status']
	}
</script>
