<template>
<div>
	<div v-if="loaded && page == 'browse'" class="container messages-page p-0 p-md-2 mt-n4" style="min-height: 50vh;">
		<div class="col-12 col-md-8 offset-md-2 p-0 px-md-2">
			<div class="card shadow-none border mt-4">
				<div class="card-header bg-white py-4">
					<span class="h4 font-weight-bold mb-0">Direct Messages</span>
					<span class="float-right">
						<a class="btn btn-outline-primary font-weight-bold py-0 rounded-pill" href="#" @click.prevent="goto('add')">New Message</a>
					</span>
				</div>
				<div class="card-header bg-white">
					<ul class="nav nav-pills nav-fill">
						<li class="nav-item">
							<a :class="[tab == 'inbox' ? 'nav-link px-4 font-weight-bold rounded-pill active' : 'nav-link px-4 font-weight-bold rounded-pill']" @click.prevent="switchTab('inbox')" href="#">Inbox</a>
						</li>
						<li class="nav-item">
							<a :class="[tab == 'sent' ? 'nav-link px-4 font-weight-bold rounded-pill active' : 'nav-link px-4 font-weight-bold rounded-pill']" @click.prevent="switchTab('sent')" href="#">Sent</a>
						</li>
						<li class="nav-item">
							<a :class="[tab == 'filtered' ? 'nav-link px-4 font-weight-bold rounded-pill active' : 'nav-link px-4 font-weight-bold rounded-pill']" @click.prevent="switchTab('filtered')" href="#">Filtered</a>
						</li>
					</ul>
				</div>
				<ul v-if="tab == 'inbox'" class="list-group list-group-flush">
					<div v-if="!messages.inbox.length" class="list-group-item d-flex justify-content-center align-items-center" style="min-height: 40vh;">
						<p class="lead mb-0">No messages found :(</p>
					</div>
					<div v-else v-for="(thread, index) in messages.inbox" :key="'dm_inbox'+index">
						<a class="list-group-item text-dark text-decoration-none border-left-0 border-right-0 border-top-0" :href="'/account/direct/t/'+thread.id">
						<div class="media d-flex align-items-center">
							<img class="mr-3 rounded-circle img-thumbnail" :src="thread.avatar" width="32" onerror="this.onerror=null;this.src='/storage/avatars/default.jpg';" v-once>
							<div class="media-body">
								<p class="mb-0">
									<span class="font-weight-bold text-truncate">
										{{thread.name}}
									</span>
									<span class="pl-1 text-muted small text-truncate" style="font-weight: 500;">
										{{thread.isLocal ? '@' + thread.username : thread.username}}
									</span>
								</p>
								<p class="text-muted mb-0" style="font-size:13px;font-weight: 500;">
									<span>
										<i class="far fa-comment text-primary"></i> 
									</span>
									<span class="pl-1 pr-3">
										Received
									</span>
									<span>
										{{thread.timeAgo}}
									</span>
								</p>
							</div>
							<span class="float-right">
								<i class="fas fa-chevron-right fa-lg text-lighter"></i>
							</span>
						</div>
						</a>
					</div>
				</ul>
				<ul v-if="tab == 'sent'" class="list-group list-group-flush">
					<div v-if="!messages.sent.length" class="list-group-item d-flex justify-content-center align-items-center" style="min-height: 40vh;">
						<p class="lead mb-0">No messages found :(</p>
					</div>
					<div v-else v-for="(thread, index) in messages.sent" :key="'dm_sent'+index">
						<a class="list-group-item text-dark text-decoration-none border-left-0 border-right-0 border-top-0" href="#" @click.prevent="loadMessage(thread.id)">
						<div class="media d-flex align-items-center">
							<img class="mr-3 rounded-circle img-thumbnail" :src="thread.avatar" width="32" onerror="this.onerror=null;this.src='/storage/avatars/default.jpg';" v-once>
							<div class="media-body">
								<p class="mb-0">
									<span class="font-weight-bold text-truncate">
										{{thread.name}}
									</span>
									<span class="pl-1 text-muted small text-truncate" style="font-weight: 500;">
										{{thread.isLocal ? '@' + thread.username : thread.username}}
									</span>
								</p>
								<p class="text-muted mb-0" style="font-size:13px;font-weight: 500;">
									<span>
										<i class="far fa-paper-plane text-primary"></i> 
									</span>
									<span class="pl-1 pr-3">
										Delivered
									</span>
									<span>
										{{thread.timeAgo}}
									</span>
								</p>
							</div>
							<span class="float-right">
								<i class="fas fa-chevron-right fa-lg text-lighter"></i>
							</span>
						</div>
						</a>
					</div>
				</ul>
				<ul v-if="tab == 'filtered'" class="list-group list-group-flush">
					<div v-if="!messages.filtered.length" class="list-group-item d-flex justify-content-center align-items-center" style="min-height: 40vh;">
						<p class="lead mb-0">No messages found :(</p>
					</div>
					<div v-else v-for="(thread, index) in messages.filtered" :key="'dm_filtered'+index">
						<a class="list-group-item text-dark text-decoration-none border-left-0 border-right-0 border-top-0" href="#" @click.prevent="loadMessage(thread.id)">
						<div class="media d-flex align-items-center">
							<img class="mr-3 rounded-circle img-thumbnail" :src="thread.avatar" width="32" onerror="this.onerror=null;this.src='/storage/avatars/default.jpg';" v-once>
							<div class="media-body">
								<p class="mb-0">
									<span class="font-weight-bold text-truncate">
										{{thread.name}}
									</span>
									<span class="pl-1 text-muted small text-truncate" style="font-weight: 500;">
										{{thread.isLocal ? '@' + thread.username : thread.username}}
									</span>
								</p>
								<p class="text-muted mb-0" style="font-size:13px;font-weight: 500;">
									<span>
										<i class="fas fa-shield-alt" style="color:#fd9426"></i> 
									</span>
									<span class="pl-1 pr-3">
										Filtered
									</span>
									<span>
										{{thread.timeAgo}}
									</span>
								</p>
							</div>
							<span class="float-right">
								<i class="fas fa-chevron-right fa-lg text-lighter"></i>
							</span>
						</div>
						</a>
					</div>
				</ul>
			</div>
			<div v-if="tab == 'inbox'" class="mt-3 text-center">
				<button class="btn btn-outline-primary rounded-pill btn-sm" :disabled="inboxPage == 1" @click="messagePagination('inbox', 'prev')">Prev</button>
				<button class="btn btn-outline-primary rounded-pill btn-sm" :disabled="messages.inbox.length != 8" @click="messagePagination('inbox', 'next')">Next</button>
			</div>
			<div v-if="tab == 'sent'" class="mt-3 text-center">
				<button class="btn btn-outline-primary rounded-pill btn-sm" :disabled="sentPage == 1" @click="messagePagination('sent', 'prev')">Prev</button>
				<button class="btn btn-outline-primary rounded-pill btn-sm" :disabled="messages.sent.length != 8" @click="messagePagination('sent', 'next')">Next</button>
			</div>
			<div v-if="tab == 'filtered'" class="mt-3 text-center">
				<button class="btn btn-outline-primary rounded-pill btn-sm" :disabled="filteredPage == 1" @click="messagePagination('filtered', 'prev')">Prev</button>
				<button class="btn btn-outline-primary rounded-pill btn-sm" :disabled="messages.filtered.length != 8" @click="messagePagination('filtered', 'next')">Next</button>
			</div>
		</div>
	</div>

	<div v-if="loaded && page == 'add'" class="container messages-page p-0 p-md-2 mt-n4" style="min-height: 60vh;">
		<div class="col-12 col-md-8 offset-md-2 p-0 px-md-2">
			<div class="card shadow-none border mt-4">
				<div class="card-header bg-white py-4 d-flex justify-content-between">
					<span class="cursor-pointer px-3" @click="goto('browse')"><i class="fas fa-chevron-left"></i></span>
					<span class="h4 font-weight-bold mb-0">New Direct Message</span>
					<span><i class="fas fa-chevron-right text-white"></i></span>
				</div>
				<div class="card-body d-flex align-items-center justify-content-center" style="height: 60vh;">
					<div>
						<p class="mb-0 font-weight-bold">Select Recipient</p>
						<autocomplete 
							:search="composeSearch"
							:disabled="composeLoading"
							placeholder="@dansup"
							aria-label="Search usernames"
							:get-result-value="getTagResultValue"
							@submit="onTagSubmitLocation"
							ref="autocomplete"
						>
						</autocomplete>
						<div style="width:300px;"></div>
					</div>
				</div>
			</div>
		</div>
	</div>
</div>
</template>

<style type="text/css" scoped>
</style>

<script type="text/javascript">
import Autocomplete from '@trevoreyre/autocomplete-vue'
import '@trevoreyre/autocomplete-vue/dist/style.css'
export default {
	components: { 
		Autocomplete 
	},
	data() {
		return {
			config: window.App.config,
			loaded: false,
			profile: {},
			page: 'browse',
			pages: ['browse', 'add', 'read'],
			tab: 'inbox',
			tabs: ['inbox', 'sent', 'filtered'],
			inboxPage: 1,
			sentPage: 1,
			filteredPage: 1,
			threads: [],
			thread: false,
			threadIndex: false,

			replyText: '',
			composeUsername: '',

			ctxContext: null,
			ctxIndex: null,

			uploading: false,
			uploadProgress: null,

			messages: {
				inbox: [],
				sent: [],
				filtered: []
			},

			newType: 'select',
			composeLoading: false,
		}
	},

	mounted() {
		this.fetchProfile();
		let self = this;
		axios.get('/api/direct/browse', {
			params: {
				a: 'inbox'
			}
		})
		.then(res => {
			self.loaded = true;
			this.threads = res.data
			this.messages.inbox = res.data;
		});
	},

	updated() {
		$('[data-toggle="tooltip"]').tooltip();
	},

	methods: {
		fetchProfile() {
			axios.get('/api/pixelfed/v1/accounts/verify_credentials').then(res => {
				this.profile = res.data;
				window._sharedData.curUser = res.data;
				window.App.util.navatar();
			});
		},

		goto(l = 'browse') {
			this.page = l;
		},

		loadMessage(id) {
			let url = '/account/direct/t/' + id;
			window.location.href = url;
			return;
		},

		truncate(t) {
			return _.truncate(t);
		},

		switchTab(tab) {
			let self = this;
			switch(tab) {
				case 'inbox':
					if(this.messages.inbox.length == 0) {
						// fetch
					}
				break;
				case 'sent':
				if(this.messages.sent.length == 0) {
					axios.get('/api/direct/browse', {
						params: {
							a: 'sent'
						}
					})
					.then(res => {
						self.loaded = true;
						self.threads = res.data
						self.messages.sent = res.data;
					});
				}
				break;
				case 'filtered':
					if(this.messages.filtered.length == 0) {
						axios.get('/api/direct/browse', {
							params: {
								a: 'filtered'
							}
						})
						.then(res => {
							self.loaded = true;
							self.threads = res.data
							self.messages.filtered = res.data;
						});
					}
				break;
			}
			this.tab = tab;
		},

		composeSearch(input) {
			if (input.length < 1) { return []; };
			let self = this;
			let results = [];
			return axios.post('/api/direct/lookup', {
				q: input
			}).then(res => {
				return res.data;
			});
		},

		getTagResultValue(result) {
			// return '@' + result.name;
			return result.local ? '@' + result.name : result.name;
		},

		onTagSubmitLocation(result) {
			//this.$refs.autocomplete.value = '';
			this.composeLoading = true;
			window.location.href = '/account/direct/t/' + result.id;
			return;
		},

		messagePagination(tab, dir) {
			if(tab == 'inbox') {
				this.inboxPage = dir == 'prev' ? this.inboxPage - 1 : this.inboxPage + 1;
				axios.get('/api/direct/browse', {
					params: {
						a: 'inbox',
						page: this.inboxPage
					}
				})
				.then(res => {
					self.loaded = true;
					this.threads = res.data
					this.messages.inbox = res.data;
				});
			}
			if(tab == 'sent') {
				this.sentPage = dir == 'prev' ? this.sentPage - 1 : this.sentPage + 1;
				axios.get('/api/direct/browse', {
					params: {
						a: 'sent',
						page: this.sentPage
					}
				})
				.then(res => {
					self.loaded = true;
					this.threads = res.data
					this.messages.sent = res.data;
				});
			}
			if(tab == 'filtered') {
				this.filteredPage = dir == 'prev' ? this.filteredPage - 1 : this.filteredPage + 1;
				axios.get('/api/direct/browse', {
					params: {
						a: 'filtered',
						page: this.filteredPage
					}
				})
				.then(res => {
					self.loaded = true;
					this.threads = res.data
					this.messages.filtered = res.data;
				});
			}
		}
	}
}
</script>
