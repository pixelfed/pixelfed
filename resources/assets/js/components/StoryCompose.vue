<template>
<div>
	<div class="container">
		<p class="display-4 text-center py-5">Share Your Story</p>

		<div class="d-flex justify-content-center align-item-center">
			<div class="bg-dark" style="width:400px;height:600px">
				<p class="text-center text-light font-weight-bold">Add Photo</p>
			</div>
		</div>
	</div>
</div>
</template>

<style type="text/css" scoped>
	.navtab .nav-link {
		color: #657786;
	}

	.navtab .nav-link.active {
		color: #08d;
		border-bottom: 4px solid #08d;
	}
</style>

<script type="text/javascript">
	export default {
		data() {
			return {
				currentTab: 'upload',
			};
		},

		mounted() {
			this.welcomeMessage();
		},

		methods: {
			welcomeMessage() {
			}
		}
	}
</script>