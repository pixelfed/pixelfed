<template>
	<div class="landing-directory-component">
		<section class="page-wrapper">
			<div class="container container-compact">
				<div class="card bg-bluegray-900" style="border-radius: 10px;">
					<div class="card-header bg-bluegray-800 nav-menu" style="border-top-left-radius: 10px; border-top-right-radius: 10px;">
						<ul class="nav justify-content-around">
						  <li class="nav-item">
							<router-link to="/" class="nav-link">About</router-link>
							</li>
							<li v-if="config.show_directory" class="nav-item">
								<router-link to="/web/directory" class="nav-link">Directory</router-link>
							</li>
							<li v-if="config.show_explore_feed" class="nav-item">
								<router-link to="/web/explore" class="nav-link">Explore</router-link>
							</li>
						</ul>
					</div>

					<div class="card-body">
						<div class="py-3">
							<p class="lead text-center">Discover accounts and people</p>
						</div>

						<div v-if="loading" class="d-flex justify-content-center align-items-center" style="min-height: 500px;">
							<b-spinner />
						</div>

						<div v-else class="feed-list">
							<user-card
								v-for="account in feed"
								:key="account.id"
								:account="account" />

							<intersect v-if="canLoadMore && !isEmpty" @enter="enterIntersect">
								<div class="d-flex justify-content-center pt-5 pb-3">
									<b-spinner v-if="isLoadingMore" />
								</div>
							</intersect>
						</div>

						<div v-if="isEmpty">
							<div class="card card-body bg-bluegray-800">
								<div class="d-flex justify-content-center align-items-center flex-column py-5">
									<i class="fal fa-clock fa-6x text-bluegray-500"></i>
									<p class="lead font-weight-bold mt-3 mb-0">Nothing to show yet! Check back later.</p>
								</div>
							</div>
						</div>
					</div>
				</div>
			</div>

			<footer-component />
		</section>
	</div>
</template>

<script type="text/javascript">
	import UserCard from './partials/UserCard';
	import Intersect from 'vue-intersect';

	export default {
		components: {
			"user-card": UserCard,
			"intersect": Intersect,
		},

		data() {
			return {
				loading: true,
				config: window.pfl,
				pagination: undefined,
				feed: [],
				isEmpty: false,
				canLoadMore: false,
				isIntersecting: false,
				isLoadingMore: false
			}
		},

		beforeMount() {
			if(this.config.show_directory == false) {
				this.$router.push('/');
			}
		},

		mounted() {
			this.init();
		},

		methods: {
			init() {
				axios.get('/api/landing/v1/directory')
				.then(res => {
					if(!res.data.data.length) {
						this.isEmpty = true;
					}
					this.feed = res.data.data;
					this.pagination = {...res.data.links, ...res.data.meta};
				})
				.finally(() => {
					this.canLoadMore = true;
					this.$nextTick(() => {
						this.loading = false;
					})
				})
			},

			enterIntersect(e) {
				if(this.isIntersecting || !this.pagination.next_cursor) {
					return;
				}
				this.isIntersecting = true;
				this.isLoadingMore = true;

				axios.get('/api/landing/v1/directory', {
					params: {
						cursor: this.pagination.next_cursor
					}
				})
				.then(res => {
					this.feed.push(...res.data.data);
					this.pagination = {...res.data.links, ...res.data.meta};
				})
				.finally(() => {
					if(this.pagination.next_cursor) {
						this.canLoadMore = true;
					} else {
						this.canLoadMore = false;
					}
					this.isLoadingMore = false;
					this.isIntersecting = false;
				});
				console.log(e);
			}
		}
	}
</script>
