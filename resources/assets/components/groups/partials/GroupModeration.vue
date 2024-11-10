<template>
	<div class="group-moderation-component">
		<div v-if="initalLoad">
			<div v-if="tab === 'home'">
				<div class="row justify-content-center">
					<div class="col-12 col-md-6 pt-4">
						<div class="d-flex justify-content-between align-items-center mb-3">
							<p class="lead mb-0">Latest Mod Reports</p>
							<button class="btn btn-light border font-weight-bold btn-sm rounded shadow-sm">
								<i class="far fa-redo"></i>
							</button>
						</div>

						<div v-if="reports.length" class="list-group">
							<div v-for="(report, index) in reports" class="list-group-item">
								<div class="media align-items-center">
									<img :src="report.profile.avatar" width="40" height="40" class="rounded-circle mr-3">
									<div class="media-body">
										<p class="mb-0">
											<a v-if="report.total_count == 1" class="font-weight-bold" :href="report.profile.url">{{ report.profile.username }}</a>
											<span v-else>
												<a class="font-weight-bold" :href="report.profile.url">{{ report.profile.username }}</a> and <a class="font-weight-bold" href="#">{{ report.total_count - 1}} others</a>
											</span>
											reported
											<a href="#" class="font-weight-bold" :id="`report_post:${index}`">this post</a>
											as {{ report.type }}
										</p>
										<p class="mb-0 small">
											<span class="text-muted font-weight-bold">
												{{ timeago(report.created_at) }}
											</span>
											<!-- <span>·</span>
											<a class="text-muted font-weight-bold" href="#">
												View Full Report
											</a> -->
											<span>·</span>
											<a class="text-danger font-weight-bold" href="#" @click.prevent="actionMenu(index)">
												Actions
											</a>
										</p>
									</div>

									<!-- <div class="text-muted">
										<button class="btn btn-light btn-sm shadow-sm" @click.prevent="actionMenu(index)">
											<i class="far fa-cog fa-lg text-lighter"></i>
										</button>
									</div> -->

									<b-popover :target="`report_post:${index}`" triggers="hover" placement="bottom" custom-class="popover-wide">
										<template #title>
											<div class="d-flex justify-content-between">
												<span>
													&commat;{{ report.status.account.username }}
												</span>
												<span>
													{{ timeago(report.status.created_at) }}
												</span>
											</div>
										</template>

										<div v-if="report.status.pf_type == 'group:post'">
											<div v-if="report.status.media_attachments.length">
												<img :src="report.status.media_attachments[0].url" width="100%" height="300" style="object-fit:cover;">
											</div>
											<div v-else>
												<p v-html="report.status.content"></p>
											</div>
										</div>
										<div v-else-if="report.status.pf_type == 'reply-text'">
											<p v-html="report.status.content"></p>
										</div>
										<div v-else>
											<p>Cannot generate preview.</p>
										</div>

										<p class="mb-1 mt-3">
											<a class="btn btn-primary btn-block font-weight-bold" :href="report.status.url">View Post</a>
										</p>
									</b-popover>
								</div>
							</div>

							<div v-if="canLoadMore" class="list-group-item">
								<button class="btn btn-light font-weight-bold btn-block" @click.prevent="loadMoreReports()">Load more</button>
							</div>
						</div>
						<div v-else class="card card-body shadow-none border rounded-pill">
							<p class="lead font-weight-bold text-center mb-0">No moderation reports found!</p>
						</div>
					</div>
				</div>
			</div>
		</div>
		<div v-else class="col-12 col-md-6 pt-4 d-flex align-items-center justify-content-center">
			<div class="spinner-border" role="status">
				<span class="sr-only">Loading...</span>
			</div>
		</div>
	</div>
</template>

<script type="text/javascript">
	export default {
		props: {
			group: {
				type: Object
			}
		},

		data() {
			return {
				initalLoad: false,
				reports: [],
				page: 1,
				canLoadMore: false,
				tab: 'home'
			}
		},

		mounted() {
			this.getReports();
		},

		methods: {
			getReports() {
				axios.get(`/api/v0/groups/${this.group.id}/reports/list`)
				.then(res => {
					this.reports = res.data;
					this.initalLoad = true;
					this.page++;
					this.canLoadMore = res.data.length == 10;
				})
			},

			loadMoreReports() {
				axios.get(`/api/v0/groups/${this.group.id}/reports/list`, {
					params: {
						page: this.page
					}
				})
				.then(res => {
					this.reports.push(...res.data);
					this.page++;
					this.canLoadMore = res.data.length == 10;
				})
			},

			timeago(ts) {
				return App.util.format.timeAgo(ts);
			},

			actionMenu(index) {
				event.currentTarget.blur();

				swal({
					title: "Moderator Action",
					dangerMode: true,
					text: "Please select an action to take, press ESC to close",
					buttons: {
						ignore: {
							text: "Ignore",
							className: "btn-warning",
							value: "ignore"
						},
						cw: {
							text: "NSFW",
							className: "btn-warning",
							value: "cw",
						},
						delete: {
							text: "Delete",
							className: "btn-danger",
							value: "delete",
						},
					},
				})
				.then((value) => {
					switch (value) {

						case "ignore":
							axios.post(`/api/v0/groups/${this.group.id}/report/action`, {
								action: 'ignore',
								id: this.reports[index].id
							}).then(res => {
								let report = this.reports[index];
								this.$emit('decrement', report.total_count);
								this.reports.splice(index, 1);
								this.$bvToast.toast(`Ignored and closed moderation report`, {
									title: 'Moderation Action',
									autoHideDelay: 5000,
									appendToast: true
								});
							})
						break;

						case "cw":
							axios.post(`/api/v0/groups/${this.group.id}/report/action`, {
								action: 'cw',
								id: this.reports[index].id
							}).then(res => {
								let report = this.reports[index];
								this.$emit('decrement', report.total_count);
								this.reports.splice(index, 1);
								this.$bvToast.toast(`Succesfully applied content warning and closed moderation report`, {
									title: 'Moderation Action',
									autoHideDelay: 5000,
									appendToast: true
								});
							})
						break;

						case "delete":
							swal('Oops, this is embarassing!', 'We have not implemented this moderation action yet.', 'error');
						break;
					}
				});
			}
		}
	}
</script>

<style lang="scss">
	.group-moderation-component {
		min-height: 80vh;
		margin-bottom: 100px;
	}

	.popover-wide {
		min-width: 200px !important;
	}
</style>
