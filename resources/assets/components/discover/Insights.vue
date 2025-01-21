<template>
	<div class="discover-insights-component">
		<div v-if="isLoaded" class="container-fluid mt-3">

			<div class="row">
				<div class="col-md-4 col-lg-3">
					<sidebar :user="profile" />
				</div>

				<div class="col-md-6 col-lg-6">
					<b-breadcrumb class="font-default" :items="breadcrumbItems"></b-breadcrumb>

					<h1 class="font-default">Account Insights</h1>
					<p class="font-default lead">A brief overview of your account</p>
					<hr>

					<div class="row">
						<div class="col-12 col-md-6 mb-3">
							<div class="card bg-midnight">
								<div class="card-body font-default text-white">
									<h1 class="display-4 mb-n2">{{ formatCount(profile.statuses_count) }}</h1>
									<p class="primary lead mb-0 font-weight-bold">Posts</p>
								</div>
							</div>
						</div>

						<div class="col-12 col-md-6 mb-3">
							<div class="card bg-midnight">
								<div class="card-body font-default text-white">
									<h1 class="display-4 mb-n2">{{ formatCount(profile.followers_count) }}</h1>
									<p class="primary lead mb-0 font-weight-bold">Followers</p>
								</div>
							</div>
						</div>
					</div>

					<div v-if="profile.statuses_count" class="card my-3 bg-midnight">
						<div class="card-header bg-dark border-bottom border-primary text-white font-default lead">Popular Posts</div>
						<div v-if="!popularLoaded" class="card-body text-white">
							<b-spinner/>
						</div>

						<ul v-else class="list-group list-group-flush font-default text-white">
							<li v-for="post in popular" class="list-group-item bg-midnight">
								<div class="media align-items-center">
									<img
										v-if="post.media_attachments.length"
										:src="post.media_attachments[0].url"
										onerror="this.onerror=null;this.src='/storage/no-preview.png?v=0'"
										class="media-photo shadow">

									<div class="media-body">
										<p class="media-caption mb-0">{{ post.content_text.slice(0, 40) }}</p>
										<p class="mb-0">
											<span class="font-weight-bold">{{ post.favourites_count }} Likes</span>
											<span class="mx-2">·</span>
											<span class="text-muted">Posted {{ timeago(post.created_at) }} ago</span>
										</p>
									</div>

									<button class="btn btn-primary primary font-weight-bold rounded-pill" @click="gotoPost(post)">View</button>
								</div>
							</li>
						</ul>
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
			isLoaded: true,
			isLoading: true,
			profile: window._sharedData.user,
			feed: [],
			popular: [],
			popularLoaded: false,
			breadcrumbItems: [
				{
					text: 'Discover',
					href: '/i/web/discover'
				},
				{
					text: 'Account Insights',
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
				if(res.data.insights.enabled == false) {
					this.$router.push('/i/web/discover');
				}
				this.fetchPopular();
			})
		},

		fetchPopular() {
			axios.get('/api/pixelfed/v2/discover/account-insights')
			.then(res => {
				this.popular = res.data.filter(p => {
					return p.favourites_count;
				});
				this.popularLoaded = true;
			})
		},

		formatCount(val) {
			return App.util.format.count(val);
		},

		timeago(ts) {
			return App.util.format.timeAgo(ts);
		},

		gotoPost(status) {
			this.$router.push({
				name: 'post',
				path: `/i/web/post/${status.id}`,
				params: {
					id: status.id,
					cachedStatus: status,
					cachedProfile: this.profile
				}
			})
		}
	}
}
</script>

<style lang="scss" scoped>
	.discover-insights-component {
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

		.media-photo {
			width: 70px;
			height: 70px;
			border-radius: 8px;
			margin-right: 2rem;
			object-fit: cover;
		}

		.media-caption {
			letter-spacing: -0.3px;
			font-size: 17px;
			opacity: 0.7;
		}
	}
</style>
