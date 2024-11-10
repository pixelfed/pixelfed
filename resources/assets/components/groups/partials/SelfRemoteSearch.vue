<template>
	<div class="col-12 col-md-9" style="height: 100vh - 51px !important;overflow:hidden">
		<div class="row h-100 bg-lighter">
			<div class="col-12 col-md-8 border-left">
				<div class="d-flex justify-content-center">
					<div class="p-4 mb-4">
						<p class="h4 font-weight-bold mb-1">Find a Remote Group</p>
						<p class="lead text-muted">Search and explore remote federated groups.</p>
					</div>
				</div>
				<div class="mb-5">
					<div class="p-4 mb-4">
						<div class="form-group">
							<label>Group URL</label>
							<input type="text" class="form-control form-control-lg rounded-pill bg-white border" placeholder="https://pixelfed.social/groups/328323406233735168" v-model="q">
						</div>
						<button class="btn btn-primary btn-block btn-lg rounded-pill font-weight-bold">Search</button>
					</div>
				</div>
			</div>
			<div class="col-12 col-md-4 bg-white border-left">
				<div class="my-4">
					<h4 class="font-weight-bold">Tips</h4>
					<ul class="pl-3">
						<li class="font-weight-bold">Some remote groups are not supported*</li>
						<li>Read and comply with group rules defined by group admins</li>
						<li>Use the full <span class="font-weight-bold">Group URL</span> including <code>https://</code></li>
						<li>Joining private groups requires manual approval from group admins, you will recieve a notification when your membership is approved</li>
						<li>Inviting people to remote groups is not supported yet</li>
						<li>Your group membership may be terminated at any time by group admins</li>
					</ul>
					<p class="small">* Some remote groups may not be compatible, we are working to support other group implementations</p>
				</div>
			</div>
		</div>
	</div>
</template>

<script type="text/javascript">
	export default {
		data() {
			return {
				q: undefined
			}
		}
	}
</script>
