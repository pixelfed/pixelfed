<template>
	<nav class="metro-nav navbar navbar-expand navbar-light navbar-laravel sticky-top shadow-none py-1">
		<div class="container-fluid">
				<a class="navbar-brand d-flex align-items-center" href="/i/web" title="Logo" style="width:50px">
					<img src="/img/pixelfed-icon-color.svg" height="30px" class="px-2" loading="eager" alt="Pixelfed logo">
					<span class="font-weight-bold mb-0 d-none d-sm-block" style="font-size:20px;">
						{{ brandName }}
					</span>
				</a>

				<div class="collapse navbar-collapse">
					<div class="navbar-nav ml-auto">
					  <!-- <form class="form-inline search-bar" method="get" action="/i/results">
						<input class="form-control" name="q" placeholder="Search ..." aria-label="search" autocomplete="off" required style="position: relative;line-height: 0.6;width:100%;min-width: 300px;max-width: 500px;border-radius: 8px;" role="search">
					  </form> -->

						<autocomplete
							class="searchbox"
							:search="autocompleteSearch"
							:placeholder="$t('navmenu.search')"
							aria-label="Search"
							:get-result-value="getSearchResultValue"
							:debounceTime="700"
							@submit="onSearchSubmit"
							ref="autocomplete">

							<template #result="{ result, props }">
								<li
								v-bind="props"
								class="autocomplete-result sr"
								>
									<div v-if="result.s_type === 'account'" class="media align-items-center my-0">
										<img :src="result.avatar" width="40" height="40" class="sr-avatar" style="border-radius: 40px" onerror="this.src='/storage/avatars/default.png?v=0';this.onerror=null;">
										<div class="media-body sr-account">
											<div class="sr-account-acct" :class="{ compact: result.acct && result.acct.length > 24 }">
												&commat;{{ result.acct }}
												<b-button
													v-if="result.locked"
													v-b-tooltip.html
													title="Private Account"
													variant="link"
													size="sm"
													class="p-0"
													>
													<i class="far fa-lock fa-sm text-lighter ml-1"></i>
												</b-button>
											</div>
											<template v-if="result.is_admin">
												<div class="sr-account-stats">
													<div class="sr-account-stats-followers text-danger font-weight-bold">
														Admin
													</div>
													<div>·</div>
													<div class="sr-account-stats-followers font-weight-bold">
														<span>{{ formatCount(result.followers_count) }}</span>
														<span>Followers</span>
													</div>
												</div>
											</template>
											<template v-else>
												<template v-if="result.local">
													<div class="sr-account-stats">
														<div v-if="result.followers_count" class="sr-account-stats-followers font-weight-bold">
															<span>{{ formatCount(result.followers_count) }}</span>
															<span>Followers</span>
														</div>
														<div v-if="result.followers_count && result.statuses_count">·</div>
														<div v-if="result.statuses_count" class="sr-account-stats-statuses font-weight-bold">
															<span>{{ formatCount(result.statuses_count) }}</span>
															<span>Posts</span>
														</div>
														<div v-if="!result.followers_count && result.statuses_count">·</div>
														<div class="sr-account-stats-statuses font-weight-bold">
															<i class="far fa-clock fa-sm"></i>
															<span>{{ timeAgo(result.created_at) }}</span>
														</div>
													</div>
												</template>
												<template v-else>
													<div class="sr-account-stats">
														<div v-if="result.followers_count" class="sr-account-stats-followers font-weight-bold">
															<span>{{ formatCount(result.followers_count) }}</span>
															<span>Followers</span>
														</div>
														<div v-if="result.followers_count && result.statuses_count">·</div>
														<div v-if="result.statuses_count" class="sr-account-stats-statuses font-weight-bold">
															<span>{{ formatCount(result.statuses_count) }}</span>
															<span>Posts</span>
														</div>
														<div v-if="!result.followers_count && result.statuses_count">·</div>

														<div v-if="!result.followers_count && !result.statuses_count" class="sr-account-stats-statuses font-weight-bold">
															Remote Account
														</div>
														<div v-if="!result.followers_count && !result.statuses_count">
															·
														</div>
														<b-button
															v-b-tooltip.html
															:title="'Joined ' + timeAgo(result.created_at) + ' ago'"
															variant="link"
															size="sm"
															class="sr-account-stats-statuses p-0"
															>
															<i class="far fa-clock fa-sm"></i>
															<span class="font-weight-bold">{{ timeAgo(result.created_at) }}</span>
														</b-button>
													</div>
												</template>
											</template>
										</div>
									</div>

									<div v-else-if="result.s_type === 'hashtag'" class="media align-items-center my-0">
										<div class="media-icon">
											<i class="far fa-hashtag fa-large"></i>
										</div>
										<div class="media-body sr-tag">
											<div class="sr-tag-name" :class="{ compact: result.name && result.name.length > 26 }">
												#{{ result.name }}
											</div>
											<div v-if="result.count && result.count > 100" class="sr-tag-count">
												{{ formatCount(result.count) }} {{ result.count == 1 ? 'Post' : 'Posts' }}
											</div>
										</div>
									</div>

									<div v-else-if="result.s_type === 'status'" class="media align-items-center my-0">
										<img :src="result.account.avatar"  width="40" height="40" class="sr-avatar" style="border-radius: 40px" onerror="this.src='/storage/avatars/default.png?v=0';this.onerror=null;">

										<div class="media-body sr-post">
											<div class="sr-post-acct" :class="{ compact: result.acct && result.acct.length > 26 }">
												&commat;{{ truncate(result.account.acct, 20) }}
												<b-button
													v-if="result.locked"
													v-b-tooltip.html
													title="Private Account"
													variant="link"
													size="sm"
													class="p-0"
													>
													<i class="far fa-lock fa-sm text-lighter ml-1"></i>
												</b-button>
											</div>
											<div class="sr-post-action">
												<div class="sr-post-action-timestamp">
													<i class="far fa-clock fa-sm"></i>
													{{ timeAgo(result.created_at)}}
												</div>
												<div>·</div>
												<div class="sr-post-action-label">
													Tap to view post
												</div>
											</div>
										</div>
									</div>
								</li>
							</template>
						</autocomplete>

					</div>
					<div class="ml-auto">
						<ul class="navbar-nav align-items-center">
							<!-- <li class="nav-item px-md-2 d-none d-md-block">
								<router-link class="nav-link font-weight-bold text-dark" to="/i/web" title="Home" data-toggle="tooltip" data-placement="bottom">
									<i class="far fa-home fa-lg"></i>
									<span class="sr-only">Home</span>
								</router-link>
							</li>
							<li class="nav-item px-md-2 d-none d-md-block">
								<router-link class="nav-link font-weight-bold text-dark" title="Compose" data-toggle="tooltip" data-placement="bottom" to="/i/web/compose">
									<i class="far fa-plus-square fa-lg"></i>
									<span class="sr-only">Compose</span>
								</router-link>
							</li> -->
							<!-- <li class="nav-item px-md-2">
								<router-link class="nav-link font-weight-bold text-dark" to="/i/web/direct" title="Direct" data-toggle="tooltip" data-placement="bottom">
									<i class="far fa-comment-dots fa-lg"></i>
									<span class="sr-only">Direct</span>
								</router-link>
							</li>
							<li class="nav-item px-md-2 d-none d-md-block">
								<router-link class="nav-link font-weight-bold text-dark fa-layers fa-fw" to="/i/web/notifications" title="Notifications" data-toggle="tooltip" data-placement="bottom">
									<i class="far fa-bell fa-lg"></i>
									<span class="fa-layers-counter" style="background:Tomato"></span>
									<span class="sr-only">Notifications</span>
								</router-link>
							</li> -->
							<li class="nav-item dropdown ml-2">
								<a id="navbarDropdown" class="nav-link dropdown-toggle" href="#" role="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false" title="User Menu">
									<i class="d-none far fa-user fa-lg text-dark"></i>
									<span class="sr-only">User Menu</span>
									<img :src="user.avatar" class="nav-avatar rounded-circle border shadow" width="30" height="30" onerror="this.onerror=null;this.src='/storage/avatars/default.png?v=0';">
								</a>

								<div class="dropdown-menu dropdown-menu-right shadow" aria-labelledby="navbarDropdown">
									<ul class="nav flex-column">
										<li class="nav-item nav-icons">
											<div class="d-flex justify-content-between align-items-center">
												<router-link class="nav-link text-center" to="/i/web">
													<div class="icon text-lighter"><i class="far fa-home fa-lg"></i></div>
													<div class="small">{{ $t('navmenu.homeFeed') }}</div>
												</router-link>

												<router-link v-if="hasLocalTimeline" class="nav-link text-center" :to="{ name: 'timeline', params: { scope: 'local' } }">
													<div class="icon text-lighter"><i class="fas fa-stream fa-lg"></i></div>
													<div class="small">{{ $t('navmenu.localFeed') }}</div>
												</router-link>

												<router-link v-if="hasNetworkTimeline" class="nav-link text-center" :to="{ name: 'timeline', params: { scope: 'global' } }">
													<div class="icon text-lighter"><i class="far fa-globe fa-lg"></i></div>
													<div class="small">{{ $t('navmenu.globalFeed') }}</div>
												</router-link>
											</div>
										</li>

										<li class="nav-item nav-icons">
											<div class="d-flex justify-content-between align-items-center">
												<router-link class="nav-link text-center" to="/i/web/discover">
													<div class="icon text-lighter"><i class="far fa-compass"></i></div>
													<div class="small">{{ $t('navmenu.discover') }}</div>
												</router-link>

												<router-link class="nav-link text-center" to="/i/web/notifications">
													<div class="icon text-lighter">
														<i class="far fa-bell"></i>
													</div>
													<div class="small">
														{{ $t('navmenu.notifications') }}
													</div>
												</router-link>

												<router-link class="nav-link text-center px-3" :to="'/i/web/profile/' + user.id">
													<div class="icon text-lighter">
														<i class="far fa-user"></i>
													</div>
													<div class="small">{{ $t('navmenu.profile') }}</div>
												</router-link>
											</div>
											<hr class="mb-0" style="margin-top: -5px;opacity: 0.4;" />
										</li>

										<li class="nav-item">
											<router-link class="nav-link" to="/i/web/compose">
												<span class="icon text-lighter"><i class="far fa-plus-square"></i></span>
												{{ $t('navmenu.compose') }}
											</router-link>
										</li>

										<!-- <li class="nav-item">
											<router-link class="nav-link" to="/i/web/discover">
												<span class="icon text-lighter"><i class="far fa-compass"></i></span>
												{{ $t('navmenu.discover') }}
											</router-link>
										</li> -->

										<li class="nav-item">
											<router-link class="nav-link d-flex justify-content-between align-items-center" to="/i/web/direct">
												<span>
													<span class="icon text-lighter">
														<i class="far fa-envelope"></i>
													</span>
													{{ $t('navmenu.directMessages') }}
												</span>

												<!-- <span class="badge badge-danger font-weight-light rounded-pill px-2" style="transform:scale(0.86)">99+</span> -->
											</router-link>
										</li>

										<li class="nav-item">
											<a class="nav-link" href="/i/web" @click.prevent="openUserInterfaceSettings">
												<span class="icon text-lighter"><i class="far fa-brush"></i></span>
												UI Settings
											</a>
										</li>

										<!-- <li class="nav-item">
											<router-link class="nav-link d-flex justify-content-between align-items-center" to="/i/web/notifications">
												<span>
													<span class="icon text-lighter">
														<i class="far fa-bell"></i>
													</span>
													{{ $t('navmenu.notifications') }}
												</span>
											</router-link>
										</li> -->

										<!-- <li class="nav-item">
											<hr class="mt-n1" style="opacity: 0.4;margin-bottom: 0;" />

											<router-link class="nav-link" :to="'/i/web/profile/' + user.id">
												<span class="icon text-lighter">
													<i class="far fa-user"></i>
												</span>
												{{ $t('navmenu.profile') }}
											</router-link>
										</li> -->
										<li v-if="user.is_admin" class="nav-item">
											<hr class="mt-n1" style="opacity: 0.4;margin-bottom: 0;" />
											<a class="nav-link" href="/i/admin/dashboard">
												<span class="icon text-lighter">
													<i class="far fa-tools"></i>
												</span>
												{{ $t('navmenu.admin') }}
											</a>
										</li>

										<li class="nav-item">
											<hr class="mt-n1" style="opacity: 0.4;margin-bottom: 0;" />
											<a class="nav-link" href="/">
												<span class="icon text-lighter">
													<i class="fas fa-chevron-left"></i>
												</span>
												{{ $t('navmenu.backToPreviousDesign') }}
											</a>
										</li>

										<li class="nav-item">
											<hr class="mt-n1" style="opacity: 0.4;margin-bottom: 0;" />
											<a class="nav-link" href="/" @click.prevent="logout()">
												<span class="icon text-lighter">
													<i class="far fa-sign-out"></i>
												</span>
												{{ $t('navmenu.logout') }}
											</a>
										</li>
									</ul>
								</div>
							</li>
						</ul>
					</div>
				</div>
		</div>

		<b-modal
			ref="uis"
			hide-footer
			centered
			body-class="p-0 ui-menu"
			title="UI Settings">
			<div class="list-group list-group-flush">
				<div class="list-group-item px-3">
					<div class="d-flex justify-content-between align-items-center">
						<div>
							<p class="font-weight-bold mb-1">Theme</p>
							<p class="small text-muted mb-0"></p>
						</div>

						<div class="btn-group btn-group-sm">
							<button
								class="btn"
								:class="[ uiColorScheme == 'system' ? 'btn-primary' : 'btn-outline-primary']"
								@click="toggleUi('system')">
								Auto
							</button>
							<button
								class="btn"
								:class="[ uiColorScheme == 'light' ? 'btn-primary' : 'btn-outline-primary']"
								@click="toggleUi('light')">
								Light mode
							</button>
							<button
								class="btn"
								:class="[ uiColorScheme == 'dark' ? 'btn-primary' : 'btn-outline-primary']"
								@click="toggleUi('dark')">
								Dark mode
							</button>
						</div>
					</div>
				</div>

				<div class="list-group-item px-3">
					<div class="d-flex justify-content-between align-items-center">
						<div>
							<p class="font-weight-bold mb-1">Profile Layout</p>
							<p class="small text-muted mb-0"></p>
						</div>

						<div class="btn-group btn-group-sm">
							<button
								class="btn"
								:class="[ profileLayout == 'grid' ? 'btn-primary' : 'btn-outline-primary']"
								@click="toggleProfileLayout('grid')">
								Grid
							</button>
							<button
								class="btn"
								:class="[ profileLayout == 'masonry' ? 'btn-primary' : 'btn-outline-primary']"
								@click="toggleProfileLayout('masonry')">
								Masonry
							</button>
							<button
								class="btn"
								:class="[ profileLayout == 'feed' ? 'btn-primary' : 'btn-outline-primary']"
								@click="toggleProfileLayout('feed')">
								Feed
							</button>
						</div>
					</div>
				</div>

				<div class="list-group-item px-3">
					<div class="d-flex justify-content-between align-items-center">
						<div>
							<p class="font-weight-bold mb-0">Compact Media Previews</p>
						</div>
						<b-form-checkbox v-model="fixedHeight" switch size="lg" />
					</div>
				</div>

				<div class="list-group-item px-3">
					<div class="d-flex justify-content-between align-items-center">
						<div>
							<p class="font-weight-bold mb-0">Load Comments</p>
						</div>
						<b-form-checkbox v-model="autoloadComments" switch size="lg" />
					</div>
				</div>

				<div class="list-group-item px-3">
					<div class="d-flex justify-content-between align-items-center">
						<div>
							<p class="font-weight-bold mb-0">Hide Counts & Stats</p>
						</div>
						<b-form-checkbox v-model="hideCounts" switch size="lg" />
					</div>
				</div>

			</div>
		</b-modal>
	</nav>
</template>

<script type="text/javascript">
	import Autocomplete from '@trevoreyre/autocomplete-vue'
	import '@trevoreyre/autocomplete-vue/dist/style.css'

	export default {
		components: {
			Autocomplete
		},

		data() {
			return {
				brandName: 'pixelfed',
				user: window._sharedData.user,
				profileLayoutModel: 'grid',
				hasLocalTimeline: true,
				hasNetworkTimeline: false
			}
		},

		computed: {
			profileLayout: {
				get() {
					return this.$store.state.profileLayout;
				},

				set(val) {
					this.$store.commit('setProfileLayout', val);
				}
			},

			hideCounts: {
				get() {
					return this.$store.state.hideCounts;
				},

				set(val) {
					this.$store.commit('setHideCounts', val);
				}
			},
			autoloadComments: {
				get() {
					return this.$store.state.autoloadComments;
				},

				set(val) {
					this.$store.commit('setAutoloadComments', val);
				}
			},
			newReactions: {
				get() {
					return this.$store.state.newReactions;
				},

				set(val) {
					this.$store.commit('setNewReactions', val);
				}
			},

			fixedHeight: {
				get() {
					return this.$store.state.fixedHeight;
				},

				set(val) {
					this.$store.commit('setFixedHeight', val);
				}
			},

			uiColorScheme: {
				get() {
					return this.$store.state.colorScheme;
				},

				set(val) {
					this.$store.commit('setColorScheme', val);
				}
			}
		},

		mounted() {
			if(window.App.config.features.hasOwnProperty('timelines')) {
				this.hasLocalTimeline = App.config.features.timelines.local;
				this.hasNetworkTimeline = App.config.features.timelines.network;
			}

			let u = new URLSearchParams(window.location.search);
			if(u.has('q') && u.get('q') && u.has('src') && u.get('src') === 'ac') {
				this.$refs.autocomplete.setValue(u.get('q'));
				setTimeout(() => {
					let ai = document.querySelector('.autocomplete-input')
					ai.focus();
				}, 1000)
			}

			this.brandName = window.App.config.site.name;
		},

		methods: {
			autocompleteSearch(q) {
				if (!q || q.length < 2) {
					return [];
				}

				let resolve = q.startsWith('https://') || q.startsWith('@');

				return axios.get('/api/v2/search', {
					params: {
						q: q,
						resolve: resolve,
						'_pe': 1
					}
				}).then(res => {
					let results = [];
					let accounts = res.data.accounts.map(res => {
						let account = res;
						account.s_type = 'account';
						return account;
					});
					let hashtags = res.data.hashtags.map(res => {
						let tag = res;
						tag.s_type = 'hashtag';
						return tag;
					})
					// let statuses = res.data.statuses.map(res => {
					// 	let status = res;
					// 	status.s_type = 'status';
					// 	return status;
					// });

					// results.push(...statuses.slice(0,5));
					results.push(...accounts.slice(0,5));
					results.push(...hashtags.slice(0,5));

					if(res.data.statuses) {
						if(Array.isArray(res.data.statuses)) {
							let statuses = res.data.statuses.map(res => {
								let status = res;
								status.s_type = 'status';
								return status;
							});
							results.push(...statuses);
						} else {
							if(q === res.data.statuses.url) {
								this.$refs.autocomplete.value = '';

								this.$router.push({
									name: 'post',
									path: `/i/web/post/${res.data.statuses.id}`,
									params: {
										id: res.data.statuses.id,
										cachedStatus: res.data.statuses,
										cachedProfile: this.user
									}
								});
							}
						}
					}
					return results;
				});
			},

			getSearchResultValue(result) {
				return result;
			},

			onSearchSubmit(result) {
				if (result.length < 1) {
					return;
				}
				this.$refs.autocomplete.value = '';
				switch(result.s_type) {
					case 'account':
						// this.$router.push({
						// 	name: 'profile',
						// 	path: `/i/web/profile/${result.id}`,
						// 	params: {
						// 		id: result.id,
						// 		cachedProfile: result,
						// 		cachedUser: this.user
						// 	}
						// });
						location.href = `/i/web/profile/${result.id}`;
					break;

					case 'hashtag':
						// this.$router.push({
						// 	name: 'hashtag',
						// 	path: `/i/web/hashtag/${result.name}`,
						// 	params: {
						// 		id: result.name,
						// 	}
						// });
						location.href = `/i/web/hashtag/${result.name}`;
					break;

					case 'status':
						// this.$router.push({
						// 	name: 'post',
						// 	path: `/i/web/post/${result.id}`,
						// 	params: {
						// 		id: result.id,
						// 	}
						// });
						location.href = `/i/web/post/${result.id}`;
					break;
				}
			},

			truncate(text, limit = 30) {
				if(text.length <= limit) {
					return text;
				}

				return text.slice(0, limit) + '...'
			},

			timeAgo(ts) {
				return window.App.util.format.timeAgo(ts);
			},

			formatCount(val) {
				if(!val) {
					return 0;
				}

				return new Intl.NumberFormat('en-CA', { notation: 'compact' , compactDisplay: "short" }).format(val);
			},

			logout() {
				axios.post('/logout')
				.then(res => {
					location.href = '/';
				}).catch(err => {
					location.href = '/';
				})
			},

			openUserInterfaceSettings() {
				event.currentTarget.blur();
				this.$refs.uis.show();
			},

			toggleUi(ui) {
				event.currentTarget.blur();
				this.uiColorScheme = ui;
			},

			toggleProfileLayout(layout) {
				event.currentTarget.blur();
				this.profileLayout = layout;
			}
		}
	}
</script>

<style lang="scss">
	.metro-nav {
		z-index: 4;

		.dropdown-menu {
			min-width: 18rem;
			padding: 0;
			border: none;

			.nav {
				overflow: auto;
			}

			.nav-item {
				.nav-link {
					font-family: -apple-system,BlinkMacSystemFont,"Segoe UI",Roboto,Helvetica,Arial,sans-serif;
					font-weight: 500;
					color: rgba(156,163,175, 1);
					padding-left: 14px;
					margin-bottom: 5px;

					.icon {
						display: inline-block;
						width: 40px;
						text-align: center;
					}

				}

				.router-link-exact-active {
					color: var(--primary);
					font-weight: 700;
					padding-left: 14px;

					&:not(.text-center) {
						padding-left: 10px;
						border-left: 4px solid var(--primary);
					}

					.icon {
						color: var(--primary) !important;
					}
				}

				&.nav-icons {
					.small {
						font-weight: 700 !important;
					}
				}

				&:is(:last-child) {
					.nav-link {
						margin-bottom: 0;
						border-bottom-left-radius: 15px;
						border-bottom-right-radius: 15px;
					}
				}
			}
		}

		.fa-layers {
			display: inline-block;
			height: 1em;
			position: relative;
			text-align: center;
			vertical-align: -0.125em;
			width: 1em;

			.fa-layers-counter {
				background-color: #ff253a;
				border-radius: 1em;
				-webkit-box-sizing: border-box;
				box-sizing: border-box;
				color: #fff;
				height: 1.5em;
				line-height: 1;
				max-width: 5em;
				min-width: 1.5em;
				overflow: hidden;
				padding: 0.25em;
				right: 0;
				text-overflow: ellipsis;
				top: 0;
				transform: scale(.5);
				-webkit-transform-origin: top right;
				transform-origin: top right;

				display: inline-block;
			    position: absolute;

			    margin-right: -5px;
			    margin-top: -10px;
			}

			.far {
				bottom: 0;
				left: 0;
				margin: auto;
				position: absolute;
				right: 0;
				top: 0;
			}
		}

		.searchbox {
			@media (min-width: 768px) {
				width: 300px;
			}
		}

		.nav-avatar {
			@media (min-width: 768px) {
				width: 50px;
				height: 50px;
			}
		}

		.autocomplete[data-loading="true"]::after {
			content: "";
			border-right: 3px solid var(--primary);
		}

		.autocomplete {
			&-input {
				padding: 0.375rem 0.75rem 0.375rem 2.6rem;
				background-color: var(--light-gray);
				font-size: 0.9rem;
				border-radius: 50rem;
				background-image: url("data:image/svg+xml;base64,PHN2ZyB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciIHdpZHRoPSIyNCIgaGVpZ2h0PSIyNCIgZmlsbD0ibm9uZSIgc3Ryb2tlPSIjQjhDMkNDIiBzdHJva2Utd2lkdGg9IjIiIHN0cm9rZS1saW5lY2FwPSJyb3VuZCIgc3Ryb2tlLWxpbmVqb2luPSJyb3VuZCI+PGNpcmNsZSBjeD0iMTEiIGN5PSIxMSIgcj0iNSIvPjxwYXRoIGQ9Ik0xOSAxOWwtNC00Ii8+PC9zdmc+");
				box-shadow: 0 0.125rem 0.25rem rgb(0 0 0 / 8%) !important;
			}

			&-result {
				background-image: none;
				padding: 10px 12px;
				cursor: pointer;

				&-list {
					box-shadow: 0 0.125rem 0.45rem var(--border-color);
					-ms-overflow-style: none;
					scrollbar-width: none;

					&::-webkit-scrollbar {
						width: 0 !important
					}
				}

				.media-icon {
					display: flex;
					justify-content: center;
					align-items: center;
					width: 40px;
					height: 40px;
					margin-right: 12px;
					background: var(--light-gray);
					border: 1px solid var(--input-border);
					border-radius: 40px;
					box-shadow: 0 0.125rem 0.25rem rgb(0 0 0 / 8%);
				}

			}
		}

		.sr {
			&:not(:last-child) {
				border-bottom: 1px solid var(--input-border);
			}

			&-avatar {
				margin-right: 12px;
				box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075)
			}

			&-account {
				display: flex;
				flex-direction: column;
				align-items: flex-start;
				justify-content: center;
				gap: 3px;

				&-acct {
					word-wrap: break-word;
					word-break: break-all;
					font-size: 14px;
					line-height: 18px;
					font-weight: bold;
					color: var(--dark);
					margin-right: 1rem;

					&.compact {
						font-size: 12px;
					}
				}

				&-stats {
					display: flex;
					align-items: center;
					gap: 5px;
					line-height: 14px;

					&-followers,
					&-statuses {
						font-size: 11px;
						font-weight: 500;
						color: var(--text-lighter);
					}
				}
			}

			&-tag {
				display: flex;
				flex-direction: column;
				align-items: flex-start;
				justify-content: center;
				gap: 3px;

				&-name {
					word-wrap: break-word;
					word-break: break-all;
					font-size: 14px;
					line-height: 18px;
					font-weight: bold;
					color: var(--dark);
					margin-right: 1rem;

					&.compact {
						font-size: 12px;
					}
				}

				&-count {
					font-size: 11px;
					line-height: 13px;
					color: var(--text-lighter);
					font-weight: bold;
				}
			}

			&-post {
				display: flex;
				flex-direction: column;
				align-items: flex-start;
				justify-content: center;
				gap: 3px;

				&-acct {
					font-size: 14px;
					line-height: 18px;
					font-weight: bold;
					color: var(--dark);
				}

				&-action {
					display: flex;
					font-size: 11px;
					line-height: 14px;
					color: var(--text-lighter);
					font-weight: 500;
					gap: 3px;
					align-items: center;

					&-timestamp {
						font-weight: 700;
					}

					&-label {
						font-weight: 700;
					}
				}
			}
		}
	}

	.force-dark-mode {
		.autocomplete-result-list {
			border-color: var(--input-border);
		}

		.autocomplete-result:hover, .autocomplete-result[aria-selected=true] {
			box-shadow: 0;
    		background-color: rgba(255, 255, 255, .1);
		}

		.autocomplete[data-loading="true"]::after {
			content: "";
			border: 3px solid rgba(255, 255, 255, 0.22);
			border-right: 3px solid var(--primary);
		}
	}
</style>
