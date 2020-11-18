<template>
<div class="container mt-2 mt-md-5">
	<input type="file" id="pf-dz" name="media" class="d-none file-input" v-bind:accept="config.mimes">
	<div v-if="loaded" class="row">
		<div class="col-12 col-md-6 offset-md-3">

			<!-- LANDING -->
			<div v-if="page == 'landing'" class="card card-body bg-transparent border-0 shadow-none d-flex justify-content-center" style="height: 90vh;">
				<div class="text-center flex-fill mt-5 pt-5">
					<img src="/img/pixelfed-icon-grey.svg" width="60" height="60">
					<p class="font-weight-bold lead text-lighter mt-1">Stories</p>
					<!-- <p v-if="loaded" class="font-weight-bold small text-uppercase text-muted">
						<span>{{stories.length}} Active</span>
						<span class="px-2">|</span>
						<span>30K Views</span>
					</p> -->
				</div>
				<div class="flex-fill py-4">
					<div class="card w-100 shadow-none">
						<div class="list-group">
							<!-- <a class="list-group-item text-center lead text-decoration-none text-dark" href="#">Camera</a> -->
							<a class="list-group-item text-center lead text-decoration-none text-dark" href="#" @click.prevent="upload()">Add Photo</a>
							<a v-if="stories.length" class="list-group-item text-center lead text-decoration-none text-dark" href="#" @click.prevent="edit()">Edit</a>
							<!-- <a class="list-group-item text-center lead text-decoration-none text-dark" href="#">Options</a> -->
						</div>
					</div>
				</div>
				<div class="text-center flex-fill">
					<p class="text-lighter small text-uppercase">
						<a href="/" class="text-muted font-weight-bold">Home</a>
						<span class="px-2 text-lighter">|</span>
						<a href="/i/my/story" class="text-muted font-weight-bold">View My Story</a>
						<span class="px-2 text-lighter">|</span>
						<a href="/site/help" class="text-muted font-weight-bold">Help</a>
					</p>
				</div>
			</div>

			<!-- CROP -->
			<div v-if="page == 'crop'" class="card card-body bg-transparent border-0 shadow-none d-flex justify-content-center" style="height: 95vh;">
				<div class="text-center pt-5 mb-3 d-flex justify-content-between align-items-center">
					<div>
						<button class="btn btn-outline-lighter btn-sm py-0 px-md-3"><i class="pr-2 fas fa-chevron-left fa-sm"></i> Delete</button>
					</div>
					<div class="d-flex align-items-center">
						<img class="d-inline-block mr-2" src="/img/pixelfed-icon-grey.svg" width="30px" height="30px">
						<span class="font-weight-bold lead text-lighter">Stories</span>
					</div>
					<div>
						<button class="btn btn-outline-success btn-sm py-0 px-md-3">Crop <i class="pl-2 fas fa-chevron-right fa-sm"></i></button>
					</div>
				</div>
				<div class="flex-fill">
					<div class="card w-100 mt-3">
						<div class="card-body p-0">
							<vue-cropper
								ref="cropper"
								:relativeZoom="cropper.zoom"
								:aspectRatio="cropper.aspectRatio"
								:viewMode="cropper.viewMode"
								:zoomable="cropper.zoomable"
								:rotatable="true"
								:src="mediaUrl"
							>
							</vue-cropper>
						</div>
					</div>
				</div>
				<div class="text-center flex-fill">
					<p class="text-lighter small text-uppercase pt-2">
						<!-- <a href="#" class="text-muted font-weight-bold">Home</a>
						<span class="px-2 text-lighter">|</span>
						<a href="#" class="text-muted font-weight-bold">View My Story</a>
						<span class="px-2 text-lighter">|</span> -->
						<a href="/site/help" class="text-muted font-weight-bold mb-0">Help</a>
					</p>
				</div>
			</div>

			<!-- ERROR -->
			<div v-if="page == 'error'" class="card card-body bg-transparent border-0 shadow-none d-flex justify-content-center align-items-center" style="height: 90vh;">
				<p class="h3 mb-0">Oops!</p>
				<p class="text-muted lead">An error occurred, please try again later.</p>
				<p class="text-muted mb-0">
					<a class="btn btn-outline-secondary py-0 px-5 font-weight-bold" href="/">Go back</a>
				</p>
			</div>

			<!-- UPLOADING -->
			<div v-if="page == 'uploading'" class="card card-body bg-transparent border-0 shadow-none d-flex justify-content-center align-items-center" style="height: 90vh;">
				<p v-if="uploadProgress != 100" class="display-4 mb-0">Uploading {{uploadProgress}}%</p>
				<p v-else class="display-4 mb-0">Publishing Story</p>
			</div>

			<div v-if="page == 'edit'" class="card card-body bg-transparent border-0 shadow-none d-flex justify-content-center" style="height: 90vh;">
				<div class="text-center flex-fill mt-5 pt-5">
					<img src="/img/pixelfed-icon-grey.svg" width="60px" height="60px">
					<p class="font-weight-bold lead text-lighter mt-1">Stories</p>
				</div>
				<div class="flex-fill py-5">
					<div class="card w-100 shadow-none" style="max-height: 500px; overflow-y: auto">
						<div class="list-group">
							<div v-for="(story, index) in stories" class="list-group-item text-center text-dark" href="#">
								<div class="media align-items-center">
									<div class="mr-3 cursor-pointer" @click="showLightbox(story)">
										<img :src="story.src" class="img-fluid" width="70px" height="70px">
										<p class="small text-muted text-center mb-0">(expand)</p>
									</div>
									<div class="media-body">
										<p class="mb-0">Expires</p>
										<p class="mb-0 text-muted small"><span>{{expiresTimestamp(story.expires_at)}}</span></p>
									</div>
									<div class="float-right">
										<button @click="deleteStory(story, index)" class="btn btn-danger btn-sm font-weight-bold text-uppercase">Delete</button>
									</div>
								</div>
							</div>
						</div>
					</div>
				</div>
				<div class="flex-fill text-center">
					<a class="btn btn-outline-secondary py-0 px-5 font-weight-bold" href="/i/stories/new">Go back</a>
				</div>
			</div>
		</div>
	</div>
	<b-modal
		id="lightbox"
		ref="lightboxModal"
		hide-header
		hide-footer
		centered
		size="lg"
		body-class="p-0"
		>
		<div v-if="lightboxMedia" class="w-100 h-100">
			<img :src="lightboxMedia.url" style="max-height: 100%; max-width: 100%">
		</div>
	</b-modal>
</div>
</template>

<style type="text/css" scoped>

</style>

<script type="text/javascript">
	import VueTimeago from 'vue-timeago';
	import VueCropper from 'vue-cropperjs';
	import 'cropperjs/dist/cropper.css';
	export default {
		components: { 
			VueCropper,
			VueTimeago
		},

		props: ['profile-id'],
		data() {
			return {
				loaded: false,
				config: window.App.config,
				mimes: [
					'image/jpeg',
					'image/png',
					// 'video/mp4'
				],
				page: 'landing',
				pages: [
					'landing',
					'crop',
					'edit',
					'confirm',
					'error',
					'uploading'
				],
				uploading: false,
				uploadProgress: 0,
				cropper: {
					aspectRatio: 9/16,
					viewMode: 1,
					zoomable: true,
					zoom: null
				},
				mediaUrl: null,
				stories: [],
				lightboxMedia: false,
			};
		},

		mounted() {
			this.mediaWatcher();
			axios.get('/api/stories/v0/fetch/' + this.profileId)
			.then(res => {
				this.stories = res.data;
				this.loaded = true;
			});
		},

		methods: {

			upload() {
				let fi = $('.file-input[name="media"]');
				fi.trigger('click');
			},

			mediaWatcher() {
				let self = this;
				$(document).on('change', '#pf-dz', function(e) {
					self.triggerUpload();
				});
			},

			triggerUpload() {
				let self = this;
				self.uploading = true;
				let io = document.querySelector('#pf-dz');
				self.page = 'uploading';
				Array.prototype.forEach.call(io.files, function(io, i) {
					if(self.media && self.media.length + i >= self.config.uploader.album_limit) {
						swal('Error', 'You can only upload ' + self.config.uploader.album_limit + ' photos per album', 'error');
						self.uploading = false;
						self.page = 2;
						return;
					}
					let type = io.type;
					let validated = $.inArray(type, self.mimes);
					if(validated == -1) {
						swal('Invalid File Type', 'The file you are trying to add is not a valid mime type. Please upload a '+self.mimes+' only.', 'error');
						self.uploading = false;
						self.page = 'error';
						return;
					}

					let form = new FormData();
					form.append('file', io);

					let xhrConfig = {
						onUploadProgress: function(e) {
							let progress = Math.floor( (e.loaded * 100) / e.total );
							self.uploadProgress = progress;
						}
					};

					axios.post('/api/stories/v0/add', form, xhrConfig)
					.then(function(e) {
						self.uploadProgress = 100;
						self.uploading = false;
						window.location.href = '/i/my/story';
						self.mediaUrl = e.data.media_url;
					}).catch(function(e) {
						self.uploading = false;
						io.value = null;
						let msg = e.response.data.message ? e.response.data.message : 'Something went wrong.'
						swal('Oops!', msg, 'warning');
					});
					io.value = null;
					self.uploadProgress = 0;
				});
			},

			expiresTimestamp(ts) {
				ts = new Date(ts * 1000);
				return ts.toDateString() + ' ' + ts.toLocaleTimeString();
			},

			edit() {
				this.page = 'edit';
			},

			showLightbox(story) {
				this.lightboxMedia = {
					url: story.src
				}
				this.$refs.lightboxModal.show();
			},

			deleteStory(story, index) {
				if(window.confirm('Are you sure you want to delete this Story?') != true) {
					return;
				}

				axios.delete('/api/stories/v0/delete/' + story.id)
				.then(res => {
					this.stories.splice(index, 1);
					if(this.stories.length == 0) {
						window.location.href = '/i/stories/new';
					}
				});

			}
		}
	}
</script>