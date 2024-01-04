<template>
	<div v-if="status.sensitive == true" class="content-label-wrapper">
		<div class="text-light content-label">
			<p class="text-center">
				<i class="far fa-eye-slash fa-2x"></i>
			</p>
			<p class="h4 font-weight-bold text-center">
				Sensitive Content
			</p>
			<p class="text-center py-2 content-label-text">
				{{ status.spoiler_text ? status.spoiler_text : 'This album may contain sensitive content.'}}
			</p>
			<p class="mb-0">
				<button @click="toggleContentWarning()" class="btn btn-outline-light btn-block btn-sm font-weight-bold">See Post</button>
			</p>
		</div>
		<blur-hash-image
			width="32"
			height="32"
			:punch="1"
			:hash="status.media_attachments[0].blurhash"
			:alt="altText(status)"/>
	</div>
	<div v-else class="w-100 h-100 p-0 album-wrapper">
		<carousel ref="carousel" :centerMode="true" :loop="false" :per-page="1" :paginationPosition="'bottom-overlay'" paginationActiveColor="#3897f0" paginationColor="#dbdbdb" class="p-0 m-0" :id="'carousel-' + status.id">
			<slide v-for="(img, index) in status.media_attachments" :key="'px-carousel-'+img.id + '-' + index" class="" style="background: #000; display: flex;align-items: center;" :title="img.description">

				<img
					class="img-fluid w-100 p-0"
					:src="img.url"
					:alt="altText(img)"
					loading="lazy"
					:data-bp="img.url"
					onerror="this.onerror=null;this.src='/storage/no-preview.png'">

			</slide>
		</carousel>
		<div class="album-overlay">
			<p v-if="!status.sensitive && sensitive"
				@click="status.sensitive = true"
				style="
				margin-top: 0;
				padding: 10px;
				color: #fff;
				font-size: 10px;
				text-align: right;
				position: absolute;
				top: 0;
				right: 0;
				border-top-left-radius: 5px;
				cursor: pointer;
				background: linear-gradient(0deg, rgba(0,0,0,0.5), rgba(0,0,0,0.5));
			">
				<i class="fas fa-eye-slash fa-lg"></i>
			</p>

			<p @click.prevent="toggleLightbox"
				style="
				margin-top: 0;
				padding: 10px;
				color: #fff;
				font-size: 10px;
				text-align: right;
				position: absolute;
				left: 0;
				top: 0;
				border-bottom-right-radius: 5px;
				cursor: pointer;
				background: linear-gradient(0deg, rgba(0,0,0,0.5), rgba(0,0,0,0.5));
			">
				<i class="fas fa-expand fa-lg"></i>
			</p>

			<p
				v-if="status.media_attachments[0].license"
				style="
				margin-bottom: 0;
				padding: 0 5px;
				color: #fff;
				font-size: 10px;
				text-align: right;
				position: absolute;
				bottom: 0;
				right: 0;
				border-top-left-radius: 5px;
				background: linear-gradient(0deg, rgba(0,0,0,0.5), rgba(0,0,0,0.5));
			">
				<a :href="status.url" class="font-weight-bold text-light">Photo</a> by <a :href="status.account.url" class="font-weight-bold text-light">&commat;{{status.account.username}}</a> licensed under <a :href="status.media_attachments[0].license.url" class="font-weight-bold text-light">{{status.media_attachments[0].license.title}}</a>
			</p>
		</div>
	</div>
</template>


<script type="text/javascript">
	import BigPicture from 'bigpicture';

	export default {
		props: ['status'],

		data() {
			return {
				sensitive: this.status.sensitive,
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
			toggleContentWarning(status) {
				this.$emit('togglecw');
			},

			toggleLightbox(e) {
				BigPicture({
					el: e.target,
					gallery: '#carousel-' + this.status.id,
					position: this.$refs.carousel.currentPage
				})
			},

			altText(img) {
				let desc = img.description;
				if(desc) {
					return desc;
				}

				return 'Photo was not tagged with any alt text.';
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

<style type="text/css" scoped>
  .card-img-top {
	border-top-left-radius: 0 !important;
	border-top-right-radius: 0 !important;
  }
  .content-label-wrapper {
	position: relative;
  }
  .content-label {
	margin: 0;
	position: absolute;
	top:50%;
	left:50%;
	transform: translate(-50%, -50%);
	display: flex;
	flex-direction: column;
	align-items: center;
	justify-content: center;
	width: 100%;
	height: 100%;
	z-index: 2;
	background: rgba(0, 0, 0, 0.2)
  }
  .album-wrapper {
	position: relative;
  }
</style>
