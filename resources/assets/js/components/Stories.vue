<template>
	<div>
		<div class="card stories-card">
			<div class="card-header bg-white">
				<p class="mb-0 d-flex align-items-center justify-content-between">
					<span class="text-muted font-weight-bold">Stories</span>
					<a class="text-dark small" href="/account/activity">See All</a>
				</p>
			</div>
			<div class="card-body loader text-center" style="height: 120px;">
				<div class="spinner-border" role="status">
					<span class="sr-only">Loading…</span>
				</div>
			</div>
			<div class="card-body pt-2 contents" style="max-height: 120px; overflow-y: scroll;">
				<div id="stories">

				</div>
			</div>
		</div>
	</div>
</template>

<style type="text/css" scoped>

</style>

<script type="text/javascript">
	export default {
		data() {
			return {
				stories: [],
			}
		},

		beforeMount() {
			//this.fetchStories();
		},

		mounted() {

		},

		methods: {
			fetchStories() {
				axios.get('/api/v2/stories')
				.then(res => {
					this.stories = res.data;
					$('.stories-card .loader').hide();
                    const stories = pixelfed.stories.create('stories');
                    stories.update(this.stories);
				});
			}
		}
	}
</script>
