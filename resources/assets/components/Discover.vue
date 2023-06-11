<template>
	<div class="web-wrapper">
		<div v-if="isLoaded" class="container-fluid mt-3">
			<div class="row">
				<div class="col-md-4 col-lg-3">
					<sidebar :user="profile" />
				</div>

				<div v-if="tab == 'index'" class="col-md-8 col-lg-9 mt-n4">
					<div v-if="profile.is_admin" class="d-md-flex my-md-3">
						<grid-card
							:dark="true"
							:title="'Hello ' + profile.username"
							subtitle="Welcome to the new Discover experience! Only admins can see this"
							button-text="Manage Discover Settings"
							button-link="/i/web/discover/settings"
							icon-class="fal fa-cog"
							:small="true" />
					</div>
					<!-- <section class="mb-1 mb-md-3 mb-lg-4">
						<news-slider />
					</section> -->

					<!-- <discover-spotlight /> -->

					<!-- <div class="d-md-flex my-md-3">
						<grid-card
							:dark="true"
							title="The Not So Trending"
							subtitle="Explore the posts that deserve more attention"
							button-text="Explore posts"
							icon-class="fal fa-analytics"
							button-link="/i/web/discover/future-trending"
							:button-event="true"
							v-on:btn-click="toggleTab('trending')"
							:small="true" />

						<grid-card
							title="Behind The Posts"
							subtitle="Discover the people"
							button-text="Discover People"
							button-link="/i/web/discover/people"
							icon-class="fal fa-user-friends"
							:small="true" />
					</div> -->

					<daily-trending v-on:btn-click="toggleTab('trending')"/>

					<!-- <div class="d-md-flex my-md-3">
						<grid-card
							title="Explore Loops"
							subtitle="Loops are short, looping videos"
							button-text="Explore Loops"
							icon-class="fal fa-camcorder"
							button-link="/i/web/discover/loops"
							:small="false" />

						<grid-card
							:dark="true"
							title="Popular Places"
							subtitle="Explore posts by popular locations"
							button-text="Explore Popular Places"
							icon-class="fal fa-map"
							:button-event="true"
							v-on:btn-click="toggleTab('popular-places')"
							button-link="/i/web/discover/popular-places"
							:small="false" />
					</div> -->

					<div class="d-md-flex my-md-3">
						<grid-card
							v-if="config.hashtags.enabled"
							:dark="true"
							title="My Hashtags"
							subtitle="Explore posts tagged with hashtags you follow"
							button-text="Explore Posts"
							button-link="/i/web/discover/my-hashtags"
							icon-class="fal fa-hashtag"
							:small="false" />

						<grid-card
							v-if="config.memories.enabled"
							title="My Memories"
							subtitle="A distant look back"
							button-text="View Memories"
							button-link="/i/web/discover/my-memories"
							icon-class="fal fa-history"
							:small="false" />
					</div>

					<div class="d-md-flex my-md-3">
						<grid-card
							v-if="config.insights.enabled"
							title="Account Insights"
							subtitle="Get a rich overview of your account activity and interactions"
							button-text="View Account Insights"
							icon-class="fal fa-user-circle"
							button-link="/i/web/discover/account-insights"
							:small="false" />

						<grid-card
							v-if="config.friends.enabled"
							:dark="true"
							title="Find Friends"
							subtitle="Find accounts to follow based on common interests"
							button-text="Find Friends & Followers"
							button-link="/i/web/discover/find-friends"
							icon-class="fal fa-user-plus"
							:small="false" />
					</div>

					<div class="d-md-flex my-md-3">
						<grid-card
							v-if="config.server.enabled && config.server.domains && config.server.domains.length"
							:dark="true"
							title="Server Timelines"
							subtitle="Browse timelines of a specific remote instance"
							button-text="Browse Server Feeds"
							icon-class="fal fa-list"
							button-link="/i/web/discover/server-timelines"
							:small="false" />

						<!-- <grid-card
							title="Curate the Spotlight"
							subtitle="Apply to curate the spotlight for one week"
							button-text="Apply to Curate Spotlight"
							button-link="/i/web/discover/spotlight/curate/apply"
							icon-class="fal fa-thumbs-up"
							:small="false" /> -->
					</div>
				</div>

				<div v-else-if="tab == 'trending'" class="col-md-8 col-lg-9 mt-n4">
					<discover :profile="profile" />
				</div>

				<div v-else-if="tab == 'popular-places'" class="col-md-8 col-lg-9 mt-n4">
					<section class="mt-3 mb-5 section-explore">
						<div class="profile-timeline">
							<div class="row p-0 mt-5">
								<div class="col-12 mb-4 d-flex justify-content-between align-items-center">
									<p class="d-block d-md-none h1 font-weight-bold mb-0 font-default">Popular Places</p>
									<p class="d-none d-md-block display-4 font-weight-bold mb-0 font-default">Popular Places</p>
								</div>
							</div>
						</div>

						<div class="row mt-5">
							<div class="col-12 col-md-12 mb-3">
								<div class="card-img big">
									<img src="/img/places/nyc.jpg">
									<div class="title font-default">New York City</div>
								</div>
							</div>

							<div class="col-12 col-md-6 mb-3">
								<div class="card-img">
									<img src="/img/places/edmonton.jpg">
									<div class="title font-default">Edmonton</div>
								</div>
							</div>

							<div class="col-12 col-md-6 mb-3">
								<div class="card-img">
									<img src="/img/places/paris.jpg">
									<div class="title font-default">Paris</div>
								</div>
							</div>

							<div class="col-12 col-md-4 mb-3">
								<div class="card-img">
									<img src="/img/places/london.jpg">
									<div class="title font-default">London</div>
								</div>
							</div>

							<div class="col-12 col-md-4 mb-3">
								<div class="card-img">
									<img src="/img/places/vancouver.jpg">
									<div class="title font-default">Vancouver</div>
								</div>
							</div>

							<div class="col-12 col-md-4 mb-3">
								<div class="card-img">
									<img src="/img/places/toronto.jpg">
									<div class="title font-default">Toronto</div>
								</div>
							</div>
						</div>
					</section>
				</div>

			</div>

			<drawer />
		</div>
	</div>
</template>

<script type="text/javascript">
	import Drawer from './partials/drawer.vue';
	import Sidebar from './partials/sidebar.vue';
	import Rightbar from './partials/rightbar.vue';
	import Discover from './sections/DiscoverFeed.vue';
	import DiscoverNewsSlider from './partials/discover/news-slider.vue';
	import DiscoverSpotlight from './partials/discover/discover-spotlight.vue';
	import DailyTrending from './partials/discover/daily-trending.vue';
	import DiscoverGridCard from './partials/discover/grid-card.vue';

	export default {
		 components: {
		 	"drawer": Drawer,
            "sidebar": Sidebar,
            "rightbar": Rightbar,
            "discover": Discover,
            "news-slider": DiscoverNewsSlider,
            "discover-spotlight": DiscoverSpotlight,
            "daily-trending": DailyTrending,
            "grid-card": DiscoverGridCard
        },

        data() {
        	return {
        		isLoaded: false,
        		profile: undefined,
        		config: {},
        		tab: 'index',
        		popularAccounts: [],
        		followingIndex: undefined
        	}
        },

        updated() {
			// let u = new URLSearchParams(window.location.search);
			// if(u.has('ft') && u.get('ft') == '1') {
			// 	this.tab = 'index';
			// }
        },

        mounted() {
			this.profile = window._sharedData.user;
			this.fetchConfig();
        },

        methods: {
			fetchConfig() {
				axios.get('/api/pixelfed/v2/discover/meta')
				.then(res => {
					this.config = res.data;
					this.isLoaded = true;
					window._sharedData.discoverMeta = res.data;
					// this.fetchPopularAccounts();
				})
			},

        	fetchPopularAccounts() {
        		// axios.get('/api/pixelfed/discover/accounts/popular')
        		// .then(res => {
        		// 	this.popularAccounts = res.data;
        		// })
        	},

        	followProfile(index) {
        		event.currentTarget.blur();
        		this.followingIndex = index;
        		let id = this.popularAccounts[index].id;

        		axios.post('/api/v1/accounts/' + id + '/follow')
				.then(res => {
        			this.followingIndex = undefined;
        			this.popularAccounts.splice(index, 1);
				}).catch(err => {
        			this.followingIndex = undefined;
					swal('Oops!', 'An error occured when attempting to follow this account.', 'error');
				});
        	},

        	goToProfile(account) {
				this.$router.push({
					path: `/i/web/profile/${account.id}`,
					params: {
						id: account.id,
						cachedProfile: account,
						cachedUser: this.profile
					}
				})
			},

			toggleTab(index) {
				this.tab = index;
				setTimeout(() => {
					window.scrollTo({top: 0, behavior: 'smooth'});
				}, 300);
			},

			openManageModal() {
				event.currentTarget.blur();
				swal('Settings', 'Discover settings here', 'info');
			}
        }
	}
</script>

<style lang="scss" scoped>
	.card-img {
		position: relative;

		img {
			object-fit: cover;
			width: 100%;
			height: 200px;
			border-radius: 10px;

		}

		&:before,
		&:after {
			content: "";
			background: rgba(0,0,0,0.2);
			z-index: 2;
			width: 100%;
			height: 100%;
			position: absolute;
			left: 0;
			top: 0;
			border-radius: 10px;
		}

		.title {
			position: absolute;
			bottom: 5px;
			left: 10px;
			font-size: 40px;
			color: #fff;
			z-index: 3;
			font-weight: 700;
		}

		&.big {
			img {
				height: 300px;
			}
		}
	}
	.font-default {
		font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
		letter-spacing: -0.7px;
	}

	.bg-stellar {
		background: #7474BF;
		background: -webkit-linear-gradient(to right, #348AC7, #7474BF);
		background: linear-gradient(to right, #348AC7, #7474BF);
	}

	.bg-berry {
		background: #5433FF;
		background: -webkit-linear-gradient(to right, #acb6e5, #86fde8);
		background: linear-gradient(to right, #acb6e5, #86fde8);
	}

	.bg-midnight {
		background: #232526;
		background: -webkit-linear-gradient(to right, #414345, #232526);
		background: linear-gradient(to right, #414345, #232526);
	}

	.media-body {
		margin-right: 0.5rem;
	}

	.avatar {
		border-radius: 15px;
	}

	.username {
		font-size: 14px;
		line-height: 14px;
		margin-bottom: 2px;
		word-break: break-word !important;
		word-wrap: break-word !important;
	}

	.display-name {
		margin-bottom: 0;
		font-size: 12px;
		word-break: break-word !important;
		word-wrap: break-word !important;
	}

	.follower-count {
		margin-bottom: 0;
		font-size: 10px;
		word-break: break-word !important;
		word-wrap: break-word !important;
	}

	.follow {
		background-color: var(--primary);
		border-radius: 18px;
		font-weight: 600;
		padding: 5px 15px;
	}
</style>
