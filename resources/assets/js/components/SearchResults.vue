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
			<div class="col-12 d-flex justify-content-between align-items-center">
				<p class="h5 font-weight-bold text-dark">Showing results for <i>{{query}}</i></p>
				<div v-if="placesSearchEnabled" title="Show Places" data-toggle="tooltip">
					<span v-if="results.placesPagination.total > 0" class="badge badge-light mr-2 p-1 border" style="margin-top:-5px;">{{formatCount(results.placesPagination.total)}}</span>
					<div class="d-inline custom-control custom-switch">
						<input type="checkbox" class="custom-control-input" id="placesSwitch" v-model="showPlaces">
						<label class="custom-control-label font-weight-bold text-sm text-lighter" for="placesSwitch"><i class="fas fa-map-marker-alt"></i></label>
					</div>
				</div>
			</div>
			<div class="col-12 mb-5">
				<hr>
			</div>
			<div v-if="placesSearchEnabled && showPlaces" class="col-12 mb-4">
				<div class="mb-4">
					<p class="text-secondary small font-weight-bold">PLACES <span class="pl-1 text-lighter">({{results.placesPagination.total}})</span></p>
				</div>
				<div v-if="results.places.length" class="mb-5">
					<a v-for="(hashtag, index) in results.places" class="mr-3 pr-4 d-inline-block text-decoration-none" :href="buildUrl('places', hashtag)">
						<div class="pb-2">
							<div class="media align-items-center py-2">
								<div class="media-body text-truncate">
									<p class="mb-0 text-break text-dark font-weight-bold" data-toggle="tooltip" :title="hashtag.value">
										<i class="fas fa-map-marker-alt text-lighter mr-2"></i> {{hashtag.value}}
									</p>
								</div>
							</div>
						</div>
					</a>
					<p v-if="results.places.length == 20 || placesCursor > 0" class="text-center mt-3">
						<a v-if="placesCursor == 1" href="#" class="btn btn-outline-secondary btn-sm font-weight-bold py-0 disabled" disabled>
							<i class="fas fa-chevron-left mr-2"></i> Previous
						</a>
						<a v-else href="#" @click.prevent="placesPrevPage()" class="btn btn-outline-secondary btn-sm font-weight-bold py-0">
							<i class="fas fa-chevron-left mr-2"></i> Previous
						</a>

						<span class="mx-4 small text-lighter">{{placesCursor}}/{{results.placesPagination.last_page}}</span>

						<a v-if="placesCursor !== results.placesPagination.last_page" @click.prevent="placesNextPage()" href="#" class="btn btn-primary btn-sm font-weight-bold py-0">
							Next <i class="fas fa-chevron-right ml-2"></i>
						</a>
						<a v-else href="#" class="btn btn-primary btn-sm font-weight-bold py-0 disabled" disabled>
							Next <i class="fas fa-chevron-right ml-2"></i>
						</a>
					</p>
				</div>
				<div v-else>
					<div class="border py-3 text-center font-weight-bold">No results found</div>
				</div>			
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
									<p class="mb-0 text-break text-dark font-weight-bold" data-toggle="tooltip" :title="hashtag.value">
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
								<img class="mr-3 rounded-circle border" :src="profile.avatar" width="50px" height="50px" onerror="this.onerror=null;this.src='/storage/avatars/default.png?v=0';">
								<div class="media-body">
									<p class="mb-0 text-break text-dark font-weight-bold" data-toggle="tooltip" :title="profile.value">
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
					<a v-for="(status, index) in results.statuses" :key="'srs:'+index" class="mr-2 result-card" :href="buildUrl('status', status)">
						<img :src="status.thumb" width="90px" height="90px" class="mb-2" onerror="this.onerror=null;this.src='/storage/no-preview.png?v=0';" v-once>
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
		<div v-else-if="analysis == 'remote'" class="row">
			<div class="col-12 mb-5">
				<p class="h5 font-weight-bold text-dark">Showing results for <i>{{query}}</i></p>
				<hr>
			</div>
			<div v-if="results.profiles.length" class="col-md-6 offset-3">
				<a v-for="(profile, index) in results.profiles" class="mb-2 result-card" :href="buildUrl('profile', profile)">
					<div class="pb-3">
						<div class="media align-items-center py-2 pr-3">
							<img class="mr-3 rounded-circle border" :src="profile.entity.thumb" width="50px" height="50px" onerror="this.onerror=null;this.src='/storage/avatars/default.png';">
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
			<div v-if="results.statuses.length" class="col-md-6 offset-3">
				<a v-for="(status, index) in results.statuses" class="mr-2 result-card" :href="buildUrl('status', status)">
					<img :src="status.thumb" width="90px" height="90px" class="mb-2" onerror="this.onerror=null;this.src='/storage/no-preview.png';">
				</a>
			</div>
		</div>
		<div v-else-if="analysis == 'remotePost'" class="row">
			<div class="col-12 mb-5">
				<p class="h5 font-weight-bold text-dark">Showing results for <i>{{query}}</i></p>
				<hr>
			</div>
			<div class="col-md-6 offset-md-3">
				<div v-if="results.statuses.length">
					<div v-for="(status, index) in results.statuses" class="card mb-4 shadow-none border">
						<div class="card-header p-0 m-0">
							<div style="width: 100%;height: 200px;background: #fff">
								<div class="pt-4 text-center">
									<img :src="status.thumb" class="img-fluid border" style="max-height: 140px;">
								</div>
							</div>
						</div>
						<div class="card-body">
							<div class="mt-n4 mb-2">
								<div class="media">
									
									<img class="rounded-circle p-1 mr-2 border mt-n3 bg-white shadow" src="/storage/avatars/default.png" width="70px" height="70px;" onerror="this.onerror=null;this.src='/storage/avatars/default.png';">
									<div class="media-body pt-3">
										<p class="font-weight-bold mb-0">{{status.username}}</p>
									</div>
									<div class="float-right pt-3">
										<p class="small mb-0 text-muted">{{status.timestamp}}</p>
									</div>
								</div>
							</div>
							<p class="text-center mb-3 lead" v-html="status.caption"></p>
							<!-- <p class="text-center text-muted small text-uppercase mb-4">2 likes</p> -->
							<!-- <div class="d-flex justify-content-center">
								<a class="btn btn-primary btn-sm py-1 px-4 text-uppercase font-weight-bold" :href="status.url" style="font-weight: 500">View Post</a>
							</div> -->
						</div>
						<div class="card-footer">
							<a class="btn btn-primary btn-block font-weight-bold rounded-0" :href="status.url">View Post</a>
						</div>
					</div>
				</div>
				<div v-else>
					<div class="border py-3 text-center font-weight-bold">No results found</div>
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
				statuses: [],
				places: [],
			},
			filters: {
				hashtags: true,
				profiles: true,
				statuses: true
			},
			analysis: 'profile',
			showPlaces: false,
			placesCursor: 1,
			placesCache: [],
			placesSearchEnabled: false,
			searchVersion: 2
		}
	},
	beforeMount() {
		this.bootSearch();
	},
	mounted() {
		$('.search-bar input').val(this.query);
	},
	updated() {
		$('[data-toggle="tooltip"]').tooltip();
	},
	methods: {
		bootSearch() {
			let lexer = this.searchLexer();
			this.analysis = lexer;
			this.fetchSearchResults();
			axios.get('/api/pixelfed/v1/accounts/verify_credentials').then(res => {
				window._sharedData.curUser = res.data;
				window.App.util.navatar();
			});
		},

		fetchSearchResults() {
			if(this.analysis == 'remote') {
				let term = this.query;
				let parsed = new URL(term);
				if(parsed.host === window.location.host) {
					window.location.href = term;
					return;
				}
			}
			this.searchContext(this.analysis);
		},

		searchLexer() {
			let q = this.query;

			if(q.startsWith('#')) {
				return 'hashtag';
			}

			if((q.match(/@/g) || []).length == 2) {
				return 'webfinger';
			}

			if(q.startsWith('@')) {
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
							'v': this.searchVersion,
							'scope': 'all'
						}
					}).then(res => {
						let results = res.data;
						this.results.hashtags = results.hashtags ? results.hashtags : [];
						this.results.profiles = results.profiles ? results.profiles : [];
						this.results.statuses = results.posts ? results.posts : [];
						this.results.places   = results.places ? results.places : [];
						this.placesCache = results.places;
						this.results.placesPagination   = results.placesPagination ? results.placesPagination : [];
						this.loading = false;
					}).catch(err => {
						this.loading = false;
						this.networkError = true;
					});
				break;

				case 'remote': 
					axios.get('/api/search', {
						params: {
							'q': this.query,
							'src': 'metro',
							'v': this.searchVersion,
							'scope': 'remote'
						}
					}).then(res => {
						let results = res.data;
						this.results.hashtags = results.hashtags ? results.hashtags : [];
						this.results.profiles = results.profiles ? results.profiles : [];
						this.results.statuses = results.posts ? results.posts : [];

						if(this.results.profiles.length) {
							this.analysis = 'profile';
						}
						if(this.results.statuses.length) {
							this.analysis = 'remotePost';
						}
						this.loading = false;
					}).catch(err => {
						this.loading = false;
						this.networkError = true;
					});
				break;

				case 'hashtag':
					axios.get('/api/search', {
						params: {
							'q': this.query.slice(1),
							'src': 'metro',
							'v': this.searchVersion,
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
						this.networkError = true;
					});
				break;

				case 'profile':
					axios.get('/api/search', {
						params: {
							'q': this.query,
							'src': 'metro',
							'v': this.searchVersion,
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
						this.networkError = true;
					});
				break;

				case 'webfinger':
					axios.get('/api/search', {
						params: {
							'q': this.query,
							'src': 'metro',
							'v': this.searchVersion,
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
						this.networkError = true;
					});
				break;

				default:
					this.loading = false;
					this.networkError = true;
				break;
			}
		},

		placesPrevPage() {
			this.placesCursor--;
			if(this.placesCursor == 1) {
				this.results.places = this.placesCache.slice(0, 20);
				return;
			}
			let plc = this.placesCursor * 20;
			this.results.places = this.placesCache.slice(plc, 20);
			return;
		},

		placesNextPage() {
			this.placesCursor++;
			let plc = this.placesCursor * 20;
			if(this.placesCache.length > 20) {
				this.results.places = this.placesCache.slice(this.placesCursor == 1 ? 0 : plc, 20);
				return;
			} 
			axios.get('/api/search', {
				params: {
					'q': this.query,
					'src': 'metro',
					'v': this.searchVersion,
					'scope': 'all',
					'page': this.placesCursor
				}
			}).then(res => {
				let results = res.data;
				this.results.places = results.places ? results.places : [];
				this.placesCache.push(...results.places);
				this.loading = false;
			}).catch(err => {
				this.loading = false;
				this.networkError = true;
			});

		},

		formatCount(num) {
			let count = window.App.util.format.count(num);
			return count;
		}
	}

}
</script>
