<template>
	<div v-if="status.sensitive == true" class="content-label-wrapper">
		<div class="text-light content-label">
			<p class="text-center">
				<i class="far fa-eye-slash fa-2x"></i>
			</p>
			<p class="h4 font-weight-bold text-center">
				Sensitive Content
			</p>
			<p class="text-center py-2">
				{{ status.spoiler_text ? status.spoiler_text : 'This post may contain sensitive content.'}}
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
	<div v-else>
		<div :title="status.media_attachments[0].description" style="position: relative;">
			<img class="card-img-top"
				:src="status.media_attachments[0].url"
				loading="lazy"
				:alt="altText(status)"
				:width="width()"
				:height="height()"
				onerror="this.onerror=null;this.src='/storage/no-preview.png'"
				@click.prevent="toggleLightbox">

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
				"><a :href="status.url" class="font-weight-bold text-light">Photo</a> by <a :href="status.account.url" class="font-weight-bold text-light">&commat;{{status.account.username}}</a> licensed under <a :href="status.media_attachments[0].license.url" class="font-weight-bold text-light">{{status.media_attachments[0].license.title}}</a></p>
		</div>
	</div>
</template>

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
</style>

<script type="text/javascript">
	export default {
		props: ['status'],

		data() {
			return {
				sensitive: this.status.sensitive
			}
		},

		methods: {
			altText(status) {
				let desc = status.media_attachments[0].description;
				if(desc) {
					return desc;
				}

				return 'Photo was not tagged with any alt text.';
			},

			toggleContentWarning(status) {
				this.$emit('togglecw');
			},

			toggleLightbox() {
				this.$emit('lightbox');
			},

			width() {
				if( !this.status.media_attachments[0].meta ||
					!this.status.media_attachments[0].meta.original ||
					!this.status.media_attachments[0].meta.original.width ) {
					return;
				}
				return this.status.media_attachments[0].meta.original.width;
			},

			height() {
				if( !this.status.media_attachments[0].meta ||
					!this.status.media_attachments[0].meta.original ||
					!this.status.media_attachments[0].meta.original.height ) {
					return;
				}
				return this.status.media_attachments[0].meta.original.height;
			}
		}
	}
</script>
