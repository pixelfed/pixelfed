<template>
<div class="container">
	<div v-if="loading" class="pt-5 text-center">
		<div class="spinner-border" role="status">
			<span class="sr-only">Loading…</span>
		</div>
	</div>
	<div v-if="networkError" class="pt-5 text-center">
		<p class="lead font-weight-lighter">An error occured, results could not be loaded.<br> Please try again later.</p>
	</div>

	<div v-if="!loading && !networkError" class="mt-5 row">

		<div class="col-12 col-md-3">
			<div>
				<p class="font-weight-bold">Filters</p>
				<div class="custom-control custom-checkbox">
					<input type="checkbox" class="custom-control-input" id="filter1" v-model="filters.hashtags">
					<label class="custom-control-label text-muted" for="filter1">Show Hashtags</label>
				</div>
				<div class="custom-control custom-checkbox">
					<input type="checkbox" class="custom-control-input" id="filter2" v-model="filters.profiles">
					<label class="custom-control-label text-muted" for="filter2">Show Profiles</label>
				</div>
				<div class="custom-control custom-checkbox">
					<input type="checkbox" class="custom-control-input" id="filter3" v-model="filters.statuses">
					<label class="custom-control-label text-muted" for="filter3">Show Statuses</label>
				</div>
			</div>
		</div>
		<div class="col-12 col-md-9">
			<p class="h3 font-weight-lighter">Showing results for <i>{{query}}</i></p>
			<hr>

			<div v-if="filters.hashtags && results.hashtags.length" class="row mb-4">
				<p class="col-12 font-weight-bold text-muted">Hashtags</p>
				<a v-for="(hashtag, index) in results.hashtags" class="col-12 col-md-4" style="text-decoration: none;" :href="hashtag.url">
					<div class="card card-body text-center">
						<p class="lead mb-0 text-truncate text-dark">
							#{{hashtag.value}}
						</p>
						<p class="lead mb-0 small font-weight-bold text-dark">
							{{hashtag.count}} posts
						</p>
					</div>
				</a>
			</div>

			<div v-if="filters.profiles && results.profiles.length" class="row mb-4">
				<p class="col-12 font-weight-bold text-muted">Profiles</p>
				<a v-for="(profile, index) in results.profiles" class="col-12 col-md-4" style="text-decoration: none;" :href="profile.url">
					<div class="card card-body text-center border-left-primary">
						<p class="lead mb-0 text-truncate text-dark">
							{{profile.value}}
						</p>
					</div>
				</a>
			</div>

			<div v-if="filters.statuses && results.statuses.length" class="row mb-4">
				<p class="col-12 font-weight-bold text-muted">Statuses</p>
				<a v-for="(status, index) in results.statuses" class="col-12 col-md-4" style="text-decoration: none;" :href="status.url">
					<div class="card card-body text-center border-left-primary">
						<p class="lead mb-0 text-truncate text-dark">
							{{status.value}}
						</p>
					</div>
				</a>
			</div>

		</div>

	</div>

</div>
</template>

<style type="text/css" scoped>

</style>

<script type="text/javascript">
export default {
	props: ['query'],

	data() {
		return {
			loading: true,
			networkError: false,
			results: {
				hashtags: [],
				profiles: [],
				statuses: []
			},
			filters: {
				hashtags: true,
				profiles: true,
				statuses: true
			}
		}
	},
	beforeMount() {
		this.fetchSearchResults();
	},
	mounted() {
		$('.search-form input').val(this.query);
	},
	updated() {

	},
	methods: {
		fetchSearchResults() {
			axios.get('/api/search/' + this.query)
				.then(res => {
					let results = res.data;
					this.results.hashtags = results.filter(i => {
						return i.type == 'hashtag';
					});
					this.results.profiles = results.filter(i => {
						return i.type == 'profile';
					});
					this.results.statuses = results.filter(i => {
						return i.type == 'status';
					});
					this.loading = false;
				}).catch(err => {
					this.loading = false;
					this.networkError = true;
				})
		},
	}

}
</script>
