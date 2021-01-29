<template>
<div class="bg-dark text-white">
	<div v-if="!loaded" style="height: 100vh;" class="d-flex justify-content-center align-items-center">
		<div class="text-center">
			<div class="spinner-border text-light" role="status">
				<span class="sr-only">Loading...</span>
			</div>
			<p class="mb-0 lead mt-2">Loading</p>
		</div>
	</div>
	<div v-if="loaded && notEnoughData" style="height: 100vh;" class="d-flex justify-content-center align-items-center">
		<div class="text-center">
			<p class="display-4">Oops!</p>
			<p class="h3 font-weight-light py-3">We don't have enough data to display your <span class="font-weight-bold">#my2020</span>.</p>
			<p class="mb-0 h5 font-weight-light">We hope to see you next year!</p>
		</div>
	</div>
	<div v-if="loaded && !notEnoughData" class="d-flex justify-content-center align-items-center" style="width:100%;height:100vh;min-height:500px; padding: 0 15px;">

		<div v-if="page == 1" class="text-center">
			<p class="h1 font-weight-light">Hello {{user.username}}!</p>
			<p class="h1 py-4">Your 2020 on Pixelfed.</p>
			<p class="h4 font-weight-light mb-0 animate__animated animate__bounceInDown">Use the buttons below to navigate.</p>
		</div>

		<div v-if="page == 2" class="text-center mw-500">
			<p class="display-4">User #<span class="font-weight-bold">{{stats.account.user_id}}</span></p>
			<p class="h3 font-weight-light mb-0">You joined Pixelfed on {{stats.account.created_at}}</p>
		</div>

		<div v-if="page == 3" class="text-center mw-500">
			<p class="display-4">You created <span class="font-weight-bold">{{stats.account.posts_count}}</span> posts</p>
			<p class="h3 font-weight-light mb-0">The average user created <span class="font-weight-bold">{{stats.average.posts}}</span> posts this year.</p>
		</div>

		<div v-if="page == 4" class="text-center mw-500">
			<p class="display-4">You liked <span class="font-weight-bold">{{stats.account.likes_count}}</span> posts</p>
			<p class="h3 font-weight-light mb-0">The average user liked <span class="font-weight-bold">{{stats.average.likes}}</span> posts this year.</p>
		</div>

		<div v-if="page == 5" class="text-center mw-500">
			<div v-if="stats.account.most_popular">
				<p class="h1 font-weight-light mb-0 text-break md-line-height">Your most popular post of 2020 was created on <span class="font-weight-bold">{{stats.account.most_popular.created_at}}</span> with <span class="font-weight-bold">{{stats.account.most_popular.likes_count}}</span> likes.</p>
				<p class="mt-4 mb-0">
					<a class="btn btn-outline-light btn-lg btn-block rounded-pill" :href="stats.account.most_popular.url">View Post</a>
				</p>
			</div>
			<div v-else>
				<p class="h1 font-weight-light mb-0 text-break md-line-height">The most popular post of 2020 was created by <span class="font-weight-bold">{{stats.popular.post.username}}</span> on <span class="font-weight-bold">{{stats.popular.post.created_at}}</span> with <span class="font-weight-bold">{{stats.popular.post.likes_count}}</span> likes.</p>
				<p class="mt-4 mb-0">
					<a class="btn btn-outline-light btn-lg btn-block rounded-pill" :href="stats.popular.post.url">View Post</a>
				</p>
			</div>
		</div>

		<div v-if="page == 6" class="text-center mw-500">
			<p class="display-4"><span class="font-weight-bold">{{stats.account.followers_this_year}}</span> New Followers</p>
			<p class="h3 font-weight-light mb-0">You followed <span class="font-weight-bold">{{stats.account.followed_this_year}}</span> accounts this year!</p>
		</div>

		<div v-if="page == 7" class="text-center mw-500">
			<div v-if="stats.account.hashtag">
				<p class="h1 text-break">Your favourite hashtag was <span class="font-weight-bold">#{{stats.account.hashtag.name}}</span>.</p>
				<p class="h3 font-weight-light mb-0">You used it <span class="font-weight-bold">{{stats.account.hashtag.count}}</span> times!</p>
			</div>
			<div v-else>
				<p class="h1 text-break">The most popular hashtag was <span class="font-weight-bold">#{{stats.popular.hashtag.name}}</span></p>
				<p class="h3 font-weight-light mb-0">It was used <span class="font-weight-bold">{{stats.popular.hashtag.count}}</span> times!</p>
			</div>
		</div>

		<div v-if="page == 8" class="text-center mw-500">
			<p class="display-4">You tagged <span class="font-weight-bold">{{stats.account.places_total}}</span> places.</p>
			<p v-if="stats.account.places_total" class="h3 font-weight-light mb-0">You tagged <span class="font-weight-bold">{{stats.account.places.name}}</span> a total of <span class="font-weight-bold">{{stats.account.places.count}}</span> times!</p>
			<p v-else class="h3 font-weight-light mb-0">The most tagged place was <span class="font-weight-bold">{{stats.popular.places.name}}</span> that was tagged a total of <span class="font-weight-bold">{{stats.popular.places.count}}</span> times!</p>
		</div>

		<div v-if="page == 9" class="text-center">
			<p class="display-4">Happy 2021!</p>
			<p class="h3 font-weight-light mb-0">We wish you the best in the new year.</p>
		</div>

	</div>
	<div v-if="loaded" class="fixed-top">
		<p class="text-center mt-3 d-flex justify-content-center align-items-center mb-0">
			<img src="/img/pixelfed-icon-grey.svg" width="60" height="60">
			<span class="text-light font-weight-bold ml-3" style="font-size: 22px;">#my2020</span>
		</p>
	</div>
	<div v-if="loaded" class="fixed-bottom">
		<p class="text-center">
			<a v-if="!notEnoughData" :class="prevClass()" href="#" @click.prevent="prevPage()" :disabled="page == 1"><i class="fas fa-chevron-left"></i> Back</a>
			<a class="btn btn-outline-light rounded-pill mx-3" href="/">Back to Pixelfed</a>
			<a v-if="!notEnoughData" :class="nextClass()" href="#" @click.prevent="nextPage()">Next <i class="fas fa-chevron-right"></i></a>
		</p>
	</div>
</div>
</template>

<style type="text/css" scoped>
	.md-line-height {
		line-height: 1.65 !important;
	}
	.mw-500 {
		max-width: 500px;
	}
</style>

<script type="text/javascript">
	
export default {
	data() {
		return {
			config: window.App.config,
			user: {},
			loggedIn: false,
			loaded: false,
			page: 1,
			stats: [],
			notEnoughData: false,
			reportedView: false
		}
	},

	mounted() {
		let u = new URLSearchParams(window.location.search);
		if( u.has('v') && 
			u.has('ned') && 
			u.has('sl') && 
			u.get('v') == 20 && 
			u.get('sl') >= 1 && 
			u.get('sl') <= 9
		) {
			if(u.get('ned') == 0) {
				this.page = u.get('sl');
			} else {
				this.notEnoughData = true;
			}
		}

		axios.get('/api/pixelfed/v1/accounts/verify_credentials')
		.then(res => {
			this.user = res.data;
			window._sharedData.curUser = res.data;
		});

		this.fetchData();
	}, 

	updated() {
	},

	methods: {
		fetchData() {
			axios.get('/api/pixelfed/v2/seasonal/yir')
			.then(res => {
				this.stats = res.data;
				this.loaded = true;
				this.shortcuts();
			})
		},

		nextPage() {
			if(this.page == 9) {
				return;
			}

			if(this.page == 7 && this.stats.popular.places == null) {
				this.page = 9;
				window.history.pushState({}, {}, '/i/my2020?v=20&ned=0&sl=9');
				return;
			}

			if(this.page == 8) {
				axios.post('/api/pixelfed/v2/seasonal/yir', {
					'profile_id' : this.user.profile_id
				})
			}
			++this.page;
			window.history.pushState({}, {}, '/i/my2020?v=20&ned=0&sl=' + this.page);
		},

		prevPage() {
			if(this.page == 1) {
				return;
			}
			if(this.page == 9 && this.stats.popular.places == null) {
				this.page = 7;
				window.history.pushState({}, {}, '/i/my2020?v=20&ned=0&sl=7');
				return;
			}
			--this.page;
			if(this.page == 1) {
				window.history.pushState({}, {}, '/i/my2020');
			} else {
				window.history.pushState({}, {}, '/i/my2020?v=20&ned=0&sl=' + this.page);
			}
		},

		prevClass() {
			return this.page == 1
				? 'btn btn-outline-muted rounded-pill'
				: 'btn btn-outline-light rounded-pill';
		},

		nextClass() {
			return this.page == 9
				? 'btn btn-outline-muted rounded-pill'
				: 'btn btn-outline-light rounded-pill';
		},

		dateFormat(d) {
		},

		shortcuts() {
			let self = this;
			window.addEventListener("keydown", function(event) {
				if (event.defaultPrevented) {
					return;
				}

				switch(event.code) {
					case "KeyA":
					case "ArrowLeft":
					self.prevPage();
					break;
					case "KeyD":
					case "ArrowRight":
					self.nextPage();
					break;
				}

				event.preventDefault();
				}, true);
		}
	}
}

</script>