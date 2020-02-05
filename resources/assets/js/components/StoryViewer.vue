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
			}
		},

		beforeMount() {
			this.fetchStories();
		},

		methods: {
			fetchStories() {
				axios.get('/api/stories/v0/profile/' + this.pid)
				.then(res => {
					let data = res.data;
					if(data.length == 0) {
						window.location.href = '/';
						return;
					}
					window._storyData = data;
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
					document.querySelectorAll('#storyContainer .story')[0].click()
				})
				.catch(err => {
					window.location.href = '/';
					return;
				});
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