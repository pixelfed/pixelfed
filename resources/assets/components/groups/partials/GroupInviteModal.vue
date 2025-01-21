<template>
	<div class="group-invite-modal">
		<b-modal
			ref="modal"
			hide-header
			hide-footer
			centered
			rounded
			body-class="rounded group-invite-modal-wrapper">

			<div class="text-center py-3 d-flex align-items-center flex-column">
				<div class="bg-light rounded-circle d-flex justify-content-center align-items-center mb-3" style="width: 100px;height: 100px;">
					<i class="far fa-user-plus fa-2x text-lighter"></i>
				</div>
				<p class="h4 font-weight-bold mb-0">Invite Friends</p>
				<!-- <p class="mb-0">Search {{ group.name }} for posts, comments or members.</p> -->
			</div>

			<transition name="fade">
				<div v-if="usernames.length < 5" class="d-flex justify-content-between mt-1">
					<autocomplete
						:search="autocompleteSearch"
						placeholder="Search friends by username"
						aria-label="Search this group"
						:get-result-value="getSearchResultValue"
						:debounceTime="700"
						@submit="onSearchSubmit"
						style="width: 100%;"
						ref="autocomplete"
						>
							<template #result="{ result, props }">
							<li
							v-bind="props"
							class="autocomplete-result"
							>
								<div class="text-truncate">
									<p class="result-name mb-0 font-weight-bold">
										{{ result.username }}
									</p>
								</div>
							</li>
						</template>
					</autocomplete>

					<button class="btn btn-light border rounded-circle text-lighter ml-3" style="width: 52px;height:50px;" @click="close">
						<i class="fal fa-times fa-lg"></i>
					</button>
				</div>
			</transition>

			<transition name="fade">
				<div v-if="usernames.length" class="pt-3">
					<div v-for="(result, index) in usernames" class="py-1">
						<div class="media align-items-center">
							<img src="/storage/avatars/default.jpg" class="rounded-circle border mr-3" width="45" height="45">
							<div class="media-body">
								<p class="lead mb-0">{{ result.username }}</p>
							</div>
							<button class="btn btn-link text-lighter btn-sm" @click="removeUsername(index)"><i class="far fa-times-circle fa-lg"></i></button>
						</div>
					</div>
				</div>
			</transition>

			<transition name="fade">
				<button v-if="usernames && usernames.length" class="btn btn-primary btn-lg btn-block font-weight-bold rounded font-weight-bold mt-3" @click="submitInvites">Invite</button>
			</transition>

			<div class="text-center pt-3 small">
				<p class="mb-0">You can invite up to 5 friends at a time, and 20 friends in total.</p>
			</div>
		</b-modal>
	</div>
</template>

<script type="text/javascript">
	import Autocomplete from '@trevoreyre/autocomplete-vue'
	import '@trevoreyre/autocomplete-vue/dist/style.css'

	export default {
		props: {
			group: {
				type: Object
			},

			profile: {
				type: Object
			}
		},

		components: {
			'autocomplete-input': Autocomplete,
		},

		data() {
			return {
				query: '',
				recent: [],
				loaded: false,
				usernames: [],
				isSubmitting: false
			}
		},

		methods: {
			open() {
				this.$refs.modal.show();
			},

			close() {
				this.$refs.modal.hide();
			},

			autocompleteSearch(q) {
				if (!q || q.length == 0) {
					return [];
				}

				return axios.post(`/api/v0/groups/search/invite/friends`, {
					q: q,
					g: this.group.id,
					v: '0.2'
				}).then(res => {
					let data = res.data.filter(r => {
						return this.usernames.map(u => u.username).indexOf(r.username) == -1;
					});
					return data;
				});
				return [];
			},

			getSearchResultValue(result) {
				return result.username;
			},

			onSearchSubmit(result) {
				this.usernames.push(result);
				this.$refs.autocomplete.value = '';
				//
			},

			removeUsername(index) {
				event.currentTarget.blur();
				this.usernames.splice(index, 1);
			},

			submitInvites() {
				this.isSubmitting = true;
				event.currentTarget.blur();
				axios.post('/api/v0/groups/search/invite/friends/send', {
					g: this.group.id,
					uids: this.usernames.map(u => u.id)
				}).then(res => {
					this.usernames = [];
					this.isSubmitting = false;
					this.close();
					swal('Success', 'Successfully sent invite(s)', 'success');
				}).catch(err => {
					this.usernames = [];
					this.isSubmitting = false;
					if(err.response.status === 422) {
						swal('Error', err.response.data.error, 'error');
					} else {
						swal('Oops!', 'An error occured, please try again later', 'error');
					}
					this.close();
				})
			}
		}
	}
</script>

<style lang="scss">
	.group-invite-modal {

		&-wrapper {
			.media {
				height: 60px;
				padding: 10px;
				border-radius: 10px;
				user-select: none;
				cursor: pointer;

				&:hover {
					background-color: #E5E7EB;
				}
			}
		}
	}
</style>
