<template>
	<div>
		<div v-if="modal != 'true'" class="dropdown">
			<button class="btn btn-link text-dark no-caret dropdown-toggle py-0" type="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false" title="Post options">
				<span v-bind:class="[size =='lg' ? 'fas fa-ellipsis-v fa-lg text-muted' : 'fas fa-ellipsis-v fa-sm text-lighter']"></span>
			</button>
			<div class="dropdown-menu dropdown-menu-right">
				<a class="dropdown-item font-weight-bold text-decoration-none" :href="status.url">Go to post</a>
				<!-- <a class="dropdown-item font-weight-bold text-decoration-none" href="#">Share</a>
				<a class="dropdown-item font-weight-bold text-decoration-none" href="#">Embed</a> -->
				<span v-if="activeSession == true && statusOwner(status) == false">
					<a class="dropdown-item font-weight-bold" :href="reportUrl(status)">Report</a>
				</span>
				<span v-if="activeSession == true && statusOwner(status) == true">
					<a class="dropdown-item font-weight-bold text-decoration-none" @click.prevent="muteProfile(status)">Mute Profile</a>
					<a class="dropdown-item font-weight-bold text-decoration-none" @click.prevent="blockProfile(status)">Block Profile</a>
				</span>
				<span v-if="activeSession == true && profile.is_admin == true">
					<div class="dropdown-divider"></div>
					<a class="dropdown-item font-weight-bold text-danger text-decoration-none" v-on:click="deletePost(status)">Delete</a>
					<div class="dropdown-divider"></div>
					<h6 class="dropdown-header">Mod Tools</h6>
					<a class="dropdown-item font-weight-bold text-decoration-none" v-on:click="moderatePost(status, 'autocw')">
						<p class="mb-0">Enforce CW</p>
						<p class="mb-0  small text-muted">Adds a CW to every post <br> made by this account.</p>
					</a>
					<a class="dropdown-item font-weight-bold text-decoration-none" v-on:click="moderatePost(status, 'noautolink')">
						<p class="mb-0">No Autolinking</p>
						<p class="mb-0 small text-muted">Do not transform mentions, <br> hashtags or urls into HTML.</p>
					</a>
					<a class="dropdown-item font-weight-bold text-decoration-none" v-on:click="moderatePost(status, 'unlisted')">
						<p class="mb-0">Unlisted Posts</p>
						<p class="mb-0 small text-muted">Removes account from <br> public/network timelines.</p>
					</a>
					<a class="dropdown-item font-weight-bold text-decoration-none" v-on:click="moderatePost(status, 'disable')">
						<p class="mb-0">Disable Account</p>
						<p class="mb-0 small text-muted">Temporarily disable account <br> until next time user log in.</p>
					</a>
					<a class="dropdown-item font-weight-bold text-decoration-none" v-on:click="moderatePost(status, 'suspend')">
						<p class="mb-0">Suspend Account</p>
						<p class="mb-0 small text-muted">This prevents any new interactions, <br> without deleting existing data.</p>
					</a>

				</span>
			</div>
		</div>
		<div v-if="modal == 'true'">
			<span data-toggle="modal" :data-target="'#mt_pid_'+status.id">
				<span v-bind:class="[size =='lg' ? 'fas fa-ellipsis-v fa-lg text-muted' : 'fas fa-ellipsis-v fa-sm text-lighter']"></span>
			</span>
			<div class="modal" tabindex="-1" role="dialog" :id="'mt_pid_'+status.id">
				<div class="modal-dialog modal-sm modal-dialog-centered" role="document">
					<div class="modal-content">
						<div class="modal-body text-center">
							<div class="list-group">
								<a class="list-group-item text-dark text-decoration-none" :href="statusUrl(status)">Go to post</a>
								<!-- a class="list-group-item font-weight-bold text-decoration-none" :href="status.url">Share</a>
								<a class="list-group-item font-weight-bold text-decoration-none" :href="status.url">Embed</a> -->
								<a class="list-group-item text-dark text-decoration-none" href="#" @click.prevent="hidePost(status)">Hide</a>
								<a v-if="activeSession == true && !statusOwner(status)" class="list-group-item text-danger font-weight-bold text-decoration-none" :href="reportUrl(status)">Report</a>
								<div v-if="activeSession == true && statusOwner(status) == true || profile.is_admin == true" class="list-group-item text-danger font-weight-bold cursor-pointer" @click.prevent="deletePost">Delete</div>
								<a class="list-group-item text-lighter text-decoration-none" href="#" @click.prevent="closeModal()">Close</a>
							</div>
						</div>
					</div>
				</div>
			</div>
		</div>
	</div>
</template>

<style type="text/css" scoped>
	.text-lighter {
		color:#B8C2CC !important;
	}
	.modal-body {
		padding: 0;
	}
</style>

<script type="text/javascript">
	export default {
		props: ['feed', 'status', 'profile', 'size', 'modal'],

		data() {
			return {
				activeSession: false
			};
		},

		mounted() {
			let el = document.querySelector('body');
			this.activeSession = el.classList.contains('loggedIn') ? true : false;
		},

		methods: {
			reportUrl(status) {
				let type = status.in_reply_to ? 'comment' : 'post';
				let id = status.id;
				return '/i/report?type=' + type + '&id=' + id;
			},

			timestampFormat(timestamp) {
				let ts = new Date(timestamp);
				return ts.toDateString() + ' ' + ts.toLocaleTimeString();
			},

			editUrl(status) {
				return status.url + '/edit';
			},

			redirect(url) {
				window.location.href = url;
				return;
			},

			replyUrl(status) {
				let username = this.profile.username;
				let id = status.account.id == this.profile.id ? status.id : status.in_reply_to_id;
				return '/p/' + username + '/' + id;
			},

			mentionUrl(status) {
				let username = status.account.username;
				let id = status.id;
				return '/p/' + username + '/' + id;
			},

			statusOwner(status) {
				let sid = parseInt(status.account.id);
				let uid = parseInt(this.profile.id);
				if(sid == uid) {
					return true;
				} else {
					return false;
				}
			},

			deletePost() {
				this.$emit('deletePost');
				$('#mt_pid_'+this.status.id).modal('hide');
			},

			hidePost(status) {
				status.sensitive = true;
				$('#mt_pid_'+status.id).modal('hide');
			},

			moderatePost(status, action, $event) {
				let username = status.account.username;
				switch(action) {
					case 'autocw':
						let msg = 'Are you sure you want to enforce CW for ' + username + ' ?';
						swal({
							title: 'Confirm',
							text: msg,
							icon: 'warning',
							buttons: true,
							dangerMode: true
						});
					break;
					case 'suspend':
						msg = 'Are you sure you want to suspend the account of ' + username + ' ?';
						swal({
							title: 'Confirm',
							text: msg,
							icon: 'warning',
							buttons: true,
							dangerMode: true
						});
					break;
				}
			},

			muteProfile(status) {
				if($('body').hasClass('loggedIn') == false) {
					return;
				}

				axios.post('/i/mute', {
					type: 'user',
					item: status.account.id
				}).then(res => {
					swal('Success', 'You have successfully muted ' + status.account.acct, 'success');
				}).catch(err => {
					swal('Error', 'Something went wrong. Please try again later.', 'error');
				});
			},

			blockProfile(status) {
				if($('body').hasClass('loggedIn') == false) {
					return;
				}

				axios.post('/i/block', {
					type: 'user',
					item: status.account.id
				}).then(res => {
					swal('Success', 'You have successfully blocked ' + status.account.acct, 'success');
				}).catch(err => {
					swal('Error', 'Something went wrong. Please try again later.', 'error');
				});
			},

			statusUrl(status) {
				if(status.local == true) {
					return status.url;
				}

				return '/i/web/post/_/' + status.account.id + '/' + status.id;
			},

			profileUrl(status) {
				if(status.local == true) {
					return status.account.url;
				}

				return '/i/web/profile/_/' + status.account.id;
			},

			closeModal() {
				$('#mt_pid_'+this.status.id).modal('hide');
			}
		}
	}
</script>
