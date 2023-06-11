<template>
	<div class="timeline-onboarding">
		<div class="card card-body shadow-sm mb-3 p-5" style="border-radius: 15px;">
			<h1 class="text-center mb-4">✨ {{ $t('timeline.onboarding.welcome') }}</h1>

			<p class="text-center mb-3" style="font-size: 22px;">
				{{ $t('timeline.onboarding.thisIsYourHomeFeed') }}
			</p>

			<p class="text-center lead">{{ $t('timeline.onboarding.letUsHelpYouFind') }}</p>

			<p v-if="newlyFollowed" class="text-center mb-0">
				<a href="/i/web" class="btn btn-primary btn-lg primary font-weight-bold rounded-pill px-4" onclick="location.reload()">
					{{ $t('timeline.onboarding.refreshFeed') }}
				</a>
			</p>
		</div>

		<div class="row">
			<div class="col-12 col-md-6 mb-3" v-for="(profile, index) in popularAccounts">
				<div class="card shadow-sm border-0 rounded-px">
					<div class="card-body p-2">
						<profile-card
							:key="'pfc' + index"
							:profile="profile"
							class="w-100"
							v-on:follow="follow(index)"
							v-on:unfollow="unfollow(index)"
						/>
					</div>
				</div>
			</div>
		</div>
	</div>
</template>

<script type="text/javascript">
	import ProfileCard from './../profile/ProfileHoverCard.vue';

	export default {
		props: {
			profile: {
				type: Object
			}
		},

		components: {
			"profile-card": ProfileCard
		},

		data() {
			return {
				popularAccounts: [],
				newlyFollowed: 0
			};
		},

		mounted() {
			this.fetchPopularAccounts();
		},

		methods: {
			fetchPopularAccounts() {
        		axios.get('/api/pixelfed/discover/accounts/popular')
        		.then(res => {
        			this.popularAccounts = res.data;
        		})
        	},

        	follow(index) {
        		axios.post('/api/v1/accounts/' + this.popularAccounts[index].id + '/follow')
				.then(res => {
					this.newlyFollowed++;
					this.$store.commit('updateRelationship', [res.data]);
					this.$emit('update-profile', {
						'following_count': this.profile.following_count + 1
					})
				});
        	},

        	unfollow(index) {
        		axios.post('/api/v1/accounts/' + this.popularAccounts[index].id + '/unfollow')
				.then(res => {
					this.newlyFollowed--;
					this.$store.commit('updateRelationship', [res.data]);
					this.$emit('update-profile', {
						'following_count': this.profile.following_count - 1
					})
				});
        	}
		}
	}
</script>

<style lang="scss">
.timeline-onboarding {
	.profile-hover-card-inner {
		width: 100%;

		.d-flex {
			max-width: 100% !important;
		}
	}
}
</style>
