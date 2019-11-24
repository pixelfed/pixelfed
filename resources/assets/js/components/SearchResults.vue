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

		<div class="col-12 col-md-2 mb-4">
			<div v-if="results.hashtags || results.profiles || results.statuses">
				<p class="font-weight-bold">Filters</p>
				<div class="custom-control custom-checkbox">
					<input type="checkbox" class="custom-control-input" id="filter1" v-model="filters.hashtags">
					<label class="custom-control-label text-muted font-weight-light" for="filter1">Hashtags</label>
				</div>
				<div class="custom-control custom-checkbox">
					<input type="checkbox" class="custom-control-input" id="filter2" v-model="filters.profiles">
					<label class="custom-control-label text-muted font-weight-light" for="filter2">Profiles</label>
				</div>
				<div class="custom-control custom-checkbox">
					<input type="checkbox" class="custom-control-input" id="filter3" v-model="filters.statuses">
					<label class="custom-control-label text-muted font-weight-light" for="filter3">Statuses</label>
				</div>
			</div>
		</div>
		<div class="col-12 col-md-10">
			<p class="h5 font-weight-bold">Showing results for <i>{{query}}</i></p>
			<hr>

			<div v-if="filters.hashtags && results.hashtags" class="row mb-4">
				<p class="col-12 font-weight-bold text-muted">Hashtags</p>
				<a v-for="(hashtag, index) in results.hashtags" class="col-12 col-md-3 mb-3" style="text-decoration: none;" :href="hashtag.url">
					<div class="card card-body text-center shadow-none border">
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
					<div class="card card-body text-center shadow-none border">
						<p class="text-center">
							<img :src="profile.entity.thumb" width="32px" height="32px" class="rounded-circle box-shadow">
						</p>
						<p class="font-weight-bold text-truncate text-dark">
							{{profile.value}}
						</p>
						<p class="mb-0 text-center">
							<button v-if="profile.entity.follow_request" type="button" class="btn btn-secondary btn-sm py-1 font-weight-bold" disabled>Follow Requested</button>
							<button v-if="!profile.entity.follow_request && profile.entity.following" type="button" class="btn btn-secondary btn-sm py-1 font-weight-bold" @click.prevent="followProfile(profile, index)">Unfollow</button>
							<button v-if="!profile.entity.follow_request && !profile.entity.following" type="button" class="btn btn-primary btn-sm py-1 font-weight-bold" @click.prevent="followProfile(profile, index)">Follow</button>
						</p>
					</div>
				</a>
			</div>

			<div v-if="filters.statuses && results.statuses" class="row mb-4">
				<p class="col-12 font-weight-bold text-muted">Statuses</p>
				<div v-for="(status, index) in results.statuses" class="col-4 p-0 p-sm-2 p-md-3 hashtag-post-square">
					<a class="card info-overlay card-md-border-0" :href="status.url">
						<div :class="[status.filter ? 'square ' + status.filter : 'square']">
							<div class="square-content" :style="'background-image: url('+status.thumb+')'"></div>
						</div>
					</a>
				</div>
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

		followProfile(profile, index) {
			this.loading = true;
			axios.post('/i/follow', {
				item: profile.entity.id
			}).then(res => {
				if(profile.entity.local == true) {
					this.fetchSearchResults();
					return;
				} else {
					this.loading = false;
					this.results.profiles[index].entity.follow_request = true;
					return;
				}
			}).catch(err => {
				if(err.response.data.message) {
					swal('Error', err.response.data.message, 'error');
				}
			});
		},
	}

}
</script>
