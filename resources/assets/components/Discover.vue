<template>
	<div class="web-wrapper">
		<div class="container-fluid mt-3">
			<div class="row">
				<div class="col-md-4 col-lg-3">
					<sidebar :user="profile" />
				</div>

				<div class="col-md-8 col-lg-9 mt-n4">
					<discover :profile="profile" />
				</div>
			</div>

			<drawer />
		</div>
	</div>
</template>

<script type="text/javascript">
	import Drawer from './partials/drawer.vue';
	import Sidebar from './partials/sidebar.vue';
	import Discover from './sections/DiscoverFeed.vue';

	export default {
		 components: {
		 	"drawer": Drawer,
            "sidebar": Sidebar,
            "discover": Discover,
        },

        data() {
        	return {
        		profile: undefined,
        	}
        },

        mounted() {
			this.profile = window._sharedData.user;
        }
	}
</script>
