<template>
	<div>
		<input type="file" name="media" class="d-none file-input" multiple="">
		<div class="timeline">
			<div class="card status-card card-md-rounded-0">

				<div class="card-header d-inline-flex align-items-center bg-white">
					<img v-bind:src="profile.avatar" width="32px" height="32px" style="border-radius: 32px;" class="box-shadow">
					<a class="username font-weight-bold pl-2 text-dark" v-bind:href="profile.url">
						{{profile.username}}
					</a>
					<div class="text-right" style="flex-grow:1;">
						<div class="dropdown">
							<button class="btn btn-link text-dark no-caret dropdown-toggle" type="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false" title="Post options">
								<span class="fas fa-ellipsis-v fa-lg text-muted"></span>
							</button>
							<div class="dropdown-menu dropdown-menu-right" aria-labelledby="dropdownMenuButton">
								<div class="dropdown-item small font-weight-bold" v-on:click="mediaDrawer = !mediaDrawer">Show Media Toolbar</div>
								<div class="dropdown-divider"></div>
							</div>
						</div>
					</div>
				</div>

				<div class="postPresenterContainer">


					<div v-if="ids.length == 0" class="w-100 h-100 bg-light py-5 cursor-pointer" style="border-bottom: 1px solid #f1f1f1" v-on:click="addMedia()">
						<p class="text-center mb-0 font-weight-bold p-5">Click here to add photos.</p>
					</div>
					<div v-if="ids.length > 0">
						
						<b-carousel id="p-carousel"
							style="text-shadow: 1px 1px 2px #333;"
							controls
							indicators
							background="#ffffff"
							:interval="0"
							v-model="carouselCursor"
						>
							<b-carousel-slide  v-if="ids.length > 0" v-for="(preview, index) in media" :key="'preview_media_'+index">
								<div slot="img" :class="[media[index].filter_class?media[index].filter_class + ' cursor-pointer':' cursor-pointer']" v-on:click="addMedia()">
									<img class="d-block img-fluid w-100" :src="preview.url" :alt="preview.description" :title="preview.description">
								</div>
							</b-carousel-slide>
						</b-carousel>
					</div>

					<div v-if="mediaDrawer" class="bg-lighter p-2 row">
						<div class="col-4">
							<select class="form-control form-control-sm" id="filterSelectDropdown" v-on:change="toggleFilter($event)">
								<option value="none">No filter</option>
								<option v-for="(filter, index) in filters" :value="filter[1]" :selected="filter[1]==media[carouselCursor].filter_class?'selected':''">{{filter[0]}}</option>
							</select>
						</div>
						<div class="col-5">
							<button class="btn btn-outline-primary btn-sm mr-1" v-on:click="mediaAltText()">Alt Text</button>
							<button class="btn btn-outline-primary btn-sm mr-1" v-on:click="mediaLicense()">License</button>
						</div>
						<div class="col-3 text-right">
							<button class="btn btn-outline-danger btn-sm font-weight-bold mr-1" v-on:click="deleteMedia()"><i class="fas fa-trash"></i></button>
							<button class="btn btn-outline-secondary btn-sm font-weight-bold" v-on:click="updateMedia()"><i class="fas fa-times"></i></button>
						</div>
					</div>
				</div>

				<div :class="[mediaDrawer?'glass card-body disabled':'card-body']">
					<div class="reactions my-1">
						<h3 class="far fa-heart pr-3 m-0 text-lighter" title="Like"></h3>
						<h3 class="far fa-comment pr-3 m-0 text-lighter" title="Comment"></h3>
					</div>

					<div class="likes font-weight-bold">
						<span class="like-count">0</span> likes
					</div>
					<div class="caption">
						<p class="mb-2 read-more" style="overflow: hidden;">
							<span class="username font-weight-bold d-inline-block clearfix">
								<bdi><a class="text-dark" :href="profile.url">{{profile.username}}</a></bdi>
							</span>
							<span contenteditable="" style="outline:none;" v-on:keyup="textWatcher"></span>
						</p>
					</div>
					<div class="comments">
					</div>
					<div class="timestamp pt-1">
						<p class="small text-uppercase mb-0">
							<span class="text-muted">
								Draft
							</span>
						</p>
					</div>
				</div>

				<div :class="[mediaDrawer?'glass card-footer':'card-footer']">
					<div class="d-flex justify-content-between align-items-center">
						<div>
							<div class="custom-control custom-switch d-inline mr-3">
								<input type="checkbox" class="custom-control-input" id="nsfwToggle" v-model="nsfw">
								<label class="custom-control-label small font-weight-bold text-muted pt-1" for="nsfwToggle">NSFW</label>
							</div>
							<div class="dropdown d-inline">
								<button class="btn btn-outline-secondary btn-sm py-0 dropdown-toggle" type="button" id="visibility" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
									Public
								</button>
								<div class="dropdown-menu" aria-labelledby="visibility" style="width: 200px;">
									<a class="dropdown-item active" href="#" data-id="public" data-title="Public">
										<div class="row">
											<div class="col-12 col-sm-2 px-0 text-center">
												<i class="fas fa-globe"></i>
											</div> 
											<div class="col-12 col-sm-10 pl-2">
												<p class="font-weight-bold mb-0">Public</p>
												<p class="small mb-0">Anyone can see</p>
											</div> 
										</div>
									</a>
									<a class="dropdown-item" href="#" data-id="private" data-title="Followers Only">
										<div class="row">
											<div class="col-12 col-sm-2 px-0 text-center">
												<i class="fas fa-lock"></i>
											</div> 
											<div class="col-12 col-sm-10 pl-2">
												<p class="font-weight-bold mb-0">Followers Only</p>
												<p class="small mb-0">Only followers can see</p>
											</div> 
										</div>
									</a>

									<a class="dropdown-item" href="#" data-id="circle" data-title="Circle">
										<div class="row">
											<div class="col-12 col-sm-2 px-0 text-center">
												<i class="far fa-circle"></i>
											</div> 
											<div class="col-12 col-sm-10 pl-2">
												<p class="font-weight-bold mb-0">Circle</p>
												<p class="small mb-0">Select a circle</p>
											</div> 
										</div>
									</a>
									<a class="dropdown-item" href="#" data-id="direct" data-title="Direct Message">
										<div class="row">
											<div class="col-12 col-sm-2 px-0 text-center">
												<i class="fas fa-envelope"></i>
											</div> 
											<div class="col-12 col-sm-10 pl-2">
												<p class="font-weight-bold mb-0">Direct Message</p>
												<p class="small mb-0">Recipients only</p>
											</div> 
										</div>
									</a>
								</div>
							</div>
						</div>
						<div class="small text-muted font-weight-bold">
							{{composeTextLength}} / 500
						</div>
						<div class="pl-md-5">
							<div class="btn-group">
								<button type="button" class="btn btn-primary btn-sm font-weight-bold" v-on:click="compose()">{{composeState[0].toUpperCase() + composeState.slice(1)}}</button>
								<button type="button" class="btn btn-primary btn-sm dropdown-toggle dropdown-toggle-split" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
									<span class="sr-only">Toggle Dropdown</span>
								</button>
								<div class="dropdown-menu dropdown-menu-right">
									<a :class="[composeState == 'publish' ?'dropdown-item font-weight-bold active':'dropdown-item font-weight-bold ']" href="#" v-on:click.prevent="composeState = 'publish'">Publish now</a>
									<a :class="[composeState == 'draft' ?'dropdown-item font-weight-bold active':'dropdown-item font-weight-bold ']" href="#" v-on:click.prevent="composeState = 'draft'">Save as draft</a>
									<a :class="[composeState == 'schedule' ?'dropdown-item font-weight-bold active':'dropdown-item font-weight-bold ']" href="#" v-on:click.prevent="composeState = 'schedule'">Schedule for later</a>
									<div class="dropdown-divider"></div>
									<a :class="[composeState == 'delete' ?'dropdown-item font-weight-bold active':'dropdown-item font-weight-bold ']" href="#" v-on:click.prevent="composeState = 'delete'">Delete</a>
								</div>
							</div>
						</div>
					</div>
				</div>

			</div>
		</div>
	</div>
</template>

<style type="text/css" scoped>
.glass {
	-webkit-filter: blur(2px);
	-moz-filter: blur(2px);
	-o-filter: blur(2px);
	-ms-filter: blur(2px);
	filter: blur(2px);
	width: 100%;
	height: 100%;
}
</style>
<script type="text/javascript">
export default {
	data() {
		return {
			profile: {},
			create: {
				hasGeneratedSelect: false, 
				selectedFilter: false, 
				currentFilterName: false, 
				currentFilterClass: false
			},
			composeText: '',
			composeTextLength: 27,
			nsfw: false,
			filters: [],
			ids: [],
			media: [],
			meta: {
				'id': false,
				'cursor': false,
				'cw': false,
				'alt': null,
				'filter': null,
				'license': null,
				'preserve_exif': false,
			},
			cursor: 1,
			carouselCursor: 0,
			visibility: 'public',
			cropmode: false,
			croppie: false,
			limit: pixelfed.settings.maxAlbumLength,
			acceptedMimes: pixelfed.settings.acceptedMimes,
			mediaDrawer: false,
			composeState: 'publish',
			filter: {
				name: null,
				class: null
			}
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

	watch: {
		composeText: function (newComposeText, oldComposeText) {
			this.debouncedTextWatcher();
		}
	},

	created: function() {
		this.debouncedTextWatcher = _.debounce(this.textWatcher, 300)
	},

	methods: {

		fetchProfile() {
			axios.get('/api/v1/accounts/verify_credentials').then(res => {
				this.profile = res.data;
			}).catch(err => {
				console.log(err)
			});
		},

		addMedia() {
			let el = $(event.target);
			el.attr('disabled', '');
			let fi = $('.file-input[name="media"]');
			fi.trigger('click');
			el.blur();
			el.removeAttr('disabled');
		},

		textWatcher() {
			this.composeText = event.target.innerText;
			this.composeTextLength = event.target.innerText.length;
		},

		mediaWatcher() {
			let self = this;
			$(document).on('change', '.file-input', function(e) {
				let io = document.querySelector('.file-input');
				Array.prototype.forEach.call(io.files, function(io, i) {
					if(self.media && self.media.length + i >= self.limit) {
						return;
					}
					let type = io.type;
					let acceptedMimes = pixelfed.settings.acceptedMimes.split(',');
					let validated = $.inArray(type, acceptedMimes);
					if(validated == -1) {
						swal('Invalid File Type', 'The file you are trying to add is not a valid mime type. Please upload a '+pixelfed.uploader.acceptedMimes+' only.', 'error');
						return;
					}

					let form = new FormData();
					form.append('file', io);

					let config = {
						onUploadProgress: function(e) {
							let progress = Math.round( (e.loaded * 100) / e.total );
						}
					};

					axios.post('/api/v1/media', form, config)
					.then(function(e) {
						self.ids.push(e.data.id);
						self.media.push(e.data);
						self.mediaDrawer = true;
					}).catch(function(e) {
						swal('Oops, something went wrong!', 'An unexpected error occurred.', 'error');
					});
					io.value = null;
				});
			});
		},

		toggleFilter(e) {
			this.media[this.carouselCursor].filter_class = e.target.value;

			// todo: deprecate
			this.create.selectedFilter = true;
			this.create.filterName = val;
			this.create.filterClass = val;
			this.create.currentFilterName = val;
			this.create.currentFilterClass = val;
			this.filter.class = val;
		},

		updateMedia() {
			this.mediaDrawer = false;
		},

		deleteMedia() {
			let id = this.media[this.carouselCursor].id;
			axios.delete('/api/v1/media', {
				params: {
					id: id
				}
			}).then(res => {
				if(this.media.length == 0) {
					this.mediaDrawer = false;
				}
				this.ids.splice(this.carouselCursor, 1);
				this.media.splice(this.carouselCursor, 1);
			}).catch(err => {
				swal('Whoops!', 'An error occured when attempting to delete this, please try again', 'error');
			});
		},

		mediaAltText() {
			swal({
				text: 'Add a media description',
				content: "input"
			}).then(val => {
				let media = this.media[this.carouselCursor];
				media.alt = val;
			});

		},

		mediaLicense() {
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
			if(this.media.length == 0) {
				swal('Whoops!', 'You need to add media before you can save this!', 'warning');
				return;
			}
			let data = {
				media: this.media,
				caption: this.composeText,
				visibility: this.visibility,
				cw: this.nsfw
			};
			axios.post('/api/local/status/compose', data)
			.then(res => {
				let data = res.data;
				window.location.href = data;
			}).catch(err => {
				swal('Oops, something went wrong!', 'An unexpected error occurred.', 'error');
			});
		}
	}
}
</script>