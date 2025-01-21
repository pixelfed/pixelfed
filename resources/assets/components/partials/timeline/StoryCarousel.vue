<template>
	<div class="story-carousel-component">
		<div v-if="canShow" class="d-flex story-carousel-component-wrapper" style="overflow-y: auto;z-index: 3;">
			<a class="col-4 col-lg-3 col-xl-2 px-1 text-dark text-decoration-none" href="/i/stories/new" style="max-width: 120px;">
				<template v-if="selfStory && selfStory.length">
					<div
						class="story-wrapper text-white shadow-sm mb-3"
						:style="{ background: `linear-gradient(rgba(0,0,0,0.2),rgba(0,0,0,0.4)), url(${selfStory[0].latest.preview_url})`, backgroundSize: 'cover', backgroundPosition: 'center'}"
						style="width: 100%;height:200px;border-radius:15px;">
						<div class="story-wrapper-blur d-flex flex-column align-items-center justify-content-between" style="display: block;width: 100%;height:100%;">
							<p class="mb-4"></p>
							<p class="mb-0"><i class="fal fa-plus-circle fa-2x"></i></p>
							<p class="font-weight-bold">My Story</p>
						</div>
					</div>
				</template>
				<template v-else>
					<div
						class="story-wrapper text-white shadow-sm d-flex flex-column align-items-center justify-content-between"
						style="width: 100%;height:200px;border-radius:15px;">
						<p class="mb-4"></p>
						<p class="mb-0"><i class="fal fa-plus-circle fa-2x"></i></p>
						<p class="font-weight-bold">{{ $t('story.add') }}</p>
					</div>
				</template>
			</a>

			<div v-for="(story, index) in stories" class="col-4 col-lg-3 col-xl-2 px-1" style="max-width: 120px;">
				<template v-if="story.hasOwnProperty('url')">
					<a class="story" :href="story.url">
						<div
							v-if="story.latest && story.latest.type == 'photo'"
							class="shadow-sm story-wrapper"
							:class="{ seen: story.seen }"
							:style="{ background: `linear-gradient(rgba(0,0,0,0.2),rgba(0,0,0,0.4)), url(${story.latest.preview_url})`, backgroundSize: 'cover', backgroundPosition: 'center'}">
							<div class="story-wrapper-blur" style="display: block;width: 100%;height:100%;position:relative;">
								<div class="px-2" style="display: block;width: 100%;bottom:0;position: absolute;">
									<p class="mt-3 mb-0">
										<img :src="story.avatar" width="30" height="30" class="avatar" draggable="false" onerror="this.onerror=null;this.src='/storage/avatars/default.jpg?v=0';">
									</p>
									<p class="mb-0"></p>
									<p class="username font-weight-bold small text-truncate">
										{{ story.username }}
									</p>
								</div>
							</div>
						</div>

						<div
							v-else
							class="shadow-sm story-wrapper">
							<div class="px-2" style="display: block;width: 100%;bottom:0;position: absolute;">
								<p class="mt-3 mb-0">
									<img :src="story.avatar" width="30" height="30" class="avatar">
								</p>
								<p class="mb-0"></p>
								<p class="username font-weight-bold small text-truncate">
									{{ story.username }}
								</p>
							</div>
						</div>
					</a>
				</template>

				<template v-else>
					<div
						class="story shadow-sm story-wrapper seen"
						:style="{ background: `linear-gradient(rgba(0,0,0,0.01),rgba(0,0,0,0.04))`}">
						<div class="story-wrapper-blur" style="display: block;width: 100%;height:100%;position:relative;">
							<div class="px-2" style="display: block;width: 100%;bottom:0;position: absolute;">
								<p class="mt-3 mb-0">
								</p>
								<p class="mb-0"></p>
								<p class="username font-weight-bold small text-truncate">
								</p>
							</div>
						</div>
					</div>
				</template>
			</div>

			<template v-if="selfStory && selfStory.length && stories.length < 2">
				<div v-for="i in 5" class="col-4 col-lg-3 col-xl-2 px-1 story" style="max-width: 120px;">
					<div
						class="shadow-sm story-wrapper seen"
						:style="{ background: `linear-gradient(rgba(0,0,0,0.01),rgba(0,0,0,0.04))`}">
						<div class="story-wrapper-blur" style="display: block;width: 100%;height:100%;position:relative;">
							<div class="px-2" style="display: block;width: 100%;bottom:0;position: absolute;">
								<p class="mt-3 mb-0">
								</p>
								<p class="mb-0"></p>
								<p class="username font-weight-bold small text-truncate">
								</p>
							</div>
						</div>
					</div>
				</div>
			</template>
		</div>
	</div>
</template>

<script type="text/javascript">
	export default {
		props: {
			profile: {
				type: Object
			}
		},

		data() {
			return {
				canShow: false,
				stories: [],
				selfStory: undefined
			}
		},

		mounted() {
			this.fetchStories();
		},

		methods: {
			fetchStories() {
				axios.get('/api/web/stories/v1/recent')
				.then(res => {
					if(res.data && res.data.length) {
						this.selfStory = res.data.filter(s => s.pid == this.profile.id);
						let activeStories = res.data.filter(s => s.pid !== this.profile.id);
						this.stories = activeStories;
						this.canShow = true;


						if(!activeStories || !activeStories.length || activeStories.length < 5) {
							this.stories.push(...Array(5 - activeStories.length).keys())
						}
					}
				})
			}
		}
	}
</script>

<style lang="scss">
	.story-carousel-component {
		&-wrapper {
			-ms-overflow-style: none;
			scrollbar-width: none;

			&::-webkit-scrollbar {
				width: 0 !important
			}
		}

		.story {
			&-wrapper {
				display: block;
				position: relative;
				width: 100%;
				height: 200px;
				border-radius: 15px;
				margin-bottom: 1rem;
				background: #b24592;
				background: -webkit-linear-gradient(to right, #b24592, #f15f79);
				background: linear-gradient(to right, #b24592, #f15f79);
				overflow: hidden;
				border: 1px solid var(--border-color);

				.username {
					color: #fff;
				}

				.avatar {
					border-radius: 6px;
					margin-bottom: 5px;
				}

				&.seen {
					opacity: 30%;
				}

				&-blur {
					border-radius: 15px;
					overflow: hidden;
					background: rgba(0, 0, 0, 0.2);
					backdrop-filter: blur(8px);
				}
			}
		}
	}

	.force-dark-mode {
		.story-wrapper {
			&.seen {
				opacity: 50%;
				background: linear-gradient(rgba(255,255,255,0.12),rgba(255,255,255,0.14)) !important;
			}
		}
	}
</style>
