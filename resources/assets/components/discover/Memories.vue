<template>
	<div class="discover-my-memories web-wrapper">
		<div v-if="isLoaded" class="container-fluid mt-3">
			<div class="row">
				<div class="col-md-4 col-lg-3">
					<sidebar :user="profile" />
				</div>

				<div v-if="tabIndex === 0" class="col-md-6 col-lg-6">
					<b-breadcrumb class="font-default" :items="breadcrumbItems"></b-breadcrumb>

					<h1 class="font-default">My Memories</h1>
					<p class="font-default lead">Posts from this day in previous years</p>

					<hr>

					<b-spinner v-if="!feedLoaded" />

					<status-card
						v-for="(post, idx) in feed"
						:key="'ti0:'+idx+':'+post.id"
						:profile="profile"
						:status="post"
						/>

					<p v-if="feedLoaded && feed.length == 0" class="lead">No memories found :(</p>
				</div>

				<div v-else-if="tabIndex === 1" class="col-md-6 col-lg-6">
					<b-breadcrumb class="font-default" :items="breadcrumbItems"></b-breadcrumb>

					<h1 class="font-default">My Memories</h1>
					<p class="font-default lead">Posts I've liked from this day in previous years</p>

					<hr>

					<b-spinner v-if="!likedLoaded" />

					<status-card
						v-for="(post, idx) in liked"
						:key="'ti1:'+idx+':'+post.id"
						:profile="profile"
						:status="post"
						/>

					<p v-if="likedLoaded && liked.length == 0" class="lead">No memories found :(</p>
				</div>

				<div class="col-md-2 col-lg-3">
					<div class="nav flex-column nav-pills font-default">
						<a class="nav-link" :class="{ active: tabIndex == 0 }" href="#" @click.prevent="toggleTab(0)">My Posts</a>
						<a class="nav-link" :class="{ active: tabIndex == 1 }" href="#" @click.prevent="toggleTab(1)">Posts I've Liked</a>
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
        		profile: window._sharedData.user,
        		curDate: undefined,
        		tabIndex: 0,
        		feedLoaded: false,
        		likedLoaded: false,
        		feed: [],
        		liked: [],
        		breadcrumbItems: [
					{
						text: 'Discover',
						href: '/i/web/discover'
					},
					{
						text: 'My Memories',
						active: true
					}
				]
        	}
        },

        mounted() {
        	this.curDate = new Date();
        	this.fetchConfig();
        },

        methods: {
        	fetchConfig() {
        		if(
        			window._sharedData.hasOwnProperty('discoverMeta') &&
        			window._sharedData.discoverMeta
        		) {
        			this.config = window._sharedData.discoverMeta;
        			this.isLoaded = true;
        			if(this.config.memories.enabled == false) {
						this.$router.push('/i/web/discover');
        			} else {
        				this.fetchMemories();
        			}
        			return;
        		}
				axios.get('/api/pixelfed/v2/discover/meta')
				.then(res => {
					this.config = res.data;
					this.isLoaded = true;
					window._sharedData.discoverMeta = res.data;
					if(res.data.memories.enabled == false) {
						this.$router.push('/i/web/discover');
					} else {
						this.fetchMemories();
					}
				})
			},

			fetchMemories() {
				axios.get('/api/pixelfed/v2/discover/memories')
				.then(res => {
					this.feed = res.data;
					this.feedLoaded = true;
				});
			},

			fetchLiked() {
				axios.get('/api/pixelfed/v2/discover/memories?type=liked')
				.then(res => {
					this.liked = res.data;
					this.likedLoaded = true;
				});
			},

        	toggleTab(idx) {
        		if(idx == 1) {
        			if(!this.likedLoaded) {
        				this.fetchLiked();
        			}
        		}
        		this.tabIndex = idx;
        	}
        }
	}
</script>

<style lang="scss" scoped>
	.discover-my-memories {
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
