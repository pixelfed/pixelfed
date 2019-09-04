<template>
<div>
	<input type="file" id="pf-dz" name="media" class="w-100 h-100 d-none file-input" draggable="true" multiple="true" v-bind:accept="config.uploader.media_types">
	<div class="timeline">
		<div class="card status-card card-md-rounded-0 w-100 h-100" style="display:flex;">
			<div class="card-header d-inline-flex align-items-center bg-white">
				<div>
					<a v-if="page == 1" href="#" @click.prevent="closeModal()" class="font-weight-bold text-decoration-none text-muted">
						<i class="fas fa-times fa-lg"></i>
						<span class="font-weight-bold mb-0">{{pageTitle}}</span>
					</a>
					<span v-else>
						<span>
							<a class="text-lighter text-decoration-none mr-3" href="#" @click.prevent="goBack()"><i class="fas fa-long-arrow-alt-left fa-lg"></i></a>
						</span>
						<span class="font-weight-bold mb-0">{{pageTitle}}</span>
					</span>
				</div>
				<div class="text-right" style="flex-grow:1;">
					<!-- <a v-if="page > 1" class="font-weight-bold text-decoration-none" href="#" @click.prevent="page--">Back</a> -->
					<span v-if="pageLoading">
						<div class="spinner-border spinner-border-sm" role="status">
							<span class="sr-only">Loading...</span>
						</div>
					</span>
					<a v-if="!pageLoading && (page > 1 && page <= 3) || (page == 1 && ids.length != 0)" class="font-weight-bold text-decoration-none" href="#" @click.prevent="nextPage">Next</a>
					<a v-if="!pageLoading && page == 4" class="font-weight-bold text-decoration-none" href="#" @click.prevent="compose">Post</a>
				</div>
			</div>
			<div class="card-body p-0 border-top">
				<div v-if="page == 1" class="w-100 h-100 d-flex justify-content-center align-items-center" style="min-height: 400px;">
					<div class="text-center">
						<p>
							<a class="btn btn-primary font-weight-bold" href="/i/compose">Compose Post</a>
						</p>
						<hr>
						<p>
							<button type="button" class="btn btn-outline-primary font-weight-bold" @click.prevent="addMedia">Compose Post <sup>BETA</sup></button>
						</p>
						<p>
							<button class="btn btn-outline-primary font-weight-bold" @click.prevent="createCollection">New Collection</button>
						</p>
						<!-- <p>
							<button class="btn btn-outline-primary font-weight-bold" @click.prevent="showAddToStoryCard()">Add To My Story</button>
						</p> -->
						<p>
							<a class="font-weight-bold" href="/site/help">Need Help?</a>
						</p>
						<p class="text-muted mb-0 small text-center">Formats: <b>{{acceptedFormats()}}</b> up to <b>{{maxSize()}}</b></p>
						<p class="text-muted mb-0 small text-center">Albums can contain up to <b>{{config.uploader.album_limit}}</b> photos or videos</p>
					</div>
				</div>

				<div v-if="page == 2" class="w-100 h-100">
					<div v-if="ids.length > 0">
						<vue-cropper
							ref="cropper"
							:relativeZoom="cropper.zoom"
							:aspectRatio="cropper.aspectRatio"
							:viewMode="cropper.viewMode"
							:zoomable="cropper.zoomable"
							:rotatable="true"
							:src="media[0].url"
						>
						</vue-cropper>
					</div>
				</div>

				<div v-if="page == 3" class="w-100 h-100">
					<div slot="img" style="display:flex;min-height: 420px;align-items: center;">
						<img :class="'d-block img-fluid w-100 ' + [media[carouselCursor].filter_class?media[carouselCursor].filter_class:'']" :src="media[carouselCursor].url" :alt="media[carouselCursor].description" :title="media[carouselCursor].description">
					</div>
					<hr>
					<div v-if="ids.length > 0 && media[carouselCursor].type == 'Image'" class="align-items-center px-2 pt-2">
						<ul class="nav media-drawer-filters text-center">
							<li class="nav-item">
								<div class="p-1 pt-3">
									<img :src="media[carouselCursor].url" width="100px" height="60px" v-on:click.prevent="toggleFilter($event, null)" class="cursor-pointer">
								</div>
								<a :class="[media[carouselCursor].filter_class == null ? 'nav-link text-primary active' : 'nav-link text-muted']" href="#" v-on:click.prevent="toggleFilter($event, null)">No Filter</a>
							</li>
							<li class="nav-item" v-for="(filter, index) in filters">
								<div class="p-1 pt-3">
									<img :src="media[carouselCursor].url" width="100px" height="60px" :class="filter[1]" v-on:click.prevent="toggleFilter($event, filter[1])">
								</div>
								<a :class="[media[carouselCursor].filter_class == filter[1] ? 'nav-link text-primary active' : 'nav-link text-muted']" href="#" v-on:click.prevent="toggleFilter($event, filter[1])">{{filter[0]}}</a>
							</li>
						</ul>
					</div>
				</div>

				<div v-if="page == 4" class="w-100 h-100">
					<div class="border-bottom mt-2">
						<div class="media px-3">
							<img :src="media[0].url" width="42px" height="42px" :class="[media[0].filter_class?'mr-2 ' + media[0].filter_class:'mr-2']">
							<div class="media-body">
								<div class="form-group">
									<label class="font-weight-bold text-muted small d-none">Caption</label>
									<textarea class="form-control border-0 rounded-0 no-focus" rows="2" placeholder="Write a caption..." style="resize:none" v-model="composeText"></textarea>
								</div>
							</div>
						</div>
					</div>
					<div class="border-bottom">
						<p class="px-4 mb-0 py-2 cursor-pointer" @click="showTagCard()">Tag people</p>
					</div>
					<div class="border-bottom">
						<p class="px-4 mb-0 py-2 cursor-pointer" @click="showLocationCard()" v-if="!place">Add location</p>
						<p v-else class="px-4 mb-0 py-2">
							<span class="text-lighter">Location:</span> {{place.name}}, {{place.country}}
							<span class="float-right">
								<a href="#" @click.prevent="showLocationCard()" class="text-muted font-weight-bold small mr-2">Change</a>
								<a href="#" @click.prevent="place = false" class="text-muted font-weight-bold small">Remove</a>
							</span>
						</p>
					</div>
					<div class="border-bottom">
						<p class="px-4 mb-0 py-2">
							<span class="text-lighter">Visibility:</span> {{visibilityTag}}
							<span class="float-right">
								<a href="#" @click.prevent="showVisibilityCard()" class="text-muted font-weight-bold small mr-2">Change</a>
							</span>
						</p>
					</div>
					<div style="min-height: 200px;">
						<p class="px-4 mb-0 py-2 small font-weight-bold text-muted cursor-pointer" @click="showAdvancedSettingsCard()">Advanced settings</p>
					</div>
				</div>

				<div v-if="page == 'tagPeople'" class="w-100 h-100 p-3">
					<p class="text-center lead text-muted mb-0 py-5">This feature is not available yet.</p>
				</div>

				<div v-if="page == 'addLocation'" class="w-100 h-100 p-3">
					<p class="mb-0">Add Location</p>
					<autocomplete 
						:search="locationSearch"
						placeholder="Search locations ..."
						aria-label="Search locations ..."
						:get-result-value="getResultValue"
						@submit="onSubmitLocation"
					>
					</autocomplete>
				</div>

				<div v-if="page == 'advancedSettings'" class="w-100 h-100">
					<div class="list-group list-group-flush">
						<div class="list-group-item d-flex justify-content-between">
							<div>
								<div class="text-dark ">Turn off commenting</div>
								<p class="text-muted small mb-0">Disables comments for this post, you can change this later.</p>
							</div>
							<div>
								<div class="custom-control custom-switch" style="z-index: 9999;">
									<input type="checkbox" class="custom-control-input" id="asdisablecomments" v-model="commentsDisabled">
									<label class="custom-control-label" for="asdisablecomments"></label>
								</div>
							</div>
						</div>
						<div class="list-group-item d-flex justify-content-between">
							<div>
								<div class="text-dark ">Contains NSFW Media</div>
							</div>
							<div>
								<div class="custom-control custom-switch" style="z-index: 9999;">
									<input type="checkbox" class="custom-control-input" id="asnsfw" v-model="nsfw">
									<label class="custom-control-label" for="asnsfw"></label>
								</div>
							</div>
						</div>
						<a class="list-group-item" @click.prevent="page = 'altText'">
							<div class="text-dark">Write alt text</div>
							<p class="text-muted small mb-0">Alt text describes your photos for people with visual impairments.</p>
						</a>
						<a href="#" class="list-group-item" @click.prevent="page = 'addToCollection'">
							<div class="text-dark">Add to Collection</div>
							<p class="text-muted small mb-0">Add this post to a collection.</p>
						</a>
						<a href="#" class="list-group-item" @click.prevent="page = 'schedulePost'">
							<div class="text-dark">Schedule</div>
							<p class="text-muted small mb-0">Schedule post for a future date.</p>
						</a>
						<a href="#" class="list-group-item" @click.prevent="page = 'mediaMetadata'">
							<div class="text-dark">Metadata</div>
							<p class="text-muted small mb-0">Manage media exif and metadata.</p>
						</a>
					</div>
				</div>

				<div v-if="page == 'visibility'" class="w-100 h-100">
					<div class="list-group list-group-flush">
						<div :class="'list-group-item lead cursor-pointer ' + [visibility == 'public'?'text-primary':'']" @click="toggleVisibility('public')">Public</div>
						<div :class="'list-group-item lead cursor-pointer ' + [visibility == 'unlisted'?'text-primary':'']" @click="toggleVisibility('unlisted')">Unlisted</div>
						<div :class="'list-group-item lead cursor-pointer ' + [visibility == 'private'?'text-primary':'']" @click="toggleVisibility('private')">Followers Only</div>
					</div>
				</div>

				<div v-if="page == 'altText'" class="w-100 h-100 p-3">
					<p class="text-center lead text-muted mb-0 py-5">This feature is not available yet.</p>
				</div>

				<div v-if="page == 'addToCollection'" class="w-100 h-100 p-3">
					<p class="text-center lead text-muted mb-0 py-5">This feature is not available yet.</p>
				</div>

				<div v-if="page == 'schedulePost'" class="w-100 h-100 p-3">
					<p class="text-center lead text-muted mb-0 py-5">This feature is not available yet.</p>
				</div>

				<div v-if="page == 'mediaMetadata'" class="w-100 h-100 p-3">
					<p class="text-center lead text-muted mb-0 py-5">This feature is not available yet.</p>
				</div>

				<div v-if="page == 'addToStory'" class="w-100 h-100 p-3">
					<p class="text-center lead text-muted mb-0 py-5">This feature is not available yet.</p>
				</div>

			</div>

			<!-- card-footers -->
			<div v-if="page == 2" class="card-footer bg-white d-flex justify-content-between">
				<div>
					<button type="button" class="btn btn-outline-secondary" @click="rotate"><i class="fas fa-undo"></i></button>
				</div>
				<div>
					<div class="d-inline-block button-group">
						<button :class="'btn font-weight-bold ' + [cropper.aspectRatio == 16/9 ? 'btn-primary':'btn-light']" @click.prevent="changeAspect(16/9)">16:9</button>
						<button :class="'btn font-weight-bold ' + [cropper.aspectRatio == 4/3 ? 'btn-primary':'btn-light']" @click.prevent="changeAspect(4/3)">4:3</button>
						<button :class="'btn font-weight-bold ' + [cropper.aspectRatio == 3/2 ? 'btn-primary':'btn-light']" @click.prevent="changeAspect(3/2)">3:2</button>
						<button :class="'btn font-weight-bold ' + [cropper.aspectRatio == 1 ? 'btn-primary':'btn-light']" @click.prevent="changeAspect(1)">1:1</button>
						<button :class="'btn font-weight-bold ' + [cropper.aspectRatio == 2/3 ? 'btn-primary':'btn-light']" @click.prevent="changeAspect(2/3)">2:3</button>
					</div>
				</div>
			</div>
		</div>
	</div>
</div>
</template>

<style type="text/css" scoped>
	.media-drawer-filters {
		overflow-x: scroll;
		flex-wrap:unset;
	}
	.media-drawer-filters .nav-link {
		min-width:100px;
		padding-top: 1rem;
		padding-bottom: 1rem;
	}
	.media-drawer-filters .active {
		color: #fff;
		font-weight: bold;
	}
    @media (hover: none) and (pointer: coarse) {
	    .media-drawer-filters::-webkit-scrollbar {
	        display: none;
	    }
    }
    .no-focus {
		border-color: none;
		outline: 0;
		box-shadow: none;
    }
	a.list-group-item {
		text-decoration: none;
	}
	a.list-group-item:hover {
		text-decoration: none;
		background-color: #f8f9fa !important;
	}
</style>

<script type="text/javascript">
import VueCropper from 'vue-cropperjs';
import 'cropperjs/dist/cropper.css';
import Autocomplete from '@trevoreyre/autocomplete-vue'
import '@trevoreyre/autocomplete-vue/dist/style.css'

export default {
	components: { 
		VueCropper,
		Autocomplete 
	},

	data() {
		return {
			config: window.App.config,
			pageLoading: false,
			profile: {},
			composeText: '',
			composeTextLength: 0,
			nsfw: false,
			filters: [],
			ids: [],
			media: [],
			carouselCursor: 0,
			uploading: false,
			uploadProgress: 100,
			composeType: false,
			page: 1,
			composeState: 'publish',
			visibility: 'public',
			visibilityTag: 'Public',
			nsfw: false,
			place: false,
			commentsDisabled: false,
			pageTitle: '',

			cropper: {
				aspectRatio: 1,
				viewMode: 1,
				zoomable: true,
				zoom: 0
			},

			taggedUsernames: false,
			namedPages: [
				'tagPeople',
				'addLocation',
				'advancedSettings',
				'visibility',
				'altText',
				'addToCollection',
				'schedulePost',
				'mediaMetadata',
				'addToStory'
			]
		}
	},

	beforeMount() {
		this.fetchProfile();
	},

	mounted() {
		this.mediaWatcher();
		this.filters = [
			['1977','filter-1977'], 
			['Aden','filter-aden'], 
			['Amaro','filter-amaro'], 
			['Ashby','filter-ashby'], 
			['Brannan','filter-brannan'], 
			['Brooklyn','filter-brooklyn'], 
			['Charmes','filter-charmes'], 
			['Clarendon','filter-clarendon'], 
			['Crema','filter-crema'], 
			['Dogpatch','filter-dogpatch'], 
			['Earlybird','filter-earlybird'], 
			['Gingham','filter-gingham'], 
			['Ginza','filter-ginza'], 
			['Hefe','filter-hefe'], 
			['Helena','filter-helena'], 
			['Hudson','filter-hudson'], 
			['Inkwell','filter-inkwell'], 
			['Kelvin','filter-kelvin'], 
			['Kuno','filter-juno'], 
			['Lark','filter-lark'], 
			['Lo-Fi','filter-lofi'], 
			['Ludwig','filter-ludwig'], 
			['Maven','filter-maven'], 
			['Mayfair','filter-mayfair'], 
			['Moon','filter-moon'], 
			['Nashville','filter-nashville'], 
			['Perpetua','filter-perpetua'], 
			['Poprocket','filter-poprocket'], 
			['Reyes','filter-reyes'], 
			['Rise','filter-rise'], 
			['Sierra','filter-sierra'], 
			['Skyline','filter-skyline'], 
			['Slumber','filter-slumber'], 
			['Stinson','filter-stinson'], 
			['Sutro','filter-sutro'], 
			['Toaster','filter-toaster'], 
			['Valencia','filter-valencia'], 
			['Vesper','filter-vesper'], 
			['Walden','filter-walden'], 
			['Willow','filter-willow'], 
			['X-Pro II','filter-xpro-ii']
		];
	},

	methods: {
		fetchConfig() {
			axios.get('/api/v2/config').then(res => {
				this.config = res.data;
				window.pixelfed.config = window.pixelfed.config || res.data;
				if(this.config.uploader.media_types.includes('video/mp4') == false) {
					this.composeType = 'post'
				}
			});
		},

		fetchProfile() {
			axios.get('/api/v1/accounts/verify_credentials').then(res => {
				this.profile = res.data;
				window.pixelfed.currentUser = res.data;
				if(res.data.locked == true) {
					this.visibility = 'private';
				}
			}).catch(err => {
			});
		},

		addMedia(event) {
			let el = $(event.target);
			el.attr('disabled', '');
			let fi = $('.file-input[name="media"]');
			fi.trigger('click');
			el.blur();
			el.removeAttr('disabled');
		},

		mediaWatcher() {
			let self = this;
			self.mediaDragAndDrop();
			$(document).on('change', '#pf-dz', function(e) {
				self.mediaUpload();
			});
		},

		mediaUpload() {
			let self = this;
			self.uploading = true;
			let io = document.querySelector('#pf-dz');
			Array.prototype.forEach.call(io.files, function(io, i) {
				if(self.media && self.media.length + i >= self.config.uploader.album_limit) {
					swal('Error', 'You can only upload ' + self.config.uploader.album_limit + ' photos per album', 'error');
					return;
				}
				let type = io.type;
				let acceptedMimes = self.config.uploader.media_types.split(',');
				let validated = $.inArray(type, acceptedMimes);
				if(validated == -1) {
					swal('Invalid File Type', 'The file you are trying to add is not a valid mime type. Please upload a '+self.config.uploader.media_types+' only.', 'error');
					return;
				}

				let form = new FormData();
				form.append('file', io);

				let xhrConfig = {
					onUploadProgress: function(e) {
						let progress = Math.round( (e.loaded * 100) / e.total );
						self.uploadProgress = progress;
					}
				};

				axios.post('/api/v1/media', form, xhrConfig)
				.then(function(e) {
					self.uploadProgress = 100;
					self.ids.push(e.data.id);
					self.media.push(e.data);
					self.page = 2;
					setTimeout(function() {
						self.uploading = false;
					}, 1000);
				}).catch(function(e) {
					self.uploading = false;
					io.value = null;
					swal('Oops, something went wrong!', 'An unexpected error occurred.', 'error');
				});
				io.value = null;
				self.uploadProgress = 0;
			});
		},

		mediaDragAndDrop() {
			let self = this;
			let pdz = document.getElementById('content');

			function allowDrag(e) {
				e.dataTransfer.dropEffect = 'copy';
				e.preventDefault();
			}

			function handleDrop(e) {
				e.preventDefault();
				let dz = document.querySelector('#pf-dz');
				dz.files = e.dataTransfer.files;
				$('#composeModal').modal('show');
				self.mediaUpload();
			}

			window.addEventListener('dragenter', function(e) {
			});

			pdz.addEventListener('dragenter', allowDrag);
			pdz.addEventListener('dragover', allowDrag);

			pdz.addEventListener('dragleave', function(e) {
				//
			});

			pdz.addEventListener('drop', handleDrop);
		},

		toggleFilter(e, filter) {
			this.media[this.carouselCursor].filter_class = filter;
		},

		updateMedia() {
			this.mediaDrawer = false;
		},

		deleteMedia() {
			if(window.confirm('Are you sure you want to delete this media?') == false) {
				return;
			}
			let id = this.media[this.carouselCursor].id;
			axios.delete('/api/v1/media', {
				params: {
					id: id
				}
			}).then(res => {
				if(this.media.length == 1) {
					this.mediaDrawer = false;
					this.ids = [];
					this.media = [];
					this.carouselCursor = 0;
				}
				this.ids.splice(this.carouselCursor, 1);
				this.media.splice(this.carouselCursor, 1);
			}).catch(err => {
				swal('Whoops!', 'An error occured when attempting to delete this, please try again', 'error');
			});
		},

		mediaAltText() {
			return;
			// deprecate 
			swal({
				text: 'Add a media description',
				content: "input"
			}).then(val => {
				let media = this.media[this.carouselCursor];
				media.alt = val;
			});

		},

		mediaLicense() {
			return;
			// deprecate
			swal({
				text: 'Add a media license',
				content: "input",
				button: {
					text: "Update",
					closeModal: true,
				},
			}).then(val => {
				let media = this.media[this.carouselCursor];
				media.license = val;
			});

		},

		compose() {
			let state = this.composeState;

			if(this.uploadProgress != 100 || this.ids.length == 0) {
				return;
			}

			if(this.composeText.length > this.config.uploader.max_caption_length) {
				swal('Error', 'Caption is too long', 'error');
				return;
			}

			switch(state) {
				case 'publish' :
					if(this.media.length == 0) {
						swal('Whoops!', 'You need to add media before you can save this!', 'warning');
						return;
					}
					if(this.composeText == 'Add optional caption...') {
						this.composeText = '';
					}
					let data = {
						media: this.media,
						caption: this.composeText,
						visibility: this.visibility,
						cw: this.nsfw,
						comments_disabled: this.commentsDisabled,
						place: this.place
					};
					axios.post('/api/local/status/compose', data)
					.then(res => {
						let data = res.data;
						window.location.href = data;
					}).catch(err => {
						swal('Oops, something went wrong!', 'An unexpected error occurred.', 'error');
					});
					return;
				break;

				case 'delete' :
					this.mediaDrawer = false;
					this.ids = [];
					this.media = [];
					this.carouselCursor = 0;
					this.composeText = '';
					this.composeTextLength = 0;
					$('#composeModal').modal('hide');
					return;
				break;
			}
		},

		about() {
			let text = document.createElement('div');
			text.innerHTML = `
				<p class="small font-weight-bold">Please visit the <a href="/site/kb/sharing-media">Sharing Media</a> page for more info.</p>
			`;
			swal({
				title: 'Compose UI v3', 
				content: text, 
				icon: 'info'
			});
		},

		closeModal() {
			this.composeType = '';
			$('#composeModal').modal('hide');
		},

		composeMessage() {
			let config = this.config;
			let composeType = this.composeType;
			let video = config.uploader.media_types.includes('video/mp4');

			return video ? 
			'Click here to add photos or videos' :
			'Click here to add photos';
		},

		createCollection() {
			window.location.href = '/i/collections/create';
		},

		nextPage() {
			switch(this.page) {
				case 1:
					this.page = 3;
				break;

				case 2:
					this.pageLoading = true;
					let self = this;
					this.$refs.cropper.getCroppedCanvas().toBlob(function(blob) {
						let data = new FormData();
						data.append('file', blob);
						let url = '/api/local/compose/media/update/' + self.ids[self.carouselCursor];

						axios.post(url, data).then(res => {
							self.media[self.carouselCursor].url = res.data.url;
							self.pageLoading = false;
							self.page++;
						}).catch(err => {
						});
					});
				break;

				case 3:
				case 4:
					this.page++;
				break;
			}
		},

		rotate() {
			this.$refs.cropper.rotate(90);
		},

		changeAspect(ratio) {
			this.cropper.aspectRatio = ratio;
			this.$refs.cropper.setAspectRatio(ratio);
		},

		maxSize() {
			let limit = this.config.uploader.max_photo_size;
			return limit / 1000 + ' MB';
		},

		acceptedFormats() {
			let formats = this.config.uploader.media_types;
			return formats.split(',').map(f => {
				return ' ' + f.split('/')[1];
			}).toString();
		},

		showTagCard() {
			this.pageTitle = 'Tag People';
			this.page = 'tagPeople';
		},

		showLocationCard() {
			this.pageTitle = 'Add Location';
			this.page = 'addLocation';
		},

		showAdvancedSettingsCard() {
			this.pageTitle = 'Advanced Settings';
			this.page = 'advancedSettings';
		},

		locationSearch(input) {
			if (input.length < 1) { return []; };
			let results = [];
			return axios.get('/api/local/compose/location/search', {
				params: {
					q: input
				}
			}).then(res => {
				return res.data;
			});
		},

		getResultValue(result) {
			return result.name + ', ' + result.country
		},

		onSubmitLocation(result) {
			this.place = result;
			this.pageTitle = '';
			this.page = 4;
			return;
		},

		goBack() {
			this.pageTitle = '';
			if(this.page == 'addToStory') {
				this.page = 1;
			} else {
				this.namedPages.indexOf(this.page) != -1 ? this.page = 4 : this.page--;
			}
		},

		showVisibilityCard() {
			this.pageTitle = 'Post Visibility';
			this.page = 'visibility';
		},

		showAddToStoryCard() {
			this.pageTitle = 'Add to Story';
			this.page = 'addToStory';
		},

		toggleVisibility(state) {
			let tags = {
				public: 'Public',
				private: 'Followers Only',
				unlisted: 'Unlisted'
			}
			this.visibility = state;
			this.visibilityTag = tags[state];
			this.pageTitle = '';
			this.page = 4;
		}
	}
}
</script>
