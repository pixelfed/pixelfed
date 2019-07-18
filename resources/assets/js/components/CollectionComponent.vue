<template>
<div>
	<div class="row">
		<div class="col-4 p-0 p-sm-2 p-md-3 p-xs-1" v-for="(s, index) in posts">
			<a class="card info-overlay card-md-border-0" :href="s.url">
				<div class="square">
					<span v-if="s.pf_type == 'photo:album'" class="float-right mr-3 post-icon"><i class="fas fa-images fa-2x"></i></span>
					<span v-if="s.pf_type == 'video'" class="float-right mr-3 post-icon"><i class="fas fa-video fa-2x"></i></span>
					<span v-if="s.pf_type == 'video:album'" class="float-right mr-3 post-icon"><i class="fas fa-film fa-2x"></i></span>
					<div class="square-content" v-bind:style="previewBackground(s)">
					</div>
					<div class="info-overlay-text">
						<h5 class="text-white m-auto font-weight-bold">
							<span>
								<span class="far fa-heart fa-lg p-2 d-flex-inline"></span>
								<span class="d-flex-inline">{{s.favourites_count}}</span>
							</span>
							<span>
								<span class="fas fa-retweet fa-lg p-2 d-flex-inline"></span>
								<span class="d-flex-inline">{{s.reblogs_count}}</span>
							</span>
						</h5>
					</div>
				</div>
			</a>
		</div>
	</div>
</div>
</template>

<style type="text/css" scoped></style>

<script type="text/javascript">
export default {
	props: ['collection-id'],

	data() {
		return {
			loaded: false,
			posts: [],
		}
	},

	beforeMount() {
		this.fetchItems();
	},

	mounted() {
	},

	methods: {
		fetchItems() {
			axios.get('/api/local/collection/items/' + this.collectionId)
			.then(res => {
				this.posts = res.data;
			});
		},
		
		previewUrl(status) {
			return status.sensitive ? '/storage/no-preview.png?v=' + new Date().getTime() : status.media_attachments[0].preview_url;
		},

		previewBackground(status) {
			let preview = this.previewUrl(status);
			return 'background-image: url(' + preview + ');';
		},
	}
}
</script>