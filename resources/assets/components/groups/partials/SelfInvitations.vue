<template>
	<div class="col-12 col-md-9" style="height: 100vh - 51px !important;overflow:hidden">
		<div class="row h-100">
			<div class="col-12 col-md-8 bg-lighter border-left">
				<div class="p-4 mb-4">
					<p class="h4 font-weight-bold mb-1 text-center">Group Invitations</p>
				</div>
				<div class="p-4 mb-4">
					<p class="font-weight-bold text-center text-muted">You don't have any group invites</p>
				</div>
			</div>
			<div class="col-12 col-md-4 bg-white border-left">
				<div class="p-4">
					<div class="bg-light rounded-lg border p-3">
						<p class="lead font-weight-bold mb-0">Send Invite</p>
						<p class="mb-3">Invite friends to your groups</p>
						<div class="form-group" style="position: relative;">
							<span style="position: absolute; top:50%;transform: translateY(-50%);left:15px;padding-right:5px;">
								<i class="fas fa-search text-lighter"></i>
							</span>
							<input class="form-control bg-white rounded-pill" placeholder="Search username..." style="padding-left:40px">
						</div>
					</div>
				</div>
				<hr>
				<div class="p-4 mb-2">
					<p class="h4 font-weight-bold mb-1 text-center">Invitations Sent</p>
				</div>
				<div class="px-4 mb-4">
					<p class="font-weight-bold text-center text-muted">You have not sent any group invites</p>
				</div>
			</div>
		</div>
	</div>
</template>

<script type="text/javascript">
	export default {

	}
</script>
