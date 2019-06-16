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

		<div class="col-12 col-md-3 mb-4">
			<div v-if="results.hashtags || results.profiles || results.statuses">
				<p class="font-weight-bold">Filters</p>
				<div class="custom-control custom-checkbox">
					<input type="checkbox" class="custom-control-input" id="filter1" v-model="filters.hashtags">
					<label class="custom-control-label text-muted font-weight-light" for="filter1">Show Hashtags</label>
				</div>
				<div class="custom-control custom-checkbox">
					<input type="checkbox" class="custom-control-input" id="filter2" v-model="filters.profiles">
					<label class="custom-control-label text-muted font-weight-light" for="filter2">Show Profiles</label>
				</div>
				<div class="custom-control custom-checkbox">
					<input type="checkbox" class="custom-control-input" id="filter3" v-model="filters.statuses">
					<label class="custom-control-label text-muted font-weight-light" for="filter3">Show Statuses</label>
				</div>
			</div>
		</div>
		<div class="col-12 col-md-9">
			<p class="h5 font-weight-bold">Showing results for <i>{{query}}</i></p>
			<hr>

			<div v-if="filters.hashtags && results.hashtags" class="row mb-4">
				<p class="col-12 font-weight-bold text-muted">Hashtags</p>
				<a v-for="(hashtag, index) in results.hashtags" class="col-12 col-md-3 mb-3" style="text-decoration: none;" :href="hashtag.url">
					<div class="card card-body text-center">
						<p class="lead mb-0 text-truncate text-dark" data-toggle="tooltip" :title="hashtag.value">
							#{{hashtag.value}}
						</p>
						<p class="lead mb-0 small font-weight-bold text-dark">
							{{hashtag.count}} posts
						</p>
					</div>
				</a>
			</div>

			<div v-if="filters.profiles && results.profiles" class="row mb-4">
				<p class="col-12 font-weight-bold text-muted">Profiles</p>
				<a v-for="(profile, index) in results.profiles" class="col-12 col-md-4 mb-3" style="text-decoration: none;" :href="profile.url">
					<div class="card card-body text-center">
						<p class="text-center">
							<img :src="profile.entity.thumb" width="32px" height="32px" class="rounded-circle box-shadow">
						</p>
						<p class="font-weight-bold text-truncate text-dark">
							{{profile.value}}
						</p>
						<p class="mb-0 text-center">
							 <button :class="[profile.entity.following ? 'btn btn-secondary btn-sm py-1 font-weight-bold' : 'btn btn-primary btn-sm py-1 font-weight-bold']" v-on:click="followProfile(profile.entity.id)">
							 	{{profile.entity.following ? 'Unfollow' : 'Follow'}}
							 </button>
						</p>
					</div>
				</a>
			</div>

			<div v-if="filters.statuses && results.statuses" class="row mb-4">
				<p class="col-12 font-weight-bold text-muted">Statuses</p>
				<a v-for="(status, index) in results.statuses" class="col-12 col-md-4 mb-3" style="text-decoration: none;" :href="status.url">
					<div class="card">
						<img class="card-img-top img-fluid" :src="status.thumb">
						<div class="card-body text-center ">
							<p class="mb-0 small text-truncate font-weight-bold text-muted" v-html="status.value">
							</p>
						</div>
					</div>
				</a>
			</div>

			<div v-if="!results.hashtags && !results.profiles && !results.statuses">
				<p class="text-center lead">No results found!</p>
			</div>

		</div>

	</div>

</div>
</template>

<style type="text/css" scoped>

</style>

<script type="text/javascript">
export default {
	props: ['query', 'profileId'],

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
		$('.search-bar input').val(this.query);
	},
	updated() {

	},
	methods: {
		fetchSearchResults() {
			axios.get('/api/search', {
				params: {
					'q': this.query,
					'src': 'metro',
					'v': 1
				}
			}).then(res => {
				let results = res.data;
				this.results.hashtags = results.hashtags;
				this.results.profiles = results.profiles;
				this.results.statuses = results.posts;
				this.loading = false;
			}).catch(err => {
				this.loading = false;
				// this.networkError = true;
			})
		},

		followProfile(id) {
			// todo: finish AP Accept handling to enable remote follows
			axios.post('/i/follow', {
				item: id
			}).then(res => {
				window.location.href = window.location.href;
			}).catch(err => {
				if(err.response.data.message) {
					swal('Error', err.response.data.message, 'error');
				}
			});
		},
	}

}
</script>
