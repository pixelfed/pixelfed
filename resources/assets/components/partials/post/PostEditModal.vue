<template>
	<b-modal
		centered
		v-model="isOpen"
		body-class="p-0"
		footer-class="d-flex justify-content-between align-items-center">
		<template #modal-header="{ close }">
			<div class="d-flex flex-grow-1 justify-content-between align-items-center">
				<span style="width:40px;"></span>
				<h5 class="font-weight-bold mb-0">Edit Post</h5>
				<b-button size="sm" variant="link" @click="close()">
					<i class="far fa-times text-dark fa-lg"></i>
				</b-button>
			</div>
		</template>

		<b-card
			v-if="isLoading"
			no-body
			flush
			class="shadow-none p-0">
			<b-card-body style="min-height:300px" class="d-flex align-items-center justify-content-center">
				<div class="d-flex justify-content-center align-items-center flex-column" style="gap: 0.4rem;">
					<b-spinner variant="primary" />
					<p class="small mb-0 font-weight-lighter">Loading Post...</p>
				</div>
			</b-card-body>
		</b-card>

		<b-card
			v-else-if="!isLoading && isOpen && status && status.id"
			no-body
			flush
			class="shadow-none p-0">
			<b-card-header header-tag="nav">
				<b-nav tabs fill card-header>
					<b-nav-item :active="tabIndex === 0" @click="toggleTab(0)">Caption</b-nav-item>
					<b-nav-item :active="tabIndex === 1" @click="toggleTab(1)">Media</b-nav-item>
					<!-- <b-nav-item :active="tabIndex === 2" @click="toggleTab(2)">Audience</b-nav-item> -->
					<b-nav-item :active="tabIndex === 4" @click="toggleTab(3)">Other</b-nav-item>
				</b-nav>
			</b-card-header>
			<b-card-body style="min-height:300px">
				<template v-if="tabIndex === 0">
					<p class="font-weight-bold small">Caption</p>
					<div class="media mb-0">
						<div class="media-body">
							<div class="form-group">
								<label class="font-weight-bold text-muted small d-none">Caption</label>
								<vue-tribute :options="tributeSettings">
									<textarea
										class="form-control border-0 rounded-0 no-focus"
										rows="4"
										placeholder="Write a caption..."
										v-model="fields.caption"
										:maxlength="config.uploader.max_caption_length"
										v-on:keyup="composeTextLength = fields.caption.length"></textarea>
								</vue-tribute>
								<p class="help-text small text-right text-muted mb-0">{{composeTextLength}}/{{config.uploader.max_caption_length}}</p>
							</div>
						</div>
					</div>

					<hr />

					<p class="font-weight-bold small">Sensitive/NSFW</p>
					<div class="border py-2 px-3 bg-light rounded">
						<b-form-checkbox v-model="fields.sensitive" name="check-button" switch style="font-weight:300">
							<span class="ml-1 small">Contains spoilers, sensitive or nsfw content</span>
						</b-form-checkbox>
					</div>
					<transition name="slide-fade">
						<div v-if="fields.sensitive" class="form-group mt-3">
							<label class="font-weight-bold small">Content Warning</label>
							<textarea
								class="form-control"
								rows="2"
								placeholder="Add an optional spoiler/content warning..."
								:maxlength="140"
								v-model="fields.spoiler_text"></textarea>
							<p class="help-text small text-right text-muted mb-0">{{fields.spoiler_text ? fields.spoiler_text.length : 0}}/140</p>
						</div>
					</transition>
				</template>

				<template v-else-if="tabIndex === 1">
					<div class="list-group">
						<div
							class="list-group-item"
							v-for="(media, idx) in fields.media"
							:key="'edm:' + media.id + ':' + idx">
							<div class="d-flex justify-content-between align-items-center">
								<template v-if="media.type === 'image'">
									<img
										:src="media.url"
										width="40"
										height="40"
										style="object-fit: cover;"
										class="bg-light rounded cursor-pointer"
										@click="toggleLightbox"
										/>
								</template>

								<p class="d-none d-lg-block mb-0"><span class="small font-weight-light">{{ media.mime }}</span></p>

								<button
									class="btn btn-sm font-weight-bold rounded-pill px-4"
									style="font-size: 13px"
									:class="[ media.description && media.description.length ? 'btn-success' : 'btn-outline-muted']"
									@click.prevent="handleAddAltText(idx)"
									>
									{{ media.description && media.description.length ? 'Edit Alt Text' : 'Add Alt Text' }}
								</button>

								<div v-if="fields.media && fields.media.length > 1" class="btn-group">
									<a
										class="btn btn-outline-secondary btn-sm"
										href="#"
										:disabled="idx === 0"
										:class="{ disabled: idx === 0}"
										@click.prevent="toggleMediaOrder('prev', idx)">
										<i class="fas fa-arrow-alt-up"></i>
									</a>
									<a
										class="btn btn-outline-secondary btn-sm"
										href="#"
										:disabled="idx === fields.media.length - 1"
										:class="{ disabled: idx === fields.media.length - 1}"
										@click.prevent="toggleMediaOrder('next', idx)">
										<i class="fas fa-arrow-alt-down"></i>
									</a>
								</div>

								<button
									class="btn btn-outline-danger btn-sm"
									v-if="fields.media && fields.media.length && fields.media.length > 1"
									@click.prevent="removeMedia(idx)">
									<i class="far fa-trash-alt"></i>
								</button>
							</div>
							<transition name="slide-fade">
								<template v-if="altTextEditIndex === idx">
									<div class="form-group mt-1">
										<label class="font-weight-bold small">Alt Text</label>
										<b-form-textarea
											v-model="media.description"
											placeholder="Describe your image for the visually impaired..."
											rows="3"
											max-rows="6"
											@input="handleAltTextUpdate(idx)"
										></b-form-textarea>
										<div class="d-flex justify-content-between">
											<a class="font-weight-bold small text-muted" href="#" @click.prevent="altTextEditIndex = undefined">Close</a>
											<p class="help-text small mb-0">
												{{ fields.media[idx].description ? fields.media[idx].description.length : 0 }}/{{config.uploader.max_altext_length}}
											</p>
										</div>
									</div>
								</template>
							</transition>
						</div>
					</div>
				</template>

				<!-- <template v-else-if="tabIndex === 2">
					<p class="font-weight-bold small">Audience</p>

					<div class="list-group">
						<div
							v-if="!status.account.locked"
							class="list-group-item font-weight-bold cursor-pointer"
							:class="{ 'text-primary': fields.visibility == 'public' }"
							@click="toggleVisibility('public')">
							Public
							<i v-if="fields.visibility == 'public'" class="far fa-check-circle ml-1"></i>
						</div>
						<div
							v-if="!status.account.locked"
							class="list-group-item font-weight-bold cursor-pointer"
							:class="{ 'text-primary': fields.visibility == 'unlisted' }"
							@click="toggleVisibility('unlisted')">
							Unlisted
							<i v-if="fields.visibility == 'unlisted'" class="far fa-check-circle ml-1"></i>
						</div>
						<div
							class="list-group-item font-weight-bold cursor-pointer"
							:class="{ 'text-primary': fields.visibility == 'private' }"
							@click="toggleVisibility('private')">
							Followers Only
							<i v-if="fields.visibility == 'private'" class="far fa-check-circle ml-1"></i>
						</div>
					</div>
				</template> -->

				<template v-else-if="tabIndex === 3">
					<p class="font-weight-bold small">Location</p>
					<autocomplete
						:search="locationSearch"
						placeholder="Search locations ..."
						aria-label="Search locations ..."
						:get-result-value="getResultValue"
						@submit="onSubmitLocation"
					>
					</autocomplete>

					<div v-if="fields.location && fields.location.hasOwnProperty('id')" class="mt-3 border rounded p-3 d-flex justify-content-between">
						<p class="font-weight-bold mb-0">
							{{ fields.location.name }}, {{ fields.location.country}}
						</p>
						<button class="btn btn-link text-danger m-0 p-0" @click.prevent="clearLocation">
							<i class="far fa-trash"></i>
						</button>
					</div>
				</template>
			</b-card-body>
		</b-card>

		<template
			#modal-footer="{ ok, cancel, hide }">
			<b-button class="rounded-pill px-3 font-weight-bold" variant="outline-muted" @click="cancel()">
				Cancel
			</b-button>

			<b-button
				class="rounded-pill font-weight-bold"
				variant="primary"
				style="min-width: 195px"
				@click="handleSave"
				:disabled="!canSave">
				<template v-if="isSubmitting">
					<b-spinner small />
				</template>
				<template v-else>
					Save Updates
				</template>
			</b-button>
		</template>
	</b-modal>
</template>

<script type="text/javascript">
	import Autocomplete from '@trevoreyre/autocomplete-vue';
	import BigPicture from 'bigpicture';

	export default {
		components: {
			Autocomplete,
		},

		data() {
			return {
				config: window.App.config,
				status: undefined,
				isLoading: true,
				isOpen: false,
				isSubmitting: false,
				tabIndex: 0,
				canEdit: false,
				composeTextLength: 0,
				canSave: false,
				originalFields: {
					caption: undefined,
					visibility: undefined,
					sensitive: undefined,
					location: undefined,
					spoiler_text: undefined,
					media: [],
				},
				fields: {
					caption: undefined,
					visibility: undefined,
					sensitive: undefined,
					location: undefined,
					spoiler_text: undefined,
					media: [],
				},
				medias: undefined,
				altTextEditIndex: undefined,
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
			}
		},

		watch: {
			fields: {
				deep: true,
				immediate: true,
				handler: function(n, o) {
					if(!this.canEdit) {
						return;
					}
                    this.canSave = this.originalFields !== JSON.stringify(this.fields);
                }
			}
		},

		methods: {
			reset() {
				this.status = undefined;
				this.tabIndex = 0;
				this.isOpen = false;
				this.canEdit = false;
				this.composeTextLength = 0;
				this.canSave = false;
				this.originalFields = {
					caption: undefined,
					visibility: undefined,
					sensitive: undefined,
					location: undefined,
					spoiler_text: undefined,
					media: [],
				};
				this.fields = {
					caption: undefined,
					visibility: undefined,
					sensitive: undefined,
					location: undefined,
					spoiler_text: undefined,
					media: [],
				};
				this.medias = undefined;
				this.altTextEditIndex = undefined;
				this.isSubmitting = false;
			},

			async show(status) {
				await axios.get('/api/v1/statuses/' + status.id, {
					params: {
						'_pe': 1
					}
				})
				.then(res => {
					this.reset();
					this.init(res.data);
				})
				.finally(() => {
					setTimeout(() => {
						this.isLoading = false;
					}, 500);
				})
			},

			init(status) {
				this.reset();
				this.originalFields = JSON.stringify({
					caption: status.content_text,
					visibility: status.visibility,
					sensitive: status.sensitive,
					location: status.place,
					spoiler_text: status.spoiler_text,
					media: status.media_attachments
				})
				this.fields = {
					caption: status.content_text,
					visibility: status.visibility,
					sensitive: status.sensitive,
					location: status.place,
					spoiler_text: status.spoiler_text,
					media: status.media_attachments
				}
				this.status = status;
				this.medias = status.media_attachments;
				this.composeTextLength = status.content_text ? status.content_text.length : 0;
				this.isOpen = true;
				setTimeout(() => {
					this.canEdit = true;
				}, 1000);
			},

			toggleTab(idx) {
				this.tabIndex = idx;
				this.altTextEditIndex = undefined;
			},

			toggleVisibility(vis) {
				this.fields.visibility = vis;
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
				this.fields.location = result;
				this.tabIndex = 0;
			},

			clearLocation() {
				event.currentTarget.blur();
				this.fields.location = null;
				this.tabIndex = 0;
			},

			handleAltTextUpdate(idx) {
				if (this.fields.media[idx].description.length == 0) {
					this.fields.media[idx].description = null;
				}
			},

			moveMedia(from, to, arr) {
				const newArr = [...arr];

				const item = newArr.splice(from, 1)[0];
				newArr.splice(to, 0, item);

				return newArr;
			},

			toggleMediaOrder(dir, idx) {
				if(dir === 'prev') {
					this.fields.media = this.moveMedia(idx, idx - 1, this.fields.media);
				}

				if(dir === 'next') {
					this.fields.media = this.moveMedia(idx, idx + 1, this.fields.media);
				}
			},

			toggleLightbox(e) {
				BigPicture({
					el: e.target
				})
			},

			handleAddAltText(idx) {
				event.currentTarget.blur();
				this.altTextEditIndex = idx
			},

			removeMedia(idx) {
				swal({
					title: 'Confirm',
					text: 'Are you sure you want to remove this media from your post?',
					buttons: {
						cancel: "Cancel",
						confirm: {
							text: "Confirm Removal",
							value: "remove",
							className: "swal-button--danger"
						}
					}
				})
				.then((val) => {
					if(val === 'remove') {
						this.fields.media.splice(idx, 1);
					}
				})
			},

			async handleSave() {
				event.currentTarget.blur();
				this.canSave = false;
				this.isSubmitting = true;

				await this.checkMediaUpdates();

				axios.put('/api/v1/statuses/' + this.status.id, {
					status: this.fields.caption,
					spoiler_text: this.fields.spoiler_text,
					sensitive: this.fields.sensitive,
					media_ids: this.fields.media.map(m => m.id),
					location: this.fields.location
				})
				.then(res => {
					this.isOpen = false;
					this.$emit('update', res.data);
					swal({
						title: 'Post Updated',
						text: 'You have successfully updated this post!',
						icon: 'success',
						buttons: {
							close: {
								text: "Close",
								value: "close",
								close: true,
								className: "swal-button--cancel"
							},
							view: {
								text: "View Post",
								value: "view",
								className: "btn-primary"
							}
						}
					})
					.then((val) => {
						if(val === 'view') {
							if(this.$router.currentRoute.name === 'post') {
								window.location.reload();
							} else {
								this.$router.push('/i/web/post/' + this.status.id);
							}
						}
					});
				})
				.catch(err => {
					this.isSubmitting = false;
					if(err.response.data.hasOwnProperty('error')) {
						swal('Error', err.response.data.error, 'error');
					} else {
						swal('Error', 'An error occured, please try again later', 'error');
					}
					console.log(err);
				})
			},

			async checkMediaUpdates() {
				const cached = JSON.parse(this.originalFields);
				const medias = JSON.stringify(cached.media);
				if (medias !== JSON.stringify(this.fields.media)) {
					await axios.all(this.fields.media.map((media) => this.updateAltText(media)))
				}
			},

			async updateAltText(media) {
				return await axios.put('/api/v1/media/' + media.id, {
					description: media.description
				});
			}
		}
	}
</script>

<style lang="scss" scoped>
	div, p {
		font-family: var(--font-family-sans-serif);
	}

	.nav-link {
		font-size: 13px;
		font-weight: 600;
		color: var(--text-lighter);

		&.active {
			font-weight: 800;
			color: var(--primary);
		}
	}

	.slide-fade-enter-active {
		transition: all .5s ease;
	}
	.slide-fade-leave-active {
		transition: all .2s cubic-bezier(0.5, 1.0, 0.6, 1.0);
	}
	.slide-fade-enter, .slide-fade-leave-to {
		transform: translateY(20px);
		opacity: 0;
	}
</style>
