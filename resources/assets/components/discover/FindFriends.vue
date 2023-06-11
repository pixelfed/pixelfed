<template>
	<div class="discover-find-friends-component">
		<div v-if="isLoaded" class="container-fluid mt-3">

			<div class="row">
				<div class="col-md-4 col-lg-3">
					<sidebar :user="profile" />
				</div>

				<div class="col-md-6 col-lg-6">
					<b-breadcrumb class="font-default" :items="breadcrumbItems"></b-breadcrumb>

					<h1 class="font-default">Find Friends</h1>
					<!-- <p class="font-default lead">Posts from hashtags you follow</p> -->
					<hr>

					<b-spinner v-if="isLoading" />

					<div v-if="!isLoading" class="row justify-content-center">
						<div class="col-12 col-lg-10 mb-3" v-for="(profile, index) in popularAccounts">
							<div class="card shadow-sm border-0 rounded-px">
								<div class="card-body p-2">
									<profile-card
										:key="'pfc' + index"
										:profile="profile"
										class="w-100"
										v-on:follow="follow(index)"
										v-on:unfollow="unfollow(index)"
									/>
								</div>
							</div>
						</div>
					</div>
				</div>
			</div>
		</div>
	</div>
</template>

<script type="text/javascript">
import Drawer from './../partials/drawer.vue';
import Sidebar from './../partials/sidebar.vue';
import StatusCard from './../partials/TimelineStatus.vue';
import ProfileCard from './../partials/profile/ProfileHoverCard.vue';

export default {
	components: {
		"drawer": Drawer,
		"sidebar": Sidebar,
		"status-card": StatusCard,
		"profile-card": ProfileCard
	},

	data() {
		return {
			isLoaded: true,
			isLoading: true,
			profile: window._sharedData.user,
			feed: [],
			popular: [],
			popularAccounts: [],
			popularLoaded: false,
			breadcrumbItems: [
				{
					text: 'Discover',
					href: '/i/web/discover'
				},
				{
					text: 'Find Friends',
					active: true
				}
			]
		}
	},

	mounted() {
		this.fetchConfig();
	},

	methods: {
		fetchConfig() {
			axios.get('/api/pixelfed/v2/discover/meta')
			.then(res => {
				if(res.data.friends.enabled == false) {
					this.$router.push('/i/web/discover');
				} else {
					this.fetchPopularAccounts();
				}
			})
			.catch(e => {
				this.isLoading = false;
			})
		},

		fetchPopular() {
			axios.get('/api/pixelfed/v2/discover/account-insights')
			.then(res => {
				this.popular = res.data;
				this.popularLoaded = true;
				this.isLoading = false;
			})
			.catch(e => {
				this.isLoading = false;
			})
		},

		formatCount(val) {
			return App.util.format.count(val);
		},

		timeago(ts) {
			return App.util.format.timeAgo(ts);
		},

		fetchPopularAccounts() {
			axios.get('/api/pixelfed/discover/accounts/popular')
			.then(res => {
				this.popularAccounts = res.data;
				this.isLoading = false;
			})
			.catch(e => {
				this.isLoading = false;
			})
		},

		follow(index) {
			axios.post('/api/v1/accounts/' + this.popularAccounts[index].id + '/follow')
			.then(res => {
				this.newlyFollowed++;
				this.$store.commit('updateRelationship', [res.data]);
				this.$emit('update-profile', {
					'following_count': this.profile.following_count + 1
				})
			});
		},

		unfollow(index) {
			axios.post('/api/v1/accounts/' + this.popularAccounts[index].id + '/unfollow')
			.then(res => {
				this.newlyFollowed--;
				this.$store.commit('updateRelationship', [res.data]);
				this.$emit('update-profile', {
					'following_count': this.profile.following_count - 1
				})
			});
		}
	}
}
</script>

<style lang="scss">
	.discover-find-friends-component {
		.bg-stellar {
			background: #7474BF;
			background: -webkit-linear-gradient(to right, #348AC7, #7474BF);
			background: linear-gradient(to right, #348AC7, #7474BF);
		}

		.bg-midnight {
			background: #232526;
			background: -webkit-linear-gradient(to right, #414345, #232526);
			background: linear-gradient(to right, #414345, #232526);
		}

		.font-default {
			font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
			letter-spacing: -0.7px;
		}

		.active {
			font-weight: 700;
		}

		.profile-hover-card-inner {
			width: 100%;

			.d-flex {
				max-width: 100% !important;
			}
		}
	}
</style>
