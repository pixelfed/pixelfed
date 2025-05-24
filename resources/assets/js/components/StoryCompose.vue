<template>
<div class="story-compose-component container mt-2 mt-md-5 bg-black">
	<input type="file" id="pf-dz" name="media" class="d-none file-input" v-bind:accept="config.mimes">
	<span class="fixed-top text-right m-3 cursor-pointer" @click="navigateTo()">
		<i class="fal fa-times-circle fa-2x text-lighter"></i>
	</span>
	<div v-if="loaded" class="row">
		<div class="col-12 col-md-6 offset-md-3 bg-dark rounded-lg px-0">

			<!-- LANDING -->
			<div v-if="page == 'landing'" class="card card-body bg-transparent border-0 shadow-none d-flex justify-content-center" style="height: 90vh;">
				<div class="text-center flex-fill pt-3">
					<img class="mb-2" src="/img/pixelfed-icon-color.svg" width="70" height="70">
					<p class="lead text-lighter font-weight-light mb-0">Stories</p>
				</div>
				<div class="flex-fill py-4">
					<p class="text-center lead font-weight-light text-lighter mb-4">{{ $t("story.shareWithFollowers")}}</p>
					<div class="card w-100 shadow-none bg-transparent">
						<div class="d-flex">
							<button type="button" class="btn btn-outline-light btn-lg font-weight-bold btn-block rounded-pill my-1" :disabled="stories.length >= 20" @click="upload()">
								{{ $t("story.add")}}
							</button>
							<!-- <button :disabled="stories.length >= 20" type="button" class="btn btn-outline-light btn-lg font-weight-bold btn-block rounded-pill my-1 ml-2" @click="newPoll">
								Create Poll
							</button> -->
						</div>
						<p
							v-if="stories.length >= 20"
							class="font-weight-bold text-muted text-center">
							{{ $t("story.limit") }}
						</p>

						<button
							type="button"
							class="btn btn-outline-light btn-lg font-weight-bold btn-block rounded-pill my-3"
							@click="viewMyStory"
							:disabled="stories.length == 0">
							<span>{{ $t("story.myStory")  }}</span>
							<sup v-if="stories.length" class="ml-2 px-2 text-light bg-danger rounded-pill" style="font-size: 12px;padding-top:2px;padding-bottom:3px;">{{ stories.length }}</sup>
						</button>

					</div>
				</div>
				<div class="text-center flex-fill">
					<p class="text-uppercase mb-0">
						<a href="/" class="text-lighter font-weight-bold">Home</a>
						<span class="px-2 text-lighter">|</span>
						<a href="/site/help" class="text-lighter font-weight-bold">{{ $t("navmenu.help")}}</a>
					</p>
					<p class="small text-muted mb-0">v 1.0.0</p>
				</div>
			</div>

			<div v-else-if="page == 'crop'" class="d-flex justify-content-center flex-fill" style="position: relative;height: 90vh;">
				<vue-cropper
					class="w-100 h-100 p-0"
					ref="croppa"
					:aspectRatio="cropper.aspectRatio"
					:viewMode="3"
					:dragMode="'move'"
					:autoCropArea="1"
					:guides="false"
					:highlight="false"
					:cropBoxMovable="false"
					:cropBoxResizable="false"
					:toggleDragModeOnDblclick="false"
					:src="mediaUrl"
				>
				</vue-cropper>
				<div class="crop-container">
					<div class="d-flex justify-content-between align-items-center">
						<button
							type="button"
							class="btn btn-outline-muted rounded-pill font-weight-bold px-4"
							@click="deleteCurrentStory()">
							{{ $t("story.cancel")}}
						</button>

						<div class="text-center">
							<h4 class="font-weight-light text-light mb-n1">{{ $t("story.crop")}}</h4>
							<span class="small text-light">{{ $t("story.zoom")  }}</span>
						</div>

						<button
							type="button"
							class="btn btn-outline-light rounded-pill font-weight-bold px-4"
							@click="performCrop()">
							{{ $t("story.next")  }}
						</button>
					</div>
				</div>
			</div>

			<div v-else-if="page == 'error'" class="card card-body bg-transparent border-0 shadow-none d-flex justify-content-center align-items-center" style="height: 90vh;">
				<div class="text-center flex-fill pt-3">
					<img class="mb-2" src="/img/pixelfed-icon-color.svg" width="70" height="70">
					<p class="lead text-lighter font-weight-light mb-0">Stories</p>
				</div>
				<div class="flex-fill text-center">
					<p class="h3 mb-0 text-light">{{ $t("common.oops") }}</p>
					<p class="text-muted lead">{{ $t("common.errorMsg")}}</p>
					<p class="text-muted mb-0">
						<a class="btn btn-outline-muted py-0 px-5 rounded-pill font-weight-bold" href="/">{{ $t("story.goBack")  }}</a>
					</p>
				</div>
			</div>

			<div v-else-if="page == 'uploading'" class="card card-body bg-transparent border-0 shadow-none d-flex justify-content-center align-items-center" style="height: 90vh;">
				<div class="spinner-border text-lighter" role="status">
					<span class="sr-only">{{ $t('common.loading') }}</span>
				</div>
			</div>

			<div v-else-if="page == 'cropping'" class="card card-body bg-transparent border-0 shadow-none d-flex justify-content-center align-items-center" style="height: 90vh;">
				<div class="spinner-border text-lighter" role="status">
					<span class="sr-only">{{ $t('common.loading') }}</span>
				</div>
			</div>

			<div v-else-if="page == 'preview'" class="card card-body bg-transparent border-0 shadow-none d-flex justify-content-center align-items-center" style="height: 90vh;">
				<div class="text-center flex-fill pt-3">
					<img class="mb-2" src="/img/pixelfed-icon-color.svg" width="70" height="70">
					<p class="lead text-lighter font-weight-light mb-0">Stories</p>
				</div>
				<div class="flex-fill">
					<div class="form-group pb-3">
						<label for="durationSlider" class="text-light lead font-weight-bold">{{ $t("story.options")  }}</label>
						<div class="custom-control custom-checkbox mb-2">
							<input type="checkbox" class="custom-control-input" id="optionReplies" v-model="canReply">
							<label class="custom-control-label text-light font-weight-lighter" for="optionReplies">{{ $t("story.allowReplies")  }}</label>
						</div>
						<div class="custom-control custom-checkbox mb-2">
							<input type="checkbox" class="custom-control-input" id="formReactions" v-model="canReact">
							<label class="custom-control-label text-light font-weight-lighter" for="formReactions">{{ $t("story.allowReactions")  }}</label>
						</div>
					</div>
					<div v-if="!canPostPoll" class="form-group">
						<video ref="previewVideo" v-if="mediaType == 'video'" class="mb-4 w-100" style="max-height:200px;object-fit:contain;">
							<source :src="mediaUrl" type="video/mp4">
						</video>
						<label for="durationSlider" class="text-light lead font-weight-bold">{{ $t("story.storyDuration")  }}</label>
						<input type="range" class="custom-range" min="3" :max="max_duration" step="1" id="durationSlider" v-model="duration">
						<p class="help-text text-center">
							<span class="text-light">{{duration}} {{ $t("story.seconds")  }}</span>
						</p>
					</div>
				</div>
				<div class="flex-fill w-100 px-md-5">
					<div class="d-flex">
						<a class="btn btn-outline-muted btn-block font-weight-bold my-3 mr-3 rounded-pill" href="/" @click.prevent="deleteCurrentStory()">
							{{ $t("story.cancel")  }}
						</a>

						<a class="btn btn-primary btn-block font-weight-bold my-3 rounded-pill" href="#" @click.prevent="shareStoryToFollowers()">
							Post {{ canPostPoll ? 'Poll' : 'Story'}}
						</a>
					</div>
				</div>
			</div>

			<div v-else-if="page == 'edit'" class="card card-body bg-transparent border-0 shadow-none d-flex justify-content-center" style="height: 90vh;">
				<div class="text-center flex-fill mt-5">
					<p class="text-muted font-weight-light mb-1">
						<i class="fal fa-history fa-5x"></i>
					</p>
					<p class="text-muted font-weight-bold mb-0">STORIES</p>
				</div>
				<div class="flex-fill py-4">
					<p class="lead font-weight-bold text-lighter">{{ $t('story.myStories') }}</p>
					<div class="card w-100 shadow-none bg-transparent" style="max-height: 50vh; overflow-y: scroll">
						<div class="list-group">
							<div v-for="(story, index) in stories" class="list-group-item bg-transparent text-center border-muted text-lighter" href="#">
								<div class="media align-items-center">
									<div class="mr-3 cursor-pointer" @click="showLightbox(story)">
										<img :src="story.src" class="rounded-circle border" width="40px" height="40px" style="object-fit: cover;">
									</div>
									<div class="media-body text-left">
										<p class="mb-0 text-muted font-weight-bold"><span>{{timeago(story.created_at)}} ago</span></p>
									</div>
									<div class="flex-grow-1 text-right">
										<button v-if="story.viewers.length" @click="toggleShowViewers(index)" class="btn btn-link btn-sm mr-1">
											<i class="fal fa-eye fa-lg text-muted"></i>
										</button>
										<button @click="deleteStory(story, index)" class="btn btn-link btn-sm">
											<i class="fal fa-trash-alt fa-lg text-muted"></i>
										</button>
									</div>
								</div>
								<div v-if="story.showViewers && story.viewers.length" class="m-2 text-left">
									<p class="font-weight-bold mb-2">{{ $t("story.viewdBy") }}</p>
									<div v-for="viewer in story.viewers" class="d-flex">
										<img src="/storage/avatars/default.png" width="24" height="24" class="rounded-circle mr-2">
										<p class="mb-0 font-weight-bold">viewer.username</p>
									</div>
								</div>
							</div>
						</div>
					</div>
				</div>
				<div class="flex-fill text-center">
					<a class="btn btn-outline-secondary btn-block px-5 font-weight-bold" href="/i/stories/new" @click.prevent="goBack()">{{ $t("story.goBack") }}</a>
				</div>
			</div>

			<div v-else-if="page == 'createPoll'" class="card card-body bg-transparent border-0 shadow-none d-flex justify-content-center" style="height: 90vh;">
				<div class="text-center pt-3">
					<img class="mb-2" src="/img/pixelfed-icon-color.svg" width="70" height="70">
					<p class="lead text-lighter font-weight-light mb-0">Stories</p>
				</div>
				<div class="flex-fill mt-3">
					<div class="align-items-center">
						<div class="form-group mb-5">
							<label class="font-weight-bold text-lighter">Poll Question</label>
							<input class="form-control form-control-lg rounded-pill bg-muted shadow text-white border-0" placeholder="Ask a poll question here..." v-model="pollQuestion" />
						</div>
						<label class="font-weight-bold text-lighter">Poll Answers</label>
						<div v-for="(option, index) in pollOptions" class="form-group mb-4">
							<input class="form-control form-control-lg rounded-pill bg-muted shadow text-white border-0" placeholder="Add a poll answer here..." v-model="pollOptions[index]" />
						</div>
						<div v-if="pollOptions.length < 4" class="mb-3">
							<button
								class="btn btn-block font-weight-bold rounded-pill shadow"
								:class="[ (pollQuestion && pollQuestion.length) > 6 && (pollOptions.length == 0 || pollOptions.length && pollOptions[pollOptions.length - 1].length > 3) ? 'btn-muted' : 'btn-outline-muted' ]"
								:disabled="!pollQuestion || pollQuestion.length < 6"
								@click="addOptionInput">
								Add poll option
							</button>
						</div>
						<!-- <div v-for="(option, index) in pollOptions" class="form-group mb-4 d-flex align-items-center" style="max-width:400px;position: relative;">
							<span class="font-weight-bold mr-2" style="position: absolute;left: 10px;">{{ index + 1 }}.</span>
							<input v-if="pollOptions[index].length < 50" type="text" class="form-control rounded-pill" placeholder="Add a poll option, press enter to save" v-model="pollOptions[index]" style="padding-left: 30px;padding-right: 90px;">
							<textarea v-else class="form-control" v-model="pollOptions[index]" placeholder="Add a poll option, press enter to save" rows="3" style="padding-left: 30px;padding-right:90px;"></textarea>
							<button class="btn btn-danger btn-sm rounded-pill font-weight-bold" style="position: absolute;right: 5px;" @click="deletePollOption(index)">
								<i class="fas fa-trash"></i> Delete
							</button>
						</div> -->
					</div>
				</div>
				<div class="flex-fill text-center">
					<a v-if="canPostPoll" class="btn btn-outline-light btn-block px-5 font-weight-bold rounded-pill" href="/i/stories/new" @click.prevent="pollPreview">{{ $t("story.next")}}</a>
					<a class="btn btn-outline-secondary btn-block px-5 font-weight-bold rounded-pill" href="/i/stories/new" @click.prevent="goBack()">{{ $t('story.goBack')}}</a>
				</div>
			</div>
		</div>
	</div>
	<div v-else class="row">
		<div class="col-12 col-md-6 offset-md-3 bg-dark rounded-lg px-0" style="height: 90vh;">
			<div class="w-100 h-100 d-flex justify-content-center align-items-center">
				<div class="spinner-border text-lighter" role="status">
					<span class="sr-only">{{ $t('common.loading') }}</span>
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
					'video/mp4'
				],
				page: 'landing',
				pages: [
					'landing',
					'crop',
					'edit',
					'confirm',
					'error',
					'uploading',
					'createPoll'
				],
				uploading: false,
				uploadProgress: 0,
				cropper: {
					aspectRatio: 9/16,
					viewMode: 3,
					zoomable: true,
					zoom: null
				},
				mediaUrl: null,
				mediaId: null,
				mediaType: null,
				stories: [],
				lightboxMedia: false,
				duration: 10,
				canReply: true,
				canReact: true,
				poll: {
					question: null,
					options: []
				},
				pollQuestion: null,
				pollOptions: [],
				canPostPoll: false,
				max_duration: 15
			};
		},

		watch: {
			duration: function(val) {
				if(this.mediaType == 'video') {
					this.$refs.previewVideo.currentTime = val;
					this.$refs.previewVideo.play();
				}
			},

			pollQuestion: function(val) {
				if(val.length < 6) {
					this.canPostPoll = false;
				}
			},

			pollOptions: function(val) {
				let len = this.pollOptions.filter(o => {
					return o.length >= 2;
				});

				if(len.length >= 2) {
					this.canPostPoll = true;
				} else {
					this.canPostPoll = false;
				}
			}
		},

		mounted() {
			$('body').addClass('bg-black');
			this.mediaWatcher();
			setTimeout(() => {
				axios.get('/api/web/stories/v1/profile/' + this.profileId)
				.then(res => {
					if(res.data.length) {
						this.stories = res.data[0].nodes.map(s => {
							s.showViewers = false;
							s.viewers = [];
							return s;
						});
					}
					this.loaded = true;
				});
			}, 400);
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
					axios.post('/api/web/stories/v1/add', form, xhrConfig)
					.then(function(e) {
						self.uploadProgress = 100;
						self.uploading = false;
						self.mediaUrl = e.data.media_url;
						self.mediaId = e.data.media_id;
						self.mediaType = e.data.media_type;
						self.page = e.data.media_type === 'video' ? 'preview' : 'crop';
						if(e.data.hasOwnProperty('media_duration')) {
							self.max_duration = e.data.media_duration;
						}
						// window.location.href = '/i/my/story';
					}).catch(function(e) {
						self.uploading = false;
						io.value = null;
						let msg = e.response.data.message ? e.response.data.message : e.response.data.error ? e.response.data.error :'Something went wrong.'
						swal('Oops!', msg, 'warning');
						self.page = 'error';
					});
					self.uploadProgress = 0;
				});
				document.querySelector('#pf-dz').value = '';
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

				axios.delete('/api/web/stories/v1/delete/' + story.id)
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
				axios.post('/api/web/stories/v1/crop', {
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
				if(this.canPostPoll) {
					axios.post('/api/web/stories/v1/publish/poll', {
						question: this.pollQuestion,
						options: this.pollOptions,
						can_reply: this.canReply,
						can_react: this.canReact
					}).then(res => {
						window.location.href = '/i/my/story?id=' + this.mediaId;
					})
				} else {
					axios.post('/api/web/stories/v1/publish', {
						media_id: this.mediaId,
						duration: this.duration,
						can_reply: this.canReply,
						can_react: this.canReact
					}).then(res => {
						window.location.href = '/i/my/story?id=' + this.mediaId;
					})
				}
			},

			viewMyStory() {
				window.location.href = '/i/my/story';
			},

			toggleShowViewers(index) {
				this.stories[index].showViewers = this.stories[index].showViewers ? false : true;
			},

			timeago(ts) {
				return App.util.format.timeAgo(ts);
			},

			newPoll() {
				this.page = 'createPoll';
			},

			addOptionInput() {
				let c = this.pollOptions.filter(o => {
					return o.length < 3;
				});
				if(c.length) {
					return;
				}
				this.pollOptions.push([]);
			},

			pollPreview() {
				let opts = this.pollOptions;
				let dd = [...new Set(this.pollOptions)];
				if(dd.length != opts.length) {
					swal('Oops!', 'You cannot use duplicate poll answers, please remove any duplicates and try again.', 'error');
					return;
				}
				this.page = 'preview';
			}
		}
	}
</script>

<style lang="scss">
	.bg-black {
		background-color: #262626;
	}
</style>
<style lang="scss" scoped>
	.story-compose-component {
		#lightbox .modal-content {
			background: transparent;
		}

		::placeholder {
			color: #ccc;
		}

		.crop-container {
			z-index: 9;
			position: absolute;
			top: 0;
			width: 100%;
			min-height: 100px;
			padding: 15px 30px;
			background: linear-gradient(180deg, rgba(38,38,38, 0.8) 0%, rgba(38,38,38,0) 100%);
		}
	}
</style>
