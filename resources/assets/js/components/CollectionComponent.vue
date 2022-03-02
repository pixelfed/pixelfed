<template>
<div class="w-100 h-100">
	<div v-if="!loaded" style="height: 80vh;" class="d-flex justify-content-center align-items-center">
		<img src="/img/pixelfed-icon-grey.svg" class="">
	</div>
	<div class="row mt-3" v-if="loaded">
		<div class="col-12 p-0 mb-3">
			<picture class="d-flex align-items-center justify-content-center">
				<div class="dims"></div>
				<div style="z-index:500;position: absolute;" class="text-white">
					<p class="display-4 text-center pt-3">{{title || 'Untitled Collection'}}</p>
					<p class="lead text-center mb-3">{{description}}</p>
					<p class="text-center">
						{{posts.length}} photos · by <a :href="'/' + profileUsername" class="font-weight-bold text-white">{{profileUsername}}</a>
					</p>
					<p v-if="owner == true" class="pt-3 text-center">
						<span>
							<button class="btn btn-outline-light btn-sm" @click.prevent="addToCollection" onclick="this.blur();">
								<span v-if="loadingPostList == false">Add Photo</span>
								<span v-else class="px-4">
									<div class="spinner-border spinner-border-sm" role="status">
									  <span class="sr-only">Loading...</span>
									</div>
								</span>
							</button>
							 &nbsp; &nbsp; 
							<button class="btn btn-outline-light btn-sm" @click.prevent="editCollection" onclick="this.blur();">Edit</button>
							 &nbsp; &nbsp; 
							<button class="btn btn-outline-light btn-sm" @click.prevent="deleteCollection">Delete</button>
						</span>
					</p>
				</div>
				<img :src="previewUrl(posts[0])"
					 alt=""
					 style="width:100%; height: 600px; object-fit: cover;" 
				>
			</picture>
		</div>
		<div class="col-12 p-0">
			<masonry
			  :cols="{default: 2, 700: 2, 400: 1}"
			  :gutter="{default: '5px'}"
			>
				<div v-for="(s, index) in posts">
					<a class="card info-overlay card-md-border-0 mb-1" :href="s.url">
						<img :src="previewUrl(s)" class="img-fluid w-100">
					</a>
				</div>
			</masonry>
		</div>
	</div>
	<b-modal ref="editModal" id="edit-modal" hide-footer centered title="Edit Collection" body-class="">
		<form>
			<div class="form-group">
				<label for="title" class="font-weight-bold text-muted">Title</label>
				<input type="text" class="form-control" id="title" placeholder="Untitled Collection" v-model="title">
			</div>
			<div class="form-group">
				<label for="description" class="font-weight-bold text-muted">Description</label>
				<textarea class="form-control" id="description" placeholder="Add a description here ..." v-model="description" rows="3"></textarea>
			</div>
			<div class="form-group">
				<label for="visibility" class="font-weight-bold text-muted">Visibility</label>
				<select class="custom-select" v-model="visibility">
					<option value="public">Public</option>
					<option value="private">Followers Only</option>
				</select>
			</div>
			<div class="d-flex justify-content-between align-items-center pt-3">
				<a
					class="text-primary font-weight-bold text-decoration-none"
					href="#"
					@click.prevent="showEditPhotosModal">
					Edit Photos
				</a>

				<div v-if="collection.published_at">
					<button
						type="button"
						class="btn btn-primary btn-sm py-1 font-weight-bold px-3 float-right"
						@click.prevent="updateCollection">
						Save
					</button>
				</div>

				<div v-else class="float-right">
					<button
						type="button"
						class="btn btn-outline-primary btn-sm py-1 font-weight-bold px-3"
						@click.prevent="publishCollection">
						Publish
					</button>

					<button
						type="button"
						class="btn btn-primary btn-sm py-1 font-weight-bold px-3"
						@click.prevent="updateCollection">
						Save
					</button>
				</div>
			</div>
		</form>
	</b-modal>
	<b-modal ref="addPhotoModal" id="add-photo-modal" hide-footer centered title="Add Photo" body-class="m-3">
		<div class="form-group">
			<label for="title" class="font-weight-bold text-muted">Add Recent Post</label>
			<div class="row m-1" v-if="postsList.length > 0">
				<div v-for="(p, index) in postsList" :key="'postList-'+index" class="col-4 p-1 cursor-pointer" @click="addRecentId(p)">
					<div class="square">
						<div class="square-content" v-bind:style="'background-image: url(' + p.media_attachments[0].url + ');'"></div>
					</div>
				</div>
				<div class="col-12">
					<hr>
				</div>
			</div>
		</div>
		<form>
			<div class="form-group">
				<label for="title" class="font-weight-bold text-muted">Add Post by URL</label>
				<input type="text" class="form-control" placeholder="https://pixelfed.dev/p/admin/1" v-model="photoId">
				<p class="help-text small text-muted">Only local, public posts can be added</p>
			</div>
			<button type="button" class="btn btn-primary btn-sm py-1 font-weight-bold px-3 float-right" @click.prevent="pushId">
				<span v-if="addingPostToCollection" class="px-4">
					<div class="spinner-border spinner-border-sm" role="status">
						<span class="sr-only">Loading...</span>
					</div>
				</span>
				<span v-else>
					Add Photo
				</span>
			</button>
		</form>
	</b-modal>
	<b-modal ref="editPhotosModal" id="edit-photos-modal" hide-footer centered title="Edit Collection Photos" body-class="m-3">
		<div class="form-group">
			<p class="font-weight-bold text-dark text-center">Select a Photo to Delete</p>
			<div class="row m-1 scrollbar-hidden" v-if="posts.length > 0" style="max-height: 350px;overflow-y: auto;">
				<div v-for="(p, index) in posts" :key="'plm-'+index" class="col-4 p-1 cursor-pointer">
					<div :class="[markedForDeletion.indexOf(p.id) == -1 ? 'square' : 'square  delete-border']" @click="markPhotoForDeletion(p.id)">
						<div class="square-content" v-bind:style="'background-image: url(' + p.media_attachments[0].url + ');'"></div>
					</div>
				</div>
			</div>
			<div v-show="markedForDeletion.length > 0">
				<button type="button" @click.prevent="confirmDeletion" class="btn btn-primary font-weight-bold py-0 btn-block mb-0 mt-4">Delete {{markedForDeletion.length}} {{markedForDeletion.length == 1 ? 'photo':'photos'}}</button>
			</div>
		</div>

	</b-modal>
</div>
</template>

<style type="text/css" scoped>
	.dims {
		position: absolute;
		top: 0;
		right: 0;
		bottom: 0;
		left: 0;
		background: rgba(0,0,0,.68);
		z-index: 300;
	}
	.scrollbar-hidden::-webkit-scrollbar {
		display: none;
	}
	.delete-border {
		border: 4px solid #ff0000;
	}
	.delete-border .square-content {
		background-color: red;
		background-blend-mode: screen;
	}
</style>

<script type="text/javascript">
import VueMasonry from 'vue-masonry-css'

Vue.use(VueMasonry);
export default {
	props: [
		'collection-id', 
		'collection-title',
		'collection-description',
		'collection-visibility',
		'profile-id',
		'profile-username'
	],

	data() {
		return {
			collection: {},
			config: window.App.config,
			loaded: false,
			posts: [],
			ids: [],
			currentUser: false,
			owner: false,
			title: this.collectionTitle,
			description: this.collectionDescription,
			visibility: this.collectionVisibility,
			photoId: '',
			postsList: [],
			loadingPostList: false,
			addingPostToCollection: false,
			markedForDeletion: []
		}
	},

	beforeMount() {
		this.fetchCollection();
	},

	mounted() {
	},

	methods: {
		fetchCollection() {
			axios.get('/api/local/collection/' + this.collectionId)
			.then(res => {
				this.collection = res.data;
				this.fetchCurrentUser();
			})
		},

		fetchCurrentUser() {
			if(document.querySelectorAll('body')[0].classList.contains('loggedIn') == true) {
				axios.get('/api/pixelfed/v1/accounts/verify_credentials').then(res => {
					this.currentUser = res.data;
					this.owner = this.currentUser.id == this.profileId;
					window._sharedData.curUser = res.data;
					window.App.util.navatar();
					this.fetchItems();
				});
			} else {
				this.fetchItems();
			}
		},

		fetchItems() {
			axios.get('/api/local/collection/items/' + this.collectionId)
			.then(res => {
				this.posts = res.data;
				this.ids = this.posts.map(p => {
					return p.id;
				});
				this.loaded = true;
			});
		},
		
		previewUrl(status) {
			return status.sensitive ? '/storage/no-preview.png?v=' + new Date().getTime() : status.media_attachments[0].preview_url;
		},

		previewBackground(status) {
			let preview = this.previewUrl(status);
			return 'background-image: url(' + preview + ');';
		},

		addToCollection() {
			let self = this;
			this.loadingPostList = true;
			if(this.postsList.length == 0) {
				axios.get('/api/pixelfed/v1/accounts/'+this.profileId+'/statuses', {
					params: {
						min_id: 1,
						limit: 13
					}
				})
				.then(res => {
					self.postsList = res.data.filter(l => {
						return self.ids.indexOf(l.id) == -1;
					}).splice(0,9);
					self.loadingPostList = false;
					self.$refs.addPhotoModal.show();
				}).catch(err => {
					self.loadingPostList = false;
					swal('An Error Occured', 'We cannot process your request at this time, please try again later.', 'error');
				})
			} else {
				this.$refs.addPhotoModal.show();
				this.loadingPostList = false;
			}
		},

		pushId() {
			let max = this.config.uploader.max_collection_length;
			let addingPostToCollection = true;
			let self = this;
			if(this.posts.length >= max) {
				swal('Error', 'You can only add ' + max + ' posts per collection', 'error');
				return;
			}
			let url = this.photoId;
			let origin = window.location.origin;
			let split = url.split('/');
			if(url.slice(0, origin.length) !== origin) {
				swal('Invalid URL', 'You can only add posts from this instance', 'error');
				this.photoId = '';
			}
			if(url.slice(0, origin.length + 3) !== origin + '/p/' || split.length !== 6) {
				swal('Invalid URL', 'Invalid URL', 'error');
				this.photoId = '';
			}

			axios.post('/api/local/collection/item', {
				collection_id: this.collectionId,
				post_id: split[5]
			}).then(res => {
				self.ids.push(...split[5]);
			}).catch(err => {
				swal('Invalid URL', 'The post you entered was invalid', 'error');
				this.photoId = '';
			});
			self.$refs.addPhotoModal.hide();
			window.location.reload();
		},

		editCollection() {
			this.$refs.editModal.show();
		},

		deleteCollection() {
			if(this.owner == false) {
				return;
			}

			let confirmed = window.confirm('Are you sure you want to delete this collection?');
			if(confirmed) {
				axios.delete('/api/local/collection/' + this.collectionId)
				.then(res => {
					window.location.href = '/';
				});
			} else {
				return;
			}
		},

		publishCollection() {
			if(this.owner == false) {
				return;
			}

			let confirmed = window.confirm('Are you sure you want to publish this collection?');
			if(confirmed) {
				axios.post('/api/local/collection/' + this.collectionId + '/publish', {
					title: this.title,
					description: this.description,
					visibility: this.visibility
				})
				.then(res => {
					window.location.href = '/';
				});
			} else {
				return;
			}
		},

		updateCollection() {
			this.$refs.editModal.hide();
			axios.post('/api/local/collection/' + this.collectionId, {
				title: this.title,
				description: this.description,
				visibility: this.visibility
			}).then(res => {
				console.log(res.data);
			});
		},

		showEditPhotosModal() {
			this.$refs.editModal.hide();
			this.$refs.editPhotosModal.show();
		},

		markPhotoForDeletion(id) {
			this.markedForDeletion.indexOf(id) == -1 ?
			this.markedForDeletion.push(id) :
			this.markedForDeletion = this.markedForDeletion.filter(d => {
				return d != id;
			});
		},

		confirmDeletion() {
			let self = this;
			let confirmed = window.confirm('Are you sure you want to delete this?');
			if(confirmed) {
				this.markedForDeletion.forEach(mfd => {
					axios.delete('/api/local/collection/item', {
						params: {
							collection_id: self.collectionId,
							post_id: mfd
						}
					})
					.then(res => {
						self.removeItem(mfd);
						this.$refs.editPhotosModal.hide();
					})
					.catch(err => {
						swal(
							'Oops!',
							'An error occured with your request, please try again later.',
							'error'
						);
					})
				});
				this.markedForDeletion = [];
			}
		},

		removeItem(id) {
			this.posts = this.posts.filter(post => {
				return post.id != id;
			});
		},

		addRecentId(post) {
			let self = this;
			axios.post('/api/local/collection/item', {
				collection_id: self.collectionId,
				post_id: post.id
			}).then(res => {
				window.location.reload();
			}).catch(err => {
				swal('Oops!', 'An error occured, please try selecting another post.', 'error');
				this.photoId = '';
			});
		}		
	}
}
</script>
