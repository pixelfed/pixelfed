<template>
	<div class="sidebar-component sticky-top d-none d-md-block">
		<!-- <input type="file" class="d-none" ref="avatarUpdateRef" @change="handleAvatarUpdate()"> -->
		<!-- <div class="card shadow-sm mb-3 cursor-pointer" style="border-radius: 15px;" @click="gotoMyProfile()"> -->
		<div class="card shadow-sm mb-3" style="border-radius: 15px;">
			<div class="card-body p-2">
				<div class="media user-card user-select-none">
					<div style="position: relative;">
						<img :src="user.avatar" class="avatar shadow cursor-pointer" draggable="false" onerror="this.onerror=null;this.src='/storage/avatars/default.png?v=0';" @click="gotoMyProfile()">
						<button class="btn btn-light btn-sm avatar-update-btn" @click="updateAvatar()">
							<span class="avatar-update-btn-icon"></span>
						</button>
					</div>
					<div class="media-body">
						<p class="display-name" v-html="getDisplayName()"></p>
						<p class="username primary">&commat;{{ user.username }}</p>
						<p class="stats">
							<span class="stats-following">
								<span class="following-count">{{ formatCount(user.following_count) }}</span> Following
							</span>
							<span class="stats-followers">
								<span class="followers-count">{{ formatCount(user.followers_count) }}</span> Followers
							</span>
						</p>
					</div>
				</div>
			</div>
		</div>

		<div class="btn-group btn-group-lg btn-block mb-4">
			<!-- <button type="button" class="btn btn-outline-primary btn-block font-weight-bold" style="border-top-left-radius: 18px;border-bottom-left-radius:18px;font-size:18px;font-weight:300!important" @click="createNewPost()">
				<i class="fal fa-arrow-circle-up mr-1"></i> {{ $t('navmenu.compose') }} Post
			</button> -->
			<router-link to="/i/web/compose" class="btn btn-primary btn-block font-weight-bold">
				<i class="fal fa-arrow-circle-up mr-1"></i> {{ $t('navmenu.compose') }} Post
			</router-link>
			<button type="button" class="btn btn-outline-primary dropdown-toggle dropdown-toggle-split" data-toggle="dropdown" aria-expanded="false">
				<span class="sr-only">Toggle Dropdown</span>
			</button>
			<div class="dropdown-menu dropdown-menu-right">
				<a class="dropdown-item font-weight-bold" href="/i/collections/create">Create Collection</a>
				<a v-if="hasStories" class="dropdown-item font-weight-bold" href="/i/stories/new">Create Story</a>
				<div class="dropdown-divider"></div>
				<a class="dropdown-item font-weight-bold" href="/settings/home">Account Settings</a>
			</div>
		</div>

		<!-- <router-link to="/i/web/compose" class="btn btn-primary btn-lg btn-block mb-4 shadow-sm font-weight-bold">
			<i class="far fa-plus-square mr-1"></i> {{ $t('navmenu.compose') }}
		</router-link> -->

		<div class="sidebar-sticky shadow-sm">
			<ul class="nav flex-column">
				<li class="nav-item">
					<div class="d-flex justify-content-between align-items-center">
						<!-- <router-link class="nav-link text-center" to="/i/web">
							<div class="icon text-lighter"><i class="far fa-home fa-lg"></i></div>
							<div class="small">{{ $t('navmenu.homeFeed') }}</div>
						</router-link> -->
                        <a
                            class="nav-link text-center"
                            href="/i/web"
                            :class="[ $route.path == '/i/web' ? 'router-link-exact-active active' : '' ]"
                            @click.prevent="goToFeed('home')">
                            <div class="icon text-lighter"><i class="far fa-home fa-lg"></i></div>
                            <div class="small">{{ $t('navmenu.homeFeed') }}</div>
                        </a>

						<!-- <router-link v-if="hasLocalTimeline" class="nav-link text-center" :to="{ name: 'timeline', params: { scope: 'local' } }">
							<div class="icon text-lighter"><i class="fas fa-stream fa-lg"></i></div>
							<div class="small">{{ $t('navmenu.localFeed') }}</div>
						</router-link> -->
                        <a
                            v-if="hasLocalTimeline"
                            class="nav-link text-center"
                            href="/i/web/timeline/local"
                            :class="[ $route.path == '/i/web/timeline/local' ? 'router-link-exact-active active' : '' ]"
                            @click.prevent="goToFeed('local')">
                            <div class="icon text-lighter"><i class="fas fa-stream fa-lg"></i></div>
                            <div class="small">{{ $t('navmenu.localFeed') }}</div>
                        </a>

						<!-- <router-link v-if="hasNetworkTimeline" class="nav-link text-center" :to="{ name: 'timeline', params: { scope: 'global' } }">
							<div class="icon text-lighter"><i class="far fa-globe fa-lg"></i></div>
							<div class="small">{{ $t('navmenu.globalFeed') }}</div>
						</router-link> -->
                        <a
                            v-if="hasNetworkTimeline"
                            class="nav-link text-center"
                            href="/i/web/timeline/global"
                            :class="[ $route.path == '/i/web/timeline/global' ? 'router-link-exact-active active' : '' ]"
                            @click.prevent="goToFeed('global')">
                            <div class="icon text-lighter"><i class="far fa-globe fa-lg"></i></div>
                            <div class="small">{{ $t('navmenu.globalFeed') }}</div>
                        </a>
					</div>
					<hr class="mb-0" style="margin-top: -5px;opacity: 0.4;" />
				</li>

				<!-- <li class="nav-item">
				</li>

				<li class="nav-item">

				</li> -->


				<!-- <li v-for="(link, index) in links" class="nav-item">
					<router-link class="nav-link" :to="link.path">
						<span v-if="link.icon" class="icon text-lighter"><i :class="[ link.icon ]"></i></span>
						{{ link.name }}
					</router-link>
				</li> -->

				<li class="nav-item">
					<router-link class="nav-link" to="/i/web/discover">
						<span class="icon text-lighter"><i class="far fa-compass"></i></span>
						{{ $t('navmenu.discover') }}
					</router-link>
				</li>

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

				<!-- <li class="nav-item">
					<router-link class="nav-link" to="/i/web/groups">
						<span class="icon text-lighter"><i class="far fa-layer-group"></i></span>
						{{ $t('navmenu.groups') }}
					</router-link>
				</li> -->

				<li v-if="hasLiveStreams" class="nav-item">
					<router-link class="nav-link d-flex justify-content-between align-items-center" to="/i/web/livestreams">
						<span>
							<span class="icon text-lighter">
								<i class="far fa-record-vinyl"></i>
							</span>
							Livestreams
						</span>
					</router-link>
				</li>

				<li class="nav-item">
					<router-link class="nav-link d-flex justify-content-between align-items-center" to="/i/web/notifications">
						<span>
							<span class="icon text-lighter">
								<i class="far fa-bell"></i>
							</span>
							{{ $t('navmenu.notifications') }}
						</span>

						<!-- <span class="badge badge-danger font-weight-light rounded-pill px-2" style="transform:scale(0.86)">99+</span> -->
					</router-link>
				</li>

				<li class="nav-item">
					<hr class="mt-n1" style="opacity: 0.4;margin-bottom: 0;" />

					<router-link class="nav-link" :to="'/i/web/profile/' + user.id">
						<span class="icon text-lighter">
							<i class="far fa-user"></i>
						</span>
						{{ $t('navmenu.profile') }}
					</router-link>

					<!-- <router-link class="nav-link" to="/i/web/settings">
						<span class="icon text-lighter">
							<i class="far fa-cog"></i>
						</span>
						{{ $t('navmenu.settings') }}
					</router-link> -->
				</li>
				<!-- <li class="nav-item">
					<router-link class="nav-link" to="/i/web/drive">
						<span class="icon text-lighter">
							<i class="far fa-cloud-upload"></i>
						</span>
						{{ $t('navmenu.drive') }}
					</router-link>
				</li> -->
				<!-- <li class="nav-item">
					<router-link class="nav-link" to="/i/web/settings">
						<span class="icon text-lighter">
							<i class="fas fa-cog"></i>
						</span>
						{{ $t('navmenu.settings') }}
					</router-link>
				</li>
				<li class="nav-item">
					<a class="nav-link" href="/i/web/help">
						<span class="icon text-lighter">
							<i class="fas fa-info-circle"></i>
						</span>
						Help
					</a>
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
					<a class="nav-link" href="/?force_old_ui=1">
						<span class="icon text-lighter">
							<i class="fas fa-chevron-left"></i>
						</span>
						{{ $t('navmenu.backToPreviousDesign') }}
					</a>
				</li>
				<!-- <li class="nav-item">
					<router-link class="nav-link" to="/i/web/?a=feed">
						<span class="fas fa-stream pr-2 text-lighter"></span>
						Feed
					</router-link>
				</li>
				<li class="nav-item">
					<router-link class="nav-link" to="/i/web/discover">
						<span class="fas fa-compass pr-2 text-lighter"></span>
						Discover
					</router-link>
				</li>
				<li class="nav-item">
					<router-link class="nav-link" to="/i/web/stories">
						<span class="fas fa-history pr-2 text-lighter"></span>
						Stories
					</router-link>
				</li> -->
			</ul>
		</div>

		<!-- <div class="sidebar-sitelinks">
			<a href="/site/about">{{ $t('navmenu.about') }}</a>
			<a href="/site/language">{{ $t('navmenu.language') }}</a>
			<a href="/site/terms">{{ $t('navmenu.privacy') }}</a>
			<a href="/site/terms">{{ $t('navmenu.terms') }}</a>
		</div> -->

		<div class="sidebar-attribution pr-3 d-flex justify-content-between align-items-center">
			<router-link to="/i/web/language">
				<i class="fal fa-language fa-2x" alt="Select a language"></i>
			</router-link>
			<a href="/site/help" class="font-weight-bold">{{ $t('navmenu.help') }}</a>
			<a href="/site/privacy" class="font-weight-bold">{{ $t('navmenu.privacy') }}</a>
			<a href="/site/terms" class="font-weight-bold">{{ $t('navmenu.terms') }}</a>
			<a href="https://pixelfed.org" class="font-weight-bold powered-by">Powered by Pixelfed</a>
		</div>

		<!-- <b-modal
			ref="avatarUpdateModal"
			centered
			hide-footer
			header-class="py-2"
			body-class="p-0"
			title-class="w-100 text-center pl-4 font-weight-bold"
			title-tag="p"
			title="Upload Avatar"
		>
		<div class="d-flex align-items-center justify-content-center">
			<div
				v-if="avatarUpdateIndex === 0"
				class="py-5 user-select-none cursor-pointer"
				@click="avatarUpdateStep(0)">
				<p class="text-center primary">
					<i class="fal fa-cloud-upload fa-3x"></i>
				</p>
				<p class="text-center lead">Drag photo here or click here</p>
				<p class="text-center small text-muted mb-0">Must be a <strong>png</strong> or <strong>jpg</strong> image up to 2MB</p>
			</div>

			<div v-else-if="avatarUpdateIndex === 1" class="w-100 p-5">

				<div class="d-md-flex justify-content-between align-items-center">
					<div class="text-center mb-4">
						<p class="small font-weight-bold" style="opacity:0.7;">Current</p>
						<img :src="user.avatar" class="shadow" style="width: 150px;height: 150px;object-fit: cover;border-radius: 18px;opacity: 0.7;">
					</div>

					<div class="text-center mb-4">
						<p class="font-weight-bold">New</p>
						<img :src="avatarUpdateFile" class="shadow" style="width: 220px;height: 220px;object-fit: cover;border-radius: 18px;">
					</div>
				</div>

				<hr>

				<div class="d-flex justify-content-between">
					<button class="btn btn-light font-weight-bold btn-block mr-3" @click="avatarUpdateClose()">Cancel</button>
					<button class="btn btn-primary primary font-weight-bold btn-block mt-0">Upload</button>
				</div>
			</div>
		</div>
		</b-modal> -->

		<!-- <b-modal
			ref="createPostModal"
			centered
			hide-footer
			header-class="py-2"
			body-class="p-0 w-100 h-100"
			title-class="w-100 text-center pl-4 font-weight-bold"
			title-tag="p"
			title="Create New Post"
			>
			<compose-simple />
		</b-modal> -->

		<update-avatar ref="avatarUpdate" :user="user" />
	</div>
</template>

<script type="text/javascript">
	import { mapGetters } from 'vuex'
	// import ComposeSimple from './../sections/ComposeSimple.vue';
	import UpdateAvatar from './modal/UpdateAvatar.vue';

	export default {
		props: {
			user: {
				type: Object,
				default: (function() {
					return {
						avatar: '/storage/avatars/default.jpg',
						username: false,
						display_name: '',
						following_count: 0,
						followers_count: 0
					};
				})
			},

			links: {
				type: Array,
				default: function() {
					return [
						// {
						// 	name: "Home",
						// 	path: "/i/web",
						// 	icon: "fas fa-home"
						// },
						// {
						// 	name: "Local",
						// 	path: "/i/web/timeline/local",
						// 	icon: "fas fa-stream"
						// },
						// {
						// 	name: "Global",
						// 	path: "/i/web/timeline/global",
						// 	icon: "far fa-globe"
						// },
						// {
						// 	name: "Audiences",
						// 	path: "/i/web/discover",
						// 	icon: "far fa-circle-notch"
						// },
						{
							name: "Discover",
							path: "/i/web/discover",
							icon: "fas fa-compass"
						},
						// {
						// 	name: "Events",
						// 	path: "/i/events",
						// 	icon: "far fa-calendar-alt"
						// },
						{
							name: "Groups",
							path: "/i/web/groups",
							icon: "far fa-user-friends"
						},
						// {
						// 	name: "Live",
						// 	path: "/i/web/?t=live",
						// 	icon: "far fa-play"
						// },
						// {
						// 	name: "Marketplace",
						// 	path: "/i/web/marketplace",
						// 	icon: "far fa-shopping-cart"
						// },
						// {
						// 	name: "Stories",
						// 	path: "/i/web/?t=stories",
						// 	icon: "fas fa-history"
						// },
						{
							name: "Videos",
							path: "/i/web/videos",
							icon: "far fa-video"
						}
					];
				}
			}
		},

		components: {
			// ComposeSimple,
			UpdateAvatar
		},

		computed: {
			...mapGetters([
				'getCustomEmoji'
			])
		},

		data() {
			return {
				loaded: false,
				hasLocalTimeline: true,
				hasNetworkTimeline: false,
				hasLiveStreams: false,
                hasStories: false,
			}
		},

		mounted() {
			if(window.App.config.features.hasOwnProperty('timelines')) {
				this.hasLocalTimeline = App.config.features.timelines.local;
				this.hasNetworkTimeline = App.config.features.timelines.network;
				//this.hasLiveStreams = App.config.ab.hls == true;
			}
            if(window.App.config.features.hasOwnProperty('stories')) {
                this.hasStories = App.config.features.stories;
            }
			// if(!this.user.username) {
			// 	this.user = window._sharedData.user;
			// }
			// setTimeout(() => {
			// 	this.user = window._sharedData.curUser;
			// 	this.loaded = true;
			// }, 300);
		},

		methods: {
			getDisplayName() {
				let self = this;
				let profile = this.user;
				let dn = profile.display_name;
				if(!dn) {
					return profile.username;
				}
				if(dn.includes(':')) {
					let re = /(<a?)?:\w+:(\d{18}>)?/g;
					let un = dn.replaceAll(re, function(em) {
						let shortcode = em.slice(1, em.length - 1);
						let emoji = self.getCustomEmoji.filter(e => {
							return e.shortcode == shortcode;
						});
						return emoji.length ? `<img draggable="false" class="emojione custom-emoji" alt="${emoji[0].shortcode}" title="${emoji[0].shortcode}" src="${emoji[0].url}" data-original="${emoji[0].url}" data-static="${emoji[0].static_url}" width="16" height="16" onerror="this.onerror=null;this.src='/storage/emoji/missing.png';" />`: em;
					});
					return un;
				} else {
					return dn;
				}
			},

			gotoMyProfile() {
				let user = this.user;
				this.$router.push({
					name: 'profile',
					path: `/i/web/profile/${user.id}`,
					params: {
						id: user.id,
						cachedProfile: user,
						cachedUser: user
					}
				})
			},

			formatCount(count = 0, locale = 'en-GB', notation = 'compact') {
				return new Intl.NumberFormat(locale, { notation: notation , compactDisplay: "short" }).format(count);
			},

			updateAvatar() {
				event.currentTarget.blur();
				// swal('update avatar', 'test', 'success');
				this.$refs.avatarUpdate.open();
			},

			createNewPost() {
				this.$refs.createPostModal.show();
			},

            goToFeed(feed) {
                const curPath = this.$route.path;
                switch(feed) {
                    case 'home':
                        if(curPath == '/i/web') {
                            this.$emit('refresh');
                        } else {
                            this.$router.push('/i/web');
                        }
                    break;

                    case 'local':
                        if(curPath == '/i/web/timeline/local') {
                            this.$emit('refresh');
                        } else {
                            this.$router.push({ name: 'timeline', params: { scope: 'local' }});
                        }
                    break;

                    case 'global':
                        if(curPath == '/i/web/timeline/global') {
                            this.$emit('refresh');
                        } else {
                            this.$router.push({ name: 'timeline', params: { scope: 'global' }});
                        }
                    break;
                }
            }
		}
	}
</script>

<style lang="scss">
	.sidebar-component {
		.sidebar-sticky {
			background-color: var(--card-bg);
			border-radius: 15px;
		}

		&.sticky-top {
			top: 90px;
		}

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

				&:hover {
					background-color: var(--light-hover-bg);
				}

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

			&:first-child {
				.nav-link {
					.small {
						font-weight: 700;
					}

					&:first-child {
						border-top-left-radius: 15px;
					}

					&:last-child {
						border-top-right-radius: 15px;
					}
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

		.sidebar-heading {
			font-size: .75rem;
			text-transform: uppercase;
		}

		.user-card {
			align-items: center;

			.avatar {
				width: 75px;
				height: 75px;
				border-radius: 15px;
				margin-right: 0.8rem;
				border: 1px solid var(--border-color);
			}

			.avatar-update-btn {
				position: absolute;
				right: 12px;
				bottom: 0;
				width: 20px;
				height: 20px;
				background: rgba(255,255,255,0.9);
				border: 1px solid #dee2e6 !important;
				padding: 0;
				border-radius: 50rem;

				&-icon {
					font-family: 'Font Awesome 5 Free';
					font-weight: 400;
					-webkit-font-smoothing: antialiased;
					display: inline-block;
					font-style: normal;
					font-variant: normal;
					text-rendering: auto;
					line-height: 1;

					&:before {
						content: "\F013";
					}
				}
			}

			.username {
				font-weight: 600;
				font-size: 13px;
				margin-bottom: 0;
			}

			.display-name {
				color: var(--body-color);
				line-height: 0.8;
				font-size: 14px;
				font-weight: 800 !important;
				user-select: all;
				font-family: -apple-system,BlinkMacSystemFont,"Segoe UI",Roboto,Helvetica,Arial,sans-serif;
				margin-bottom: 0;
                word-break: break-all;
			}

			.stats {
				margin-top: 0;
				margin-bottom: 0;
				font-size: 12px;
				user-select: none;

				.stats-following {
					margin-right: 0.8rem;
				}

				.following-count,
				.followers-count {
					font-weight: 800;
				}
			}
		}

		.btn-primary {
			background-color: var(--primary);

			&.router-link-exact-active {
				opacity: 0.5;
				pointer-events: none;
				cursor: unset;
			}
		}

		.sidebar-sitelinks {
			margin-top: 1rem;
			display: flex;
			justify-content: space-between;
			padding: 0 2rem;

			a {
				font-size: 12px;
				color: #B8C2CC;
			}

			.active {
				color: #212529;
				font-weight: 600;
			}
		}

		.sidebar-attribution {
			margin-top: 0.5rem;
			font-size: 10px;
			color: #B8C2CC;
			padding-left: 2rem;

			a {
				color: #B8C2CC !important;

				&.powered-by {
					opacity: 0.5;
				}
			}
		}
	}
</style>
