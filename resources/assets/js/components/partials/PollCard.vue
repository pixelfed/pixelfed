<template>
<div>
	<div class="card shadow-none rounded-0" :class="{ border: showBorder, 'border-top-0': !showBorderTop}">
		<div class="card-body">
			<div class="media">
				<img class="rounded-circle box-shadow mr-2" :src="status.account.avatar" width="32px" height="32px" alt="avatar">
				<div class="media-body">
					<div class="pl-2 d-flex align-items-top">
						<a class="username font-weight-bold text-dark text-decoration-none text-break" href="#">
							{{status.account.acct}}
						</a>
						<span class="px-1 text-lighter">
							·
						</span>
						<a class="font-weight-bold text-lighter" :href="statusUrl(status)">
							{{shortTimestamp(status.created_at)}}
						</a>
						<span class="d-none d-md-block px-1 text-lighter">
							·
						</span>
						<span class="d-none d-md-block px-1 text-primary font-weight-bold">
							<i class="fas fa-poll-h"></i> Poll <sup class="text-lighter">BETA</sup>
						</span>
						<span class="d-none d-md-block px-1 text-lighter">
							·
						</span>
						<span class="d-none d-md-block px-1 text-lighter font-weight-bold">
							<span v-if="status.poll.expired">
								Closed
							</span>
							<span v-else>
								Closes in {{ shortTimestampAhead(status.poll.expires_at) }}
							</span>
						</span>
						<span class="text-right" style="flex-grow:1;">
							<button class="btn btn-link text-dark py-0" type="button" @click="ctxMenu()">
								<span class="fas fa-ellipsis-h text-lighter"></span>
								<span class="sr-only">Post Menu</span>
							</button>
						</span>
					</div>
					<div class="pl-2">
						<div class="poll py-3">

							<div class="pt-2 text-break d-flex align-items-center mb-3" style="font-size: 17px;">
								<span class="btn btn-primary px-2 py-1">
									<i class="fas fa-poll-h fa-lg"></i>
								</span>

								<span class="font-weight-bold ml-3" v-html="status.content"></span>
							</div>

							<div class="mb-2">
								<div v-if="tab === 'vote'">
									<p v-for="(option, index) in status.poll.options">
										<button
											class="btn btn-block lead rounded-pill"
											:class="[ index == selectedIndex ? 'btn-primary' : 'btn-outline-primary' ]"
											@click="selectOption(index)"
											:disabled="!authenticated">
											{{ option.title }}
										</button>
									</p>

									<p v-if="selectedIndex != null" class="text-right">
										<button class="btn btn-primary btn-sm font-weight-bold px-3" @click="submitVote()">Vote</button>
									</p>
								</div>
								<div v-else-if="tab === 'voted'">
									<div v-for="(option, index) in status.poll.options" class="mb-3">
										<button
											class="btn btn-block lead rounded-pill"
											:class="[ index == selectedIndex ? 'btn-primary' : 'btn-outline-secondary' ]"
											disabled>
											{{ option.title }}
										</button>
										<div class="font-weight-bold">
											<span class="text-muted">{{ calculatePercentage(option) }}%</span>
											<span class="small text-lighter">({{option.votes_count}} {{option.votes_count == 1 ? 'vote' : 'votes'}})</span>
										</div>
									</div>
								</div>
								<div v-else-if="tab === 'results'">
									<div v-for="(option, index) in status.poll.options" class="mb-3">
										<button
											class="btn btn-outline-secondary btn-block lead rounded-pill"
											disabled>
											{{ option.title }}
										</button>
										<div class="font-weight-bold">
											<span class="text-muted">{{ calculatePercentage(option) }}%</span>
											<span class="small text-lighter">({{option.votes_count}} {{option.votes_count == 1 ? 'vote' : 'votes'}})</span>
										</div>
									</div>
								</div>
							</div>

							<div>
								<p class="mb-0 small text-lighter font-weight-bold d-flex justify-content-between">
									<span>{{ status.poll.votes_count }} votes</span>
									<a v-if="tab != 'results' && authenticated && !activeRefreshTimeout & status.poll.expired != true && status.poll.voted" class="text-lighter" @click.prevent="refreshResults()" href="#">Refresh Results</a>
									<span v-if="tab != 'results' && authenticated && refreshingResults" class="text-lighter">
										<div class="spinner-border spinner-border-sm" role="status">
											<span class="sr-only">Loading...</span>
										</div>
									</span>
								</p>
							</div>
							<div>
								<span class="d-block d-md-none small text-lighter font-weight-bold">
									<span v-if="status.poll.expired">
										Closed
									</span>
									<span v-else>
										Closes in {{ shortTimestampAhead(status.poll.expires_at) }}
									</span>
								</span>
							</div>
						</div>
					</div>
				</div>
			</div>
		</div>
	</div>

	<context-menu
		ref="contextMenu"
		:status="status"
		:profile="profile"
		v-on:status-delete="statusDeleted"
	/>
</div>
</template>

<script type="text/javascript">
	import ContextMenu from './ContextMenu.vue';

	export default {
		props: {
			reactions: {
				type: Object
			},

			status: {
				type: Object
			},

			profile: {
				type: Object
			},

			showBorder: {
				type: Boolean,
				default: true
			},

			showBorderTop: {
				type: Boolean,
				default: false
			},

			fetchState: {
				type: Boolean,
				default: false
			}
		},

		components: {
			"context-menu": ContextMenu
		},

		data() {
			return {
				authenticated: false,
				tab: 'vote',
				selectedIndex: null,
				refreshTimeout: undefined,
				activeRefreshTimeout: false,
				refreshingResults: false
			}
		},

		mounted() {

			if(this.fetchState) {
				axios.get('/api/v1/polls/' + this.status.poll.id)
				.then(res => {
					this.status.poll = res.data;
					if(res.data.voted) {
						this.selectedIndex = res.data.own_votes[0];
						this.tab = 'voted';
					}
					this.status.poll.expired = new Date(this.status.poll.expires_at) < new Date();
					if(this.status.poll.expired) {
						this.tab = this.status.poll.voted ? 'voted' : 'results';
					}
				})
			} else {
				if(this.status.poll.voted) {
					this.tab = 'voted';
				}
				this.status.poll.expired = new Date(this.status.poll.expires_at) < new Date();
				if(this.status.poll.expired) {
					this.tab = this.status.poll.voted ? 'voted' : 'results';
				}
				if(this.status.poll.own_votes.length) {
					this.selectedIndex = this.status.poll.own_votes[0];
				}
			}
			this.authenticated = $('body').hasClass('loggedIn');
		},

		methods: {
			selectOption(index) {
				event.currentTarget.blur();
				this.selectedIndex = index;
				// if(this.options[index].selected) {
				// 	this.selectedIndex = null;
				// 	this.options[index].selected = false;
				// 	return;
				// }

				// this.options = this.options.map(o => {
				// 	o.selected = false;
				// 	return o;
				// });

				// this.options[index].selected = true;
				// this.selectedIndex = index;
				// this.options[index].score = 100;
			},

			submitVote() {
				// todo: send vote

				axios.post('/api/v1/polls/'+this.status.poll.id+'/votes', {
					'choices': [
						this.selectedIndex
					]
				}).then(res => {
					console.log(res.data);
					this.status.poll = res.data;
				});
				this.tab = 'voted';
			},

			viewResultsTab() {
				this.tab = 'results';
			},

			viewPollTab() {
				this.tab = this.selectedIndex != null ? 'voted' : 'vote';
			},

			formatCount(count) {
				return App.util.format.count(count);
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

			timestampFormat(timestamp) {
				let ts = new Date(timestamp);
				return ts.toDateString() + ' ' + ts.toLocaleTimeString();
			},

			shortTimestamp(ts) {
				return window.App.util.format.timeAgo(ts);
			},

			shortTimestampAhead(ts) {
				return window.App.util.format.timeAhead(ts);
			},

			refreshResults() {
				this.activeRefreshTimeout = true;
				this.refreshingResults = true;
				axios.get('/api/v1/polls/' + this.status.poll.id)
				.then(res => {
					this.status.poll = res.data;
					if(this.status.poll.voted) {
						this.selectedIndex = this.status.poll.own_votes[0];
						this.tab = 'voted';
						this.setActiveRefreshTimeout();
						this.refreshingResults = false;
					}
				}).catch(err => {
					swal('Oops!', 'An error occured while fetching the latest poll results. Please try again later.', 'error');
					this.setActiveRefreshTimeout();
					this.refreshingResults = false;
				});
			},

			setActiveRefreshTimeout() {
				let self = this;
				this.refreshTimeout = setTimeout(function() {
					self.activeRefreshTimeout = false;
				}, 30000);
			},

			statusDeleted(status) {
				this.$emit('status-delete', status);
			},

			ctxMenu() {
				this.$refs.contextMenu.open();
			},

			likeStatus() {
				this.$emit('likeStatus', this.status);
			},

			calculatePercentage(option) {
				let status = this.status;
				return status.poll.votes_count == 0 ? 0 : Math.round((option.votes_count / status.poll.votes_count) * 100);
			}
		}
	}
</script>
