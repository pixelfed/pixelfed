<template>
	<div>
		<b-modal
			v-if="profile && profile.hasOwnProperty('avatar')"
			ref="home"
			hide-footer
			centered
			rounded
			title="Limit Interactions"
			body-class="rounded">

			<div class="media mb-3">
				<a :href="profile.url" class="text-dark text-decoration-none">
					<img :src="profile.avatar" width="56" height="56" class="rounded-circle border mr-2" />
				</a>

				<div class="media-body">
					<p class="lead font-weight-bold mb-0">
						<a :href="profile.url" class="text-dark text-decoration-none">{{profile.username}}</a>
						<span v-if="profile.role == 'founder'" class="member-label rounded ml-1">Admin</span>
					</p>
					<p class="text-muted mb-0">Member since {{formatDate(profile.joined)}}</p>
				</div>
			</div>

			<div class="w-100 bg-light mb-1 font-weight-bold d-flex justify-content-center align-items-center border rounded" style="min-height:240px;">
				<div v-if="limitsLoaded" class="py-3">
					<p class="lead mb-0">Interaction Permissions</p>
					<p class="small text-muted">Last updated: {{ updated ? formatDate(updated) : 'Never' }}</p>

					<div class="form-check">
						<input class="form-check-input" type="checkbox" v-model="limits.can_post" :disabled="savingChanges">
						<label class="form-check-label">
							Can create posts
						</label>
					</div>

					<div class="form-check">
						<input class="form-check-input" type="checkbox" v-model="limits.can_comment" :disabled="savingChanges">
						<label class="form-check-label">
							Can create comments
						</label>
					</div>

					<div class="form-check">
						<input class="form-check-input" type="checkbox" v-model="limits.can_like" :disabled="savingChanges">
						<label class="form-check-label">
							Can like posts and comments
						</label>
					</div>

					<hr>

					<button class="btn btn-primary font-weight-bold float-right" @click.prevent="saveChanges" :disabled="savingChanges" style="width:130px;">
						<b-spinner v-if="savingChanges" variant="light" small />
						<span v-else>Save changes</span>
					</button>
				</div>
				<div v-else class="d-flex align-items-center flex-column">
					 <b-spinner variant="muted"  />
					 <p class="pt-3 small text-muted font-weight-bold">Loading interaction limits...</p>
				</div>
			</div>
		</b-modal>
	</div>
</template>

<script type="text/javascript">
	export default {
		props: {
			group: {
				type: Object
			},
			profile: {
				type: Object
			}
		},

		data() {
			return {
				limitsLoaded: false,
				limits: {
					can_post: true,
					can_comment: true,
					can_like: true
				},
				updated: null,
				savingChanges: false
			}
		},

		methods: {
			fetchInteractionLimits() {
				axios.get(`/api/v0/groups/${this.group.id}/members/interaction-limits`, {
					params: {
						profile_id: this.profile.id
					}
				})
				.then(res => {
					this.limits = res.data.limits;
					this.updated = res.data.updated_at;
					this.limitsLoaded = true;
				}).catch(err => {
					this.$refs.home.hide();
					swal('Oops!', 'Cannot fetch interaction limits at this time, please try again later.', 'error');
				})
			},

			open(profile) {
				this.loaded = true;
				this.$refs.home.show();
				this.fetchInteractionLimits();
			},

			formatDate(ts) {
				return new Date(ts).toDateString();
			},

			saveChanges() {
				event.currentTarget.blur();
				this.savingChanges = true;

				axios.post(`/api/v0/groups/${this.group.id}/members/interaction-limits`, {
					profile_id: this.profile.id,
					can_post: this.limits.can_post,
					can_comment: this.limits.can_comment,
					can_like: this.limits.can_like,
				})
				.then(res => {
					this.savingChanges = false;
					this.$refs.home.hide();

					this.$bvToast.toast(`Updated interaction limits for ${this.profile.username}`, {
						title: 'Success',
						variant: 'success',
						autoHideDelay: 5000
					});
				}).catch(err => {
					this.savingChanges = false;
					this.$refs.home.hide();

					if(err.response.status == 422 && err.response.data.error == 'limit_reached') {
							swal('Limit Reached', 'You cannot add any more member limitations', 'info');
							// swal({
							// 	title: 'Limit Reached',
							// 	text: 'You cannot add any more member limitations',
							// 	icon: 'info',
							// 	buttons: {
							// 		info: {
							// 			className: 'btn-light border',
							// 			text: 'More info',
							// 			value: 'more-info'
							// 		},

							// 		ok: {
							// 			text: 'Ok',
							// 			value: null
							// 		},
							// 	}
							// }).then(value => {
							// 	if(value == 'more-info') {
							// 		location.href = '/site/kb/groups/interaction-limits';
							// 	}
							// });
					} else {
						swal('Oops!', 'An error occured while processing this request, please try again later.', 'error');
					}
				});
			}
		}
	}
</script>
