<template>
	<div>
		<div class="card">
			<div class="card-body" id="stories">
				
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

		},

		mounted() {

		},

		methods: {
			fetchStories() {
				axios.get('/api/v2/stories')
				.then(res => {
					this.stories = res.data
				});
			}
		}
	}
</script>