<template>
<div class="container">
	<div v-if="loading" class="row">
		<div class="col-12 mt-5 pt-5">
			<div class="text-center">
				<div class="spinner-border" role="status">
					<span class="sr-only">Loading...</span>
				</div>
			</div>
		</div>
	</div>
	<div v-if="stories.length != 0">
		<div id="storyContainer" class="d-none m-3"></div>
	</div>
</div>
</template>

<script type="text/javascript">
	import 'zuck.js/dist/zuck.css';
	import 'zuck.js/dist/skins/snapgram.css';
	window.Zuck = require('zuck.js');

	export default {
		props: ['pid'],

		data() {
			return {
				loading: true,
				stories: {},
				preloadIndex: null
			}
		},

		beforeMount() {
			this.fetchStories();
		},

		methods: {
			fetchStories() {
				let self = this;
				axios.get('/api/stories/v0/profile/' + this.pid)
				.then(res => {
					self.stories = res.data;
					if(res.data.length == 0) {
						window.location.href = '/';
						return;
					}
					self.preloadImages();
				})
				.catch(err => {
					console.log(err);
					// window.location.href = '/';
					return;
				});
			},

			preloadImages() {
				let self = this;
				for (var i = 0; i < this.stories[0].items.length; i++) {
					var preload = new Image();
					$(preload).on('load', function() {

						self.preloadIndex = i;
						if(i == self.stories[0].items.length) {
							self.loadViewer();
							return;
						}
					});
					preload.src = self.stories[0].items[i].src;
				}
			},

			loadViewer() {
				let data = this.stories;

				if(!window.stories) {
					window.stories = new Zuck('storyContainer', {
						stories: data,
						localStorage: false,
						callbacks:  {
							onOpen (storyId, callback) {
								document.body.style.overflow = "hidden";
								callback()
							},

							onEnd (storyId, callback) {
								axios.post('/i/stories/viewed', {
									id: storyId
								});
								callback();
							},

							onClose (storyId, callback) {
								document.body.style.overflow = "auto";
								callback();
								window.location.href = '/';
							},
						}
					});

					this.loading = false;
					// todo: refactor this mess
					document.querySelectorAll('#storyContainer .story')[0].click();
				}
				return;
			}
		}
	}
</script>

<style type="text/css">
	#storyContainer .story {
		margin-right: 2rem;
		width: 100%;
		max-width: 64px;
	}
	.stories.carousel .story > .item-link > .item-preview {
		height: 64px;
	}
	#zuck-modal.with-effects {
		width: 100%;
	}
	.stories.carousel .story > .item-link > .info .name {
		font-weight: 600;
		font-size: 12px;
	}
	.stories.carousel .story > .item-link > .info {
	}
</style>