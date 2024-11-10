<template>
	<div class="group-topics-component">
		<div class="row justify-content-center">
			<div class="col-12 col-md-8">
				<div class="card card-body border shadow-sm">
					<div class="d-flex justify-content-between align-items-center mb-3">
						<p class="h4 font-weight-bold mb-0">Group Topics</p>
						<select class="form-control bg-light rounded-lg border font-weight-bold py-2" style="width:95px;" disabled>
							<option>All</option>
							<option>Pinned</option>
						</select>
					</div>

					<div v-if="feed.length">
						<div v-for="(tag, index) in feed" class="">
							<div class="media py-2">
								<i class="fas fa-hashtag fa-lg text-lighter mr-3 mt-2"></i>
								<div :class="{ 'border-bottom': index != feed.length - 1 }" class="media-body">
									<a :href="tag.url" class="font-weight-bold mb-1 text-dark" style="font-size: 16px;">
										{{ tag.name }}
									</a>
									<p style="font-size: 13px;" class="text-muted">{{ tag.count }} posts in this group</p>
								</div>
							</div>
						</div>
					</div>

					<div v-else class="py-5">
						<p class="lead text-center font-weight-bold">No topics found</p>
					</div>
				</div>
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
				feed: []
			}
		},

		mounted() {
			this.fetchTopics();
		},

		methods: {
			fetchTopics() {
				axios.get('/api/v0/groups/topics/list', {
					params: {
						gid: this.group.id
					}
				}).then(res => {
					this.feed = res.data;
				})
			}
		}
	}
</script>

<style lang="scss">
	.group-topics-component {
		margin-bottom: 50vh;
	}
</style>
