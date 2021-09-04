<template>
	<div>
		<div v-if="show" class="card card-body p-0 border mt-md-4 mb-md-3 shadow-none">
			<div v-if="loading" class="w-100 h-100 d-flex align-items-center justify-content-center">
				<div class="spinner-border spinner-border-sm text-lighter" role="status">
					<span class="sr-only">Loading...</span>
				</div>
			</div>
			<div v-else class="d-flex align-items-center justify-content-start scrolly">
				<div
					v-for="(story, index) in stories"
					class="px-3 pt-3 text-center cursor-pointer"
					:class="{ seen: story.seen }"
					@click="showStory(index)">
					<span
						:class="[
							story.seen ? 'not-seen' : '',
							story.local ? '' : 'remote'
						]"
						class="mb-1 ring">
						<img :src="story.avatar" width="60" height="60" class="rounded-circle border" onerror="this.onerror=null;this.src='/storage/avatars/default.png?v=2'">
					</span>
					<p
						class="small font-weight-bold text-truncate"
						:class="{ 'text-lighter': story.seen }"
						style="max-width: 69px"
						:title="story.username"
						>
						{{story.username}}
					</p>
				</div>
			</div>
		</div>
	</div>
</template>

<script type="text/javascript">
	export default {
		props: ['list', 'scope'],
		data() {
			return {
				loading: true,
				show: true,
				stories: {},
			}
		},

		mounted() {
			this.fetchStories();
		},

		methods: {
			fetchStories() {
				axios.get('/api/web/stories/v1/recent')
				.then(res => {
					let data = res.data;
					if(!res.data.length) {
						this.show = false;
						return;
					}
					this.stories = res.data;
					this.loading = false;
				}).catch(err => {
					this.loading = false;
					this.$bvToast.toast('Cannot load stories. Please try again later.', {
						title: 'Error',
						variant: 'danger',
						autoHideDelay: 5000
					});
					this.show = false;
				});
			},

			showStory(index) {
				let suffix;

				switch(this.scope) {
					case 'home':
						suffix = '/?t=1';
					break;
					case 'local':
						suffix = '/?t=2';
					break;
					case 'network':
						suffix = '/?t=3';
					break;

				}
				window.location.href = this.stories[index].url + suffix;
			}
		}
	}
</script>

<style lang="scss" scoped>
	.card {
		height: 122px;
	}

	.ring {
		display: block;
		width: 66px;
		height: 66px;
		border-radius: 50%;
		padding: 3px;
		background: radial-gradient(ellipse at 70% 70%, #ee583f 8%, #d92d77 42%, #bd3381 58%);

		&.remote {
			background: radial-gradient(ellipse at 70% 70%, #f64f59 8%, #c471ed 42%, #12c2e9 58%);
		}

		&.not-seen {
			opacity: 0.55;
			background: #ccc;
		}

		img {
			background: #fff;
			padding: 3px;
		}
	}

	.scrolly {
		-ms-overflow-style: none;
		scrollbar-width: none;
		overflow-y: scroll;

		&::-webkit-scrollbar {
			display: none;
		}
	}
</style>
