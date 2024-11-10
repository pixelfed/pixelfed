<template>
	<div class="group-media-component">
		<div class="row justify-content-center">
			<div class="col-12">
				<div class="card card-body border shadow-sm">
					<div class="d-flex justify-content-between align-items-center mb-3">
						<p class="h4 font-weight-bold mb-0">Media</p>
						<!-- <div>
							<a href="#" class="font-weight-bold mr-3"><i class="fas fa-plus fa-sm"></i> Create Album</a>
							<a href="#" class="font-weight-bold">Add Photos/Video</a>
						</div> -->
					</div>

					<div v-if="isLoaded">
						<div class="mb-5">
							<button
								:class="[ tab == 'photo' ? 'text-primary font-weight-bold' : 'text-lighter' ]"
								class="btn btn-light mr-2"
								@click="switchTab('photo')">
								Photos
							</button>
							<button
								:class="[ tab == 'video' ? 'text-primary font-weight-bold' : 'text-lighter' ]"
								class="btn btn-light mr-2"
								@click="switchTab('video')">
								Videos
							</button>
							<button
								:class="[ tab == 'album' ? 'text-primary font-weight-bold' : 'text-lighter' ]"
								class="btn btn-light mr-2"
								@click="switchTab('album')">
								Albums
							</button>
						</div>

						<div v-if="tab == 'photo'" class="row px-3">
							<div v-for="(status, index) in photos" class="m-1">
								<a :href="status.url" class="bh-content">
									<img :src="getMediaSource(status)" width="205" height="205" style="object-fit: cover;">
								</a>
							</div>

							<div v-if="photos.length == 0" class="col-12 py-5 text-center">
								<p class="lead font-weight-bold mb-0">No photos found</p>
							</div>
						</div>

						<div v-if="tab == 'video'" class="row px-3">
							<div v-for="(status, index) in videos" class="m-1">
								<a :href="status.url" class="bh-content text-decoration-none">
									<img v-if="!status.media_attachments[0].preview_url.endsWith('no-preview.png')" :src="getMediaSource(status)" width="205" height="205" style="object-fit: cover;">
									<div v-else class="bg-light text-dark d-flex align-items-center justify-content-center border" style="width:205px;height:205px;">
										<p class="font-weight-bold mb-0">No preview available</p>
									</div>
								</a>
							</div>

							<div v-if="videos.length == 0" class="col-12 py-5 text-center">
								<p class="lead font-weight-bold mb-0">No videos found</p>
							</div>
						</div>

						<div v-if="tab == 'album'" class="row px-3">
							<div v-for="(status, index) in albums" class="m-1">
								<a :href="status.url" class="bh-content">
									<img :src="getMediaSource(status)" width="205" height="205" style="object-fit: cover;">
								</a>
							</div>

							<div v-if="albums.length == 0" class="col-12 py-5 text-center">
								<p class="lead font-weight-bold mb-0">No albums found</p>
							</div>
						</div>

						<div v-if="hasNextPage[tab]" class="mt-3">
							<button class="btn btn-light font-weight-bold btn-block border" @click="loadNextPage">Load more</button>
						</div>
					</div>
					<div v-else class="d-flex align-items-center justify-content-center" style="height:500px">
						<div class="spinner-border text-primary" role="status">
							<span class="sr-only">Loading...</span>
						</div>
					</div>
				</div>
			</div>
		</div>
	</div>
</template>

<script type="text/javascript">
	export default {
		props: {
			group: {
				type: Object
			}
		},

		data() {
			return {
				isLoaded: false,
				feed: [],
				photos: [],
				videos: [],
				albums: [],
				tab: 'photo',
				tabs: [
					'photo',
					'video',
					'album'
				],
				page: {
					'photo': 1,
					'video': 1,
					'album': 1
				},
				hasNextPage: {
					'photo': false,
					'video': false,
					'album': false
				}
			}
		},

		mounted() {
			this.fetchMedia();
		},

		methods: {
			fetchMedia() {
				axios.get('/api/v0/groups/media/list', {
					params: {
						gid: this.group.id,
						page: this.page[this.tab],
						type: this.tab
					}
				}).then(res => {
					if(res.data.length > 0) {
						this.hasNextPage[this.tab] = true;
					}

					this.isLoaded = true;

					res.data.forEach(status => {
						if(status.pf_type == 'photo') {
							this.photos.push(status);
						}

						if(status.pf_type == 'video') {
							this.videos.push(status);
						}

						if(status.pf_type == 'photo:album') {
							this.albums.push(status);
						}
					})
					this.page[this.tab] = this.page[this.tab] + 1;
				}).catch(err => {
					this.hasNextPage[this.tab] = false;
					console.log(err.response);
				})
			},

			loadNextPage() {
				axios.get('/api/v0/groups/media/list', {
					params: {
						gid: this.group.id,
						page: this.page[this.tab],
						type: this.tab,
					}
				}).then(res => {
					if(res.data.length == 0) {
						this.hasNextPage[this.tab] = false;
						return;
					}
					res.data.forEach(status => {
						if(status.pf_type == 'photo') {
							this.photos.push(status);
						}

						if(status.pf_type == 'video') {
							this.videos.push(status);
						}

						if(status.pf_type == 'photo:album') {
							this.albums.push(status);
						}
					})
					this.page[this.tab] = this.page[this.tab] + 1;
				}).catch(err => {
					this.hasNextPage[this.tab] = false;
				})
			},

			formatDate(ts) {
				return new Date(ts).toDateString();
			},

			switchTab(tab) {
				this.tab = tab;
				this.fetchMedia();
			},

			lightbox(status) {
				this.lightboxStatus = status.media_attachments[0];
				this.$refs.lightboxModal.show();
			},

			hideLightbox() {
				this.lightboxStatus = null;
				this.$refs.lightboxModal.hide();
			},

			blurhashWidth(status) {
				if(!status.media_attachments[0].meta) {
					return 25;
				}
				let aspect = status.media_attachments[0].meta.original.aspect;
				if(aspect == 1) {
					return 25;
				} else if(aspect > 1) {
					return 30;
				} else {
					return 20;
				}
			},

			blurhashHeight(status) {
				if(!status.media_attachments[0].meta) {
					return 25;
				}
				let aspect = status.media_attachments[0].meta.original.aspect;
				if(aspect == 1) {
					return 25;
				} else if(aspect > 1) {
					return 20;
				} else {
					return 30;
				}
			},

			getMediaSource(status) {
				let media = status.media_attachments[0];

				if(media.preview_url && media.preview_url.endsWith('storage/no-preview.png')) {
					return media.url;
				}

                if(media.preview_url && media.preview_url.length) {
                    return media.url;
                }

				return media.url;
			}
		}
	}
</script>

<style lang="scss">
	.group-members-component {

	}
</style>
