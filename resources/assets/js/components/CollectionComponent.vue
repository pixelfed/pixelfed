<template>
<div class="w-100 h-100">
	<div v-if="!loaded" style="height: 80vh;" class="d-flex justify-content-center align-items-center">
		<img src="/img/pixelfed-icon-grey.svg" class="">
	</div>
	<div class="row mt-3" v-if="loaded">
		<div class="col-12 p-0 mb-3">
			<div v-if="owner && !collection.published_at">
				<div class="alert alert-danger d-flex justify-content-center">
					<div class="media align-items-center">
						<i class="far fa-exclamation-triangle fa-3x mr-3"></i>
						<div class="media-body">
							<p class="font-weight-bold mb-0">
								This collection is unpublished.
							</p>
							<p class="small mb-0">
								This collection is not visible to anyone else until you publish it. <br />
								To publish, click on the <strong>Edit</strong> button and then click on the <strong>Publish</strong> button.
							</p>
						</div>
					</div>
				</div>
			</div>
		</div>

		<div class="col-12 p-0 mb-3">

			<div class="d-flex align-items-center justify-content-center overflow-hidden">
				<div class="dims"></div>
				<div style="z-index:500;position: absolute;" class="text-white mx-5">
					<p class="text-center pt-3 text-break" style="font-size: 3rem;line-height: 3rem;">{{title || 'Untitled Collection'}}</p>
					<div class="text-center mb-3 text-break read-more" style="overflow-y: hidden">{{description}}</div>
					<p class="text-center">

						<span v-if="owner && collection.visibility != 'public'">
							<span
								v-if="collection.visibility == 'draft'"
								class="btn btn-outline-light btn-sm text-capitalize py-0"
								style="font-size: 10px"
								>
								<i class="far fa-lock"></i> Draft
							</span>
							<span
								v-else-if="collection.visibility == 'private'"
								class="btn btn-outline-light btn-sm text-capitalize py-0"
								style="font-size: 10px"
								>
								Followers Only
							</span>
							<span>·</span>
						</span>
						<span>{{collection.post_count}} photos</span>
						<span>·</span>
						<span>by <a :href="'/' + profileUsername" class="font-weight-bold text-white">{{profileUsername}}</a></span>
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
				<img
					v-if="posts && posts.length"
					:src="previewUrl(posts[0])"
					 alt=""
					 style="width:100%; height: 400px; object-fit: cover;"
				>
				<div v-else class="bg-info" style="width:100%; height: 400px;"></div>
			</div>
		</div>
		<div class="col-12 p-0">
			<!-- <masonry
			  :cols="{default: 2, 700: 2, 400: 1}"
			  :gutter="{default: '5px'}"
			> -->
			<div v-if="posts && posts.length > 0" class="row px-3 px-md-0">
				<div v-for="(s, index) in posts" class="col-6 col-md-4 feed">
					<!-- <a class="card info-overlay card-md-border-0 mb-4 square" :href="s.url">
						<img :src="previewUrl(s)" class="square-content w-100" style="object-fit: cover;">
					</a> -->

					<a v-if="s.hasOwnProperty('pf_type') && s.pf_type == 'video'" class="card info-overlay card-md-border-0" :href="statusUrl(s)">
							<div class="square">
								<div class="square-content">
									<div class="info-overlay-text-label rounded">
										<h5 class="text-white m-auto font-weight-bold">
											<span>
												<span class="far fa-video fa-2x p-2 d-flex-inline"></span>
											</span>
										</h5>
									</div>
									<blur-hash-canvas
										width="32"
										height="32"
										class="rounded"
										:hash="s.media_attachments[0].blurhash">
									</blur-hash-canvas>
								</div>
							</div>
						</a>

						<a v-else-if="s.sensitive" class="card info-overlay card-md-border-0" :href="statusUrl(s)">
							<div class="square">
								<div class="square-content">
									<div class="info-overlay-text-label rounded">
										<h5 class="text-white m-auto font-weight-bold">
											<span>
												<span class="far fa-eye-slash fa-lg p-2 d-flex-inline"></span>
											</span>
										</h5>
									</div>
									<blur-hash-canvas
										width="32"
										height="32"
										class="rounded"
										:hash="s.media_attachments[0].blurhash">
									</blur-hash-canvas>
								</div>
							</div>
						</a>

						<a v-else class="card info-overlay card-md-border-0" :href="statusUrl(s)">
							<div class="square">
								<div class="square-content">
									<!-- <img :src="previewUrl(s)" class="img-fluid w-100 rounded-lg" onerror="this.onerror=null;this.src='/storage/no-preview.png?v=0'">
									<span class="badge badge-light" style="position: absolute;bottom:2px;right:2px;opacity: 0.4;">
										{{ timeago(s.created_at) }}
									</span> -->
									<blur-hash-image
										width="32"
										height="32"
										class="rounded"
										:hash="s.media_attachments[0].blurhash"
										:src="previewUrl(s)" />
								</div>
							</div>
						</a>
				</div>

				<div v-if="canLoadMore" class="col-12">
					<intersect @enter="enterIntersect">
						<div class="card card-body shadow-none border">
							<div class="d-flex justify-content-center align-items-center flex-column">
								<b-spinner variant="muted" />
								<p class="text-lighter small mt-2 mb-0">Loading more...</p>
							</div>
						</div>
					</intersect>
				</div>
			</div>
			<!-- </masonry> -->
		</div>
	</div>
	<b-modal ref="editModal" id="edit-modal" hide-footer centered title="Edit Collection" body-class="">
		<form>
			<div class="form-group">
				<label for="title" class="font-weight-bold text-muted">Title</label>
				<input type="text" class="form-control" id="title" placeholder="Untitled Collection" v-model="title" maxlength="50">
                <div class="text-right small text-muted">
                    <span>{{title ? title.length : 0}}/50</span>
                </div>
			</div>
			<div class="form-group">
				<label for="description" class="font-weight-bold text-muted">Description</label>
				<textarea class="form-control" id="description" placeholder="Add a description here ..." v-model="description" rows="3" maxlength="500"></textarea>
                <div class="text-right small text-muted">
                    <span>{{description ? description.length : 0}}/500</span>
                </div>
			</div>
			<div class="form-group">
				<label for="visibility" class="font-weight-bold text-muted">Visibility</label>
				<select class="custom-select" v-model="visibility">
					<option value="public">Public</option>
					<option value="private">Followers Only</option>
					<option value="draft">Draft</option>
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
					    v-if="posts.length > 0"
						type="button"
						class="btn btn-outline-primary btn-sm py-1 font-weight-bold px-3"
						@click.prevent="publishCollection">
						Publish
					</button>

					<button
						v-else
						type="button"
						class="btn btn-outline-primary btn-sm py-1 font-weight-bold px-3 disabled" disabled>
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
			<div class="row m-1" v-if="postsList.length > 0" style="max-height: 360px; overflow-y: auto;">
				<div v-for="(p, index) in postsList" :key="'postList-'+index" class="col-4 p-1 cursor-pointer" @click="addRecentId(p)">
					<div class="square border">
						<div class="square-content" v-bind:style="'background-image: url(' + getPreviewUrl(p) + ');'"></div>
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
						<div class="square-content border" v-bind:style="'background-image: url(' + p.media_attachments[0].url + ');'"></div>
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

<style lang="scss" scoped>
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

	.info-overlay-text-field {
		font-size: 13.5px;
		margin-bottom: 2px;

		@media (min-width: 768px) {
			font-size: 20px;
			margin-bottom: 15px;
		}
	}

	.feed {
		.card.info-overlay {
			margin-bottom: 2rem;
		}
	}
</style>

<script type="text/javascript">
import VueMasonry from 'vue-masonry-css';
import Intersect from 'vue-intersect';

export default {
	props: [
		'collection-id', 
		'collection-title',
		'collection-description',
		'collection-visibility',
		'profile-id',
		'profile-username'
	],

	components: {
		"intersect": Intersect,
	},

	data() {
		return {
			collection: {},
			config: window.App.config,
			loaded: false,
			posts: [],
			ids: [],
			user: false,
			owner: false,
			title: this.collectionTitle,
			description: this.collectionDescription,
			visibility: this.collectionVisibility,
			photoId: '',
			postsList: [],
			loadingPostList: false,
			addingPostToCollection: false,
			markedForDeletion: [],
			canLoadMore: false,
			isIntersecting: false,
			page: 1
		}
	},

	beforeMount() {
		this.fetchCollection();
	},

    updated() {
        this.initReadMore();
    },

	methods: {
		enterIntersect() {
			if(this.isIntersecting) {
				return;
			}
			this.isIntersecting = true;
			this.page++;
			this.fetchItems();
		},

		statusUrl(s) {
			return '/i/web/post/' + s.id;
		},

		fetchCollection() {
			axios.get('/api/local/collection/' + this.collectionId)
			.then(res => {
				this.collection = res.data;
				if(this.collection.post_count > 9) {
					this.canLoadMore = true;
				}
				this.fetchCurrentUser();
			})
		},

		fetchCurrentUser() {
			if(document.querySelectorAll('body')[0].classList.contains('loggedIn') == true) {
				axios.get('/api/pixelfed/v1/accounts/verify_credentials').then(res => {
					this.user = res.data;
					this.owner = this.user.id == this.profileId;
					window._sharedData.curUser = res.data;
					window.App.util.navatar();
					this.fetchItems();
				});
			} else {
				this.fetchItems();
			}
		},

		fetchItems() {
			axios.get(
				'/api/local/collection/items/' + this.collectionId,
				{
					params: {
						page: this.page
					}
				}
			)
			.then(res => {
				if(res.data.length == 0) {
					console.log('no items found');
					this.loaded = true;
					this.isIntersecting = false;
					this.canLoadMore = false;
					return;
				}
				let data = res.data.filter(p => {
					return this.ids.indexOf(p.id) == -1;
				});
				this.posts.push(...data);
				this.ids = this.posts.map(p => {
					return p.id;
				});
				this.loaded = true;
				this.isIntersecting = false;
				if(data.length == 0) {
					this.canLoadMore = false;
				}
			});
		},

		previewUrl(status) {
			return status && status.sensitive ? '/storage/no-preview.png?v=' + new Date().getTime() : status.media_attachments[0].url;
		},

		previewBackground(status) {
			let preview = this.previewUrl(status);
			return 'background-image: url(' + preview + ');';
		},

		addToCollection() {
			let self = this;
			this.loadingPostList = true;
			if(this.postsList.length == 0) {
				axios.get('/api/v1/accounts/'+this.profileId+'/statuses', {
					params: {
						min_id: 1,
						limit: 40
					}
				})
				.then(res => {
					self.postsList = res.data.filter(l => {
						return  (l.visibility == 'public' || l.visibility == 'unlisted') && l.sensitive == false && self.ids.indexOf(l.id) == -1; 
					});
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

            if(!url.includes('/i/web/post/') && !url.includes('/p/')) {
                swal('Invalid URL', 'Invalid URL', 'error');
                this.photoId = '';
                return;
            }

            let fragment = split[split.length - 1].split('?')[0];

			axios.post('/api/local/collection/item', {
				collection_id: this.collectionId,
				post_id: fragment
			}).then(res => {
				self.ids.push(...fragment);
				self.posts.push(res.data);
				self.collection.post_count++;
				self.id = '';
			}).catch(err => {
				swal('Invalid URL', 'The post you entered was invalid', 'error');
				this.photoId = '';
			});
			self.$refs.addPhotoModal.hide();
			// window.location.reload();
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
			if (this.posts.length === 0) {
				swal('Error', 'You cannot publish an empty collection');
				return;
			}

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
					console.log(res.data);
					// window.location.href = res.data.url;
				}).catch(err => {
					swal('Something went wrong', 'There was a problem with your request, please try again later.', 'error')
			    });
			} else {
				return;
			}
		},

		updateCollection() {
			this.closeModals();
			axios.post('/api/local/collection/' + this.collectionId, {
				title: this.title,
				description: this.description,
				visibility: this.visibility
			}).then(res => {
				this.collection = res.data;
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
						this.collection.post_count = this.collection.post_count - 1;
						this.closeModals();

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
			this.ids = this.ids.filter(post_id => {
				return post_id != id;
			});
		},

		addRecentId(post) {
			let self = this;
			axios.post('/api/local/collection/item', {
				collection_id: self.collectionId,
				post_id: post.id
			}).then(res => {
				// window.location.reload();
				this.closeModals();
				this.posts.push(res.data);
				this.ids.push(post.id);
				this.collection.post_count++;
			}).catch(err => {
				swal('Oops!', 'An error occured, please try selecting another post.', 'error');
				this.photoId = '';
			});
		},

		timeago(ts) {
			return App.util.format.timeAgo(ts);
		},

		closeModals() {
			this.$refs.editModal.hide();
			this.$refs.addPhotoModal.hide();
			this.$refs.editPhotosModal.hide();
		},

		getPreviewUrl(post) {
			if(!post.media_attachments || !post.media_attachments.length) {
				return '/storage/no-preview.png';
			}

			let media = post.media_attachments[0];

			if(media.preview_url.endsWith('storage/no-preview.png')) {
				return media.type === 'image' ?
					media.url :
					'/storage/no-preview.png';
			}

			return media.preview_url;
		},

        initReadMore() {
          $('.read-more').each(function(k,v) {
              let el = $(this);
              let attr = el.attr('data-readmore');
              if(typeof attr !== typeof undefined && attr !== false) {
                return;
              }
              el.readmore({
                collapsedHeight: 38,
                heightMargin: 38,
                moreLink: '<a href="#" class="d-block text-center small font-weight-bold mt-n3 mb-2" style="color: rgba(255, 255, 255, 0.5)">Show more</a>',
                lessLink: '<a href="#" class="d-block text-center small font-weight-bold mt-n3 mb-2" style="color: rgba(255, 255, 255, 0.5)">Show less</a>',
              });
          });
        }
	}
}
</script>
