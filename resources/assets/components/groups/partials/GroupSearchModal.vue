<template>
	<div class="group-search-modal">
		<b-modal
			ref="modal"
			hide-header
			hide-footer
			centered
			rounded
			body-class="rounded group-search-modal-wrapper">
			<div class="d-flex justify-content-between">
				<autocomplete
					:search="autocompleteSearch"
					placeholder="Search this group"
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

			<div v-if="recent && recent.length" class="pt-5">
				<h5 class="mb-2">Recent Searches</h5>
				<a v-for="(result, index) in recent" class="media align-items-center text-decoration-none text-dark" :href="result.action">
					<div class="bg-light rounded-circle mr-3 border d-flex justify-content-center align-items-center" style="width: 40px;height:40px">
						<i class="far fa-search"></i>
					</div>
					<div class="media-body">
						<p class="mb-0">{{ result.value }}</p>
					</div>
				</a>
			</div>

			<div class="pt-5">
				<h5 class="mb-2">Explore This Group</h5>
				<div class="media align-items-center" @click="viewMyActivity">
					<img
                        :src="profile?.avatar"
                        width="40"
                        height="40"
                        class="mr-3 border rounded-circle"
                        onerror="this.onerror=null;this.src='/storage/avatars/default.png?v=2'"
                    />
					<div class="media-body">
						<p class="mb-0">{{ profile?.display_name || profile?.username }}</p>
						<p class="mb-0 small text-muted">See your group activity.</p>
					</div>
				</div>
				<div class="media align-items-center" @click="viewGroupSearch">
					<div class="bg-light rounded-circle mr-3 border d-flex justify-content-center align-items-center" style="width: 40px;height:40px">
						<i class="far fa-search"></i>
					</div>
					<div class="media-body">
						<p class="mb-0">Search all groups</p>
					</div>
				</div>
			</div>

			<!-- <div class="text-center py-3 small">
				<p class="lead font-weight-normal mb-0">Looking for something?</p>
				<p class="mb-0">Search {{ group.name }} for posts, comments or members.</p>
			</div> -->
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
			'autocomplete': Autocomplete,
		},

		data() {
			return {
				query: '',
				recent: [],
				loaded: false
			}
		},

		methods: {
			open() {
				this.fetchRecent();
				this.$refs.modal.show();
			},

			close() {
				this.$refs.modal.hide();
			},

			fetchRecent() {
				axios.get('/api/v0/groups/search/getrec', {
					params: {
						g: this.group.id
					}
				}).then(res => {
					this.recent = res.data;
				})
			},

			autocompleteSearch(q) {
				if (!q || q.length < 2) {
					return [];
				}

				return axios.post(`/api/v0/groups/search/lac`, {
					q: q,
					g: this.group.id,
					v: '0.2'
				}).then(res => {
					return res.data;
				});
				return [];
			},

			getSearchResultValue(result) {
				return result.username;
			},

			onSearchSubmit(result) {
				if (result.length < 1) {
					return [];
				}

				axios.post('/api/v0/groups/search/addrec', {
					g: this.group.id,
					q: {
						value: result.username,
						action: result.url
					}
				}).then(res => {
					location.href = result.url;
				});
			},

			viewMyActivity() {
				location.href = `/groups/${this.group.id}/user/${this.profile.id}?rf=group_search`;
			},

			viewGroupSearch() {
				location.href = `/groups/home?ct=gsearch&rf=group_search&rfid=${this.group.id}`;
			},

			addToRecentSearches() {

			}
		}
	}
</script>

<style lang="scss">
	.group-search-modal {

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
