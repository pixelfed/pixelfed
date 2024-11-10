<template>
	<div class="group-compose-form">
		<input ref="photoInput" id="photoInput" type="file" class="d-none file-input" accept="image/jpeg,image/png" @change="handlePhotoChange">
		<input ref="videoInput" id="videoInput" type="file" class="d-none file-input" accept="video/mp4" @change="handleVideoChange">
		<div class="card card-body border mb-3 shadow-sm rounded-lg">
			<div class="media align-items-top">
				<img v-if="profile" :src="profile.avatar" class="rounded-circle border mr-3" width="42px" height="42px" onerror="this.onerror=null;this.src='/storage/avatars/default.png?v=2'">
				<div class="media-body">
					<div class="d-block" style="min-height: 80px;">
						<div v-if="isUploading" class="w-100">
							<p class="font-weight-light mb-1">Uploading media ...</p>
							<div class="progress rounded-pill" style="height:4px">
								<div class="progress-bar" role="progressbar" :aria-valuenow="uploadProgress" aria-valuemin="0" aria-valuemax="100" :style="{ width: uploadProgress + '%'}"></div>
							</div>
						</div>
						<div v-else class="form-group mb-3">
							<textarea
								class="form-control"
								:class="{
									'form-control-lg': !composeText || composeText.length < 40,
									'rounded-pill': !composeText || composeText.length < 40,
									'bg-light': !composeText || composeText.length < 40,
									'border-0': !composeText || composeText.length < 40
								}"
								:rows="!composeText || composeText.length < 40 ? 1 : 5"
								:placeholder="placeholder"
								style="resize: none;"
								v-model="composeText"
								></textarea>

							<div v-if="composeText" class="small text-muted mt-1" style="min-height: 20px;">
								<span class="float-right font-weight-bold">
									{{ composeText ? composeText.length : 0 }}/500
								</span>
							</div>
						</div>
					</div>
					<div v-if="tab" class="tab">
						<div v-if="tab === 'poll'">
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
							</div>
						</div>
					</div>
					<div v-if="!isUploading" class="">
						<div>
							<div v-if="photoName && photoName.length" class="bg-light rounded-pill mb-4 py-2">
								<div class="media align-items-center">
									<span style="width: 40px;height: 40px;border-radius:50px;opacity: 0.6;" class="d-flex align-items-center justify-content-center bg-primary mx-3">
										<i class="fal fa-image fa-lg text-white"></i>
									</span>
									<div class="media-body">
										<p class="mb-0 font-weight-bold text-muted">
											{{ photoName }}
										</p>
									</div>
									<button class="btn btn-link font-weight-bold text-decoration-none" @click.prevent="clearFileInputs">
										Delete
									</button>
								</div>
							</div>
							<div v-if="videoName && videoName.length" class="bg-light rounded-pill mb-4 py-2">
								<div class="media align-items-center">
									<span style="width: 40px;height: 40px;border-radius:50px;opacity: 0.6;" class="d-flex align-items-center justify-content-center bg-primary mx-3">
										<i class="fal fa-video fa-lg text-white"></i>
									</span>
									<div class="media-body">
										<p class="mb-0 font-weight-bold text-muted">
											{{ videoName }}
										</p>
									</div>
									<button class="btn btn-link font-weight-bold text-decoration-none" @click.prevent="clearFileInputs">
										Delete
									</button>
								</div>
							</div>
						</div>

						<div>
							<button
								class="btn btn-light border font-weight-bold py-1 px-2 rounded-lg mr-3"
								@click="switchTab('photo')"
								:disabled="photoName || videoName">
								<i class="fal fa-image mr-2"></i>
								<span>Add Photo</span>
							</button>
							<!-- <button
								class="btn btn-light border font-weight-bold py-1 px-2 rounded-lg mr-3"
								@click="switchTab('video')"
								:disabled="photoName || videoName">
								<i class="fal fa-video mr-2"></i>
								<span>Add Video</span>
							</button>
							<button
								v-if="allowPolls"
								:class="[ tab == 'poll' ? 'btn-primary' : 'btn-light' ]"
								class="btn border font-weight-bold py-1 px-2 rounded-lg mr-3"
								@click="switchTab('poll')"
								:disabled="photoName || videoName">

								<i class="fal fa-poll-h mr-2"></i>
								<span>Add Poll</span>
							</button> -->
							<!-- <button v-if="allowEvent" class="btn btn-light border font-weight-bold py-1 px-2 rounded-lg">
								<i class="fal fa-calendar-alt mr-1"></i>
								<span>Create Event</span>
							</button> -->
						</div>
					</div>
				</div>
			</div>
			<p v-if="!isUploading && composeText && composeText.length > 1 || !isUploading && ['photo', 'video'].includes(tab)" class="mb-0">
				<button class="btn btn-primary font-weight-bold float-right px-5 rounded-pill mt-3" @click="newPost()" :disabled="isPosting">
					<span v-if="isPosting">
						<div class="spinner-border text-white spinner-border-sm" role="status">
							<span class="sr-only">Loading...</span>
						</div>
					</span>
					<span v-else>Post</span>
				</button>
			</p>
		</div>
	</div>
</template>

<script type="text/javascript">
	export default {
		props: {
			profile: {
				type: Object
			},

			groupId: {
				type: String
			}
		},

		data() {
			return {
				config: window.App.config,
				composeText: undefined,
				tab: null,
				placeholder: 'Write something...',
				allowPhoto: true,
				allowVideo: true,
				allowPolls: true,
				allowEvent: true,
				pollOptionModel: null,
				pollOptions: [],
				pollExpiry: 1440,
				uploadProgress: 0,
				isUploading: false,
				isPosting: false,
				photoName: undefined,
				videoName: undefined
			}
		},

		methods: {
			newPost() {
				if(this.isPosting) {
					return;
				}

				this.isPosting = true;
				let self = this;
				let type = 'text';
				let data = new FormData();
				data.append('group_id', this.groupId);
				if(this.composeText && this.composeText.length) {
					data.append('caption', this.composeText);
				}

				switch(this.tab) {
					case 'poll':
						if(!this.pollOptions || this.pollOptions.length < 2 || this.pollOptions.length > 4) {
							swal('Oops!', 'A poll must have 2-4 choices.', 'error');
							return;
						}

						if(!this.composeText || this.composeText.length < 5) {
							swal('Oops!', 'A poll question must be at least 5 characters.', 'error');
							return;
						}

						for (var i = 0; i < this.pollOptions.length; i++) {
							data.append('pollOptions[]', this.pollOptions[i]);
						}

						data.append('expiry', this.pollExpiry);
						type = 'poll';
					break;

					case 'photo':
						data.append('photo', this.$refs.photoInput.files[0]);
						type = 'photo';
						this.isUploading = true;
					break;

					case 'video':
						data.append('video', this.$refs.videoInput.files[0]);
						type = 'video';
						this.isUploading = true;
					break;
				}

				data.append('type', type);

				axios.post('/api/v0/groups/status/new', data, {
					onUploadProgress: function(progressEvent) {
						self.uploadProgress = Math.floor(progressEvent.loaded / progressEvent.total * 100);
					}
				})
				.then(res => {
					this.isPosting = false;
					this.isUploading = false;
					this.uploadProgress = 0;
					this.composeText = null;
					this.photo = null;
					this.tab = null;
					this.clearFileInputs(false);
					this.$emit('new-status', res.data);
				}).catch(err => {
					if(err.response.status == 422) {
						this.isPosting = false;
						this.isUploading = false;
						this.uploadProgress = 0;
						this.photo = null;
						this.tab = null;
						this.clearFileInputs(false);
						swal('Oops!', err.response.data.error, 'error');
					} else {
						this.isPosting = false;
						this.isUploading = false;
						this.uploadProgress = 0;
						this.photo = null;
						this.tab = null;
						this.clearFileInputs(false);
						swal('Oops!', 'An error occured while processing your request, please try again later', 'error');
					}
				});
			},

			switchTab(newTab) {
				if(newTab == this.tab) {
					this.tab = null;
					this.placeholder = 'Write something...';
					return;
				}

				switch(newTab) {
					case 'poll':
						this.placeholder = 'Write poll question here...'
					break;

					case 'photo':
						this.$refs.photoInput.click();
					break;

					case 'video':
						this.$refs.videoInput.click();
					break;

					default:
						this.placeholder = 'Write something...';
				}

				this.tab = newTab;
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

			handlePhotoChange() {
				event.currentTarget.blur();
				this.tab = 'photo';
				this.photoName = event.target.files[0].name;
			},

			handleVideoChange() {
				event.currentTarget.blur();
				this.tab = 'video';
				this.videoName = event.target.files[0].name;
			},

			clearFileInputs(blur = true) {
				if(blur) {
 					event.currentTarget.blur();
				}
				this.tab = null;
				this.$refs.photoInput.value = null;
				this.photoName = null;
				this.$refs.videoInput.value = null;
				this.videoName = null;
			}
		}
	}
</script>

<style lang="scss">
	.group-compose-form {
	}
</style>
