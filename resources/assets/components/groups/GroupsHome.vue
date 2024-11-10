<template>
	<div class="groups-home-component w-100 h-100">
		<div v-if="initialLoad" class="row border-bottom m-0 p-0">
			<div class="col-2 shadow" style="height: 100vh;background:#fff;top:51px;overflow: hidden;z-index: 1;position: sticky;">
				<div class="p-1">
					<div class="d-flex justify-content-between align-items-center py-3">
						<p class="h2 font-weight-bold mb-0">Groups</p>
						<a class="btn btn-light px-2 rounded-circle" href="/settings/home">
							<i class="fas fa-cog fa-lg"></i>
						</a>
					</div>

					<div class="mb-3">
						<autocomplete
							:search="autocompleteSearch"
							placeholder="Search groups by name"
							aria-label="Search groups by name"
							:get-result-value="getSearchResultValue"
							:debounceTime="700"
							@submit="onSearchSubmit"
							ref="autocomplete"
							>
							<template #result="{ result, props }">
								<li
								v-bind="props"
								class="autocomplete-result"
								>

									<div class="media align-items-center">
										<img v-if="result.local && result.metadata && result.metadata.hasOwnProperty('header') && result.metadata.header.hasOwnProperty('url')" :src="result.metadata.header.url" width="32" height="32">
										<div v-else class="icon-placeholder">
											<i class="fal fa-user-friends"></i>
										</div>
										<div class="media-body text-truncate mr-3">
											<p class="result-name mb-n1 font-weight-bold">
												{{ truncateName(result.name) }}
												<span v-if="result.verified" class="fa-stack ml-n2" title="Verified Group" data-toggle="tooltip">
													<i class="fas fa-circle fa-stack-1x fa-lg" style="color: #22a7f0cc;font-size:18px"></i>
													<i class="fas fa-check fa-stack-1x text-white" style="font-size:10px"></i>
												</span>
											</p>
											<p class="mb-0 text-muted" style="font-size: 10px;">
												<span v-if="!result.local" title="Remote Group">
													<i class="far fa-globe"></i>
												</span>
												<span v-if="!result.local">·</span>
												<span class="font-weight-bold">{{ result.member_count }} members</span>
											</p>
										</div>
									</div>
								</li>
							</template>
						</autocomplete>
					</div>

					<button
						class="btn btn-light group-nav-btn"
						:class="{ active: tab == 'feed' }"
						@click="switchTab('feed')">
						<div class="group-nav-btn-icon">
							<i class="fas fa-list"></i>
						</div>
						<div class="group-nav-btn-name">
							Your Feed
						</div>
					</button>

					<button
						class="btn btn-light group-nav-btn"
						:class="{ active: tab == 'discover' }"
						@click="switchTab('discover')">
						<div class="group-nav-btn-icon">
							<i class="fas fa-compass"></i>
						</div>
						<div class="group-nav-btn-name">
							Discover
						</div>
					</button>

					<button
						class="btn btn-light group-nav-btn"
						:class="{ active: tab == 'mygroups' }"
						@click="switchTab('mygroups')">
						<div class="group-nav-btn-icon">
							<i class="fas fa-list"></i>
						</div>
						<div class="group-nav-btn-name">
							My Groups
						</div>
					</button>

					<button
						class="btn btn-light group-nav-btn"
						:class="{ active: tab == 'notifications' }"
						@click="switchTab('notifications')">
						<div class="group-nav-btn-icon">
							<i class="far fa-bell"></i>
						</div>
						<div class="group-nav-btn-name">
							Your Notifications
						</div>
					</button>

					<!-- <button
						class="btn btn-light group-nav-btn"
						:class="{ active: tab == 'invitations' }"
						@click="switchTab('invitations')">
						<div class="group-nav-btn-icon">
							<i class="fas fa-user-plus"></i>
						</div>
						<div class="group-nav-btn-name">
							Group Invitations
						</div>
					</button> -->

					<button
						class="btn btn-light group-nav-btn"
						:class="{ active: tab == 'remotesearch' }"
						@click="switchTab('remotesearch')">
						<div class="group-nav-btn-icon">
							<i class="fas fa-search-plus"></i>
						</div>
						<div class="group-nav-btn-name">
							Find a remote group
						</div>
					</button>

					<button
						v-if="config && config.limits.user.create.new"
						class="btn btn-primary btn-block rounded-pill font-weight-bold mt-3"
						@click="switchTab('creategroup')"
						:disabled="tab == 'creategroup'">
						<i class="fas fa-plus mr-2"></i> Create New Group
					</button>

					<hr>
					<div v-for="group in groups" class="ml-2">
						<div class="card shadow-sm border text-decoration-none text-dark">
							<img v-if="group.metadata && group.metadata.hasOwnProperty('header')" :src="group.metadata.header.url" class="card-img-top" style="width: 100%; height: auto;object-fit: cover;max-height: 160px;">
							<div v-else class="bg-primary" style="width:100%;height:160px;"></div>
							<div class="card-body">
								<div class="lead font-weight-bold d-flex align-items-top" style="height: 60px;">
									{{ group.name }}
									<span v-if="group.verified" class="fa-stack ml-n2 mt-n2">
										<i class="fas fa-circle fa-stack-1x fa-lg" style="color: #22a7f0cc;font-size:18px"></i>
										<i class="fas fa-check fa-stack-1x text-white" style="font-size:10px"></i>
									</span>
								</div>
								<div class="text-muted font-weight-light d-flex justify-content-between">
									<span>{{group.member_count}} Members</span>
									<span style="font-size: 12px;padding: 2px 5px;color: rgba(75, 119, 190, 1);background:rgba(137, 196, 244, 0.2);border:1px solid rgba(137, 196, 244, 0.3);font-weight:400;text-transform: capitalize;" class="rounded">{{ group.self.role }}</span>
								</div>
								<hr>
								<p class="mb-0">
									<a class="btn btn-light btn-block border rounded-lg font-weight-bold" :href="group.url">View Group</a>
								</p>
							</div>
						</div>
					</div>
				</div>
			</div>

			<keep-alive>
				<transition name="fade">
					<self-feed v-if="tab == 'feed'" :profile="profile" v-on:switchtab="switchTab" />
					<self-discover v-if="tab == 'discover'" :profile="profile" />
					<self-notifications v-if="tab == 'notifications'" :profile="profile" />
					<self-invitations v-if="tab == 'invitations'" :profile="profile" />
					<self-remote-search v-if="tab == 'remotesearch'" :profile="profile" />
					<self-groups v-if="tab == 'mygroups'" :profile="profile" />
					<create-group v-if="tab == 'creategroup'" :profile="profile" />
					<div v-if="tab == 'gsearch'">
						<div class="col-12 px-5">
							<div class="my-4">
		                        <p class="h1 font-weight-bold mb-1">Group Search</p>
		                        <p class="lead text-muted mb-0">Search and explore groups.</p>
		                    </div>
		                    <div class="media align-items-center text-lighter">
		                    	<i class="far fa-chevron-left fa-lg mr-3"></i>
		                    	<div class="media-body">
		                    		<p class="lead mb-0">Use the search bar on the side menu</p>
		                    	</div>
		                    </div>
						</div>
					</div>
				</transition>
			</keep-alive>
		</div>
		<div v-else class="row justify-content-center mt-5">
			<b-spinner />
		</div>
	</div>
</template>

<script type="text/javascript">
	import GroupStatus from './partials/GroupStatus.vue';
	import SelfFeed from './partials/SelfFeed.vue';
	import SelfDiscover from './partials/SelfDiscover.vue';
	import SelfGroups from './partials/SelfGroups.vue';
	import SelfNotifications from './partials/SelfNotifications.vue';
	import SelfInvitations from './partials/SelfInvitations.vue';
	import SelfRemoteSearch from './partials/SelfRemoteSearch.vue';
	import CreateGroup from './CreateGroup.vue';
	import Autocomplete from '@trevoreyre/autocomplete-vue'
	import '@trevoreyre/autocomplete-vue/dist/style.css'

	export default {
		data() {
			return {
				initialLoad: false,
				config: {},
				groups: [],
				profile: {},
				tab: null,
				searchQuery: undefined,
			};
		},

		components: {
			'autocomplete-input': Autocomplete,
			'group-status': GroupStatus,
			'self-discover': SelfDiscover,
			'self-groups': SelfGroups,
			'self-feed': SelfFeed,
			'self-notifications': SelfNotifications,
			'self-invitations': SelfInvitations,
			'self-remote-search': SelfRemoteSearch,
			"create-group": CreateGroup
		},

		mounted() {
			this.fetchConfig();
		},

		methods: {
			init() {
				document.querySelectorAll("footer").forEach(e => e.parentNode.removeChild(e));
				document.querySelectorAll(".mobile-footer-spacer").forEach(e => e.parentNode.removeChild(e));
				document.querySelectorAll(".mobile-footer").forEach(e => e.parentNode.removeChild(e));
				// let u = new URLSearchParams(window.location.search);
				// if(u.has('ct')) {
				// 	if(['mygroups', 'notifications', 'discover', 'remotesearch', 'creategroup', 'gsearch'].includes(u.get('ct'))) {
				// 		if(u.get('ct') == 'creategroup' && this.config.limits.user.create.new == false) {
				// 			this.tab = 'feed';
				// 			history.pushState(null, null, '/groups/feed');
				// 		} else {
				// 			this.tab = u.get('ct');
				// 		}
				// 	} else {
				// 		this.tab = 'feed';
				// 		history.pushState(null, null, '/groups/feed');
				// 	}
				// } else {
				// 	this.tab = 'feed';
				// }
				this.initialLoad = true;
			},

			fetchConfig() {
				axios.get('/api/v0/groups/config')
				.then(res => {
					this.config = res.data;
					this.fetchProfile();
				});
			},

			fetchProfile() {
				axios.get('/api/pixelfed/v1/accounts/verify_credentials')
				.then(res => {
					this.profile = res.data;
					this.init();
					window._sharedData.curUser = res.data;
					window.App.util.navatar();
				})
			},

			fetchSelfGroups() {
				axios.get('/api/v0/groups/self/list')
				.then(res => {
					this.groups = res.data;
				});
			},

			switchTab(tab) {
				event.currentTarget.blur();
				window.scrollTo(0,0);
				this.tab = tab;

				if(tab != 'feed') {
					history.pushState(null, null, '/groups/home?ct=' + tab);
				} else {
					history.pushState(null, null, '/groups/home');
				}
			},

			autocompleteSearch(input) {
				if (!input || input.length < 2) {
					if(this.tab = 'searchresults') {
						this.tab = 'feed';
					}
					return [];
				};

				this.searchQuery = input;
				// this.tab = 'searchresults';

				if(input.startsWith('http')) {
					let url = new URL(input);
					if(url.hostname == location.hostname) {
						location.href = input;
						return [];
					}
					return [];
				}

				if(input.startsWith('#')) {
					this.$bvToast.toast(input, {
						title: 'Hashtag detected',
						variant: 'info',
						autoHideDelay: 5000
					});
					return [];
				}

				return axios.post('/api/v0/groups/search/global', {
					q: input,
					v: '0.2'
				})
				.then(res => {
					this.searchLoading = false;
					return res.data;
				}).catch(err => {

					if(err.response.status === 422) {
						this.$bvToast.toast(err.response.data.error.message, {
							title: 'Cannot display search results',
							variant: 'danger',
							autoHideDelay: 5000
						});
					}

					return [];
				})
			},

			getSearchResultValue(result) {
				return result.name;
			},

			onSearchSubmit(result) {
				if (result.length < 1) {
					return [];
				}

				location.href = result.url;
			},

			truncateName(val) {
				if(val.length < 24) {
					return val;
				}

				return val.substr(0, 23) + '...';
			}
		}
	}
</script>

<style lang="scss">
	.groups-home-component {
        font-family: var(--font-family-sans-serif);

		.group-nav-btn {
			display: block;
			width: 100%;
			padding-left: 0;
			padding-top: 0.3rem;
			padding-bottom: 0.3rem;
			margin-bottom: 0.3rem;
			border-radius: 1.5rem;
			text-align: left;
			color: #6c757d;
			background-color: transparent;
			border-color: transparent;
			justify-content: flex-start;

			&.active {
				background-color: #EFF6FF !important;
				border:1px solid #DBEAFE !important;
				color: #212529;

				.group-nav-btn-icon {
					background-color: #2c78bf !important;
					color: #fff !important;
				}
			}

			&-icon {
				display: inline-flex;
				width: 35px;
				height: 35px;
				padding: 12px;
				background-color: #E5E7EB;
				border-radius: 17px;
				margin: auto 0.3rem;
				align-items: center;
				justify-content: center;
			}

			&-name {
				display: inline-block;
				margin-left: 0.3rem;
				font-weight: 700;
			}
		}

		.autocomplete-input {
			height: 2.375rem;
			background-color: #f8f9fa !important;
			font-size: 0.9rem;
			color: #495057;
			border-radius: 50rem;
			border-color: transparent;

			&:focus,
			&[aria-expanded=true] {
				box-shadow: none;
			}
		}

		.autocomplete-result {
			background: none;
			padding: 12px;

			&:hover,
			&:focus {
				background-color: #EFF6FF !important;
			}

			.media {
				img {
					object-fit: cover;
					border-radius: 4px;
					margin-right: 0.6rem;
				}

				.icon-placeholder {
					display: flex;
					width: 32px;
					height: 32px;
					background-color: #2c78bf;
					border-radius: 4px;
					justify-content: center;
					align-items: center;
					color: #fff;
					margin-right: 0.6rem;
				}
			}
		}

		.autocomplete-result-list {
			padding-bottom: 0;
		}

		.fade-enter-active, .fade-leave-active {
			transition: opacity 200ms;
		}

		.fade-enter, .fade-leave-to {
			opacity: 0;
		}
	}
</style>
