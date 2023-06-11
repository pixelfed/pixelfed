<template>
	<div class="dms-page-component">
		<div v-if="isLoaded" class="container-fluid mt-3">
			<div class="row">
				<div class="col-md-3 d-md-block">
					<sidebar :user="profile" />
				</div>

				<div class="col-md-5 offset-md-1 mb-5 order-2 order-md-1">
					<h1 class="font-weight-bold mb-4">Direct Messages</h1>
					<div v-if="threadsLoaded">
						<div v-for="(thread, idx) in threads" class="card shadow-sm mb-1" style="border-radius:15px;">
							<div class="card-body p-3">
								<div class="media">
									<img :src="thread.accounts[0].avatar" width="45" height="45" class="shadow-sm mr-3" style="border-radius: 15px;" onerror="this.onerror=null;this.src='/storage/avatars/default.png?v=0';">

									<div class="media-body">
										<!-- <p class="lead mb-n2">{{ thread.accounts[0].display_name }}</p> -->
										<div class="d-flex justify-content-between align-items-start mb-1">
											<p class="dm-display-name font-weight-bold mb-0">&commat;{{ thread.accounts[0].acct }}</p>
											<p class="font-weight-bold small text-muted mb-0">{{ timeago(thread.last_status.created_at) }} ago</p>
										</div>

										<p class="dm-thread-summary text-muted mr-4" v-html="threadSummary(thread.last_status)"></p>
									</div>

									<router-link class="btn btn-link stretched-link align-self-center mr-n3" :to="`/i/web/direct/thread/${thread.accounts[0].id}`">
										<i class="fal fa-chevron-right fa-lg text-lighter"></i>
									</router-link>
								</div>
							</div>
						</div>

						<div v-if="!threads || !threads.length" class="row justify-content-center">
							<div class="col-12 text-center">
								<img src="/img/illustrations/dk-nature-man-monochrome.svg" class="img-fluid" style="opacity: 0.6;">
								<p class="lead text-muted font-weight-bold">Your inbox is empty</p>
							</div>
						</div>

						<div v-if="canLoadMore">
							<intersect @enter="enterIntersect">
								<dm-placeholder />
							</intersect>
						</div>
					</div>

					<div v-else>
						<dm-placeholder />
					</div>
				</div>

				<div class="col-md-3 d-md-block order-1 order-md-2 mb-4">
					<button class="btn btn-dark shadow-sm font-weight-bold btn-block" @click="openCompose"><i class="far fa-envelope mr-1"></i> Compose</button>
					<hr>
					<div class="d-flex d-md-block">
						<button
							v-for="(tab, index) in tabs"
							class="btn shadow-sm font-weight-bold btn-block text-capitalize mt-0 mt-md-2 mx-1 mx-md-0"
							:class="[ index === tabIndex ? 'btn-primary' : 'btn-light' ]"
							@click="toggleTab(index)"
							>
								{{ $t('directMessages.' + tab) }}
						</button>
					</div>
				</div>
			</div>

			<drawer />
		</div>
		<div v-else class="d-flex justify-content-center align-items-center" style="height:calc(100vh - 58px);">
			<b-spinner />
		</div>

		<b-modal
			ref="compose"
			hide-header
			hide-footer
			centered
			rounded
			size="md"
		>
			<div class="card shadow-none mt-4">
				<div class="card-body d-flex align-items-center justify-content-between flex-column" style="min-height: 50vh;">
					<h3 class="font-weight-bold">New Direct Message</h3>
					<div>
						<p class="mb-0 font-weight-bold">Select Recipient</p>
						<autocomplete
							:search="composeSearch"
							:disabled="composeLoading"
							placeholder="@dansup"
							aria-label="Search usernames"
							:get-result-value="getTagResultValue"
							@submit="onTagSubmitLocation"
							ref="autocomplete"
						>
						</autocomplete>
						<p class="small text-muted">Search by username, or webfinger (@dansup@pixelfed.social)</p>
						<div style="width:300px;"></div>
					</div>
					<div>
						<button class="btn btn-outline-dark rounded-pill font-weight-bold px-5 py-1" @click="closeCompose">Cancel</button>
					</div>
				</div>
			</div>
		</b-modal>
	</div>
</template>

<script type="text/javascript">
	import Drawer from './partials/drawer.vue';
	import Sidebar from './partials/sidebar.vue';
	import Placeholder from './partials/placeholders/DirectMessagePlaceholder.vue';
	import Intersect from 'vue-intersect'

	export default {
		components: {
			"drawer": Drawer,
            "sidebar": Sidebar,
            "intersect": Intersect,
            "dm-placeholder": Placeholder
        },

		data() {
			return {
				isLoaded: false,
				profile: undefined,
				canLoadMore: true,
				threadsLoaded: false,
				composeLoading: false,
				threads: [],
				tabIndex: 0,
				tabs: [
					'inbox',
					'sent',
					'requests'
				],
				page: 1,
				ids: [],
				isIntersecting: false
			}
		},

		mounted() {
			this.profile = window._sharedData.user;
			this.isLoaded = true;
			this.fetchThreads();
        },

        methods: {
        	fetchThreads() {
        		axios.get('/api/v1/conversations', {
        			params: {
        				scope: this.tabs[this.tabIndex]
        			}
        		})
        		.then(res => {
        			let data = res.data.filter(m => {
        				return m && m.hasOwnProperty('last_status') && m.last_status;
        			})
        			let ids = data.map(dm => dm.accounts[0].id);
        			this.ids = ids;
        			this.threads = data;
        			this.threadsLoaded = true;
        			this.page++;
        		});
        	},

        	timeago(ts) {
        		return App.util.format.timeAgo(ts);
        	},

        	enterIntersect() {
        		if(this.isIntersecting) {
        			return;
        		}

        		this.isIntersecting = true;

        		axios.get('/api/v1/conversations', {
        			params: {
        				scope: this.tabs[this.tabIndex],
        				page: this.page
        			}
        		})
        		.then(res => {
        			let data = res.data.filter(m => {
        				return m && m.hasOwnProperty('last_status') && m.last_status;
        			})
        			data.forEach(dm => {
        				if(this.ids.indexOf(dm.accounts[0].id) == -1) {
        					this.ids.push(dm.accounts[0].id);
        					this.threads.push(dm);
        				}
        			})
        			// this.threads.push(...res.data);
        			if(!res.data.length || res.data.length < 5) {
        				this.canLoadMore = false;
        				this.isIntersecting = false;
        				return;
        			}
        			this.page++;
        			this.isIntersecting = false;
        		});
        	},

        	toggleTab(index) {
        		event.currentTarget.blur();
        		this.threadsLoaded = false;
        		this.page = 1;
        		this.tabIndex = index;
        		this.fetchThreads();
        	},

        	threadSummary(status, len = 50) {
        		if(status.pf_type == 'photo') {
        			let sender = this.profile.id == status.account.id;
        			let icon = '<div class="' + (sender ? 'text-muted' : 'text-primary') + ' border px-2 py-1 mt-1 rounded" style="font-size:11px;width: fit-content"><i class="far fa-image mr-1"></i> <span>';
        			icon += sender ? 'Sent a photo' : 'Received a photo';
        			return icon + '</span></div>';
        		}

        		if(status.pf_type == 'video') {
        			let sender = this.profile.id == status.account.id;
        			let icon = '<div class="' + (sender ? 'text-muted' : 'text-primary') + ' border px-2 py-1 mt-1 rounded" style="font-size:11px;width: fit-content"><i class="far fa-video mr-1"></i> <span>';
        			icon += sender ? 'Sent a video' : 'Received a video';
        			return icon + '</span></div>';
        		}

        		let res = '';

        		if(this.profile.id == status.account.id) {
        			res += '<i class="far fa-reply-all fa-flip-both"></i> ';
        		}

        		let content = status.content;
        		let text = content.replace(/(<([^>]+)>)/gi, "");

        		if(text.length > len) {
        			return res + text.slice(0, len) + '...';
        		}

        		return res + text;
        	},

        	openCompose() {
        		this.$refs.compose.show();
        	},

        	composeSearch(input) {
				if (input.length < 1) { return []; };
				let self = this;
				let results = [];
				return axios.post('/api/direct/lookup', {
					q: input
				}).then(res => {
					return res.data;
				});
			},

			getTagResultValue(result) {
				// return '@' + result.name;
				return result.local ? '@' + result.name : result.name;
			},

			onTagSubmitLocation(result) {
				//this.$refs.autocomplete.value = '';
				this.composeLoading = true;
				window.location.href = '/i/web/direct/thread/' + result.id;
				return;
			},

			closeCompose() {
				this.$refs.compose.hide();
			}
        }
	}
</script>

<style lang="scss" scoped>
	.dms-page-component {
		font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;

		.dm {
			&-thread-summary {
				margin-bottom: 0;
				font-size: 12px;
				line-height: 12px;
			}

			&-display-name {
				font-size: 16px;
			}
		}
	}

</style>
