<template>
	<div class="group-insights-component">
		<div class="row justify-content-center">
			<div class="col-12 col-md-3 mb-3">
				<div class="bg-light p-3 border rounded d-flex justify-content-between align-items-center">
					<p class="h3 font-weight-bold mb-0">124K</p>
					<p class="font-weight-bold text-uppercase text-lighter mb-0">Posts</p>
				</div>
			</div>

			<div class="col-12 col-md-3 mb-3">
				<div class="bg-light p-3 border rounded d-flex justify-content-between align-items-center">
					<p class="h3 font-weight-bold mb-0">9K</p>
					<p class="font-weight-bold text-uppercase text-lighter mb-0">Users</p>
				</div>
			</div>

			<div class="col-12 col-md-3 mb-3">
				<div class="bg-light p-3 border rounded d-flex justify-content-between align-items-center">
					<p class="h3 font-weight-bold mb-0">1.7M</p>
					<p class="font-weight-bold text-uppercase text-lighter mb-0">Interactions</p>
				</div>
			</div>
		</div>
		<div class="row justify-content-center">
			<div class="col-12 col-md-3 mb-3">
				<div class="bg-light p-3 border rounded d-flex justify-content-between align-items-center">
					<p class="h3 font-weight-bold mb-0">50</p>
					<p class="font-weight-bold text-uppercase text-lighter mb-0">Mod Reports</p>
				</div>
			</div>
		</div>
	</div>
</template>

<script type="text/javascript">
	export default {
		props: {
			group: {
				type: Object
			}
		},

		data() {
			return {

			};
		},

		methods: {

		}
	}
</script>

<style lang="scss" scoped>
	.group-insights-component {

	}
</style>
