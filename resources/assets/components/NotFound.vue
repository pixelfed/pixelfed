<template>
<div class="container d-flex justify-content-center">
  <div class="error-page py-5 my-5" style="max-width: 450px;">
    <h3 class="font-weight-bold">404 &nbsp; Page Not Found</h3>
    <p class="lead">The page you are trying to view is not available</p>

    <div class="text-muted">
		<p class="mb-1">This can happen for a few reasons:</p>
		<ul>
			<li>The url is invalid or has a typo</li>
			<li>The page has been flagged for review by our automated abuse detection systems</li>
			<li>The content may have been deleted</li>
			<li>You do not have permission to view this content</li>
		</ul>
    </div>
  </div>
  <drawer />
</div>
</template>

<script type="text/javascript">
	import Drawer from './partials/drawer.vue';

	export default {
		components: {
			"drawer": Drawer
		}
	}
</script>
