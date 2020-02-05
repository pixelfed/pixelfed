<template>
	<div>
		<div v-if="stories.length != 0">
			<div id="storyContainer" class="m-3"></div>
		</div>
	</div>
</template>

<style type="text/css" scoped>
	#storyContainer > .story {
		margin-right: 3rem;
	}
</style>

<script type="text/javascript">
	import 'zuck.js/dist/zuck.css';
	import 'zuck.js/dist/skins/snapgram.css';
	let Zuck = require('zuck.js');

	export default {
		data() {
			return {
				stories: {},
			}
		},

		mounted() {
			this.fetchStories();
		},

		methods: {
			fetchStories() {
				axios.get('/api/stories/v0/recent')
				.then(res => {
					let data = res.data;
					let stories = new Zuck('storyContainer', {
						stories: data,
						localStorage: true,
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
							},
						}
					});

					data.forEach(d => {
						let url = '/api/stories/v0/fetch/' + d.pid;
						axios.get(url)
						.then(res => {
							res.data.forEach(item => {
								let img = new Image();
								img.src = item.src;
								stories.addItem(d.id, item);
							});
						});
					});
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
