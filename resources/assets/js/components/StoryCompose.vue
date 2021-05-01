<template>
<div class="container mt-2 mt-md-5 bg-black">
	<input type="file" id="pf-dz" name="media" class="d-none file-input" v-bind:accept="config.mimes">
	<span class="fixed-top text-right m-3 cursor-pointer" @click="navigateTo()">
		<i class="fas fa-times fa-lg text-white"></i>
	</span>
	<div v-if="loaded" class="row">
		<div class="col-12 col-md-6 offset-md-3 bg-dark rounded-lg">

			<!-- LANDING -->
			<div v-if="page == 'landing'" class="card card-body bg-transparent border-0 shadow-none d-flex justify-content-center" style="height: 90vh;">
				<div class="text-center flex-fill pt-3">
					<p class="text-muted font-weight-light mb-1">
						<i class="fas fa-history fa-5x"></i>
					</p>
					<p class="text-muted font-weight-bold mb-0">STORIES</p>
				</div>
				<div class="flex-fill py-4">
					<div class="card w-100 shadow-none bg-transparent">
						<div class="list-group bg-transparent">
							<!-- <a class="list-group-item text-center lead text-decoration-none text-dark" href="#">Camera</a> -->
							<a class="list-group-item bg-transparent lead text-decoration-none text-light font-weight-bold border-light" href="#" @click.prevent="upload()">
								<i class="fas fa-plus-square mr-2"></i>
								Add to Story
							</a>
							<a v-if="stories.length" class="list-group-item bg-transparent lead text-decoration-none text-lighter font-weight-bold border-muted" href="#" @click.prevent="edit()">
								<i class="far fa-clone mr-2"></i>
								My Story
							</a>
							<a v-if="stories.length" class="list-group-item bg-transparent lead text-decoration-none text-lighter font-weight-bold border-muted" href="#" @click.prevent="viewMyStory()">
								<i class="fas fa-history mr-2"></i>
								View My Story
							</a>
							<!-- <a v-if="stories.length" class="list-group-item bg-transparent lead text-decoration-none text-lighter font-weight-bold border-muted" href="#" @click.prevent="edit()">
								<i class="fas fa-network-wired mr-1"></i>
								Audience
							</a> -->
							<!-- <a v-if="stories.length" class="list-group-item bg-transparent lead text-decoration-none text-lighter font-weight-bold border-muted" href="#" @click.prevent="edit()">
								<i class="far fa-chart-bar mr-2"></i>
								Stats
							</a> -->
							<!-- <a class="list-group-item bg-transparent lead text-decoration-none text-lighter font-weight-bold border-muted" href="#" @click.prevent="edit()">
								<i class="far fa-folder mr-2"></i>
								Archived
							</a> -->
							<!-- <a class="list-group-item bg-transparent lead text-decoration-none text-lighter font-weight-bold border-muted" href="#" @click.prevent="edit()">
								<i class="far fa-question-circle mr-2"></i>
								Help
							</a> -->
							<a class="list-group-item bg-transparent lead text-decoration-none text-lighter font-weight-bold border-muted" href="/">
								<i class="fas fa-arrow-left mr-2"></i>
								Go back
							</a>
							<!-- <a class="list-group-item text-center lead text-decoration-none text-dark" href="#">Options</a> -->
						</div>
					</div>
				</div>
				<div class="text-center flex-fill">
					<!-- <p class="text-lighter small text-uppercase">
						<a href="/" class="text-muted font-weight-bold">Home</a>
						<span class="px-2 text-lighter">|</span>
						<a href="/i/my/story" class="text-muted font-weight-bold">View My Story</a>
						<span class="px-2 text-lighter">|</span>
						<a href="/site/help" class="text-muted font-weight-bold">Help</a>
					</p> -->
				</div>
			</div>

			<!-- CROP -->
			<div v-if="page == 'crop'" class="card card-body bg-transparent border-0 shadow-none d-flex justify-content-center" style="height: 90vh;">
				<div class="text-center py-3 d-flex justify-content-between align-items-center">
					<div>
						<button class="btn btn-outline-lighter btn-sm py-1 px-md-3" @click="deleteCurrentStory()"><i class="pr-2 fas fa-chevron-left fa-sm"></i> Delete</button>
					</div>
					<div class="">
						<p class="text-muted font-weight-light mb-1">
							<i class="fas fa-history fa-5x"></i>
						</p>
						<p class="text-muted font-weight-bold mb-0">STORIES</p>
					</div>
					<div>
						<button class="btn btn-primary btn-sm py-1 px-md-3" @click="performCrop()">Crop <i class="pl-2 fas fa-chevron-right fa-sm"></i></button>
					</div>
				</div>
				<div class="flex-fill">
					<div class="card w-100 mt-3">
						<div class="card-body p-0">
							<vue-cropper
								ref="croppa"
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
			</div>

			<!-- ERROR -->
			<div v-if="page == 'error'" class="card card-body bg-transparent border-0 shadow-none d-flex justify-content-center align-items-center" style="height: 90vh;">
				<p class="h3 mb-0 text-light">Oops!</p>
				<p class="text-muted lead">An error occurred, please try again later.</p>
				<p class="text-muted mb-0">
					<a class="btn btn-outline-secondary py-0 px-5 font-weight-bold" href="/">Go back</a>
				</p>
			</div>

			<!-- UPLOADING -->
			<div v-if="page == 'uploading'" class="card card-body bg-transparent border-0 shadow-none d-flex justify-content-center align-items-center" style="height: 90vh;">
				<p v-if="uploadProgress != 100" class="display-4 mb-0 text-muted">Uploading {{uploadProgress}}%</p>
				<p v-else class="display-4 mb-0 text-muted">Processing ...</p>
			</div>

			<!-- CROPPING -->
			<div v-if="page == 'cropping'" class="card card-body bg-transparent border-0 shadow-none d-flex justify-content-center align-items-center" style="height: 90vh;">
				<p class="display-4 mb-0 text-muted">Cropping ...</p>
			</div>

			<!-- PREVIEW -->
			<div v-if="page == 'preview'" class="card card-body bg-transparent border-0 shadow-none d-flex justify-content-center align-items-center" style="height: 90vh;">
				<div>
					<div class="form-group">
						<label for="durationSlider" class="text-light lead font-weight-bold">Story Duration</label>
						<input type="range" class="custom-range" min="3" max="10" id="durationSlider" v-model="duration">
						<p class="help-text text-center">
							<span class="text-light">{{duration}} seconds</span>
						</p>
					</div>
					<hr class="my-3">
					<a class="btn btn-primary btn-block px-5 font-weight-bold my-3" href="#" @click.prevent="shareStoryToFollowers()">
						Share Story with followers
					</a>

					<a class="btn btn-outline-muted btn-block px-5 font-weight-bold" href="/" @click.prevent="deleteCurrentStory()">
						Cancel
					</a>
				</div>
				<!-- <a class="btn btn-outline-secondary btn-block px-5 font-weight-bold" href="#">
					Share Story with everyone
				</a> -->
			</div>

			<!-- EDIT -->
			<div v-if="page == 'edit'" class="card card-body bg-transparent border-0 shadow-none d-flex justify-content-center" style="height: 90vh;">
				<div class="text-center flex-fill mt-5">
					<p class="text-muted font-weight-light mb-1">
						<i class="fas fa-history fa-5x"></i>
					</p>
					<p class="text-muted font-weight-bold mb-0">STORIES</p>
				</div>
				<div class="flex-fill py-4">
					<p class="lead font-weight-bold text-lighter">My Stories</p>
					<div class="card w-100 shadow-none bg-transparent" style="max-height: 50vh; overflow-y: scroll">
						<div class="list-group">
							<div v-for="(story, index) in stories" class="list-group-item bg-transparent text-center border-muted text-lighter" href="#">
								<div class="media align-items-center">
									<div class="mr-3 cursor-pointer" @click="showLightbox(story)">
										<img :src="story.src" class="rounded-circle border" width="40px" height="40px" style="object-fit: cover;">
									</div>
									<div class="media-body text-left">
										<p class="mb-0 text-muted font-weight-bold"><span>{{story.created_ago}} ago</span></p>
									</div>
									<div class="flex-grow-1 text-right">
										<button @click="deleteStory(story, index)" class="btn btn-link btn-sm">
											<i class="fas fa-trash-alt fa-lg text-muted"></i>
										</button>
									</div>
								</div>
							</div>
						</div>
					</div>
				</div>
				<div class="flex-fill text-center">
					<a class="btn btn-outline-secondary btn-block px-5 font-weight-bold" href="/i/stories/new" @click.prevent="goBack()">Go back</a>
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
		size="md"
		class="bg-transparent"
		body-class="p-0 bg-transparent"
		>
		<div v-if="lightboxMedia" class="w-100 h-100 bg-transparent">
			<img :src="lightboxMedia.url" style="max-height: 90vh; width: 100%; object-fit: contain;">
		</div>
	</b-modal>
</div>
</template>

<style type="text/css">
.bg-black {
	background-color: #262626;
}
#lightbox .modal-content {
	background: transparent;
}
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
					viewMode: 2,
					zoomable: true,
					zoom: null
				},
				mediaUrl: null,
				mediaId: null,
				stories: [],
				lightboxMedia: false,
				duration: 3
			};
		},

		mounted() {
			$('body').addClass('bg-black');
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

					io.value = null;
					axios.post('/api/stories/v0/add', form, xhrConfig)
					.then(function(e) {
						self.uploadProgress = 100;
						self.uploading = false;
						self.mediaUrl = e.data.media_url;
						self.mediaId = e.data.media_id;
						self.page = e.data.media_type === 'video' ? 'preview' : 'crop';
						// window.location.href = '/i/my/story';
					}).catch(function(e) {
						self.uploading = false;
						io.value = null;
						let msg = e.response.data.message ? e.response.data.message : 'Something went wrong.'
						swal('Oops!', msg, 'warning');
						self.page = 'error';
					});
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
			},

			navigateTo(path = '/') {
				window.location.href = path;
			},

			goBack() {
				this.page = 'landing';
			},

			performCrop() {
				this.page = 'cropping';
				let data = this.$refs.croppa.getData();
				axios.post('/api/stories/v0/crop', {
					media_id: this.mediaId,
					width: data.width,
					height: data.height,
					x: data.x,
					y: data.y
				}).then(res => {
					this.page = 'preview';
				});
			},

			deleteCurrentStory() {
				let story = {
					id: this.mediaId
				};
				this.deleteStory(story);
				this.page = 'landing';
			},

			shareStoryToFollowers() {
				axios.post('/api/stories/v0/publish', {
					media_id: this.mediaId,
					duration: this.duration
				}).then(res => {
					window.location.href = '/i/my/story?id=' + this.mediaId;
				})
			},

			viewMyStory() {
				window.location.href = '/i/my/story';
			}
		}
	}
</script>
