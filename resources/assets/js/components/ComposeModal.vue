<template>
<div class="compose-modal-component">
	<input type="file" id="pf-dz" name="media" class="w-100 h-100 d-none file-input" multiple="" v-bind:accept="config.uploader.media_types">
	<canvas class="d-none" id="pr_canvas"></canvas>
	<img class="d-none" id="pr_img">
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

		<div v-else-if="page == 'poll'">
			<div class="card status-card card-md-rounded-0" style="display:flex;">
				<div class="card-header d-inline-flex align-items-center justify-content-between bg-white">
					<span class="pr-3">
						<i class="fas fa-info-circle fa-lg text-primary"></i>
					</span>
					<span class="font-weight-bold">
						New Poll
					</span>
					<span v-if="postingPoll">
						<div class="spinner-border spinner-border-sm" role="status">
							<span class="sr-only">Loading...</span>
						</div>
					</span>
					<button v-else-if="!postingPoll && pollOptions.length > 1 && composeText.length" class="btn btn-primary btn-sm font-weight-bold" @click="postNewPoll">
						<span>Create Poll</span>
					</button>
					<span v-else class="font-weight-bold text-lighter">
						Create Poll
					</span>
				</div>
				<div class="h-100 card-body p-0 border-top" style="width:100%; min-height: 400px;">
					<div class="border-bottom mt-2">
						<div class="media px-3">
							<img :src="profile.avatar" width="42px" height="42px" class="rounded-circle">
							<div class="media-body">
								<div class="form-group">
									<label class="font-weight-bold text-muted small d-none">Caption</label>
									<vue-tribute :options="tributeSettings">
										<textarea class="form-control border-0 rounded-0 no-focus" rows="3" placeholder="Write a poll question..." style="" v-model="composeText" v-on:keyup="composeTextLength = composeText.length"></textarea>
									</vue-tribute>
									<p class="help-text small text-right text-muted mb-0">{{composeTextLength}}/{{config.uploader.max_caption_length}}</p>
								</div>
							</div>
						</div>
					</div>

					<div class="p-3">
						<p class="font-weight-bold text-muted small">
							Poll Options
						</p>

						<div v-if="pollOptions.length < 4" class="form-group mb-4">
							<input type="text" class="form-control rounded-pill" placeholder="Add a poll option, press enter to save" v-model="pollOptionModel" @keyup.enter="savePollOption">
						</div>

						<div v-for="(option, index) in pollOptions" class="form-group mb-4 d-flex align-items-center" style="max-width:400px;position: relative;">
							<span class="font-weight-bold mr-2" style="position: absolute;left: 10px;">{{ index + 1 }}.</span>
							<input v-if="pollOptions[index].length < 50" type="text" class="form-control rounded-pill" placeholder="Add a poll option, press enter to save" v-model="pollOptions[index]" style="padding-left: 30px;padding-right: 90px;">
							<textarea v-else class="form-control" v-model="pollOptions[index]" placeholder="Add a poll option, press enter to save" rows="3" style="padding-left: 30px;padding-right:90px;"></textarea>
							<button class="btn btn-danger btn-sm rounded-pill font-weight-bold" style="position: absolute;right: 5px;" @click="deletePollOption(index)">
								<i class="fas fa-trash"></i> Delete
							</button>
						</div>

						<hr>

						<div class="d-flex justify-content-between">
							<div>
								<p class="font-weight-bold text-muted small">
									Poll Expiry
								</p>

								<div class="form-group">
									<select class="form-control rounded-pill" style="width: 200px;" v-model="pollExpiry">
										<option value="60">1 hour</option>
										<option value="360">6 hours</option>
										<option value="1440" selected>24 hours</option>
										<option value="10080">7 days</option>
									</select>
								</div>
							</div>

							<div>
								<p class="font-weight-bold text-muted small">
									Poll Visibility
								</p>

								<div class="form-group">
									<select class="form-control rounded-pill" style="max-width: 200px;" v-model="visibility">
										<option value="public">Public</option>
										<option value="private">Followers Only</option>
									</select>
								</div>
							</div>
						</div>
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
							<button v-if="config.uploader.album_limit > media.length" class="btn btn-outline-primary btn-sm font-weight-bold" @click.prevent="addMedia" id="cm-add-media-btn"><i class="fas fa-plus"></i></button>
							<!-- <button v-if="config.uploader.album_limit > media.length" class="btn btn-outline-primary btn-sm font-weight-bold" @click.prevent="page = 'cameraRoll'" data-toggle="tooltip" data-placement="bottom" title="Upload another photo or video" ><i class="fas fa-chevron-left"></i> Camera Roll</button> -->
							<button v-else class="btn btn-outline-secondary btn-sm font-weight-bold" disabled><i class="fas fa-plus"></i></button>
							<b-tooltip target="cm-add-media-btn" triggers="hover">
								Upload another photo or video
							</b-tooltip>
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
						<a v-if="media.length == 1" href="#" class="text-center text-dark" @click.prevent="showCropPhotoCard" title="Crop & Resize" id="cm-crop-btn"><i class="fas fa-crop-alt fa-lg"></i></a>
						<b-tooltip target="cm-crop-btn" triggers="hover">
							Crop & Resize
						</b-tooltip>
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
							<a v-if="!pageLoading && page == 'addText'" class="font-weight-bold text-decoration-none" href="#" @click.prevent="composeTextPost()">Post</a>
							<a v-if="!pageLoading && page == 'video-2'" class="font-weight-bold text-decoration-none" href="#" @click.prevent="compose()">Post</a>
						</span>
					</div>
				</div>

				<div class="card-body p-0 border-top">
					<div v-if="page == 'licensePicker'" class="w-100 h-100" style="min-height: 280px;">
						<div class="list-group list-group-flush">
							<div
								v-for="(item, index) in availableLicenses"
								class="list-group-item cursor-pointer"
								:class="{
									'text-primary': licenseId === item.id,
					'font-weight-bold': licenseId === item.id
								}"
								@click="toggleLicense(item)">
								{{item.name}}
							</div>
						</div>
					</div>

					<div v-if="page == 'textOptions'" class="w-100 h-100" style="min-height: 280px;">
					</div>

					<div v-if="page == 'addText'" class="w-100 h-100" style="min-height: 280px;">
						<div class="mt-2">
							<div class="media px-3">
								<div class="media-body">
									<div class="form-group">
										<label class="font-weight-bold text-muted small d-none">Body</label>
										<textarea class="form-control border-0 rounded-0 no-focus" rows="7" placeholder="What's happening?" style="font-size:18px;resize:none" v-model="composeText" v-on:keyup="composeTextLength = composeText.length"></textarea>
										<div class="border-bottom"></div>
										<p class="help-text small text-right text-muted mb-0 font-weight-bold">{{composeTextLength}}/{{config.uploader.max_caption_length}}</p>
										<p class="mb-0 mt-2">
											<a class="btn btn-primary rounded-pill mr-2" href="#" style="height: 37px;" @click.prevent="showTextOptions()">
												<i class="fas fa-palette px-3 text-white"></i>
											</a>
											<!-- <a class="btn btn-outline-lighter rounded-pill ml-3" href="#" @click.prevent="showLocationCard()">
												<i class="fas fa-map-marker-alt px-3"></i>
											</a>
											<a class="btn btn-outline-lighter rounded-pill mx-3" href="#" @click.prevent="showTagCard()">
												<i class="fas fa-user-plus px-3"></i>
											</a> -->
											<a class="btn rounded-pill mx-3 d-inline-flex align-items-center" href="#" :class="[nsfw ? 'btn-danger' : 'btn-outline-lighter']" style="height: 37px;" @click.prevent="nsfw = !nsfw" title="Mark as sensitive/not safe for work">
												<i class="far fa-flag px-3"></i> <span class="text-muted small font-weight-bold"></span>
											</a>
											<a class="btn btn-outline-lighter rounded-pill d-inline-flex align-items-center" href="#" style="height: 37px;" @click.prevent="showVisibilityCard()">
												<i class="fas fa-eye mr-2"></i> <span class="text-muted small font-weight-bold">{{visibilityTag}}</span>
											</a>
										</p>
									</div>
								</div>
							</div>
						</div>
					</div>

					<div v-if="page == 1" class="w-100 h-100 d-flex justify-content-center align-items-center" style="min-height: 400px;">
						<div class="text-center">
							<div v-if="media.length == 0" class="card my-md-3 shadow-none border compose-action text-decoration-none text-dark">
								<div @click.prevent="addMedia" class="card-body py-2">
									<div class="media">
										<div class="mr-3 align-items-center justify-content-center" style="display:inline-flex;width:40px;height:40px;border-radius: 100%;background-color: #008DF5">
											<i class="fal fa-bolt text-white fa-lg"></i>
										</div>
										<div class="media-body text-left">
											<p class="mb-0">
												<span class="h5 mt-0 font-weight-bold text-primary">New Post</span>
											</p>
											<p class="mb-0 text-muted">Share up to {{config.uploader.album_limit}} photos or videos</p>
											<p class="mb-0 text-muted small"><span class="font-weight-bold">{{config.uploader.media_types.split(',').map(v => v.split('/')[1]).join(', ')}}</span> allowed up to <span class="font-weight-bold">{{filesize(config.uploader.max_photo_size)}}</span></p>
										</div>
									</div>
								</div>
							</div>

							<div v-if="1==0 && config.ab.top == true && media.length == 0" class="card my-md-3 shadow-none border compose-action text-decoration-none text-dark">
								<div @click.prevent="addText" class="card-body py-2">
									<div class="media">
										<div class="mr-3 align-items-center justify-content-center" style="display:inline-flex;width:40px;height:40px;border-radius: 100%;border: 2px solid #008DF5">
											<i class="far fa-edit text-primary fa-lg"></i>
										</div>
										<div class="media-body text-left">
											<p class="mb-0">
												<span class="h5 mt-0 font-weight-bold text-primary">New Text Post</span>
												<sup class="float-right mt-2">
													<span class="btn btn-outline-lighter p-1 btn-sm font-weight-bold py-0" style="font-size:10px;line-height: 0.6">BETA</span>
												</sup>
											</p>
											<p class="mb-0 text-muted">Share a text only post</p>
										</div>
									</div>
								</div>
							</div>

							<a v-if="config.features.stories == true" class="card my-md-3 shadow-none border compose-action text-decoration-none text-dark" href="/i/stories/new">
								<div class="card-body py-2">
									<div class="media">
										<div class="mr-3 align-items-center justify-content-center" style="display:inline-flex;width:40px;height:40px;border-radius: 100%;border: 1px solid #008DF5">
											<i class="fas fa-history text-primary fa-lg"></i>
										</div>
										<div class="media-body text-left">
											<p class="mb-0">
												<span class="h5 mt-0 font-weight-bold text-primary">New Story</span>
												<sup class="float-right mt-2">
													<span class="btn btn-outline-lighter p-1 btn-sm font-weight-bold py-0" style="font-size:10px;line-height: 0.6">BETA</span>
												</sup>
											</p>
											<p class="mb-0 text-muted">Add to your story</p>
										</div>
									</div>
								</div>
							</a>

							<a v-if="1==0 && config.ab.polls == true" class="card my-md-3 shadow-none border compose-action text-decoration-none text-dark" href="#" @click.prevent="newPoll">
								<div class="card-body py-2">
									<div class="media">
										<div class="mr-3 align-items-center justify-content-center" style="display:inline-flex;width:40px;height:40px;border-radius: 100%;border: 2px solid #008DF5">
											<i class="fas fa-poll-h text-primary fa-lg"></i>
										</div>
										<div class="media-body text-left">
											<p class="mb-0">
												<span class="h5 mt-0 font-weight-bold text-primary">New Poll</span>
												<sup class="float-right mt-2">
													<span class="btn btn-outline-lighter p-1 btn-sm font-weight-bold py-0" style="font-size:10px;line-height: 0.6">BETA</span>
												</sup>
											</p>
											<p class="mb-0 text-muted">Create a poll</p>
										</div>
									</div>
								</div>
							</a>

							<a class="card my-md-3 shadow-none border compose-action text-decoration-none text-dark" href="/i/collections/create">
								<div class="card-body py-2">
									<div class="media">
										<div class="mr-3 align-items-center justify-content-center" style="display:inline-flex;width:40px;height:40px;border-radius: 100%;border: 1px solid #008DF5">
											<i class="fal fa-images text-primary fa-lg"></i>
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
							<div v-if="ids.length > 0 && media[carouselCursor].type == 'image'" class="align-items-center px-2 pt-2">
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
							<div v-if="ids.length > 0 && media[carouselCursor].type == 'image'" class="align-items-center px-2 pt-2">
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
										<vue-tribute :options="tributeSettings">
											<textarea class="form-control border-0 rounded-0 no-focus" rows="3" placeholder="Write a caption..." style="" v-model="composeText" v-on:keyup="composeTextLength = composeText.length"></textarea>
										</vue-tribute>
										<p class="help-text small text-right text-muted mb-0">{{composeTextLength}}/{{config.uploader.max_caption_length}}</p>
									</div>
								</div>
							</div>
						</div>
						<div class="border-bottom">
							<p class="px-4 mb-0 py-2 cursor-pointer d-flex justify-content-between" @click="showMediaDescriptionsCard()">
								<span>Alt Text</span>
								<span>
									<i v-if="media && media.filter(m => m.alt).length == media.length" class="fas fa-check-circle fa-lg text-success"></i>
									<i v-else class="fas fa-chevron-right fa-lg text-lighter"></i>
								</span>
							</p>
						</div>
						<div class="border-bottom px-4 mb-0 py-2">
							<div class="d-flex justify-content-between">
								<div>
									<div class="text-dark ">Sensitive/NSFW Media</div>
								</div>
								<div>
									<div class="custom-control custom-switch" style="z-index: 9999;">
										<input type="checkbox" class="custom-control-input" id="asnsfw" v-model="nsfw">
										<label class="custom-control-label" for="asnsfw"></label>
									</div>
								</div>
							</div>

							<div v-if="nsfw">
								<textarea
									class="form-control mt-3"
									placeholder="Add an optional content warning or spoiler text"
									maxlength="140"
									v-model="spoilerText">
								</textarea>

								<p class="help-text small text-right text-muted mb-0">{{ spoilerTextLength }}/140</p>
							</div>
						</div>
						<div class="border-bottom">
							<p class="px-4 mb-0 py-2 cursor-pointer" @click="showTagCard()">Tag people</p>
						</div>
						<div class="border-bottom">
							<p class="px-4 mb-0 py-2 cursor-pointer" @click="showCollectionCard()">
								<span>Add to Collection <span class="ml-2 badge badge-primary">NEW</span></span>
								<span class="float-right">
									<span v-if="collectionsSelected.length" href="#" class="btn btn-outline-secondary btn-sm small mr-3 mt-n1 disabled" style="font-size:10px;padding:3px 5px;text-transform: uppercase" disabled>
										{{collectionsSelected.length}}
									</span>
									<span class="text-decoration-none"><i class="fas fa-chevron-right fa-lg text-lighter"></i></span>
								</span>
							</p>
						</div>
						<div class="border-bottom">
							<p class="px-4 mb-0 py-2 cursor-pointer" @click="showLicenseCard()">
								<span>Add license</span>
								<span class="float-right">
									<a v-if="licenseTitle" href="#" @click.prevent="showLicenseCard()" class="btn btn-outline-secondary btn-sm small mr-3 mt-n1 disabled" style="font-size:10px;padding:3px;text-transform: uppercase" disabled>{{licenseTitle}}</a>
									<a href="#" @click.prevent="showLicenseCard()" class="text-decoration-none"><i class="fas fa-chevron-right fa-lg text-lighter"></i></a>
								</span>
							</p>
						</div>
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
								<span>Audience</span>
								<span class="float-right">
									<a href="#" @click.prevent="showVisibilityCard()" class="btn btn-outline-secondary btn-sm small mr-3 mt-n1 disabled" style="font-size:10px;padding:3px;text-transform: uppercase" disabled>{{visibilityTag}}</a>
									<a href="#" @click.prevent="showVisibilityCard()" class="text-decoration-none"><i class="fas fa-chevron-right fa-lg text-lighter"></i></a>
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
						<autocomplete
							v-show="taggedUsernames.length < 10"
							:search="tagSearch"
							placeholder="@pixelfed"
							aria-label="Search usernames"
							:get-result-value="getTagResultValue"
							@submit="onTagSubmitLocation"
							ref="autocomplete"
						>
						</autocomplete>
						<p v-show="taggedUsernames.length < 10" class="font-weight-bold text-muted small">You can tag {{10 - taggedUsernames.length}} more {{taggedUsernames.length == 9 ? 'person' : 'people'}}!</p>
						<p class="font-weight-bold text-center mt-3">Tagged People</p>
						<div class="list-group">
							<div v-for="(tag, index) in taggedUsernames" class="list-group-item d-flex justify-content-between">
								<div class="media">
									<img class="mr-2 rounded-circle border" :src="tag.avatar" width="24px" height="24px">
									<div class="media-body">
										<span class="font-weight-bold">{{tag.name}}</span>
									</div>
								</div>
								<div class="custom-control custom-switch">
									<input type="checkbox" class="custom-control-input disabled" :id="'cci-tagged-privacy-switch'+index" v-model="tag.privacy" disabled>
									<label class="custom-control-label font-weight-bold text-lighter" :for="'cci-tagged-privacy-switch'+index">{{tag.privacy ? 'Public' : 'Private'}}</label>
								<a href="#" @click.prevent="untagUsername(index)" class="ml-3"><i class="fas fa-times text-muted"></i></a></div>
							</div>
							<div v-if="taggedUsernames.length == 0" class="list-group-item p-3">
								<p class="text-center mb-0 font-weight-bold text-lighter">Search usernames to tag.</p>
							</div>
						</div>
						<p class="font-weight-bold text-center small text-muted pt-3 mb-0">When you tag someone, they are sent a notification.<br>For more information on tagging, <a href="#" class="text-primary" @click.prevent="showTagHelpCard()">click here</a>.</p>
					</div>

					<div v-if="page == 'tagPeopleHelp'" class="w-100 h-100 p-3">
						<p class="mb-0 text-center py-3 px-2 lead">Tagging someone is like mentioning them, with the option to make it private between you.</p>
						<p class="mb-3 py-3 px-2 font-weight-lighter">
							You can choose to tag someone in public or private mode. Public mode will allow others to see who you tagged in the post and private mode tagged users will not be shown to others.
						</p>
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
							<!-- <div class="d-none list-group-item d-flex justify-content-between">
								<div>
									<div class="text-dark ">Optimize Media</div>
									<p v-if="mediaCropped" class="text-muted small mb-0">Media was cropped or filtered, it must be optimized.</p>
									<p v-else class="text-muted small mb-0">Compress media for smaller file size.</p>
								</div>
								<div>
									<div class="custom-control custom-switch" style="z-index: 9999;">
										<input type="checkbox" class="custom-control-input" id="asoptimizemedia" v-model="optimizeMedia" :disabled="mediaCropped">
										<label class="custom-control-label" for="asoptimizemedia"></label>
									</div>
								</div>
							</div> -->
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
							<div
								v-if="!profile.locked"
								class="list-group-item lead cursor-pointer"
								:class="{ 'text-primary': visibility == 'public' }"
								@click="toggleVisibility('public')">
								Public
							</div>
							<div
								v-if="!profile.locked"
								class="list-group-item lead cursor-pointer"
								:class="{ 'text-primary': visibility == 'unlisted' }"
								@click="toggleVisibility('unlisted')">
								Unlisted
							</div>
							<div
								class="list-group-item lead cursor-pointer"
								:class="{ 'text-primary': visibility == 'private' }"
								@click="toggleVisibility('private')">
								Followers Only
							</div>
						</div>
					</div>

					<div v-if="page == 'altText'" class="w-100 h-100 p-3">
						<div v-for="(m, index) in media">
							<div class="media">
								<img :src="m.preview_url" class="mr-3" width="50px" height="50px">
								<div class="media-body">
									<textarea class="form-control" v-model="m.alt" placeholder="Add a media description here..." :maxlength="maxAltTextLength" rows="4"></textarea>
									<p class="help-text small text-right text-muted mb-0">{{m.alt ? m.alt.length : 0}}/{{maxAltTextLength}}</p>
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
						<div v-if="collectionsLoaded && collections.length" class="list-group mb-3 collections-list-group">
							<div
								v-for="(collection, index) in collections"
								class="list-group-item cursor-pointer compose-action border"
								:class="{ active: collectionsSelected.includes(index) }"
								@click="toggleCollectionItem(index)">
								<div class="media">
								  <img :src="collection.thumb" class="mr-3" alt="" width="50px" height="50px">
								  <div class="media-body">
								    <h5 class="mt-0">{{ collection.title }}</h5>
								    <p class="mb-0 text-muted small">{{ collection.post_count }} Posts - Created {{ timeAgo(collection.published_at) }} ago</p>
								  </div>
								</div>
							</div>

							<button
								v-if="collectionsCanLoadMore"
								class="btn btn-light btn-block font-weight-bold mt-3"
								@click="loadMoreCollections">
								Load more
							</button>
						</div>
						<p class="d-flex justify-content-between mb-0">
							<button type="button" @click="clearSelectedCollections()" class="btn btn-link text-muted font-weight-bold text-decoration-none">Clear</button>
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
									<textarea class="form-control" v-model="media[carouselCursor].alt" placeholder="Add a media description here..." maxlength="140"></textarea>
									<p class="help-text small text-muted mb-0 d-flex justify-content-between">
										<span>Describe your photo for people with visual impairments.</span>
										<span>{{media[carouselCursor].alt ? media[carouselCursor].alt.length : 0}}/140</span>
									</p>
								</div>
								<div class="form-group">
									<label class="font-weight-bold text-muted small">License</label>
									<!-- <input type="text" class="form-control" v-model="media[carouselCursor].license" placeholder="All Rights Reserved (Default license)"> -->
									<!-- <p class="help-text small text-muted mb-0 d-flex justify-content-between">
										<span></span>
										<span>{{media[carouselCursor].license ? media[carouselCursor].license.length : 0}}/140</span>
									</p> -->
									<select class="form-control" v-model="licenseId">
										<option
											v-for="(item, index) in availableLicenses"
											:value="item.id"
											:selected="item.id == licenseId">
											{{item.name}}
										</option>
									</select>
								</div>
							</div>
						</div>
						<hr>
						<p class="d-flex justify-content-between mb-0">
							<button type="button" @click="goBack()" class="btn btn-link text-muted font-weight-bold text-decoration-none">Cancel</button>
							<button type="button" @click="goBack()" class="btn btn-primary font-weight-bold">Save</button>
						</p>
					</div>

					<div v-if="page == 'video-2'" class="w-100 h-100">
						<div v-if="video.title.length" class="border-bottom">
							<div class="media p-3">
								<img :src="media[0].url" width="100px" height="70px" :class="[media[0].filter_class?'mr-2 ' + media[0].filter_class:'mr-2']">
								<div class="media-body">
									<p class="font-weight-bold mb-1">{{video.title ? video.title.slice(0,70) : 'Untitled'}}</p>
									<p class="mb-0 text-muted small">{{video.description ? video.description.slice(0,90) : 'No description'}}</p>
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
						<div class="border-bottom">
							<p class="px-4 mb-0 py-2 cursor-pointer" @click="showLicenseCard()">Add license</p>
						</div>
						<div class="border-bottom">
							<p class="px-4 mb-0 py-2">
								<span>Audience</span>
								<span class="float-right">
									<a href="#" @click.prevent="showVisibilityCard()" class="btn btn-outline-secondary btn-sm small mr-3 mt-n1 disabled" style="font-size:10px;padding:3px;text-transform: uppercase" disabled>{{visibilityTag}}</a>
									<a href="#" @click.prevent="showVisibilityCard()" class="text-decoration-none"><i class="fas fa-chevron-right fa-lg text-lighter"></i></a>
								</span>
							</p>
						</div>

						<div class="p-3">
							<!-- <div class="card card-body shadow-none border d-flex justify-content-center align-items-center mb-3 p-5">
								<div class="d-flex align-items-center">
									<p class="mb-0 text-center">
										<div class="spinner-border text-primary" role="status">
											<span class="sr-only">Loading...</span>
										</div>
									</p>
									<p class="ml-3 mb-0 text-center font-weight-bold">
										Processing video
									</p>
								</div>
							</div> -->
							<div class="form-group">
								<p class="small font-weight-bold text-muted mb-0">Title</p>
								<input class="form-control" v-model="video.title" placeholder="Add a good title">
								<p class="help-text mb-0 small text-muted">{{video.title.length}}/70</p>
							</div>

							<div class="form-group mb-0">
								<p class="small font-weight-bold text-muted mb-0">Description</p>
								<textarea class="form-control" v-model="video.description" placeholder="Add an optional description" maxlength="5000" rows="5"></textarea>
								<p class="help-text mb-0 small text-muted">{{video.description.length}}/5000</p>
							</div>
						</div>
					</div>

				</div>

				<!-- card-footers -->
				<div v-if="page == 'cropPhoto'" class="card-footer bg-white d-flex justify-content-between">
					<div>
						<button type="button" class="btn btn-outline-secondary" @click="rotate"><i class="fas fa-redo"></i></button>
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

<script type="text/javascript">
import VueCropper from 'vue-cropperjs';
import 'cropperjs/dist/cropper.css';
import Autocomplete from '@trevoreyre/autocomplete-vue'
import '@trevoreyre/autocomplete-vue/dist/style.css'
import VueTribute from 'vue-tribute'

export default {

	components: {
		VueCropper,
		Autocomplete,
		VueTribute
	},

	data() {
		return {
			config: window.App.config,
			pageLoading: false,
			profile: window._sharedData.curUser,
			composeText: '',
			composeTextLength: 0,
			nsfw: false,
			filters: [],
			currentFilter: false,
			ids: [],
			media: [],
			carouselCursor: 0,
			uploading: false,
			uploadProgress: 100,
			mode: 'photo',
			modes: [
				'photo',
				'video',
				'plain'
			],
			page: 1,
			composeState: 'publish',
			visibility: 'public',
			visibilityTag: 'Public',
			place: false,
			commentsDisabled: false,
			optimizeMedia: true,
			mediaCropped: false,
			pageTitle: '',

			cropper: {
				aspectRatio: 1,
				viewMode: 1,
				zoomable: true,
				zoom: 0
			},

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
				'cameraRoll',
				'tagPeopleHelp',
				'textOptions',
				'licensePicker'
			],
			cameraRollMedia: [],
			taggedUsernames: [],
			taggedPeopleSearch: null,
			textMode: false,
			tributeSettings: {
				noMatchTemplate: function () { return null; },
				collection: [
					{
						trigger: '@',
						menuShowMinLength: 2,
						values: (function (text, cb) {
							let url = '/api/compose/v0/search/mention';
							axios.get(url, { params: { q: text }})
							.then(res => {
								cb(res.data);
							})
							.catch(err => {
								console.log(err);
							})
						})
					},
					{
						trigger: '#',
						menuShowMinLength: 2,
						values: (function (text, cb) {
							let url = '/api/compose/v0/search/hashtag';
							axios.get(url, { params: { q: text }})
							.then(res => {
								cb(res.data);
							})
							.catch(err => {
								console.log(err);
							})
						})
					}
				]
			},
			availableLicenses: [
				{
					id: 1,
					name: "All Rights Reserved",
					title: ""
				},
				{
					id: 5,
					name: "Public Domain Work",
					title: ""
				},
				{
					id: 6,
					name: "Public Domain Dedication (CC0)",
					title: "CC0"
				},
				{
					id: 11,
					name: "Attribution",
					title: "CC BY"
				},
				{
					id: 12,
					name: "Attribution-ShareAlike",
					title: "CC BY-SA"
				},
				{
					id: 13,
					name: "Attribution-NonCommercial",
					title: "CC BY-NC"
				},
				{
					id: 14,
					name: "Attribution-NonCommercial-ShareAlike",
					title: "CC BY-NC-SA"
				},
				{
					id: 15,
					name: "Attribution-NoDerivs",
					title: "CC BY-ND"
				},
				{
					id: 16,
					name: "Attribution-NonCommercial-NoDerivs",
					title: "CC BY-NC-ND"
				}
			],
			licenseIndex: 0,
			video: {
				title: '',
				description: ''
			},
			composeSettings: {
				default_license: null,
				media_descriptions: false
			},
			licenseId: 1,
			licenseTitle: null,
			maxAltTextLength: 140,
			pollOptionModel: null,
			pollOptions: [],
			pollExpiry: 1440,
			postingPoll: false,
			collections: [],
			collectionsSelected: [],
			collectionsLoaded: false,
			collectionsPage: 1,
			collectionsCanLoadMore: false,
			spoilerText: undefined,
		}
	},

	computed: {
		spoilerTextLength: function() {
			return this.spoilerText ? this.spoilerText.length : 0;
		}
	},

	beforeMount() {
		this.filters = window.App.util.filters.sort();
		axios.get('/api/compose/v0/settings')
		.then(res => {
			this.composeSettings = res.data;
			this.licenseId = this.composeSettings.default_license;
			this.maxAltTextLength = res.data.max_altext_length;
			if(this.licenseId > 10) {
				this.licenseTitle = this.availableLicenses.filter(l => {
					return l.id == this.licenseId;
				}).map(l => {
					return l.title;
				})[0];
			}
			this.fetchProfile();
		});
	},

	mounted() {
		this.mediaWatcher();
	},

	methods: {
		timeAgo(ts) {
			return App.util.format.timeAgo(ts);
		},

		fetchProfile() {
			let tags = {
				public: 'Public',
				private: 'Followers Only',
				unlisted: 'Unlisted'
			}
			if(window._sharedData.curUser.id) {
				this.profile = window._sharedData.curUser;
				if(this.composeSettings && this.composeSettings.hasOwnProperty('default_scope') && this.composeSettings.default_scope) {
					let ds = this.composeSettings.default_scope;
					this.visibility = ds;
					this.visibilityTag = tags[ds];
				}
				if(this.profile.locked == true) {
					this.visibility = 'private';
					this.visibilityTag = 'Followers Only';
				}
			} else {
				axios.get('/api/pixelfed/v1/accounts/verify_credentials').then(res => {
					window._sharedData.currentUser = res.data;
					this.profile = res.data;
					if(this.composeSettings && this.composeSettings.hasOwnProperty('default_scope') && this.composeSettings.default_scope) {
						let ds = this.composeSettings.default_scope;
						this.visibility = ds;
						this.visibilityTag = tags[ds];
					}
					if(this.profile.locked == true) {
						this.visibility = 'private';
						this.visibilityTag = 'Followers Only';
					}
				}).catch(err => {
				});
			}
		},

		addMedia(event) {
			let el = $(event.target);
			el.attr('disabled', '');
			let fi = $('.file-input[name="media"]');
			fi.trigger('click');
			el.blur();
			el.removeAttr('disabled');
		},

		addText(event) {
			this.pageTitle = 'New Text Post';
			this.page = 'addText';
			this.textMode = true;
			this.mode = 'text';
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
			if(!io.files.length) {
				self.uploading = false;
			}
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

				axios.post('/api/compose/v0/media/upload', form, xhrConfig)
				.then(function(e) {
					self.uploadProgress = 100;
					self.ids.push(e.data.id);
					self.media.push(e.data);
					self.uploading = false;
					setTimeout(function() {
						// if(type === 'video/mp4') {
						// 	self.pageTitle = 'Edit Video Details';
						// 	self.mode = 'video';
						// 	self.page = 'video-2';
						// } else {
						// 	self.page = 2;
						// }
						self.page = 3;
					}, 300);
				}).catch(function(e) {
					switch(e.response.status) {
						case 451:
							self.uploading = false;
							io.value = null;
							swal('Banned Content', 'This content has been banned and cannot be uploaded.', 'error');
							self.page = 2;
						break;

						case 429:
							self.uploading = false;
							io.value = null;
							swal('Limit Reached', 'You can upload up to 250 photos or videos per day and you\'ve reached that limit. Please try again later.', 'error');
							self.page = 2;
						break;

						case 500:
							self.uploading = false;
							io.value = null;
							swal('Error', e.response.data.message, 'error');
							self.page = 2;
						break;

						default:
							self.uploading = false;
							io.value = null;
							swal('Oops, something went wrong!', 'An unexpected error occurred.', 'error');
							self.page = 2;
						break;
					}
				});
				io.value = null;
				self.uploadProgress = 0;
			});
		},

		toggleFilter(e, filter) {
			this.media[this.carouselCursor].filter_class = filter;
			this.currentFilter = filter;
		},

		deleteMedia() {
			if(window.confirm('Are you sure you want to delete this media?') == false) {
				return;
			}
			let id = this.media[this.carouselCursor].id;

			axios.delete('/api/compose/v0/media/delete', {
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
					if(this.composeSettings.media_descriptions === true) {
						let count = this.media.filter(m => {
							return !m.hasOwnProperty('alt') || m.alt.length < 2;
						});

						if(count.length) {
							swal('Missing media descriptions', 'You have enabled mandatory media descriptions. Please add media descriptions under Advanced settings to proceed. For more information, please see the media settings page.', 'warning');
							return;
						}
					}
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
						place: this.place,
						tagged: this.taggedUsernames,
						optimize_media: this.optimizeMedia,
						license: this.licenseId,
						video: this.video,
						spoiler_text: this.spoilerText,
					};

					if(this.collectionsSelected.length) {
						data.collections = this.collectionsSelected
							.map(idx => {
								return this.collections[idx].id;
						});
					}

					axios.post('/api/compose/v0/publish', data)
					.then(res => {
						if(location.pathname === '/i/web/compose' && res.data && res.data.length) {
							location.href = '/i/web/post/' + res.data.split('/').slice(-1)[0];
						} else {
							location.href = res.data;
						}
					}).catch(err => {
						if(err.response) {
							let msg = err.response.data.message ? err.response.data.message : 'An unexpected error occured.'
							swal('Oops, something went wrong!', msg, 'error');
						} else {
							swal('Oops, something went wrong!', err.message, 'error');
						}
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

		composeTextPost() {
			let state = this.composeState;

			if(this.composeText.length > this.config.uploader.max_caption_length) {
				swal('Error', 'Caption is too long', 'error');
				return;
			}

			switch(state) {
				case 'publish' :
					let data = {
						caption: this.composeText,
						visibility: this.visibility,
						cw: this.nsfw,
						comments_disabled: this.commentsDisabled,
						place: this.place,
						tagged: this.taggedUsernames,
					};
					axios.post('/api/compose/v0/publish/text', data)
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
			$('#composeModal').modal('hide');
			this.$emit('close');
		},

		goBack() {
			this.pageTitle = '';

			switch(this.mode) {
				case 'photo':
					switch(this.page) {
						case 'addText':
							this.page = 1;
						break;

						case 'textOptions':
							this.page = 'addText';
						break;

						case 'cropPhoto':
						case 'editMedia':
							this.page = 2;
						break;

						case 'tagPeopleHelp':
							this.showTagCard();
						break;

						case 'licensePicker':
							this.page = 3;
						break;

						case 'video-2':
							this.page = 1;
						break;

						default:
							this.namedPages.indexOf(this.page) != -1 ?
							this.page = 3 : this.page--;
						break;
					}
				break;

				case 'video':
					switch(this.page) {
						case 'licensePicker':
							this.page = 'video-2';
						break;

						case 'video-2':
							this.page = 'video-2';
						break;

						default:
							this.page = 'video-2';
						break;
					}
				break;

				default:
					switch(this.page) {
						case 'addText':
							this.page = 1;
						break;

						case 'textOptions':
							this.page = 'addText';
						break;

						case 'cropPhoto':
						case 'editMedia':
							this.page = 2;
						break;

						case 'tagPeopleHelp':
							this.showTagCard();
						break;

						case 'licensePicker':
							this.page = 3;
						break;

						case 'video-2':
							this.page = 1;
						break;

						default:
							this.namedPages.indexOf(this.page) != -1 ?
							this.page = (this.mode == 'text' ? 'addText' : 3) :
							(this.mode == 'text' ? 'addText' : this.page--);
						break;
					}
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
						self.mediaCropped = true;
						let data = new FormData();
						data.append('file', blob);
						data.append('id', self.ids[self.carouselCursor]);
						let url = '/api/compose/v0/media/update';
						axios.post(url, data).then(res => {
							self.media[self.carouselCursor].url = res.data.url;
							self.pageLoading = false;
							self.page = 2;
						}).catch(err => {
						});
					});
				break;

				case 2:
					if(this.currentFilter) {
						if(window.confirm('Are you sure you want to apply this filter?')) {
							this.applyFilterToMedia();
							this.page++;
						}
					} else {
						this.page++;
					}
				break;
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

		showTagHelpCard() {
			this.pageTitle = 'About Tag People';
			this.page = 'tagPeopleHelp';
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
			if (input.length < 1) { return []; }
			let results = [];
			return axios.get('/api/compose/v0/search/location', {
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
			switch(this.mode) {
				case 'photo':
					this.pageTitle = '';
					this.page = 3;
				break;

				case 'video':
					this.pageTitle = 'Edit Video Details';
					this.page = 'video-2';
				break;

				case 'text':
					this.pageTitle = 'New Text Post';
					this.page = 'addText';
				break;
			}
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

			switch(this.mode) {
				case 'photo':
					this.pageTitle = '';
					this.page = 3;
				break;

				case 'video':
					this.pageTitle = 'Edit Video Details';
					this.page = 'video-2';
				break;

				case 'text':
					this.pageTitle = 'New Text Post';
					this.page = 'addText';
				break;
			}
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

		applyFilterToMedia() {
			// this is where the magic happens
			var ua = navigator.userAgent.toLowerCase();
			if(ua.indexOf('firefox') == -1 && ua.indexOf('chrome') == -1) {
			 	swal('Oops!', 'Your browser does not support the filter feature.', 'error');
			 	return;
			}

			let medias = this.media;
			let media = null;
			const canvas = document.getElementById('pr_canvas');
			const ctx = canvas.getContext('2d');
			let image = document.getElementById('pr_img');
			let blob = null;
			let data = null;

			for (var i = medias.length - 1; i >= 0; i--) {
				media = medias[i];
				if(media.filter_class) {
					image.src = media.url;
					image.addEventListener('load', e => {
						canvas.width = image.width;
						canvas.height = image.height;
						ctx.filter = App.util.filterCss[media.filter_class];
						ctx.drawImage(image, 0, 0, image.width, image.height);
						ctx.save();
						canvas.toBlob(function(blob) {
							data = new FormData();
							data.append('file', blob);
							data.append('id', media.id);
							axios.post('/api/compose/v0/media/update', data).then(res => {
							}).catch(err => {
							});
						});
					}, media.mime, 0.9);
					ctx.clearRect(0, 0, image.width, image.height);
				}
			}

		},

		tagSearch(input) {
			if (input.length < 1) { return []; }
			let self = this;
			let results = [];
			return axios.get('/api/compose/v0/search/tag', {
				params: {
					q: input
				}
			}).then(res => {
				if(!res.data.length) {
					return;
				}
				return res.data.filter(d => {
					return self.taggedUsernames.filter(r => {
						return r.id == d.id;
					}).length == 0;
				});
			});
		},

		getTagResultValue(result) {
			return '@' + result.name;
		},

		onTagSubmitLocation(result) {
			if(this.taggedUsernames.filter(r => {
				return r.id == result.id;
			}).length) {
				return;
			}
			this.taggedUsernames.push(result);
			this.$refs.autocomplete.value = '';
			return;
		},

		untagUsername(index) {
			this.taggedUsernames.splice(index, 1);
		},

		showTextOptions() {
			this.page = 'textOptions';
			this.pageTitle = 'Text Post Options';
		},

		showLicenseCard() {
			this.pageTitle = 'Select a License';
			this.page = 'licensePicker';
		},

		toggleLicense(license) {
			this.licenseId = license.id;

			if(this.licenseId > 10) {
				this.licenseTitle = this.availableLicenses.filter(l => {
					return l.id == this.licenseId;
				}).map(l => {
					return l.title;
				})[0];
			} else {
				this.licenseTitle = null;
			}

			switch(this.mode) {
				case 'photo':
					this.pageTitle = '';
					this.page = 3;
				break;

				case 'video':
					this.pageTitle = 'Edit Video Details';
					this.page = 'video-2';
				break;

				case 'text':
					this.pageTitle = 'New Text Post';
					this.page = 'addText';
				break;
			}
		},

		newPoll() {
			this.page = 'poll';
		},

		savePollOption() {
			if(this.pollOptions.indexOf(this.pollOptionModel) != -1) {
				this.pollOptionModel = null;
				return;
			}
			this.pollOptions.push(this.pollOptionModel);
			this.pollOptionModel = null;
		},

		deletePollOption(index) {
			this.pollOptions.splice(index, 1);
		},

		postNewPoll() {
			this.postingPoll = true;
			axios.post('/api/compose/v0/poll', {
				caption: this.composeText,
				cw: false,
				visibility: this.visibility,
				comments_disabled: false,
				expiry: this.pollExpiry,
				pollOptions: this.pollOptions
			}).then(res => {
				if(!res.data.hasOwnProperty('url')) {
					swal('Oops!', 'An error occured while attempting to create this poll. Please refresh the page and try again.', 'error');
					this.postingPoll = false;
					return;
				}
				window.location.href = res.data.url;
			}).catch(err => {
				console.log(err.response.data.error);
				if(err.response.data.hasOwnProperty('error')) {
					if(err.response.data.error == 'Duplicate detected.') {
						this.postingPoll = false;
						swal('Oops!', 'The poll you are trying to create is similar to an existing poll you created. Please make the poll question (caption) unique.', 'error');
						return;
					}
				}
				this.postingPoll = false;
				swal('Oops!', 'An error occured while attempting to create this poll. Please refresh the page and try again.', 'error');
			})
		},

		filesize(val) {
			return filesize(val * 1024, {round: 0});
		},

		showCollectionCard() {
			this.pageTitle = 'Add to Collection(s)';
			this.page = 'addToCollection';

			if(!this.collectionsLoaded) {
				this.fetchCollections();
			}
		},

		fetchCollections() {
			axios.get(`/api/local/profile/collections/${this.profile.id}`)
			.then(res => {
				this.collections = res.data;
				this.collectionsLoaded = true;
				this.collectionsCanLoadMore = res.data.length == 9;
				this.collectionsPage++;
			});
		},

		toggleCollectionItem(index) {
			if(!this.collectionsSelected.includes(index)) {
				if(this.collectionsSelected.length == 7) {
					swal('Oops!', 'You can only share to 5 collections.', 'info');
					return;
				}
				this.collectionsSelected.push(index);
			} else {
				this.collectionsSelected = this.collectionsSelected.filter(c => c != index);
			}
		},

		clearSelectedCollections() {
			this.collectionsSelected = [];
			this.pageTitle = 'Compose';
			this.page = 3;
		},

		loadMoreCollections() {
			this.collectionsCanLoadMore = false;

			axios.get(`/api/local/profile/collections/${this.profile.id}`, {
				params: {
					page: this.collectionsPage
				}
			})
			.then(res => {
				let ids = this.collections.map(c => c.id);
				let data = res.data.filter(res => {
					return !ids.includes(res.id);
				});

				if(!data || !data.length) {
					return;
				}

				this.collections.push(...data);
				this.collectionsPage++;
				this.collectionsCanLoadMore = true;
			});
		}
	}
}
</script>

<style lang="scss">
	.compose-modal-component {
		.media-drawer-filters {
			overflow-x: auto;
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
			background-color: #f8f9fa;
		}
		.compose-action:hover {
			cursor: pointer;
			background-color: #f8f9fa;
		}
		.collections-list-group {
			max-height: 500px;
			overflow-y: auto;

			.list-group-item {
				&.active {
					color: #212529;
					border-color: #60a5fa !important;
					background-color: #dbeafe !important;
				}
			}
		}
	}
</style>
