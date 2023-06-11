<template>
	<div>
		<b-modal
			v-model="isOpen"
			centered
			size="md"
			:scrollable="true"
			hide-footer
			header-class="py-2"
			body-class="p-0"
			title-class="w-100 text-center pl-4 font-weight-bold"
			title-tag="p">
			<template #modal-header="{ close }">
				<template v-if="historyIndex === undefined">
					<div class="d-flex flex-grow-1 justify-content-between align-items-center">
						<span style="width:40px;"></span>
						<h5 class="font-weight-bold mb-0">Post History</h5>
						<b-button size="sm" variant="link" @click="close()">
							<i class="far fa-times text-dark fa-lg"></i>
						</b-button>
					</div>
				</template>

				<template v-else>
					<div class="d-flex flex-grow-1 justify-content-between align-items-center pt-1">
						<b-button size="sm" variant="link" @click.prevent="historyIndex = undefined">
							<i class="fas fa-chevron-left text-primary fa-lg"></i>
						</b-button>

						<div class="d-flex align-items-center">
							<div class="d-flex align-items-center" style="gap: 5px;">
								<div class="d-flex align-items-center" style="gap: 5px;">
									<img
										:src="allHistory[0].account.avatar"
										width="16"
										height="16"
										class="rounded-circle"
										onerror="this.src='/storage/avatars/default.jpg';this.onerror=null;">
									<span class="font-weight-bold">{{ allHistory[0].account.username }}</span>
								</div>

								<div>{{ historyIndex == (allHistory.length - 1) ? 'created' : 'edited' }} {{ formatTime(allHistory[historyIndex].created_at) }}</div>
							</div>
						</div>

						<b-button size="sm" variant="link" @click="close()">
							<i class="fas fa-times text-dark fa-lg"></i>
						</b-button>
					</div>
				</template>
			</template>

			<div v-if="isLoading" class="d-flex align-items-center justify-content-center" style="min-height: 500px;">
				<b-spinner />
			</div>

			<template v-else>
				<div v-if="historyIndex === undefined" class="list-group border-top-0">
					<div
						v-for="(history, idx) in allHistory"
						class="list-group-item d-flex align-items-center justify-content-between" style="gap: 5px;">
							<div class="d-flex align-items-center" style="gap: 5px;">
								<div class="d-flex align-items-center" style="gap: 5px;">
									<img
										:src="history.account.avatar"
										width="24"
										height="24"
										class="rounded-circle"
										onerror="this.src='/storage/avatars/default.jpg';this.onerror=null;">
									<span class="font-weight-bold">{{ history.account.username }}</span>
								</div>
								<div>{{ idx == (allHistory.length - 1) ? 'created' : 'edited' }} {{ formatTime(history.created_at) }}</div>
							</div>

							<a class="stretched-link text-decoration-none" href="#" @click.prevent="historyIndex = idx">
								<div class="d-flex align-items-center" style="gap:5px;">
									<i class="far fa-chevron-right text-primary fa-lg"></i>
								</div>
							</a>
					</div>
				</div>

				<div v-else class="d-flex align-items-center flex-column border-top-0 justify-content-center">
					<!-- <img :src="allHistory[historyIndex].media_attachments[0].url" style="max-height: 400px;object-fit: contain;"> -->
					<template v-if="postType() === 'text'">
					</template>
					<template v-else-if="postType() === 'image'">
						<div style="width: 100%">
							<blur-hash-image
								:width="32"
								:height="32"
								:punch="1"
								class="img-contain border-bottom"
								:hash="allHistory[historyIndex].media_attachments[0].blurhash"
								:src="allHistory[historyIndex].media_attachments[0].url"
								/>
						</div>
					</template>
					<template v-else-if="postType() === 'album'">
						<div style="width: 100%">
							<b-carousel
								controls
								indicators
								background="#000000"
								style="text-shadow: 1px 1px 2px #333;"
							>
								<b-carousel-slide
									v-for="(media, idx) in allHistory[historyIndex].media_attachments"
									:key="'pfph:'+media.id+':'+idx"
									:img-src="media.url"
								></b-carousel-slide>
							</b-carousel>
						</div>
					</template>
					<template v-else-if="postType() === 'video'">
						<div style="width: 100%">
							<div class="embed-responsive embed-responsive-16by9 border-bottom">
								<video class="video" controls playsinline preload="metadata" loop>
									<source :src="allHistory[historyIndex].media_attachments[0].url" :type="allHistory[historyIndex].media_attachments[0].mime">
								</video>
							</div>
						</div>
					</template>
					<div class="w-100 my-4 px-4 text-break justify-content-start">
						<p class="mb-0" v-html="allHistory[historyIndex].content"></p>
						<!-- <p class="mb-0" v-html="getDiff(historyIndex)"></p> -->
					</div>
				</div>
			</template>

		</b-modal>
	</div>
</template>

<script type="text/javascript">
	export default {
		props: {
			status: {
				type: Object
			}
		},

		data() {
			return {
				isOpen: false,
				isLoading: true,
				allHistory: [],
				historyIndex: undefined,
				user: window._sharedData.user
			}
		},

		methods: {
			open() {
				this.isOpen = true;
				this.isLoading = true;
				this.historyIndex = undefined;
				this.allHistory = [];
				setTimeout(() => {
					this.fetchHistory();
				}, 300);
			},

			fetchHistory() {
				axios.get(`/api/v1/statuses/${this.status.id}/history`)
				.then(res => {
					this.allHistory = res.data;
				})
				.finally(() => {
					this.isLoading = false;
				})
			},

			getDiff(idx) {
				if(idx == this.allHistory.length - 1) {
					return this.allHistory[this.allHistory.length - 1].content;
				}

				// let r = Diff.diffChars(this.allHistory[idx - 1].content.replace(/(<([^>]+)>)/gi, ""), this.allHistory[idx].content.replace(/(<([^>]+)>)/gi, ""));
				let fragment = document.createElement('div');
				r.forEach((part) => {
					  // green for additions, red for deletions
					  // grey for common parts
					  const color = part.added ? 'green' :
					    part.removed ? 'red' : 'grey';
					  let span = document.createElement('span');
					  span.style.color = color;
					  console.log(part.value, part.value.length)
					  if(part.added) {
					  	let trimmed = part.value.trim();
					  	if(!trimmed.length) {
						  span.appendChild(document.createTextNode('·'));
					  	} else {
						  span.appendChild(document.createTextNode(part.value));
					  	}
					  } else {
						  span.appendChild(document.createTextNode(part.value));
					  }
					  fragment.appendChild(span);
				});
				return fragment.innerHTML;
			},

			formatTime(ts) {
				let date = Date.parse(ts);
				let seconds = Math.floor((new Date() - date) / 1000);
				let interval = Math.floor(seconds / 63072000);
				if (interval < 0) {
					return "0s";
				}
				if (interval >= 1) {
					return interval + (interval == 1 ? ' year' : ' years') + " ago";
				}
				interval = Math.floor(seconds / 604800);
				if (interval >= 1) {
					return interval + (interval == 1 ? ' week' : ' weeks') + " ago";
				}
				interval = Math.floor(seconds / 86400);
				if (interval >= 1) {
					return interval + (interval == 1 ? ' day' : ' days') + " ago";
				}
				interval = Math.floor(seconds / 3600);
				if (interval >= 1) {
					return interval + (interval == 1 ? ' hour' : ' hours') + " ago";
				}
				interval = Math.floor(seconds / 60);
				if (interval >= 1) {
					return interval + (interval == 1 ? ' minute' : ' minutes') + " ago";
				}
				return Math.floor(seconds) + " seconds ago";
			},

			postType() {
				if(this.historyIndex === undefined) {
					return;
				}

				let post = this.allHistory[this.historyIndex];

				if(!post) {
					return 'text';
				}

				let media = post.media_attachments;

				if(!media || !media.length) {
					return 'text';
				}

				if(media.length == 1) {
					return media[0].type;
				}

				return 'album';
			}
		}
	}
</script>

<style lang="scss">
	.img-contain {
		img {
			object-fit: contain;
		}
	}
</style>
