<template>
	<div>
		<div v-if="show" class="card card-body p-0 border mt-4 mb-3 shadow-none">
			<div id="storyContainer" :class="[list == true ? 'mt-1 mr-3 mb-0 ml-1':'mx-3 mt-3 mb-0 pb-0']"></div>
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
		props: ['list'],
		data() {
			return {
				show: false,
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
					if(!res.data.length) {
						this.show = false;
						return;
					}
					let stories = new Zuck('storyContainer', {
						list: this.list == true ? true : false,
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
				this.show = true;
			}
		}
	}
</script>

<style type="text/css">
	#storyContainer .story {
		margin-right: 2rem;
		width: 100%;
		max-width: 60px;
	}
	.stories.carousel .story > .item-link > .item-preview {
		height: 60px;
	}
	#zuck-modal.with-effects {
		width: 100%;
	}
	.stories.carousel .story > .item-link > .info .name {
		font-weight: 500;
		font-size: 11px;
	}
	.stories.carousel .story > .item-link > .info {
	}
</style>
