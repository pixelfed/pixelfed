<template>
	<div class="group-members-component">
		<div class="row justify-content-center">
			<div class="col-12 col-md-8 mb-5">
				<div v-if="isAdmin && requestCount && !hideHeader" class="card card-body border shadow-sm bg-dark text-light mb-4 rounded-pill p-2 pl-3">
					<div class="d-flex align-items-center justify-content-between">
						<span class="lead mb-0 text-lighter">
							<i class="fal fa-exclamation-triangle mr-2 text-warning"></i>
							You have <strong class="text-white">{{ requestCount }}</strong> member applications to review
						</span>
						<span>
							<button class="btn btn-primary font-weight-bold rounded-pill btn-sm px-3" @click="reviewApplicants()">Review</button>
						</span>
					</div>
				</div>

				<div class="card card-body border shadow-sm">
					<div v-if="!hideHeader">
						<p class="d-flex align-items-center mb-0">
							<span class="lead font-weight-bold">Members</span>
							<span class="mx-2">·</span>
							<span class="text-muted">{{group.member_count}}</span>
						</p>
						<!-- <p class="text-muted mb-0">
							New people who join this group will appear here. <a class="font-weight-bold text-dark" href="#">Learn More</a>
						</p> -->
						<div class="form-group mt-3" style="position: relative;">
							<i class="fas fa-search fa-lg text-lighter" style="position: absolute;left:20px; top:50%;transform:translateY(-50%);"></i>
							<input class="form-control form-control-lg bg-light border rounded-pill" style="padding-left: 50px;padding-right: 50px;" placeholder="Find a member" v-model="memberSearchModel">
							<button class="btn btn-primary font-weight-bold rounded-pill px-3" style="position: absolute;right: 6px; top: 50%;transform: translateY(-50%);">Search</button>
						</div>

						<hr>
					</div>

					<div v-if="tab == 'list'">
						<div class="group-members-component-paginated-list py-3">
							<div class="media align-items-center">
								<a :href="profile.url" class="text-dark text-decoration-none">
									<img
                                        :src="profile?.avatar"
                                        width="56"
                                        height="56"
                                        class="rounded-circle border mr-2"
                                        onerror="this.onerror=null;this.src='/storage/avatars/default.png?v=2'"
                                    />
								</a>
								<div class="media-body">
									<p class="lead font-weight-bold mb-0">
										{{profile.username}}
										<span class="member-label rounded ml-1">Me</span>
									</p>
									<p class="text-muted mb-0">Founded group {{formatDate(group.created_at)}}</p>
								</div>
								<!-- <button class="btn btn-light border">
									<i class="fas fa-ellipsis-h text-muted"></i>
								</button> -->
							</div>
						</div>

						<hr v-if="mutual.length > 0">

						<div v-if="mutual.length > 0" class="group-members-component-paginated-list">
							<p class="font-weight-bold mb-1">Mutual Friends</p>

							<div v-for="(member, index) in mutual" class="media align-items-center py-3">
								<a :href="member.url" class="text-dark text-decoration-none">
                                    <img
                                        :src="member?.avatar"
                                        width="56"
                                        height="56"
                                        class="rounded-circle border mr-2"
                                        onerror="this.onerror=null;this.src='/storage/avatars/default.png?v=2'"
                                    />
								</a>

								<div class="media-body">
									<p class="lead font-weight-bold mb-0">
										<a :href="member.url" class="text-dark text-decoration-none" :title="member.acct" data-toggle="tooltip">{{member.username}}</a>
										<span v-if="member.role == 'founder'" class="member-label rounded ml-1">Admin</span>
										<span v-if="!member.local" class="remote-label rounded ml-1">Remote</span>
									</p>
									<p class="text-muted mb-0">Member since {{formatDate(member.joined)}}</p>
								</div>
								<a
									class="btn btn-light lead font-weight-bolder px-3 border"
									:href="'/account/direct/t/' + member.id">
									<i class="far fa-comment-dots mr-1"></i> Message
								</a>
								<b-dropdown
									v-if="isAdmin"
									toggle-class="btn btn-light font-weight-bold px-3 border ml-2"
									toggle-tag="a"
									:lazy="true"
									right
									no-caret>
									<template #button-content>
										<i class="fas fa-ellipsis-h"></i>
									</template>
									<b-dropdown-item :href="member.url">View Profile</b-dropdown-item>
									<b-dropdown-item :href="'/account/direct/t/'+member.id">Send Message</b-dropdown-item>
									<b-dropdown-item>View Activity</b-dropdown-item>
									<b-dropdown-divider></b-dropdown-divider>
									<b-dropdown-item link-class="font-weight-bold" @click.prevent="openInteractionLimitModal(member)">Limit interactions</b-dropdown-item>
									<b-dropdown-item link-class="font-weight-bold text-danger">Remove from group</b-dropdown-item>
								</b-dropdown>
							</div>
						</div>

						<hr v-if="members.length > 0">

						<div v-if="members.length > 0" class="group-members-component-paginated-list">
							<p class="font-weight-bold mb-1">Other Members</p>

							<div v-for="(member, index) in members" class="media align-items-center py-3">
								<a :href="member.url" class="text-dark text-decoration-none">
                                    <img
                                        :src="member?.avatar"
                                        width="56"
                                        height="56"
                                        class="rounded-circle border mr-2"
                                        onerror="this.onerror=null;this.src='/storage/avatars/default.png?v=2'"
                                    />
								</a>

								<div class="media-body">
									<p class="lead font-weight-bold mb-0">
										<a :href="member.url" class="text-dark text-decoration-none" :title="member.acct" data-toggle="tooltip">{{member.username}}</a>
										<span v-if="member.is_admin" class="member-label rounded ml-1">Admin</span>
										<span v-if="!member.local" class="remote-label rounded ml-1">Remote</span>
									</p>
									<p class="text-muted mb-0">Member since {{formatDate(member.joined)}}</p>
								</div>
								<button
									:class="[ member.following ? 'btn-primary' : 'btn-light']"
									class="btn lead font-weight-bolder px-4 border"
									@click="follow(index)">
									<span v-if="member.following">
										Following
									</span>
									<span v-else>
										<i class="fas fa-user-plus mr-2"></i> Follow
									</span>
								</button>
								<b-dropdown
									v-if="isAdmin"
									toggle-class="btn btn-light font-weight-bold px-3 border ml-2"
									toggle-tag="a"
									:lazy="true"
									right
									no-caret>
									<template #button-content>
										<i class="fas fa-ellipsis-h"></i>
									</template>
									<b-dropdown-item :href="member.url" link-class="font-weight-bold">View Profile</b-dropdown-item>
									<b-dropdown-item :href="'/account/direct/t/'+member.id" link-class="font-weight-bold">Send Message</b-dropdown-item>
									<b-dropdown-item link-class="font-weight-bold">View Activity</b-dropdown-item>
									<b-dropdown-divider></b-dropdown-divider>
									<b-dropdown-item link-class="font-weight-bold" @click.prevent="openInteractionLimitModal(member)">Limit interactions</b-dropdown-item>
									<b-dropdown-item link-class="font-weight-bold text-danger">Remove from group</b-dropdown-item>
								</b-dropdown>
							</div>
						</div>

						<p v-if="members.length > 0 && hasNextPage" class="mt-4">
							<button class="btn btn-light btn-block border font-weight-bold" @click="loadNextPage">Load more</button>
						</p>
					</div>

					<div v-if="tab == 'search'" class="d-flex justify-content-center align-items-center" style="min-height: 100px;">
						<div class="text-center text-muted">
							<div class="spinner-border" role="status">
								<span class="sr-only">Loading...</span>
							</div>
							<p class="lead mb-0 mt-2">Loading results ...</p>
						</div>
					</div>

					<div v-if="tab == 'results'">
						<div v-if="results.length > 0" class="group-members-component-paginated-list">
							<p class="font-weight-bold mb-1">Results</p>

							<div v-for="(member, index) in results" class="media align-items-center py-3">
								<a :href="member.url" class="text-dark text-decoration-none">
                                    <img
                                        :src="member?.avatar"
                                        width="56"
                                        height="56"
                                        class="rounded-circle border mr-2"
                                        onerror="this.onerror=null;this.src='/storage/avatars/default.png?v=2'"
                                    />
								</a>

								<div class="media-body">
									<p class="lead font-weight-bold mb-0">
										<a :href="member.url" class="text-dark text-decoration-none" :title="member.acct" data-toggle="tooltip">{{member.username}}</a>
										<span v-if="member.role == 'founder'" class="member-label rounded ml-1">Admin</span>
										<span v-if="!member.local" class="remote-label rounded ml-1">Remote</span>
									</p>
									<p class="text-muted mb-0">Member since {{formatDate(member.joined)}}</p>
								</div>
								<a
									class="btn btn-light lead font-weight-bolder px-3 border"
									:href="'/account/direct/t/' + member.id">
									<i class="far fa-comment-dots mr-1"></i> Message
								</a>
								<b-dropdown
									v-if="isAdmin"
									toggle-class="btn btn-light font-weight-bold px-3 border ml-2"
									toggle-tag="a"
									:lazy="true"
									right
									no-caret>
									<template #button-content>
										<i class="fas fa-ellipsis-h"></i>
									</template>
									<b-dropdown-item :href="member.url">View Profile</b-dropdown-item>
									<b-dropdown-item :href="'/account/direct/t/'+member.id">Send Message</b-dropdown-item>
									<b-dropdown-item>View Activity</b-dropdown-item>
									<b-dropdown-divider></b-dropdown-divider>
									<b-dropdown-item link-class="font-weight-bold" @click.prevent="openInteractionLimitModal(member)">Limit interactions</b-dropdown-item>
									<b-dropdown-item link-class="font-weight-bold text-danger">Remove from group</b-dropdown-item>
								</b-dropdown>
							</div>
							<p class="text-center mt-5">
								<a href="#" class="font-weight-bold" @click="backFromSearch">Go back</a>
							</p>
						</div>
						<div v-else class="text-center text-muted mt-3">
							<p class="lead">No results found.</p>
							<p>
								<a href="#" class="font-weight-bold" @click="backFromSearch">Go back</a>
							</p>
						</div>
					</div>

					<div v-if="tab == 'memberInteractionLimits'">
						<div v-if="results.length > 0" class="group-members-component-paginated-list">
							<p class="font-weight-bold mb-1">Interaction Limits</p>

							<div v-for="(member, index) in results" class="media align-items-center py-3">
								<a :href="member.url" class="text-dark text-decoration-none">
                                    <img
                                        :src="member?.avatar"
                                        width="56"
                                        height="56"
                                        class="rounded-circle border mr-2"
                                        onerror="this.onerror=null;this.src='/storage/avatars/default.png?v=2'"
                                    />
								</a>

								<div class="media-body">
									<p class="lead font-weight-bold mb-0">
										<a :href="member.url" class="text-dark text-decoration-none">{{member.username}}</a>
										<span v-if="member.role == 'founder'" class="member-label rounded ml-1">Admin</span>
									</p>
									<p class="text-muted mb-0">Member since {{formatDate(member.joined)}}</p>
								</div>
								<a
									class="btn btn-light lead font-weight-bolder px-3 border"
									:href="'/account/direct/t/' + member.id">
									<i class="far fa-comment-dots mr-1"></i> Message
								</a>
								<b-dropdown
									v-if="isAdmin"
									toggle-class="btn btn-light font-weight-bold px-3 border ml-2"
									toggle-tag="a"
									:lazy="true"
									right
									no-caret>
									<template #button-content>
										<i class="fas fa-ellipsis-h"></i>
									</template>
									<b-dropdown-item :href="member.url">View Profile</b-dropdown-item>
									<b-dropdown-item :href="'/account/direct/t/'+member.id">Send Message</b-dropdown-item>
									<b-dropdown-item>View Activity</b-dropdown-item>
									<b-dropdown-divider></b-dropdown-divider>
									<b-dropdown-item link-class="font-weight-bold" @click.prevent="openInteractionLimitModal(member)">Limit interactions</b-dropdown-item>
									<b-dropdown-item link-class="font-weight-bold text-danger">Remove from group</b-dropdown-item>
								</b-dropdown>
							</div>
							<p class="text-center mt-5">
								<a href="#" class="font-weight-bold" @click.prevent="backFromSearch">Go back</a>
							</p>
						</div>
						<div v-else class="text-center text-muted mt-3">
							<p class="lead">No results found.</p>
							<p>
								<a href="#" class="font-weight-bold" @click.prevent="backFromSearch">Go back</a>
							</p>
						</div>
					</div>

					<div v-if="tab == 'review'">
						<div v-if="reviewsLoaded">

							<div class="group-members-component-paginated-list">
								<div class="d-flex justify-content-between align-items-center">
									<div class="d-flex">
										<button class="btn btn-link btn-sm mr-2" @click="backFromReview">
											<i class="far fa-chevron-left fa-lg"></i>
										</button>

										<p class="font-weight-bold mb-0">Review Membership Applicants</p>
									</div>
								</div>
								<hr>

								<div v-for="(member, index) in applicants" class="media align-items-center py-3">
									<a :href="member.url" class="text-dark text-decoration-none">
                                        <img
                                            :src="member?.avatar"
                                            width="56"
                                            height="56"
                                            class="rounded-circle border mr-2"
                                            onerror="this.onerror=null;this.src='/storage/avatars/default.png?v=2'"
                                        />
									</a>

									<div class="media-body">
										<p class="lead font-weight-bold mb-0">
											<a :href="member.url" class="text-dark text-decoration-none" :title="member.acct" data-toggle="tooltip">{{member.username}}</a>
											<span v-if="!member.local" class="remote-label rounded ml-1">Remote</span>
										</p>
										<p class="text-muted mb-0 small">
											<span>
												{{ member.followers_count }} Followers
											</span>

											<span class="mx-1">·</span>

											<span>
												Joined {{formatDate(member.created_at)}}
											</span>
										</p>
									</div>

									<button
										type="button"
										class="btn btn-light lead font-weight-bolder px-3 border"
										@click="handleApplicant(index, 'ignore')">
										<i class="far fa-times mr-1"></i> Ignore
									</button>

									<button
										type="button"
										class="btn btn-danger lead font-weight-bolder px-3 border"
										@click="handleApplicant(index, 'reject')">
										<i class="far fa-times mr-1"></i> Reject
									</button>

									<button
										type="button"
										class="btn btn-primary lead font-weight-bolder px-3 border"
										@click="handleApplicant(index, 'approve')">
										<i class="far fa-check mr-1"></i> Approve
									</button>
								</div>

								<button
									v-if="applicantsCanLoadMore"
									class="btn btn-light font-weight-bold btn-block"
									@click="loadMoreApplicants"
									:disabled="loadingMoreApplicants">
									Load More
								</button>

								<div v-if="!applicants || !applicants.length">
									<p class="text-center lead mb-0">No content found</p>
									<p class="text-center mb-0">
										<a class="font-weight-bold" href="#" @click.prevent="backFromReview">Go back</a>
									</p>
								</div>
							</div>

						</div>

						<div v-else class="d-flex align-items-center justify-content-center" style="min-height: 100px;">
							<b-spinner variant="muted"  />
						</div>
					</div>

					<div v-if="tab == 'loading'" class="d-flex align-items-center justify-content-center" style="min-height: 100px;">
						<b-spinner variant="muted"  />
					</div>
				</div>
			</div>
		</div>

		<group-interaction-limits-modal
			ref="interactionModal"
			:group="group"
			:profile="activeProfile"
			/>

	</div>
</template>

<script type="text/javascript">
	import InteractionModal from './MemberLimitInteractionsModal.vue';

	export default {
		props: {
			group: {
				type: Object
			},

			profile: {
				type: Object
			},

			requestCount: {
				type: Number
			},

			isAdmin: {
				type: Boolean
			}
		},

		components: {
			'group-interaction-limits-modal': InteractionModal
		},

		data() {
			return {
				members: [],
				mutual: [],
				results: [],
				page: 1,
				hasNextPage: true,
				tab: 'list',
				memberSearchModel: null,
				activeProfile: undefined,
				hideHeader: false,
				reviewsLoaded: false,
				applicants: [],
				applicantsPage: 1,
				applicantsCanLoadMore: false,
				loadingMoreApplicants: false
			}
		},

		mounted() {
			this.fetchMembers();
			let u = new URLSearchParams(window.location.search);

			if(this.group.self.role == 'founder') {
				this.isAdmin = true;

				if(u.has('a')) {
					if(u.get('a') == 'il') {
						this.tab = 'loading';
						let pid = u.get('pid');
						axios.get('/api/v0/groups/members/get', {
							params: {
								gid: this.group.id,
								pid: pid
							}
						})
						.then(res => {
							this.results.push(res.data);
							this.tab = 'memberInteractionLimits';
							this.openInteractionLimitModal(res.data);
						});
					}

					if(u.get('a') == 'review') {
						this.reviewApplicants();
					}
				}
			} else if (u.has('a')) {
				history.pushState(null, null, `/groups/${this.group.id}/members`);
			}

		},

		watch: {
			memberSearchModel: function(val) {
				this.handleSearch();
			}
		},

		methods: {
			fetchMembers() {
				axios.get('/api/v0/groups/members/list', {
					params: {
						gid: this.group.id,
						page: this.page
					}
				}).then(res => {
					let data = res.data.filter(m => {
						return m.id != this.profile.id;
					});
					this.members = data.filter(m => {
						return !m.following
					});
					this.mutual = data.filter(m => {
						return m.following
					});
					this.page++;
					this.$nextTick(() => {
						$('[data-toggle="tooltip"]').tooltip()
					});
				}).catch(err => {
					console.log(res.response);
				})
			},

			loadNextPage() {
				axios.get('/api/v0/groups/members/list', {
					params: {
						gid: this.group.id,
						page: this.page
					}
				}).then(res => {
					if(res.data.length == 0) {
						this.hasNextPage = false;
						return;
					}

					let data = res.data.filter(m => {
						return m.id != this.profile.id;
					});
					this.members.push(...data.filter(m => {
						return !m.following
					}));
					this.mutual.push(...data.filter(m => {
						return m.following
					}));
					this.page++;
					this.$nextTick(() => {
						$('[data-toggle="tooltip"]').tooltip()
					});
				}).catch(err => {
					console.log(err.response);
				})
			},

			follow(index) {
				axios.post('/i/follow', {
					item: this.members[index].id
				}).then(res => {
					this.members[index].following = !this.members[index].following;
				}).catch(err => {
					console.log(err.response);
				})
			},

			formatDate(ts) {
				return new Date(ts).toDateString();
			},

			handleSearch() {
				if(!this.memberSearchModel || this.memberSearchModel == "" || this.memberSearchModel.length == 0) {
					this.tab == 'list';
					this.memberSearchModel = null;
					return;
				}

				this.tab = 'search';
				this.results = this.members.concat(this.mutual).filter(profile => {
					return profile.username.includes(this.memberSearchModel);
				});

				// if(this.results.length) {
					this.tab = 'results';
				// }
			},

			backFromSearch() {
				event.currentTarget.blur();
				this.memberSearchModel = null;
				this.tab = 'list';
				history.pushState(null, null, `/groups/${this.group.id}/members`);
			},

			openInteractionLimitModal(member) {
				this.activeProfile = member;
				setTimeout(() => {
					this.$refs.interactionModal.open();
				}, 500);
			},

			reviewApplicants() {
				this.hideHeader = true;
				this.tab = 'review';
				history.pushState(null, null, `/groups/${this.group.id}/members?a=review`);

				axios.get('/api/v0/groups/members/requests', {
					params: {
						gid: this.group.id
					}
				})
				.then(res => {
					this.applicants = res.data;
					this.reviewsLoaded = true;
					this.applicantsPage = 2;
					this.applicantsCanLoadMore = res.data.length == 10;
				})
			},

			handleApplicant(index, action) {
				event.currentTarget.blur();

				if(action == 'ignore') {
					this.applicants.splice(index, 1);
					return;
				}

				this.tab = 'loading';

				if(!window.confirm('Are you sure you want to perform this action?')) {
					return;
				}

				axios.post('/api/v0/groups/members/request', {
					gid: this.group.id,
					pid: this.applicants[index].id,
					action: action
				})
				.then(res => {
					this.applicants.splice(index, 1);
					this.tab = 'review';
					this.$emit('decrementrc');
					if(action == 'approve') {
						this.$emit('incrementMemberCount');
					}
				})
			},

			backFromReview() {
				event.currentTarget.blur();
				this.memberSearchModel = null;
				this.tab = 'list';
				this.hideHeader = false;
				history.pushState(null, null, `/groups/${this.group.id}/members`);
			},

			loadMoreApplicants() {
				this.loadingMoreApplicants = true;

				axios.get('/api/v0/groups/members/requests', {
					params: {
						gid: this.group.id,
						page: this.applicantsPage
					}
				})
				.then(res => {
					this.applicants.push(...res.data);
					this.applicantsCanLoadMore = res.data.length == 10;
					this.applicantsPage++;
					this.loadingMoreApplicants = false;
				})
			}
		}
	}
</script>

<style lang="scss">
	.group-members-component {
		min-height: 100vh;

		.member-label {
			padding: 2px 5px;
			font-size: 12px;
			color: rgba(75, 119, 190, 1);
			background: rgba(137, 196, 244, 0.2);
			border: 1px solid rgba(137, 196, 244, 0.3);
			font-weight: 400;
			text-transform: capitalize;
		}

		.remote-label {
			padding: 2px 5px;
			font-size: 12px;
			color: #B45309;
			background: #FEF3C7;
			border: 1px solid #FCD34D;
			font-weight: 400;
			text-transform: capitalize;
		}
	}
</style>
