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

	<div v-if="!loading && !networkError" class="mt-5">
		<div v-if="analysis == 'all'" class="row">
			<div class="col-12 mb-5">
				<p class="h5 font-weight-bold text-dark">Showing results for <i>{{query}}</i></p>
				<hr>
			</div>
			<div class="col-md-3">
				<div class="mb-4">
					<p class="text-secondary small font-weight-bold">HASHTAGS <span class="pl-1 text-lighter">({{results.hashtags.length}})</span></p>
				</div>
				<div v-if="results.hashtags.length">
					<a v-for="(hashtag, index) in results.hashtags" class="mb-2 result-card" :href="buildUrl('hashtag', hashtag)">
						<div class="pb-3">
							<div class="media align-items-center py-2 pr-3">
								<span class="d-inline-flex align-items-center justify-content-center border rounded-circle mr-3" style="width: 50px;height: 50px;">
								<i class="fas fa-hashtag text-muted"></i>
								</span>
								<div class="media-body text-truncate">
									<p class="mb-0 text-truncate text-dark font-weight-bold" data-toggle="tooltip" :title="hashtag.value">
										#{{hashtag.value}}
									</p>
									<p v-if="hashtag.count > 2" class="mb-0 small font-weight-bold text-muted text-uppercase">
									{{hashtag.count}} posts
								</p>
								</div>
							</div>
						</div>
					</a>
				</div>
				<div v-else>
					<div class="border py-3 text-center font-weight-bold">No results found</div>
				</div>
			</div>
			<div class="col-md-5">
				<div class="mb-4">
					<p class="text-secondary small font-weight-bold">PROFILES <span class="pl-1 text-lighter">({{results.profiles.length}})</span></p>
				</div>
				<div v-if="results.profiles.length">
					<a v-for="(profile, index) in results.profiles" class="mb-2 result-card" :href="buildUrl('profile', profile)">
						<div class="pb-3">
							<div class="media align-items-center py-2 pr-3">
								<img class="mr-3 rounded-circle border" :src="profile.avatar" width="50px" height="50px">
								<div class="media-body">
									<p class="mb-0 text-truncate text-dark font-weight-bold" data-toggle="tooltip" :title="profile.value">
										{{profile.value}}
									</p>
									<p class="mb-0 small font-weight-bold text-muted text-uppercase">
										{{profile.entity.post_count}} Posts
									</p>
								</div>
								<div class="ml-3">
									<a v-if="profile.entity.following" class="btn btn-primary btn-sm font-weight-bold text-uppercase py-0" :href="buildUrl('profile', profile)">Following</a>
									<a v-else class="btn btn-outline-primary btn-sm font-weight-bold text-uppercase py-0" :href="buildUrl('profile', profile)">View</a>
								</div>
							</div>
						</div>
					</a>
				</div>
				<div v-else>
					<div class="border py-3 text-center font-weight-bold">No results found</div>
				</div>
			</div>
			<div class="col-md-4">
				<div class="mb-4">
					<p class="text-secondary small font-weight-bold">STATUSES <span class="pl-1 text-lighter">({{results.statuses.length}})</span></p>
				</div>
				<div v-if="results.statuses.length">
					<a v-for="(status, index) in results.statuses" class="mr-2 result-card" :href="buildUrl('status', status)">
						<img :src="status.thumb" width="90px" height="90px" class="mb-2">
					</a>
				</div>
				<div v-else>
					<div class="border py-3 text-center font-weight-bold">No results found</div>
				</div>
			</div>
		</div>
		<div v-else-if="analysis == 'hashtag'" class="row">
			<div class="col-12 mb-5">
				<p class="h5 font-weight-bold text-dark">Showing results for <i>{{query}}</i></p>
				<hr>
			</div>
			<div class="col-md-6 offset-md-3">
				<div class="mb-4">
					<p class="text-secondary small font-weight-bold">HASHTAGS <span class="pl-1 text-lighter">({{results.hashtags.length}})</span></p>
				</div>
				<div v-if="results.hashtags.length">
					<a v-for="(hashtag, index) in results.hashtags" class="mb-2 result-card" :href="buildUrl('hashtag', hashtag)">
						<div class="pb-3">
							<div class="media align-items-center py-2 pr-3">
								<span class="d-inline-flex align-items-center justify-content-center border rounded-circle mr-3" style="width: 50px;height: 50px;">
								<i class="fas fa-hashtag text-muted"></i>
								</span>
								<div class="media-body">
									<p class="mb-0 text-truncate text-dark font-weight-bold" data-toggle="tooltip" :title="hashtag.value">
										#{{hashtag.value}}
									</p>
									<p v-if="hashtag.count > 2" class="mb-0 small font-weight-bold text-muted text-uppercase">
									{{hashtag.count}} posts
								</p>
								</div>
							</div>
						</div>
					</a>
				</div>
				<div v-else>
					<div class="border py-3 text-center font-weight-bold">No results found</div>
				</div>
			</div>
		</div>
		<div v-else-if="analysis == 'profile'" class="row">
			<div class="col-12 mb-5">
				<p class="h5 font-weight-bold text-dark">Showing results for <i>{{query}}</i></p>
				<hr>
			</div>
			<div class="col-md-6 offset-md-3">
				<div class="mb-4">
					<p class="text-secondary small font-weight-bold">PROFILES <span class="pl-1 text-lighter">({{results.profiles.length}})</span></p>
				</div>
				<div v-if="results.profiles.length">
					<div v-for="(profile, index) in results.profiles" class="card mb-4">
						<div class="card-header p-0 m-0">
							<div style="width: 100%;height: 140px;background: #0070b7"></div>
						</div>
						<div class="card-body">
							<div class="text-center mt-n5 mb-4">
								<img class="rounded-circle p-1 border mt-n4 bg-white shadow" :src="profile.entity.thumb" width="90px" height="90px;" onerror="this.onerror=null;this.src='/storage/avatars/default.png';">
							</div>
							<p class="text-center lead font-weight-bold mb-1">{{profile.value}}</p>
							<p class="text-center text-muted small text-uppercase mb-4"><!-- 2 followers --></p>
							<div class="d-flex justify-content-center">
								<button v-if="profile.entity.following" type="button" class="btn btn-outline-secondary btn-sm py-1 px-4 text-uppercase font-weight-bold mr-3" style="font-weight: 500">Following</button>
								<a class="btn btn-primary btn-sm py-1 px-4 text-uppercase font-weight-bold" :href="buildUrl('profile',profile)" style="font-weight: 500">View Profile</a>
							</div>
						</div>
					</div>
				</div>
				<div v-else>
					<div class="border py-3 text-center font-weight-bold">No results found</div>
				</div>
			</div>
		</div>
		<div v-else-if="analysis == 'webfinger'" class="row">
			<div class="col-12 mb-5">
				<p class="h5 font-weight-bold text-dark">Showing results for <i>{{query}}</i></p>
				<hr>
				<div class="col-md-6 offset-md-3">
					<div v-for="(profile, index) in results.profiles" class="card mb-2">
						<div class="card-header p-0 m-0">
							<div style="width: 100%;height: 140px;background: #0070b7"></div>
						</div>
						<div class="card-body">
							<div class="text-center mt-n5 mb-4">
								<img class="rounded-circle p-1 border mt-n4 bg-white shadow" :src="profile.entity.thumb" width="90px" height="90px;" onerror="this.onerror=null;this.src='/storage/avatars/default.png';">
							</div>
							<p class="text-center lead font-weight-bold mb-1">{{profile.value}}</p>
							<p class="text-center text-muted small text-uppercase mb-4"><!-- 2 followers --></p>
							<div class="d-flex justify-content-center">
								<!-- <button v-if="profile.entity.following" type="button" class="btn btn-outline-secondary btn-sm py-1 px-4 text-uppercase font-weight-bold mr-3" style="font-weight: 500">Unfollow</button> -->
								<!-- <button v-else type="button" class="btn btn-primary btn-sm py-1 px-4 text-uppercase font-weight-bold mr-3" style="font-weight: 500">Follow</button> -->
								<a class="btn btn-primary btn-sm py-1 px-4 text-uppercase font-weight-bold" :href="'/i/web/profile/_/' + profile.entity.id" style="font-weight: 500">View Profile</a>
							</div>
						</div>
					</div>
				</div>
			</div>
		</div>
		<div v-else class="col-12">
			<p class="text-center text-muted lead font-weight-bold">No results found</p>
		</div>
	</div>

</div>
</template>

<style type="text/css" scoped>
.result-card {
	text-decoration: none;
}
.result-card .media:hover {
	background: #EDF2F7;
}
@media (min-width: 1200px) {
	.container {
		max-width: 995px;
	}
}
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
			},
			analysis: 'profile',
		}
	},
	beforeMount() {
		this.bootSearch();
	},
	mounted() {
		$('.search-bar input').val(this.query);
	},
	methods: {
		bootSearch() {
			let lexer = this.searchLexer();
			this.analysis = lexer;
			this.fetchSearchResults();
		},

		fetchSearchResults() {
			this.searchContext(this.analysis);
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

		searchLexer() {
			let q = this.query;

			if(q.startsWith('#')) {
				return 'hashtag';
			}

			if((q.match(/@/g) || []).length == 2) {
				return 'webfinger';
			}

			if(q.startsWith('@') || q.search('@') != -1) {
				return 'profile';
			}

			if(q.startsWith('https://')) {
				return 'remote';
			}

			return 'all';
		},

		buildUrl(type = 'hashtag', obj) {
			switch(type) {
				case 'hashtag':
					return obj.url + '?src=search';
				break;

				case 'profile':
					if(obj.entity.local == true) {
						return obj.url;
					}
					return '/i/web/profile/_/' + obj.entity.id;
				break;

				default:
					return obj.url + '?src=search';
				break;

			}
		},

		searchContext(type) {
			switch(type) {
				case 'all': 
					axios.get('/api/search', {
						params: {
							'q': this.query,
							'src': 'metro',
							'v': 1,
							'scope': 'all'
						}
					}).then(res => {
						let results = res.data;
						this.results.hashtags = results.hashtags ? results.hashtags : [];
						this.results.profiles = results.profiles ? results.profiles : [];
						this.results.statuses = results.posts ? results.posts : [];
						this.loading = false;
					}).catch(err => {
						this.loading = false;
						console.log(err);
						this.networkError = true;
					});
				break;

				case 'hashtag':
					axios.get('/api/search', {
						params: {
							'q': this.query.slice(1),
							'src': 'metro',
							'v': 1,
							'scope': 'hashtag'
						}
					}).then(res => {
						let results = res.data;
						this.results.hashtags = results.hashtags ? results.hashtags : [];
						this.results.profiles = results.profiles ? results.profiles : [];
						this.results.statuses = results.posts ? results.posts : [];
						this.loading = false;
					}).catch(err => {
						this.loading = false;
						console.log(err);
						this.networkError = true;
					});
				break;

				case 'profile':
					axios.get('/api/search', {
						params: {
							'q': this.query,
							'src': 'metro',
							'v': 1,
							'scope': 'profile'
						}
					}).then(res => {
						let results = res.data;
						this.results.hashtags = results.hashtags ? results.hashtags : [];
						this.results.profiles = results.profiles ? results.profiles : [];
						this.results.statuses = results.posts ? results.posts : [];
						this.loading = false;
					}).catch(err => {
						this.loading = false;
						console.log(err);
						this.networkError = true;
					});
				break;

				case 'webfinger':
					axios.get('/api/search', {
						params: {
							'q': this.query,
							'src': 'metro',
							'v': 1,
							'scope': 'webfinger'
						}
					}).then(res => {
						let results = res.data;
						this.results.hashtags = [];
						this.results.profiles = results.profiles;
						this.results.statuses = [];
						this.loading = false;
					}).catch(err => {
						this.loading = false;
						console.log(err);
						this.networkError = true;
					});
				break;

				default:
					this.loading = false;
					this.networkError = true;
				break;
			}
		}
	}

}
</script>
