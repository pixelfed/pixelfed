<style scoped>
 .b-dropdown {
    padding:0 !important;
 }
</style>

<template>
	<div class="card mb-4">
		<div class="card-header py-1 bg-white d-flex align-items-center justify-content-between">
			<span class="font-weight-bold h5 mb-0">Circles</span>

	        <b-dropdown variant="link" no-caret right>
	          <template slot="button-content">
	              <i class="fas fa-ellipsis-v text-muted"></i><span class="sr-only">Options</span>
	          </template>
	          <b-dropdown-item class="font-weight-bold">Create a circle</b-dropdown-item>
	          <b-dropdown-divider></b-dropdown-divider>
	          <b-dropdown-item class="font-weight-bold">Settings</b-dropdown-item>
	        </b-dropdown>
		</div>
		<div class="card-body">
			<div class="text-center p-3">
				<p class="mb-0"><a class="btn btn-sm btn-outline-primary" style="border-radius: 20px;" href="/i/circle/create">Create new circle</a></p>
			</div>
		</div>
	</div>
</template>

<script>
	export default {

	}
</script>