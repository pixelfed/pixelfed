<template>
	<div class="web-wrapper">
		<div v-if="isLoaded" class="container-fluid mt-3">
			<div class="row">
				<div class="col-md-3 d-md-block">
					<sidebar :user="profile" />
				</div>

				<div class="col-md-8">
					<div class="row">
						<div class="col-12 col-md-8 offset-md-1">
							<compose-modal v-on:close="closeModal" />
						</div>
					</div>
				</div>
			</div>

			<drawer />
		</div>
	</div>
</template>

<script type="text/javascript">
	import Drawer from './partials/drawer.vue';
	import Sidebar from './partials/sidebar.vue';
	import ComposeModal from './../js/components/ComposeModal.vue';

	export default {
		 components: {
		 	"drawer": Drawer,
            "sidebar": Sidebar,
            "compose-modal": ComposeModal
        },

        data() {
        	return {
        		isLoaded: false,
        		profile: undefined,
        	}
        },

        mounted() {
			this.profile = window._sharedData.user;
			this.isLoaded = true;
        },

        methods: {
        	closeModal() {
        		this.$router.push('/i/web');
        	}
        }
	}
</script>
