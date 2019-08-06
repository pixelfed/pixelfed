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
							<button class="btn btn-outline-light btn-sm" @click.prevent="addToCollection">Add Photo</button>
							 &nbsp; &nbsp; 
							<button class="btn btn-outline-light btn-sm" @click.prevent="editCollection">Edit</button>
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
			<button type="button" class="btn btn-primary btn-sm py-1 font-weight-bold px-3 float-right" @click.prevent="updateCollection">Save</button>
		</form>
	</b-modal>
	<b-modal ref="addPhotoModal" id="add-photo-modal" hide-footer centered title="Add Photo" body-class="">
		<form>
			<div class="form-group">
				<label for="title" class="font-weight-bold text-muted">Add Post by URL</label>
				<input type="text" class="form-control" placeholder="https://pixelfed.dev/p/admin/1" v-model="photoId">
				<p class="help-text small text-muted">Only local, public posts can be added</p>
			</div>
			<button type="button" class="btn btn-primary btn-sm py-1 font-weight-bold px-3 float-right" @click.prevent="pushId">Add Photo</button>
		</form>
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
			loaded: false,
			posts: [],
			currentUser: false,
			owner: false,
			title: this.collectionTitle,
			description: this.collectionDescription,
			visibility: this.collectionVisibility,
			photoId: ''
		}
	},

	beforeMount() {
		this.fetchCurrentUser();
		this.fetchItems();
	},

	mounted() {
	},

	methods: {
		fetchCurrentUser() {
			if(document.querySelectorAll('body')[0].classList.contains('loggedIn') == true) {
				axios.get('/api/v1/accounts/verify_credentials').then(res => {
					this.currentUser = res.data;
					this.owner = this.currentUser.id == this.profileId;
				});
			}
		},
		fetchItems() {
			axios.get('/api/local/collection/items/' + this.collectionId)
			.then(res => {
				this.posts = res.data;
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
			this.$refs.addPhotoModal.show();
		},

		pushId() {
			let max = 18;
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
				location.reload();
			}).catch(err => {
				swal('Invalid URL', 'The post you entered was invalid', 'error');
				this.photoId = '';
			});
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

		updateCollection() {
			this.$refs.editModal.hide();
			axios.post('/api/local/collection/' + this.collectionId, {
				title: this.title,
				description: this.description,
				visibility: this.visibility
			}).then(res => {
				console.log(res.data);
			});
		}
	}
}
</script>