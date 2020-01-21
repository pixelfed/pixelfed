<template>
	<div v-if="status.sensitive == true">
		<details class="details-animated">
			<summary @click="loadSensitive">
				<p class="mb-0 lead font-weight-bold">{{ status.spoiler_text ? status.spoiler_text : 'CW / NSFW / Hidden Media'}}</p>
				<p class="font-weight-light">(click to show)</p>
			</summary>
			<carousel ref="carousel" :centerMode="true" :loop="false" :per-page="1" :paginationPosition="'bottom-overlay'" paginationActiveColor="#3897f0" paginationColor="#dbdbdb">
				<slide v-for="(img, index) in status.media_attachments" :key="'px-carousel-'+img.id + '-' + index" class="w-100 h-100 d-block mx-auto text-center" :title="img.description">
					<img :class="img.filter_class + ' img-fluid'" :src="img.url" :alt="img.description" onerror="this.onerror=null;this.src='/storage/no-preview.png'">
				</slide>
			</carousel>
		</details>
	</div>
	<div v-else class="w-100 h-100 p-0">
		<carousel ref="carousel" :centerMode="true" :loop="false" :per-page="1" :paginationPosition="'bottom-overlay'" paginationActiveColor="#3897f0" paginationColor="#dbdbdb" class="p-0 m-0">
			<slide v-for="(img, index) in status.media_attachments" :key="'px-carousel-'+img.id + '-' + index" class="" style="background: #000; display: flex;align-items: center;" :title="img.description">
				<img :class="img.filter_class + ' img-fluid w-100 p-0'" style="" :src="img.url" :alt="img.description" onerror="this.onerror=null;this.src='/storage/no-preview.png'">
			</slide>
		</carousel>
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
		},

		created() {
			// window.addEventListener("keydown", this.keypressNavigation);
		},

		beforeDestroy() {
			// window.removeEventListener("keydown", this.keypressNavigation);
		},

		methods: {
			loadSensitive() {
				this.$refs.carousel.onResize();
				this.$refs.carousel.goToPage(0);
			},

			keypressNavigation(e) {
				let ref = this.$refs.carousel;
				if (e.keyCode == "37") {
					e.preventDefault();

					let direction = "backward";

					ref.advancePage(direction);
					ref.$emit("navigation-click", direction);
				}

				if (e.keyCode == "39") {
					e.preventDefault();

					let direction = "forward";

					ref.advancePage(direction);
					ref.$emit("navigation-click", direction);
				}
			}
		}
	}
</script>