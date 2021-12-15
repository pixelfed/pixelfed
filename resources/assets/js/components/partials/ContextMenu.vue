<template>
	<div class="modal-stack">
		<b-modal ref="ctxModal"
			id="ctx-modal"
			hide-header
			hide-footer
			centered
			rounded
			size="sm"
			body-class="list-group-flush p-0 rounded">
			<div class="list-group text-center">
				<!-- <div v-if="status && status.account.id != profile.id && ctxMenuRelationship && ctxMenuRelationship.following" class="list-group-item rounded cursor-pointer font-weight-bold text-danger" @click="ctxMenuUnfollow()">Unfollow</div>
				<div v-if="status && status.account.id != profile.id && ctxMenuRelationship && !ctxMenuRelationship.following" class="list-group-item rounded cursor-pointer font-weight-bold text-primary" @click="ctxMenuFollow()">Follow</div> -->
				<div v-if="status.visibility !== 'archived'" class="list-group-item rounded cursor-pointer" @click="ctxMenuGoToPost()">View Post</div>
				<div v-if="status.visibility !== 'archived'" class="list-group-item rounded cursor-pointer" @click="ctxMenuGoToProfile()">View Profile</div>
				<!-- <div v-if="status && status.local == true && !status.in_reply_to_id" class="list-group-item rounded cursor-pointer" @click="ctxMenuEmbed()">Embed</div>
				<div class="list-group-item rounded cursor-pointer" @click="ctxMenuCopyLink()">Copy Link</div> -->
				<div v-if="status.visibility !== 'archived'" class="list-group-item rounded cursor-pointer" @click="ctxMenuShare()">Share</div>
				<div v-if="status && profile && profile.is_admin == true && status.visibility !== 'archived'" class="list-group-item rounded cursor-pointer" @click="ctxModMenuShow()">Moderation Tools</div>
				<div v-if="status && status.account.id != profile.id" class="list-group-item rounded cursor-pointer text-danger" @click="ctxMenuReportPost()">Report</div>
				<div v-if="status && profile.id == status.account.id && status.visibility !== 'archived'" class="list-group-item rounded cursor-pointer text-danger" @click="archivePost(status)">Archive</div>
				<div v-if="status && profile.id == status.account.id && status.visibility == 'archived'" class="list-group-item rounded cursor-pointer text-danger" @click="unarchivePost(status)">Unarchive</div>
				<div v-if="status && (profile.is_admin || profile.id == status.account.id) && status.visibility !== 'archived'" class="list-group-item rounded cursor-pointer text-danger" @click="deletePost(status)">Delete</div>
				<div class="list-group-item rounded cursor-pointer text-lighter" @click="closeCtxMenu()">Cancel</div>
			</div>
		</b-modal>
		<b-modal ref="ctxModModal"
			id="ctx-mod-modal"
			hide-header
			hide-footer
			centered
			rounded
			size="sm"
			body-class="list-group-flush p-0 rounded">
			<div class="list-group text-center">
				<p class="py-2 px-3 mb-0">
					<div class="text-center font-weight-bold text-danger">Moderation Tools</div>
					<div class="small text-center text-muted">Select one of the following options</div>
				</p>
				<div class="list-group-item rounded cursor-pointer" @click="moderatePost(status, 'unlist')">Unlist from Timelines</div>
				<div v-if="status.sensitive" class="list-group-item rounded cursor-pointer" @click="moderatePost(status, 'remcw')">Remove Content Warning</div>
				<div v-else class="list-group-item rounded cursor-pointer" @click="moderatePost(status, 'addcw')">Add Content Warning</div>
				<div class="list-group-item rounded cursor-pointer" @click="moderatePost(status, 'spammer')">
					Mark as Spammer<br />
					<span class="small">Unlist + CW existing and future posts</span>
				</div>
				<!-- <div class="list-group-item rounded cursor-pointer" @click="ctxModOtherMenuShow()">Other</div> -->
				<div class="list-group-item rounded cursor-pointer text-lighter" @click="ctxModMenuClose()">Cancel</div>
			</div>
		</b-modal>
		<b-modal ref="ctxModOtherModal"
			id="ctx-mod-other-modal"
			hide-header
			hide-footer
			centered
			rounded
			size="sm"
			body-class="list-group-flush p-0 rounded">
			<div class="list-group text-center">
				<p class="py-2 px-3 mb-0">
					<div class="text-center font-weight-bold text-danger">Moderation Tools</div>
					<div class="small text-center text-muted">Select one of the following options</div>
				</p>
				<div class="list-group-item rounded cursor-pointer font-weight-bold" @click="confirmModal()">Unlist Posts</div>
				<div class="list-group-item rounded cursor-pointer font-weight-bold" @click="confirmModal()">Moderation Log</div>
				<div class="list-group-item rounded cursor-pointer text-lighter" @click="ctxModOtherMenuClose()">Cancel</div>
			</div>
		</b-modal>
		<b-modal ref="ctxShareModal"
			id="ctx-share-modal"
			title="Share"
			hide-footer
			hide-header
			centered
			rounded
			size="sm"
			body-class="list-group-flush p-0 rounded text-center">
			<div class="list-group-item rounded cursor-pointer" @click="shareStatus(status, $event)">{{status.reblogged ? 'Unshare' : 'Share'}} to Followers</div>
			<div class="list-group-item rounded cursor-pointer" @click="ctxMenuCopyLink()">Copy Link</div>
			<div v-if="status && status.local == true && !status.in_reply_to_id" class="list-group-item rounded cursor-pointer" @click="ctxMenuEmbed()">Embed</div>
			<!-- <div class="list-group-item rounded cursor-pointer border-top-0">Email</div>
			<div class="list-group-item rounded cursor-pointer">Facebook</div>
			<div class="list-group-item rounded cursor-pointer">Mastodon</div>
			<div class="list-group-item rounded cursor-pointer">Pinterest</div>
			<div class="list-group-item rounded cursor-pointer">Pixelfed</div>
			<div class="list-group-item rounded cursor-pointer">Twitter</div>
			<div class="list-group-item rounded cursor-pointer">VK</div> -->
			<div class="list-group-item rounded cursor-pointer text-lighter" @click="closeCtxShareMenu()">Cancel</div>
		</b-modal>
		<b-modal ref="ctxEmbedModal"
			id="ctx-embed-modal"
			hide-header
			hide-footer
			centered
			rounded
			size="md"
			body-class="p-2 rounded">
			<div>
				<div class="form-group">
					<textarea class="form-control disabled text-monospace" rows="8" style="overflow-y:hidden;border: 1px solid #efefef; font-size: 12px; line-height: 18px; margin: 0 0 7px;resize:none;" v-model="ctxEmbedPayload" disabled=""></textarea>
				</div>
				<div class="form-group pl-2 d-flex justify-content-center">
					<div class="form-check mr-3">
						<input class="form-check-input" type="checkbox" v-model="ctxEmbedShowCaption" :disabled="ctxEmbedCompactMode == true">
						<label class="form-check-label font-weight-light">
							Show Caption
						</label>
					</div>
					<div class="form-check mr-3">
						<input class="form-check-input" type="checkbox" v-model="ctxEmbedShowLikes" :disabled="ctxEmbedCompactMode == true">
						<label class="form-check-label font-weight-light">
							Show Likes
						</label>
					</div>
					<div class="form-check">
						<input class="form-check-input" type="checkbox" v-model="ctxEmbedCompactMode">
						<label class="form-check-label font-weight-light">
							Compact Mode
						</label>
					</div>
				</div>
				<hr>
				<button :class="copiedEmbed ? 'btn btn-primary btn-block btn-sm py-1 font-weight-bold disabed': 'btn btn-primary btn-block btn-sm py-1 font-weight-bold'" @click="ctxCopyEmbed" :disabled="copiedEmbed">{{copiedEmbed ? 'Embed Code Copied!' : 'Copy Embed Code'}}</button>
				<p class="mb-0 px-2 small text-muted">By using this embed, you agree to our <a href="/site/terms">Terms of Use</a></p>
			</div>
		</b-modal>
		<b-modal ref="ctxReport"
			id="ctx-report"
			hide-header
			hide-footer
			centered
			rounded
			size="sm"
			body-class="list-group-flush p-0 rounded">
			<p class="py-2 px-3 mb-0">
				<div class="text-center font-weight-bold text-danger">Report</div>
				<div class="small text-center text-muted">Select one of the following options</div>
			</p>
			<div class="list-group text-center">
				<div class="list-group-item rounded cursor-pointer font-weight-bold" @click="sendReport('spam')">Spam</div>
				<div class="list-group-item rounded cursor-pointer font-weight-bold" @click="sendReport('sensitive')">Sensitive Content</div>
				<div class="list-group-item rounded cursor-pointer font-weight-bold" @click="sendReport('abusive')">Abusive or Harmful</div>
				<div class="list-group-item rounded cursor-pointer font-weight-bold" @click="openCtxReportOtherMenu()">Other</div>
				<!-- <div class="list-group-item rounded cursor-pointer" @click="ctxReportMenuGoBack()">Go Back</div> -->
				<div class="list-group-item rounded cursor-pointer text-lighter" @click="ctxReportMenuGoBack()">Cancel</div>
			</div>
		</b-modal>
		<b-modal ref="ctxReportOther"
			id="ctx-report-other"
			hide-header
			hide-footer
			centered
			rounded
			size="sm"
			body-class="list-group-flush p-0 rounded">
			<p class="py-2 px-3 mb-0">
				<div class="text-center font-weight-bold text-danger">Report</div>
				<div class="small text-center text-muted">Select one of the following options</div>
			</p>
			<div class="list-group text-center">
				<div class="list-group-item rounded cursor-pointer font-weight-bold" @click="sendReport('underage')">Underage Account</div>
				<div class="list-group-item rounded cursor-pointer font-weight-bold" @click="sendReport('copyright')">Copyright Infringement</div>
				<div class="list-group-item rounded cursor-pointer font-weight-bold" @click="sendReport('impersonation')">Impersonation</div>
				<div class="list-group-item rounded cursor-pointer font-weight-bold" @click="sendReport('scam')">Scam or Fraud</div>
				<!-- <div class="list-group-item rounded cursor-pointer font-weight-bold" @click="sendReport('terrorism')">Terrorism Related</div> -->
				<!-- <div class="list-group-item rounded cursor-pointer font-weight-bold" @click="sendReport('other')">Other or Not listed</div> -->
				<!-- <div class="list-group-item rounded cursor-pointer" @click="ctxReportOtherMenuGoBack()">Go Back</div> -->
				<div class="list-group-item rounded cursor-pointer text-lighter" @click="ctxReportOtherMenuGoBack()">Cancel</div>
			</div>
		</b-modal>
		<b-modal ref="ctxConfirm"
			id="ctx-confirm"
			hide-header
			hide-footer
			centered
			rounded
			size="sm"
			body-class="list-group-flush p-0 rounded">
			<div class="d-flex align-items-center justify-content-center py-3">
				<div>{{ this.confirmModalTitle }}</div>
			</div>
			<div class="d-flex border-top btn-group btn-group-block rounded-0" role="group">
				<button type="button" class="btn btn-outline-lighter border-left-0 border-top-0 border-bottom-0 border-right py-2" style="color: rgb(0,122,255) !important;" @click.prevent="confirmModalCancel()">Cancel</button>
				<button type="button" class="btn btn-outline-lighter border-0" style="color: rgb(0,122,255) !important;" @click.prevent="confirmModalConfirm()">Confirm</button>
			</div>
		</b-modal>
	</div>
</template>

<script type="text/javascript">
	export default {
		props: [
			'status',
			'profile'
		],

		data() {
			return {
				ctxMenuStatus: false,
				ctxMenuRelationship: false,
				ctxEmbedPayload: false,
				copiedEmbed: false,
				replySending: false,
				ctxEmbedShowCaption: true,
				ctxEmbedShowLikes: false,
				ctxEmbedCompactMode: false,
				confirmModalTitle: 'Are you sure?',
				confirmModalIdentifer: null,
				confirmModalType: false,
			}
		},

		watch: {
			ctxEmbedShowCaption: function (n,o) {
				if(n == true) {
					this.ctxEmbedCompactMode = false;
				}
				let mode = this.ctxEmbedCompactMode ? 'compact' : 'full';
				this.ctxEmbedPayload = window.App.util.embed.post(this.ctxMenuStatus.url, this.ctxEmbedShowCaption, this.ctxEmbedShowLikes, mode);
			},
			ctxEmbedShowLikes: function (n,o) {
				if(n == true) {
					this.ctxEmbedCompactMode = false;
				}
				let mode = this.ctxEmbedCompactMode ? 'compact' : 'full';
				this.ctxEmbedPayload = window.App.util.embed.post(this.ctxMenuStatus.url, this.ctxEmbedShowCaption, this.ctxEmbedShowLikes, mode);
			},
			ctxEmbedCompactMode: function (n,o) {
				if(n == true) {
					this.ctxEmbedShowCaption = false;
					this.ctxEmbedShowLikes = false;
				}
				let mode = this.ctxEmbedCompactMode ? 'compact' : 'full';
				this.ctxEmbedPayload = window.App.util.embed.post(this.ctxMenuStatus.url, this.ctxEmbedShowCaption, this.ctxEmbedShowLikes, mode);
			}
		},

		methods: {
			open() {
				this.ctxMenu();
			},

			ctxMenu() {
				this.ctxMenuStatus = this.status;
				this.ctxEmbedPayload = window.App.util.embed.post(this.status.url);
				if(this.status.account.id == this.profile.id) {
					this.ctxMenuRelationship = false;
					this.$refs.ctxModal.show();
				} else {
					axios.get('/api/pixelfed/v1/accounts/relationships', {
						params: {
							'id[]': this.status.account.id
						}
					}).then(res => {
						this.ctxMenuRelationship = res.data[0];
						this.$refs.ctxModal.show();
					});
				}
			},

			closeCtxMenu() {
				this.copiedEmbed = false;
				this.ctxMenuStatus = false;
				this.ctxMenuRelationship = false;
				this.$refs.ctxModal.hide();
				this.$refs.ctxReport.hide();
				this.$refs.ctxReportOther.hide();
				this.closeModals();
			},

			ctxMenuCopyLink() {
				let status = this.ctxMenuStatus;
				navigator.clipboard.writeText(status.url);
				this.closeModals();
				return;
			},

			ctxMenuGoToPost() {
				let status = this.ctxMenuStatus;
				window.location.href = this.statusUrl(status);
				this.closeCtxMenu();
				return;
			},

			ctxMenuGoToProfile() {
				let status = this.ctxMenuStatus;
				window.location.href = this.profileUrl(status);
				this.closeCtxMenu();
				return;
			},

			ctxMenuFollow() {
				let id = this.ctxMenuStatus.account.id;
				axios.post('/i/follow', {
					item: id
				}).then(res => {
					let username = this.ctxMenuStatus.account.acct;
					this.closeCtxMenu();
					setTimeout(function() {
						swal('Follow successful!', 'You are now following ' + username, 'success');
					}, 500);
				});
			},

			ctxMenuUnfollow() {
				let id = this.ctxMenuStatus.account.id;
				axios.post('/i/follow', {
					item: id
				}).then(res => {
					let username = this.ctxMenuStatus.account.acct;
					if(this.scope == 'home') {
						this.feed = this.feed.filter(s => {
							return s.account.id != this.ctxMenuStatus.account.id;
						});
					}
					this.closeCtxMenu();
					setTimeout(function() {
						swal('Unfollow successful!', 'You are no longer following ' + username, 'success');
					}, 500);
				});
			},

			ctxMenuReportPost() {
				this.$refs.ctxModal.hide();
				this.$refs.ctxReport.show();
				return;
			},

			ctxMenuEmbed() {
				this.closeModals();
				this.$refs.ctxEmbedModal.show();
			},

			ctxMenuShare() {
				this.$refs.ctxModal.hide();
				this.$refs.ctxShareModal.show();
			},

			closeCtxShareMenu() {
				this.$refs.ctxShareModal.hide();
				this.$refs.ctxModal.show();
			},

			ctxCopyEmbed() {
				navigator.clipboard.writeText(this.ctxEmbedPayload);
				this.ctxEmbedShowCaption = true;
				this.ctxEmbedShowLikes = false;
				this.ctxEmbedCompactMode = false;
				this.$refs.ctxEmbedModal.hide();
			},

			ctxModMenuShow() {
				this.$refs.ctxModal.hide();
				this.$refs.ctxModModal.show();
			},

			ctxModOtherMenuShow() {
				this.$refs.ctxModal.hide();
				this.$refs.ctxModModal.hide();
				this.$refs.ctxModOtherModal.show();
			},

			ctxModMenu() {
				this.$refs.ctxModal.hide();
			},

			ctxModMenuClose() {
				this.closeModals();
			},

			ctxModOtherMenuClose() {
				this.closeModals();
				this.$refs.ctxModModal.show();
			},

			formatCount(count) {
				return App.util.format.count(count);
			},

			openCtxReportOtherMenu() {
				let s = this.ctxMenuStatus;
				this.closeCtxMenu();
				this.ctxMenuStatus = s;
				this.$refs.ctxReportOther.show();
			},

			ctxReportMenuGoBack() {
				this.$refs.ctxReportOther.hide();
				this.$refs.ctxReport.hide();
				this.$refs.ctxModal.show();
			},

			ctxReportOtherMenuGoBack() {
				this.$refs.ctxReportOther.hide();
				this.$refs.ctxModal.hide();
				this.$refs.ctxReport.show();
			},

			sendReport(type) {
				let id = this.ctxMenuStatus.id;

				swal({
					'title': 'Confirm Report',
					'text': 'Are you sure you want to report this post?',
					'icon': 'warning',
					'buttons': true,
					'dangerMode': true
				}).then((res) => {
					if(res) {
						axios.post('/i/report/', {
							'report': type,
							'type': 'post',
							'id': id,
						}).then(res => {
							this.closeCtxMenu();
							swal('Report Sent!', 'We have successfully received your report.', 'success');
						}).catch(err => {
							swal('Oops!', 'There was an issue reporting this post.', 'error');
						})
					} else {
						this.closeCtxMenu();
					}
				});
			},

			closeModals() {
				this.$refs.ctxModal.hide();
				this.$refs.ctxModModal.hide();
				this.$refs.ctxModOtherModal.hide();
				this.$refs.ctxShareModal.hide();
				this.$refs.ctxEmbedModal.hide();
				this.$refs.ctxReport.hide();
				this.$refs.ctxReportOther.hide();
				this.$refs.ctxConfirm.hide();
			},

			openCtxStatusModal() {
				this.closeModals();
				this.$refs.ctxStatusModal.show();
			},

			openConfirmModal() {
				this.closeModals();
				this.$refs.ctxConfirm.show();
			},

			closeConfirmModal() {
				this.closeModals();
				this.confirmModalTitle = 'Are you sure?';
				this.confirmModalType = false;
				this.confirmModalIdentifer = null;
			},

			confirmModalConfirm() {
				switch(this.confirmModalType) {
					case 'post.delete':
						axios.post('/i/delete', {
							type: 'status',
							item: this.confirmModalIdentifer
						}).then(res => {
							this.feed = this.feed.filter(s => {
								return s.id != this.confirmModalIdentifer;
							});
							this.closeConfirmModal();
						}).catch(err => {
							this.closeConfirmModal();
							swal('Error', 'Something went wrong. Please try again later.', 'error');
						});
					break;
				}

				this.closeConfirmModal();
			},

			confirmModalCancel() {
				this.closeConfirmModal();
			},

			moderatePost(status, action, $event) {
				let username = status.account.username;
				let pid = status.id;
				let msg = '';
				let self = this;
				switch(action) {
					case 'addcw':
						msg = 'Are you sure you want to add a content warning to this post?';
						swal({
							title: 'Confirm',
							text: msg,
							icon: 'warning',
							buttons: true,
							dangerMode: true
						}).then(res =>  {
							if(res) {
								axios.post('/api/v2/moderator/action', {
									action: action,
									item_id: status.id,
									item_type: 'status'
								}).then(res => {
									swal('Success', 'Successfully added content warning', 'success');
									status.sensitive = true;
									self.closeModals();
									self.ctxModMenuClose();
								}).catch(err => {
									swal(
										'Error',
										'Something went wrong, please try again later.',
										'error'
										);
									self.closeModals();
									self.ctxModMenuClose();
								});
							}
						});
					break;

					case 'remcw':
						msg = 'Are you sure you want to remove the content warning on this post?';
						swal({
							title: 'Confirm',
							text: msg,
							icon: 'warning',
							buttons: true,
							dangerMode: true
						}).then(res =>  {
							if(res) {
								axios.post('/api/v2/moderator/action', {
									action: action,
									item_id: status.id,
									item_type: 'status'
								}).then(res => {
									swal('Success', 'Successfully added content warning', 'success');
									status.sensitive = false;
									self.closeModals();
									self.ctxModMenuClose();
								}).catch(err => {
									swal(
										'Error',
										'Something went wrong, please try again later.',
										'error'
										);
									self.closeModals();
									self.ctxModMenuClose();
								});
							}
						});
					break;

					case 'unlist':
						msg = 'Are you sure you want to unlist this post?';
						swal({
							title: 'Confirm',
							text: msg,
							icon: 'warning',
							buttons: true,
							dangerMode: true
						}).then(res =>  {
							if(res) {
								axios.post('/api/v2/moderator/action', {
									action: action,
									item_id: status.id,
									item_type: 'status'
								}).then(res => {
									this.feed = this.feed.filter(f => {
										return f.id != status.id;
									});
									swal('Success', 'Successfully unlisted post', 'success');
									self.closeModals();
									self.ctxModMenuClose();
								}).catch(err => {
									self.closeModals();
									self.ctxModMenuClose();
									swal(
										'Error',
										'Something went wrong, please try again later.',
										'error'
										);
								});
							}
						});
					break;

					case 'spammer':
						msg = 'Are you sure you want to mark this user as a spammer? All existing and future posts will be unlisted on timelines and a content warning will be applied.';
						swal({
							title: 'Confirm',
							text: msg,
							icon: 'warning',
							buttons: true,
							dangerMode: true
						}).then(res =>  {
							if(res) {
								axios.post('/api/v2/moderator/action', {
									action: action,
									item_id: status.id,
									item_type: 'status'
								}).then(res => {
									swal('Success', 'Successfully marked account as spammer', 'success');
									self.closeModals();
									self.ctxModMenuClose();
								}).catch(err => {
									self.closeModals();
									self.ctxModMenuClose();
									swal(
										'Error',
										'Something went wrong, please try again later.',
										'error'
										);
								});
							}
						});
					break;
				}
			},

			shareStatus(status, $event) {
				if($('body').hasClass('loggedIn') == false) {
					return;
				}

				this.closeModals();

				axios.post('/i/share', {
					item: status.id
				}).then(res => {
					status.reblogs_count = res.data.count;
					status.reblogged = !status.reblogged;
					if(status.reblogged) {
						swal('Success', 'You shared this post', 'success');
					} else {
						swal('Success', 'You unshared this post', 'success');
					}
				}).catch(err => {
					swal('Error', 'Something went wrong, please try again later.', 'error');
				});
			},

			statusUrl(status) {
				if(status.account.local == true) {
					return status.url;
				}

				return '/i/web/post/_/' + status.account.id + '/' + status.id;
			},

			profileUrl(status) {
				if(status.account.local == true) {
					return status.account.url;
				}

				return '/i/web/profile/_/' + status.account.id;
			},

			deletePost(status) {
				if($('body').hasClass('loggedIn') == false || this.ownerOrAdmin(status) == false) {
					return;
				}

				if(window.confirm('Are you sure you want to delete this post?') == false) {
					return;
				}

				axios.post('/i/delete', {
					type: 'status',
					item: status.id
				}).then(res => {
					this.$emit('status-delete', status.id);
					this.closeModals();
				}).catch(err => {
					swal('Error', 'Something went wrong. Please try again later.', 'error');
				});
			},

			owner(status) {
				return this.profile.id === status.account.id;
			},

			admin() {
				return this.profile.is_admin == true;
			},

			ownerOrAdmin(status) {
				return this.owner(status) || this.admin();
			},

			archivePost(status) {
				if(window.confirm('Are you sure you want to archive this post?') == false) {
					return;
				}

				axios.post('/api/pixelfed/v2/status/' + status.id + '/archive')
				.then(res => {
					this.$emit('status-delete', status.id);
					this.closeModals();
				});
			},

			unarchivePost(status) {
				if(window.confirm('Are you sure you want to unarchive this post?') == false) {
					return;
				}

				axios.post('/api/pixelfed/v2/status/' + status.id + '/unarchive')
				.then(res => {
					this.closeModals();
				});
			}
		}
	}
</script>
