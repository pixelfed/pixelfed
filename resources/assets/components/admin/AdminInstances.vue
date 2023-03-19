<template>
<div>
	<div class="header bg-primary pb-3 mt-n4">
		<div class="container-fluid">
			<div class="header-body">
				<div class="row align-items-center py-4">
					<div class="col-lg-6 col-7">
						<p class="display-1 text-white d-inline-block mb-0">Instances</p>
					</div>
				</div>
				<div class="row">
					<div class="col-xl-2 col-md-6">
						<div class="mb-3">
							<h5 class="text-light text-uppercase mb-0">Total Instances</h5>
							<span class="text-white h2 font-weight-bold mb-0 human-size">{{ prettyCount(stats.total_count) }}</span>
						</div>
					</div>

					<div class="col-xl-2 col-md-6">
						<div class="mb-3">
							<h5 class="text-light text-uppercase mb-0">New (past 14 days)</h5>
							<span class="text-white h2 font-weight-bold mb-0 human-size">{{ prettyCount(stats.new_count) }}</span>
						</div>
					</div>

					<div class="col-xl-2 col-md-6">
						<div class="mb-3">
							<h5 class="text-light text-uppercase mb-0">Banned Instances</h5>
							<span class="text-white h2 font-weight-bold mb-0 human-size">{{ prettyCount(stats.banned_count) }}</span>
						</div>
					</div>

					<div class="col-xl-2 col-md-6">
						<div class="mb-3">
							<h5 class="text-light text-uppercase mb-0">NSFW Instances</h5>
							<span class="text-white h2 font-weight-bold mb-0 human-size">{{ prettyCount(stats.nsfw_count) }}</span>
						</div>
					</div>
					<div class="col-xl-2 col-md-6">
						<div class="mb-3">
							<button class="btn btn-outline-white btn-block btn-sm mt-1" @click.prevent="showAddModal = true">Create New Instance</button>
						</div>
					</div>
				</div>
			</div>
		</div>
	</div>

	<div v-if="!loaded" class="my-5 text-center">
		<b-spinner />
	</div>

	<div v-else class="m-n2 m-lg-4">
		<div class="container-fluid mt-4">
			<div class="row mb-3 justify-content-between">
				<div class="col-12 col-md-8">
					<ul class="nav nav-pills">
						<li class="nav-item">
							<button :class="['nav-link', { active: tabIndex == 0}]" @click="toggleTab(0)">All</button>
						</li>
						<li class="nav-item">
							<button :class="['nav-link', { active: tabIndex == 1}]" @click="toggleTab(1)">New</button>
						</li>
						<li class="nav-item">
							<button :class="['nav-link', { active: tabIndex == 2}]" @click="toggleTab(2)">Banned</button>
						</li>
						<li class="nav-item">
							<button :class="['nav-link', { active: tabIndex == 3}]" @click="toggleTab(3)">NSFW</button>
						</li>
						<li class="nav-item">
							<button :class="['nav-link', { active: tabIndex == 4}]" @click="toggleTab(4)">Unlisted</button>
						</li>
						<li class="nav-item">
							<button :class="['nav-link', { active: tabIndex == 5}]" @click="toggleTab(5)">Most Users</button>
						</li>
						<li class="nav-item">
							<button :class="['nav-link', { active: tabIndex == 6}]" @click="toggleTab(6)">Most Statuses</button>
						</li>
					</ul>
				</div>
				<div class="col-12 col-md-4">
					<autocomplete
						:search="composeSearch"
						:disabled="searchLoading"
						:defaultValue="searchQuery"
						placeholder="Search instances by domain"
						aria-label="Search instances by domain"
						:get-result-value="getTagResultValue"
						@submit="onSearchResultClick"
						ref="autocomplete"
						>
							<template #result="{ result, props }">
								<li
								v-bind="props"
								class="autocomplete-result d-flex justify-content-between align-items-center"
								>
								<div class="font-weight-bold" :class="{ 'text-danger': result.banned }">
									{{ result.domain }}
								</div>
								<div class="small text-muted">
									{{ prettyCount(result.user_count) }} users
								</div>
							</li>
						</template>
					</autocomplete>
				</div>
			</div>

			<div class="table-responsive">
				<table class="table table-dark">
					<thead class="thead-dark">
						<tr>
							<th scope="col" class="cursor-pointer" v-html="buildColumn('ID', 'id')" @click="toggleCol('id')"></th>
							<th scope="col" class="cursor-pointer" v-html="buildColumn('Domain', 'name')" @click="toggleCol('name')"></th>
							<th scope="col" class="cursor-pointer" v-html="buildColumn('Software', 'software')" @click="toggleCol('software')"></th>
							<th scope="col" class="cursor-pointer" v-html="buildColumn('User Count', 'user_count')" @click="toggleCol('user_count')"></th>
							<th scope="col" class="cursor-pointer" v-html="buildColumn('Status Count', 'status_count')" @click="toggleCol('status_count')"></th>
							<th scope="col" class="cursor-pointer" v-html="buildColumn('Banned', 'banned')" @click="toggleCol('banned')"></th>
							<th scope="col" class="cursor-pointer" v-html="buildColumn('NSFW', 'auto_cw')" @click="toggleCol('auto_cw')"></th>
							<th scope="col" class="cursor-pointer" v-html="buildColumn('Unlisted', 'unlisted')" @click="toggleCol('unlisted')"></th>
							<th scope="col">Created</th>
						</tr>
					</thead>
					<tbody>
						<tr v-for="(instance, idx) in instances">
							<td class="font-weight-bold text-monospace text-muted">
								<a href="#" @click.prevent="openInstanceModal(instance.id)">
									{{ instance.id }}
								</a>
							</td>
							<td class="font-weight-bold">{{ instance.domain }}</td>
							<td class="font-weight-bold">{{ instance.software }}</td>
							<td class="font-weight-bold">{{ prettyCount(instance.user_count) }}</td>
							<td class="font-weight-bold">{{ prettyCount(instance.status_count) }}</td>
							<td class="font-weight-bold" v-html="boolIcon(instance.banned, 'text-danger')"></td>
							<td class="font-weight-bold" v-html="boolIcon(instance.auto_cw, 'text-danger')"></td>
							<td class="font-weight-bold" v-html="boolIcon(instance.unlisted, 'text-danger')"></td>
							<td class="font-weight-bold">{{ timeAgo(instance.created_at) }}</td>
						</tr>
					</tbody>
				</table>
			</div>

			<div class="d-flex align-items-center justify-content-center">
				<button
					class="btn btn-primary rounded-pill"
					:disabled="!pagination.prev"
					@click="paginate('prev')">
					Prev
				</button>
				<button
					class="btn btn-primary rounded-pill"
					:disabled="!pagination.next"
					@click="paginate('next')">
					Next
				</button>
			</div>
		</div>
	</div>

	<b-modal
		v-model="showInstanceModal"
		title="View Instance"
		header-class="d-flex align-items-center justify-content-center mb-0 pb-0"
		ok-title="Save"
		:ok-disabled="!editingInstanceChanges"
		@ok="saveInstanceModalChanges">
		<div v-if="editingInstance && canEditInstance" class="list-group">
			<div class="list-group-item d-flex align-items-center justify-content-between">
				<div class="text-muted small">Domain</div>
				<div class="font-weight-bold">{{ editingInstance.domain }}</div>
			</div>
			<div class="list-group-item d-flex align-items-center justify-content-between">
				<div v-if="editingInstance.software">
					<div class="text-muted small">Software</div>
					<div class="font-weight-bold">{{ editingInstance.software ?? 'Unknown' }}</div>
				</div>
				<div>
					<div class="text-muted small">Total Users</div>
					<div class="font-weight-bold">{{ formatCount(editingInstance.user_count ?? 0) }}</div>
				</div>
				<div>
					<div class="text-muted small">Total Statuses</div>
					<div class="font-weight-bold">{{ formatCount(editingInstance.status_count ?? 0) }}</div>
				</div>
			</div>
			<div class="list-group-item d-flex align-items-center justify-content-between">
				<div class="text-muted small">Banned</div>
				<div class="mr-n2 mb-1">
					<b-form-checkbox v-model="editingInstance.banned" switch size="lg"></b-form-checkbox>
				</div>
			</div>
			<div class="list-group-item d-flex align-items-center justify-content-between">
				<div class="text-muted small">Apply CW to Media</div>
				<div class="mr-n2 mb-1">
					<b-form-checkbox v-model="editingInstance.auto_cw" switch size="lg"></b-form-checkbox>
				</div>
			</div>
			<div class="list-group-item d-flex align-items-center justify-content-between">
				<div class="text-muted small">Unlisted</div>
				<div class="mr-n2 mb-1">
					<b-form-checkbox v-model="editingInstance.unlisted" switch size="lg"></b-form-checkbox>
				</div>
			</div>
			<div class="list-group-item d-flex justify-content-between" :class="[ instanceModalNotes ? 'flex-column gap-2' : 'align-items-center']">
				<div class="text-muted small">Notes</div>
				<transition name="fade">
					<div v-if="instanceModalNotes" class="w-100">
						<b-form-textarea v-model="editingInstance.notes" rows="3" max-rows="5" maxlength="500"></b-form-textarea>
						<p class="small text-muted">{{editingInstance.notes ? editingInstance.notes.length : 0}}/500</p>
					</div>
					<div v-else class="mb-1">
						<a href="#" class="font-weight-bold small" @click.prevent="showModalNotes()">{{editingInstance.notes ? 'View' : 'Add'}}</a>
					</div>
				</transition>
			</div>
		</div>
		<template #modal-footer>
		<div class="w-100 d-flex justify-content-between align-items-center">
			<div>
				<b-button
					variant="outline-danger"
					size="sm"
					@click="deleteInstanceModal"
				>
					Delete
				</b-button>
				<b-button
					v-if="!refreshedModalStats"
					variant="outline-primary"
					size="sm"
					@click="refreshModalStats"
				>
					Refresh Stats
				</b-button>
			</div>
		  <div>
			  <b-button
				variant="secondary"
				@click="showInstanceModal = false"
			  >
				Close
			  </b-button>
			  <b-button
				variant="primary"
				@click="saveInstanceModalChanges"
			  >
				Save
			  </b-button>
		  </div>
		</div>
	  </template>
	</b-modal>

	<b-modal
		v-model="showAddModal"
		title="Add Instance"
		ok-title="Save"
		:ok-disabled="addNewInstance.domain.length < 2"
		@ok="saveNewInstance">
		<div class="list-group">
			<div class="list-group-item d-flex align-items-center justify-content-between">
				<div class="text-muted small">Domain</div>
				<div>
					<b-form-input v-model="addNewInstance.domain" placeholder="Add domain here" />
					<p class="small text-light mb-0">Enter a valid domain without https://</p>
				</div>
			</div>

			<div class="list-group-item d-flex align-items-center justify-content-between">
				<div class="text-muted small">Banned</div>
				<div class="mr-n2 mb-1">
					<b-form-checkbox v-model="addNewInstance.banned" switch size="lg"></b-form-checkbox>
				</div>
			</div>
			<div class="list-group-item d-flex align-items-center justify-content-between">
				<div class="text-muted small">Apply CW to Media</div>
				<div class="mr-n2 mb-1">
					<b-form-checkbox v-model="addNewInstance.auto_cw" switch size="lg"></b-form-checkbox>
				</div>
			</div>
			<div class="list-group-item d-flex align-items-center justify-content-between">
				<div class="text-muted small">Unlisted</div>
				<div class="mr-n2 mb-1">
					<b-form-checkbox v-model="addNewInstance.unlisted" switch size="lg"></b-form-checkbox>
				</div>
			</div>
			<div class="list-group-item d-flex flex-column gap-2 justify-content-between">
				<div class="text-muted small">Notes</div>
				<div class="w-100">
					<b-form-textarea v-model="addNewInstance.notes" rows="3" max-rows="5" maxlength="500" placeholder="Add optional notes here"></b-form-textarea>
					<p class="small text-muted">{{addNewInstance.notes ? addNewInstance.notes.length : 0}}/500</p>
				</div>
			</div>
		</div>

	</b-modal>
</div>
</template>

<script type="text/javascript">
	import Autocomplete from '@trevoreyre/autocomplete-vue'
	import '@trevoreyre/autocomplete-vue/dist/style.css'

	export default {
		components: {
			Autocomplete,
		},

		data() {
			return {
				loaded: false,
				tabIndex: 0,
				stats: {
					total_count: 0,
					new_count: 0,
					banned_count: 0,
					nsfw_count: 0
				},
				instances: [],
				pagination: [],
				sortCol: undefined,
				sortDir: undefined,
				searchQuery: undefined,
				filterMap: [
					'all',
					'new',
					'banned',
					'cw',
					'unlisted',
					'popular_users',
					'popular_statuses'
				],
				searchLoading: false,
				showInstanceModal: false,
				instanceModal: {},
				editingInstanceChanges: false,
				canEditInstance: false,
				editingInstance: {},
				editingInstanceIndex: 0,
				instanceModalNotes: false,
				showAddModal: false,
				refreshedModalStats: false,
				addNewInstance: {
					domain: "",
					banned: false,
					auto_cw: false,
					unlisted: false,
					notes: undefined
				}
			}
		},

		mounted() {
			this.fetchStats();

			let u = new URLSearchParams(window.location.search);
			if(u.has('filter') || u.has('cursor') && !u.has('q')) {
				let url = '/i/admin/api/instances/get?';

				let filter = u.get('filter');
				if(filter) {
					this.tabIndex = this.filterMap.indexOf(filter);
					url = url + 'filter=' + filter + '&';
				}

				let cursor = u.get('cursor');
				if(cursor) {
					url = url + 'cursor=' + cursor;
				}

				this.fetchInstances(url);
			} else if(u.has('q')) {
				this.tabIndex = -1;
				this.searchQuery = u.get('q');
				let cursor = u.has('cursor');
				let q = u.get('q');
				let url = '/i/admin/api/instances/query?q=' + q;
				if(cursor) {
					url = url + '&cursor=' + u.get('cursor');
				}
				this.fetchInstances(url);
			} else {
				this.fetchInstances();
			}
		},

		watch: {
			editingInstance: {
				deep: true,
				immediate: true,
				handler: function(updated, old) {
					if(!this.canEditInstance) {
						return;
					}

					if(
						JSON.stringify(old) === JSON.stringify(this.instances.filter(i => i.id === updated.id)[0]) &&
						JSON.stringify(updated) === JSON.stringify(this.instanceModal)
					) {
						this.editingInstanceChanges = true;
					} else {
						this.editingInstanceChanges = false;
					}
				}
			}
		},

		methods: {
			fetchStats() {
				axios.get('/i/admin/api/instances/stats')
				.then(res => {
					this.stats = res.data;
				})
			},

			fetchInstances(url = '/i/admin/api/instances/get') {
				axios.get(url)
				.then(res => {
					this.instances = res.data.data;
					this.pagination = {...res.data.links, ...res.data.meta};
				})
				.then(() => {
					this.$nextTick(() => {
						this.loaded = true;
					})
				})
			},

			toggleTab(idx) {
				this.loaded = false;
				this.tabIndex = idx;
				this.searchQuery = undefined;
				let url = '/i/admin/api/instances/get?filter=' + this.filterMap[idx];
				history.pushState(null, '', '/i/admin/instances?filter=' + this.filterMap[idx]);
				this.fetchInstances(url);
			},

			prettyCount(str) {
				if(str) {
				   return str.toLocaleString('en-CA', { compactDisplay: "short", notation: "compact"});
				} else {
					return 0;
				}
				return str;
			},

			formatCount(str) {
				if(str) {
				   return str.toLocaleString('en-CA');
				} else {
					return 0;
				}
				return str;
			},

			timeAgo(str) {
				if(!str) {
					return str;
				}
				return App.util.format.timeAgo(str);
			},

			boolIcon(val, success = 'text-success', danger = 'text-muted') {
				if(val) {
					return `<i class="far fa-check-circle fa-lg ${success}"></i>`;
				}

				return `<i class="far fa-times-circle fa-lg ${danger}"></i>`;
			},

			toggleCol(col) {
				// this.sortCol = col;

				// if(!this.sortDir) {
				//     this.sortDir = 'desc';
				// } else {
				//     this.sortDir = this.sortDir == 'asc' ? 'desc' : 'asc';
				// }

				// let url = '/i/admin/api/hashtags/query?sort=' + col + '&dir=' + this.sortDir;
				// this.fetchHashtags(url);
			},

			buildColumn(name, col) {
				let icon = `<i class="far fa-sort"></i>`;
				if(col == this.sortCol) {
					icon = this.sortDir == 'desc' ?
					`<i class="far fa-sort-up"></i>` :
					`<i class="far fa-sort-down"></i>`
				}
				return `${name} ${icon}`;
			},

			paginate(dir) {
				event.currentTarget.blur();
				let apiUrl = dir == 'next' ? this.pagination.next : this.pagination.prev;
				let cursor = dir == 'next' ? this.pagination.next_cursor : this.pagination.prev_cursor;
				let url = '/i/admin/instances?';
				if(this.tabIndex && !this.searchQuery) {
					url = url + 'filter=' + this.filterMap[this.tabIndex] + '&';
				}
				if(cursor) {
					url = url + 'cursor=' + cursor;
				}
				if(this.searchQuery) {
					url = url + '&q=' + this.searchQuery;
				}
				history.pushState(null, '', url);
				this.fetchInstances(apiUrl);
			},

			composeSearch(input) {
				if (input.length < 1) { return []; };
				this.searchQuery = input;
				history.pushState(null, '', '/i/admin/instances?q=' + input);
				return axios.get('/i/admin/api/instances/query', {
					params: {
						q: input,
					}
				}).then(res => {
					if(!res || !res.data) {
						this.fetchInstances();
					} else {
						this.tabIndex = -1;
						this.instances = res.data.data;
						this.pagination = {...res.data.links, ...res.data.meta};
					}
					return res.data.data;
				});
			},

			getTagResultValue(result) {
				return result.name;
			},

			onSearchResultClick(result) {
				this.openInstanceModal(result.id);
				return;
			},

			openInstanceModal(id) {
				const cached = this.instances.filter(i => i.id === id)[0];
				this.refreshedModalStats = false;
				this.editingInstanceChanges = false;
				this.instanceModalNotes = false;
				this.canEditInstance = false;
				this.instanceModal = cached;
				this.$nextTick(() => {
					this.editingInstance = cached;
					this.showInstanceModal = true;
					this.canEditInstance = true;
				})
			},

			showModalNotes() {
				this.instanceModalNotes = true;
			},

			saveInstanceModalChanges() {
				axios.post('/i/admin/api/instances/update', this.editingInstance)
				.then(res => {
					this.showInstanceModal = false;
					this.$bvToast.toast(`Successfully updated ${res.data.data.domain}`, {
						title: 'Instance Updated',
						autoHideDelay: 5000,
						appendToast: true,
						variant: 'success'
					})
				})
			},

			saveNewInstance() {
				axios.post('/i/admin/api/instances/create', this.addNewInstance)
				.then(res => {
					this.showInstanceModal = false;
					this.instances.unshift(res.data.data);
				})
				.catch(err => {
					swal('Oops!', 'An error occured, please try again later.', 'error');
					this.addNewInstance = {
						domain: "",
						banned: false,
						auto_cw: false,
						unlisted: false,
						notes: undefined
					}
				})
			},

			refreshModalStats() {
				axios.post('/i/admin/api/instances/refresh-stats', {
					id: this.instanceModal.id
				})
				.then(res => {
					this.refreshedModalStats = true;
					this.instanceModal = res.data.data;
					this.editingInstance = res.data.data;
					this.instances = this.instances.map(i => {
						if(i.id === res.data.data.id) {
							return res.data.data;
						}
						return i;
					})
				})
			},

			deleteInstanceModal() {
				if(!window.confirm('Are you sure you want to delete this instance? This will not delete posts or profiles from this instance.')) {
					return;
				}
				axios.post('/i/admin/api/instances/delete', {
					id: this.instanceModal.id
				})
				.then(res => {
					this.showInstanceModal = false;
					this.instances = this.instances.filter(i => i.id != this.instanceModal.id);
				})
			}
		}
	}
</script>

<style lang="scss" scoped>
	.gap-2 {
		gap: 1rem;
	}
</style>
