<template>
	<div class="discover-serverfeeds-component">
		<div class="container-fluid mt-3">

			<div class="row">
				<div class="col-md-4 col-lg-3">
					<sidebar :user="profile" />
				</div>

				<div class="col-md-6 col-lg-6">
					<b-breadcrumb class="font-default" :items="breadcrumbItems"></b-breadcrumb>

					<h1 class="font-default">Server Timelines</h1>
					<p class="font-default lead">Browse timelines of a specific instance</p>

					<hr>

					<b-spinner v-if="isLoading && !initialTab" />

					<status-card
						v-if="!isLoading"
						v-for="(post, idx) in feed"
						:key="'ti1:'+idx+':'+post.id"
						:profile="profile"
						:status="post"
						/>

					<p v-if="!initialTab && !isLoading && feed.length == 0" class="lead">No posts found :(</p>

					<div v-if="initialTab === true">
						<p v-if="config.server.mode == 'allowlist'" class="lead">Select an instance from the menu</p>
					</div>
				</div>

				<div class="col-md-2 col-lg-3">
					<div v-if="config.server.mode === 'allowlist'" class="nav flex-column nav-pills font-default">
						<a
							v-for="(tag, idx) in domains"
							class="nav-link"
							:class="{ active: tagIndex == idx }"
							href="#"
							@click.prevent="toggleTag(idx)">
							{{ tag }}
						</a>
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

export default {
	components: {
		"drawer": Drawer,
		"sidebar": Sidebar,
		"status-card": StatusCard
	},

	data() {
		return {
			isLoaded: false,
			isLoading: true,
			initialTab: true,
			config: {},
			profile: window._sharedData.user,
			tagIndex: undefined,
			domains: [],
			feed: [],
			breadcrumbItems: [
				{
					text: 'Discover',
					href: '/i/web/discover'
				},
				{
					text: 'Server Timelines',
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
				this.config = res.data;
				if(this.config.server.enabled == false) {
					this.$router.push('/i/web/discover');
				}
				if(this.config.server.mode === 'allowlist') {
					this.domains = this.config.server.domains.split(',');
				}
			})
		},

		fetchFeed(domain) {
			this.isLoading = true;
			axios.get('/api/pixelfed/v2/discover/server-timeline', {
				params: {
					domain: domain
				}
			}).then(res => {
				this.feed = res.data;
				this.isLoading = false;
				this.isLoaded = true;
			})
			.catch(err => {
				this.feed = [];
				this.tagIndex = null;
				this.isLoaded = true;
				this.isLoading = false;
			})
		},

		toggleTag(tag) {
			this.initialTab = false;
			this.tagIndex = tag;
			this.fetchFeed(this.domains[tag]);
		}
	}
}
</script>

<style lang="scss" scoped>
	.discover-serverfeeds-component {
		.bg-stellar {
			background: #7474BF;
			background: -webkit-linear-gradient(to right, #348AC7, #7474BF);
			background: linear-gradient(to right, #348AC7, #7474BF);
		}
		.font-default {
			font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
			letter-spacing: -0.7px;
		}

		.active {
			font-weight: 700;
		}
	}
</style>
