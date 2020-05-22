<template>
<div>
	<input type="file" id="pf-dz" name="media" class="w-100 h-100 d-none file-input" v-bind:accept="config.uploader.media_types">
	<div class="timeline">
		<div v-if="uploading">
			<div class="card status-card card-md-rounded-0 w-100 h-100 bg-light py-3" style="border-bottom: 1px solid #f1f1f1">
				<div class="p-5 mt-2">
					<b-progress :value="uploadProgress" :max="100" striped :animated="true"></b-progress>
					<p class="text-center mb-0 font-weight-bold">Uploading ... ({{uploadProgress}}%)</p>
				</div>
			</div>
		</div>
		<div v-else-if="page == 'cameraRoll'">
			<div class="card status-card card-md-rounded-0" style="display:flex;">
				<div class="card-header d-inline-flex align-items-center justify-content-between bg-white">
					<span class="pr-3">
						<i class="fas fa-cog fa-lg text-muted"></i>
					</span>
					<span class="font-weight-bold">
						Camera Roll
					</span>
					<span class="text-primary font-weight-bold">Upload</span>
				</div>
				<div class="h-100 card-body p-0 border-top" style="width:100%; min-height: 400px;">
					<div v-if="cameraRollMedia.length > 0" class="row p-0 m-0">
						<div v-for="(m, index) in cameraRollMedia" :class="[index == 0 ? 'col-12 p-0' : 'col-3 p-0']">
							<div class="card info-overlay p-0 rounded-0 shadow-none border">
								<div class="square">
									<img class="square-content" :src="m.preview_url"></img>
								</div>
							</div>
						</div>
					</div>
					<div v-else class="w-100 h-100 d-flex justify-content-center align-items-center">
						<span class="w-100 h-100">
							<button type="button" class="btn btn-primary">Upload</button>
							<button type="button" class="btn btn-primary" @click="fetchCameraRollDrafts()">Load Camera Roll</button>
						</span>
					</div>
				</div>
			</div>
		</div>
		<div v-else>
			<div class="card status-card card-md-rounded-0 w-100 h-100" style="display:flex;">
				<div class="card-header d-inline-flex align-items-center justify-content-between bg-white">
					<div>
						<a v-if="page == 1" href="#" @click.prevent="closeModal()" class="font-weight-bold text-decoration-none text-muted">
							<i class="fas fa-times fa-lg"></i>
							<span class="font-weight-bold mb-0">{{pageTitle}}</span>
						</a>
						<span v-else-if="page == 2">
							<button v-if="config.uploader.album_limit > media.length" class="btn btn-outline-primary btn-sm font-weight-bold" @click.prevent="addMedia" data-toggle="tooltip" data-placement="bottom" title="Upload another photo or video" ><i class="fas fa-plus"></i></button>
							<!-- <button v-if="config.uploader.album_limit > media.length" class="btn btn-outline-primary btn-sm font-weight-bold" @click.prevent="page = 'cameraRoll'" data-toggle="tooltip" data-placement="bottom" title="Upload another photo or video" ><i class="fas fa-chevron-left"></i> Camera Roll</button> -->
							<button v-else class="btn btn-outline-secondary btn-sm font-weight-bold" disabled><i class="fas fa-plus"></i></button>
						</span>
						<span v-else-if="page == 3">
							<a class="text-lighter text-decoration-none mr-3 d-flex align-items-center" href="#" @click.prevent="goBack()">
								<i class="fas fa-long-arrow-alt-left fa-lg mr-2"></i>
								<span class="btn btn-outline-secondary btn-sm px-2 py-0 disabled" disabled="">{{media.length}}</span>
							</a>
							<span class="font-weight-bold mb-0">{{pageTitle}}</span>
						</span>
						<span v-else>
							<a class="text-lighter text-decoration-none mr-3" href="#" @click.prevent="goBack()"><i class="fas fa-long-arrow-alt-left fa-lg"></i></a>
						</span>
						<span class="font-weight-bold mb-0">{{pageTitle}}</span>
					</div>
					<div v-if="page == 2">
						<a v-if="media.length == 1" href="#" class="text-center text-dark" @click.prevent="showCropPhotoCard"><i class="fas fa-magic fa-lg"></i></a>
					</div>
					<div>
						<!-- <a v-if="page > 1" class="font-weight-bold text-decoration-none" href="#" @click.prevent="page--">Back</a> -->
						<span v-if="pageLoading">
							<div class="spinner-border spinner-border-sm" role="status">
								<span class="sr-only">Loading...</span>
							</div>
						</span>
						<span v-else>
							<a v-if="!pageLoading && (page > 1 && page <= 2) || (page == 1 && ids.length != 0) || page == 'cropPhoto'" class="font-weight-bold text-decoration-none" href="#" @click.prevent="nextPage">Next</a>
							<a v-if="!pageLoading && page == 3" class="font-weight-bold text-decoration-none" href="#" @click.prevent="compose()">Post</a>
						</span>
					</div>
				</div>
				<div class="card-body p-0 border-top">
					<div v-if="page == 1" class="w-100 h-100 d-flex justify-content-center align-items-center" style="min-height: 400px;">
						<div class="text-center">
							<div v-if="media.length == 0" class="card mx-md-5 my-md-3 shadow-none border compose-action text-decoration-none text-dark">
								<div @click.prevent="addMedia" class="card-body">
									<div class="media">
										<div class="mr-3 align-items-center justify-content-center" style="display:inline-flex;width:40px;height:40px;border-radius: 100%;background-color: #008DF5">
											<i class="fas fa-bolt text-white fa-lg"></i>
										</div>	
										<div class="media-body text-left">
											<p class="mb-0">
												<span class="h5 mt-0 font-weight-bold text-primary">New Post</span> 
											</p>
											<p class="mb-0 text-muted">Share up to {{config.uploader.album_limit}} photos or videos</p>
										</div>
									</div>
								</div>
							</div>
							<a v-if="config.features.stories == true" class="card mx-md-5 my-md-3 shadow-none border compose-action text-decoration-none text-dark" href="/i/stories/new">
								<div class="card-body">
									<div class="media">
										<div class="mr-3 align-items-center justify-content-center" style="display:inline-flex;width:40px;height:40px;border-radius: 100%;border: 2px solid #008DF5">
											<i class="fas fa-history text-primary fa-lg"></i>
										</div>	
										<div class="media-body text-left">
											<p class="mb-0">
												<span class="h5 mt-0 font-weight-bold text-primary">New Story</span> 
												<sup class="float-right mt-2">
													<span class="btn btn-outline-lighter p-1 btn-sm font-weight-bold py-0" style="font-size:10px;line-height: 0.6">BETA</span>
												</sup>
											</p>
											<p class="mb-0 text-muted">Add Photo to Story</p>
										</div>
									</div>
								</div>
							</a>

							<a class="card mx-md-5 my-md-3 shadow-none border compose-action text-decoration-none text-dark" href="/i/collections/create">
								<div class="card-body">
									<div class="media">
										<div class="mr-3 align-items-center justify-content-center" style="display:inline-flex;width:40px;height:40px;border-radius: 100%;border: 2px solid #008DF5">
											<i class="fas fa-images text-primary fa-lg"></i>
										</div>	
										<div class="media-body text-left">
											<p class="mb-0">
												<span class="h5 mt-0 font-weight-bold text-primary">New Collection</span> 
												<sup class="float-right mt-2">
													<span class="btn btn-outline-lighter p-1 btn-sm font-weight-bold py-0" style="font-size:10px;line-height: 0.6">BETA</span>
												</sup>
											</p>
											<p class="mb-0 text-muted">New collection of posts</p>
										</div>
									</div>
								</div>
							</a>

							
							<p class="py-3">
								<a class="font-weight-bold" href="/site/help">Help</a>
							</p>
						</div>
					</div>

					<div v-if="page == 'cropPhoto'" class="w-100 h-100">
						<div v-if="ids.length > 0">
							<vue-cropper
								ref="cropper"
								:relativeZoom="cropper.zoom"
								:aspectRatio="cropper.aspectRatio"
								:viewMode="cropper.viewMode"
								:zoomable="cropper.zoomable"
								:rotatable="true"
								:src="media[carouselCursor].url"
							>
							</vue-cropper>
						</div>
					</div>

					<div v-if="page == 2" class="w-100 h-100">
						<div v-if="media.length == 1">
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
						<div v-else-if="media.length > 1" class="d-flex-inline px-2 pt-2">
							<ul class="nav media-drawer-filters text-center">
								<li class="nav-item mx-md-4">&nbsp;</li>
								<li v-for="(m, i) in media" class="nav-item mx-md-4">
										<div class="nav-link" style="display:block;width:300px;height:300px;" @click="carouselCursor = i">
											<!-- <img :class="'d-block img-fluid w-100 ' + [m.filter_class?m.filter_class:'']" :src="m.url" :alt="m.description" :title="m.description"> -->
											<span :class="[m.filter_class?m.filter_class:'']">
												
												<span :class="'rounded border ' +  [i == carouselCursor ? ' border-primary shadow':'']" :style="'display:block;padding:5px;width:100%;height:100%;background-image: url(' + m.url + ');background-size:cover;border-width:3px !important;'"></span>
											</span>
										</div>
										<div v-if="i == carouselCursor" class="text-center mb-0 small text-lighter font-weight-bold pt-2">
											<span class="cursor-pointer" @click.prevent="showCropPhotoCard">Crop</span>
											<span class="cursor-pointer px-3" @click.prevent="showEditMediaCard()">Edit</span>
											<span class="cursor-pointer" @click="deleteMedia()">Delete</span>
										</div>
								</li>
								<li class="nav-item mx-md-4">&nbsp;</li>
							</ul>
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
						<div v-else>
							<p class="mb-0 p-5 text-center font-weight-bold">An error occured, please refresh the page.</p>
						</div>
					</div>

					<div v-if="page == 3" class="w-100 h-100">
						<div class="border-bottom mt-2">
							<div class="media px-3">
								<img :src="media[0].url" width="42px" height="42px" :class="[media[0].filter_class?'mr-2 ' + media[0].filter_class:'mr-2']">
								<div class="media-body">
									<div class="form-group">
										<label class="font-weight-bold text-muted small d-none">Caption</label>
										<textarea class="form-control border-0 rounded-0 no-focus" rows="2" placeholder="Write a caption..." style="resize:none" v-model="composeText" v-on:keyup="composeTextLength = composeText.length"></textarea>
										<p class="help-text small text-right text-muted mb-0">{{composeTextLength}}/{{config.uploader.max_caption_length}}</p>
									</div>
								</div>
							</div>
						</div>
						<div class="border-bottom d-flex justify-content-between px-4 mb-0 py-2 ">
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
						<!-- <div class="border-bottom">
							<p class="px-4 mb-0 py-2 cursor-pointer" @click="showTagCard()">Tag people</p>
						</div> -->
						<div class="border-bottom">
							<p class="px-4 mb-0 py-2 cursor-pointer" @click="showLocationCard()" v-if="!place">Add location</p>
							<p v-else class="px-4 mb-0 py-2">
								<span class="text-lighter">Location:</span> {{place.name}}, {{place.country}}
								<span class="float-right">
									<a href="#" @click.prevent="showLocationCard()" class="btn btn-outline-secondary btn-sm small mr-2" style="font-size:10px;padding:3px;text-transform: uppercase">Edit</a>
									<a href="#" @click.prevent="place = false" class="btn btn-outline-secondary btn-sm small" style="font-size:10px;padding:3px;text-transform: uppercase">Remove</a>
								</span>
							</p>
						</div>
						<div class="border-bottom">
							<p class="px-4 mb-0 py-2">
								<span class="text-lighter">Visibility:</span> {{visibilityTag}}
								<span class="float-right">
									<a v-if="profile.locked == false" href="#" @click.prevent="showVisibilityCard()" class="btn btn-outline-secondary btn-sm small mr-2" style="font-size:10px;padding:3px;text-transform: uppercase">Edit</a>
								</span>
							</p>
						</div>
						<!-- <div class="cursor-pointer border-bottom px-4 mb-0 py-2" @click.prevent="showMediaDescriptionsCard()">
							<div class="d-flex justify-content-between align-items-center">
								<div>
									<div class="text-dark">Media Descriptions</div>
									<p class="text-muted small mb-0">Describe your photos for people with visual impairments.</p>
								</div>
								<div>
									<i class="fas fa-chevron-right fa-lg text-lighter"></i>
								</div>
							</div>
						</div> -->

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
							<a href="#" class="list-group-item" @click.prevent="showMediaDescriptionsCard()">
								<div class="d-flex justify-content-between align-items-center">
									<div>
										<div class="text-dark">Media Descriptions</div>
										<p class="text-muted small mb-0">Describe your photos for people with visual impairments.</p>
									</div>
									<div>
										<i class="fas fa-chevron-right fa-lg text-lighter"></i>
									</div>
								</div>
							</a>
							<!-- <a href="#" class="list-group-item" @click.prevent="showAddToCollectionsCard()">
								<div class="d-flex justify-content-between align-items-center">
									<div>
										<div class="text-dark">Add to Collection</div>
										<p class="text-muted small mb-0">Add this post to a collection.</p>
									</div>
									<div>
										<i class="fas fa-chevron-right fa-lg text-lighter"></i>
									</div>
								</div>
							</a>
							<a href="#" class="list-group-item" @click.prevent="page = 'schedulePost'">
								<div class="d-flex justify-content-between align-items-center">
									<div>
										<div class="text-dark">Schedule</div>
										<p class="text-muted small mb-0">Schedule post for a future date.</p>
									</div>
									<div>
										<i class="fas fa-chevron-right fa-lg text-lighter"></i>
									</div>
								</div>
							</a>
							<a href="#" class="list-group-item" @click.prevent="page = 'mediaMetadata'">
								<div class="d-flex justify-content-between align-items-center">
									<div>
										<div class="text-dark">Metadata</div>
										<p class="text-muted small mb-0">Manage media exif and metadata.</p>
									</div>
									<div>
										<i class="fas fa-chevron-right fa-lg text-lighter"></i>
									</div>
								</div>
							</a> -->
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
						<div v-for="(m, index) in media">
							<div class="media">
								<img :src="m.preview_url" class="mr-3" width="50px" height="50px">
								<div class="media-body">
									<textarea class="form-control" v-model="m.alt" placeholder="Add a media description here..."></textarea>
									<p class="help-text small text-right text-muted mb-0">{{m.alt ? m.alt.length : 0}}/140</p>
								</div>
							</div>
							<hr>
						</div>
						<p class="d-flex justify-content-between mb-0">
							<button type="button" @click="goBack()" class="btn btn-link text-muted font-weight-bold text-decoration-none">Cancel</button>
							<button type="button" @click="goBack()" class="btn btn-primary font-weight-bold">Save</button>
						</p>
					</div>

					<div v-if="page == 'addToCollection'" class="w-100 h-100 p-3">
						<div class="list-group mb-3">
							<div class="list-group-item cursor-pointer compose-action border" @click="goBack()">
								<div class="media">
								  <img src="" class="mr-3" alt="" width="50px" height="50px">
								  <div class="media-body">
								    <h5 class="mt-0">collection title</h5>
								    <p class="mb-0 text-muted small">3 Photos - Created 2h ago</p>
								  </div>
								</div>
							</div>
						</div>
						<p class="d-flex justify-content-between mb-0">
							<button type="button" @click="goBack()" class="btn btn-link text-muted font-weight-bold text-decoration-none">Cancel</button>
							<button type="button" @click="goBack()" class="btn btn-primary font-weight-bold">Save</button>
						</p>
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

					<div v-if="page == 'editMedia'" class="w-100 h-100 p-3">
						<div class="media">
							<img :src="media[carouselCursor].preview_url" class="mr-3" width="50px" height="50px">
							<div class="media-body">
								<div class="form-group">
									<label class="font-weight-bold text-muted small">Media Description</label>
									<textarea class="form-control" v-model="media[carouselCursor].alt" placeholder="Add a media description here..."></textarea>
									<p class="help-text small text-muted mb-0 d-flex justify-content-between">
										<span>Describe your photo for people with visual impairments.</span>
										<span>{{media[carouselCursor].alt ? media[carouselCursor].alt.length : 0}}/140</span>
									</p>
								</div>
								<div class="form-group">
									<label class="font-weight-bold text-muted small">License</label>
									<input type="text" class="form-control" v-model="media[carouselCursor].license" placeholder="All Rights Reserved (Default license)">
									<p class="help-text small text-muted mb-0 d-flex justify-content-between">
										<span></span>
										<span>{{media[carouselCursor].license ? media[carouselCursor].license.length : 0}}/140</span>
									</p>
								</div>
							</div>
						</div>
						<hr>
						<p class="d-flex justify-content-between mb-0">
							<button type="button" @click="goBack()" class="btn btn-link text-muted font-weight-bold text-decoration-none">Cancel</button>
							<button type="button" @click="goBack()" class="btn btn-primary font-weight-bold">Save</button>
						</p>
					</div>

				</div>

				<!-- card-footers -->
				<div v-if="page == 'cropPhoto'" class="card-footer bg-white d-flex justify-content-between">
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
</div>
</template>

<style type="text/css" scoped>
	.media-drawer-filters {
		overflow-x: scroll;
		flex-wrap:unset;
	}
	.media-drawer-filters::-webkit-scrollbar {
		width: 0px;
		background: transparent;
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
	.compose-action:hover {
		cursor: pointer;
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
				'cropPhoto',
				'tagPeople',
				'addLocation',
				'advancedSettings',
				'visibility',
				'altText',
				'addToCollection',
				'schedulePost',
				'mediaMetadata',
				'addToStory',
				'editMedia',
				'cameraRoll'
			],
			cameraRollMedia: []
		}
	},

	beforeMount() {
		this.fetchProfile();
		if(this.config.uploader.media_types.includes('video/mp4') == false) {
			this.composeType = 'post'
		}
		this.filters = window.App.util.filters;
	},

	mounted() {
		this.mediaWatcher();
	},

	updated() {
		if(this.page == 2) {
			$('[data-toggle="tooltip"]').tooltip();
		}
	},

	methods: {
		fetchProfile() {
			axios.get('/api/pixelfed/v1/accounts/verify_credentials').then(res => {
				this.profile = res.data;
				window.pixelfed.currentUser = res.data;
				if(res.data.locked == true) {
					this.visibility = 'private';
					this.visibilityTag = 'Followers Only';
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
					self.uploading = false;
					self.page = 2;
					return;
				}
				let type = io.type;
				let acceptedMimes = self.config.uploader.media_types.split(',');
				let validated = $.inArray(type, acceptedMimes);
				if(validated == -1) {
					swal('Invalid File Type', 'The file you are trying to add is not a valid mime type. Please upload a '+self.config.uploader.media_types+' only.', 'error');
					self.uploading = false;
					self.page = 2;
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

				axios.post('/api/pixelfed/v1/media', form, xhrConfig)
				.then(function(e) {
					self.uploadProgress = 100;
					self.ids.push(e.data.id);
					self.media.push(e.data);
					self.uploading = false;
					setTimeout(function() {
						self.page = 2;
					}, 300);
				}).catch(function(e) {
					self.uploading = false;
					io.value = null;
					swal('Oops, something went wrong!', 'An unexpected error occurred.', 'error');
					self.page = 2;
				});
				io.value = null;
				self.uploadProgress = 0;
			});
		},

		toggleFilter(e, filter) {
			this.media[this.carouselCursor].filter_class = filter;
		},

		deleteMedia() {
			if(window.confirm('Are you sure you want to delete this media?') == false) {
				return;
			}
			let id = this.media[this.carouselCursor].id;
			
			axios.delete('/api/pixelfed/v1/media', {
				params: {
					id: id
				}
			}).then(res => {
				this.ids.splice(this.carouselCursor, 1);
				this.media.splice(this.carouselCursor, 1);
				if(this.media.length == 0) {
					this.ids = [];
					this.media = [];
					this.carouselCursor = 0;
				} else {
					this.carouselCursor = 0;
				}
			}).catch(err => {
				swal('Whoops!', 'An error occured when attempting to delete this, please try again', 'error');
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
						let msg = err.response.data.message ? err.response.data.message : 'An unexpected error occured.'
						swal('Oops, something went wrong!', msg, 'error');
					});
					return;
				break;

				case 'delete' :
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

		closeModal() {
			this.composeType = '';
			$('#composeModal').modal('hide');
		},

		goBack() {
			this.pageTitle = '';
			
			switch(this.page) {
				case 'cropPhoto':
				case 'editMedia':
					this.page = 2;
				break;

				default:
					this.namedPages.indexOf(this.page) != -1 ? this.page = 3 : this.page--;
				break;
			}
		},

		nextPage() {
			this.pageTitle = '';
			switch(this.page) {
				case 1:
					this.page = 2;
				break;

				case 'cropPhoto':
					this.pageLoading = true;
					let self = this;
					this.$refs.cropper.getCroppedCanvas({  
							maxWidth: 4096,
							maxHeight: 4096,
							fillColor: '#fff',
							imageSmoothingEnabled: false,
							imageSmoothingQuality: 'high',
						}).toBlob(function(blob) {
						let data = new FormData();
						data.append('file', blob);
						let url = '/api/local/compose/media/update/' + self.ids[self.carouselCursor];

						axios.post(url, data).then(res => {
							self.media[self.carouselCursor].url = res.data.url;
							self.pageLoading = false;
							self.page = 2;
						}).catch(err => {
						});
					});
				break;

				case 2:
				case 3:
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
			this.page = 3;
			return;
		},

		showVisibilityCard() {
			this.pageTitle = 'Post Visibility';
			this.page = 'visibility';
		},

		showAddToStoryCard() {
			this.pageTitle = 'Add to Story';
			this.page = 'addToStory';
		},

		showCropPhotoCard() {
			this.pageTitle = 'Edit Photo';
			this.page = 'cropPhoto';
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
			this.page = 3;
		},

		showMediaDescriptionsCard() {
			this.pageTitle = 'Media Descriptions';
			this.page = 'altText';
		},

		showAddToCollectionsCard() {
			this.pageTitle = 'Add to Collection';
			this.page = 'addToCollection';
		},

		showSchedulePostCard() {
			this.pageTitle = 'Schedule Post';
			this.page = 'schedulePost';
		},

		showEditMediaCard() {
			this.pageTitle = 'Edit Media';
			this.page = 'editMedia';
		},

		fetchCameraRollDrafts() {
			axios.get('/api/pixelfed/local/drafts')
			.then(res => {
				this.cameraRollMedia = res.data;
			});
		},

	}
}
</script>
