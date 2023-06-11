<template>
	<div>
		<!-- <p class="">
			<router-link class="btn btn-primary primary btn-sm rounded-pill font-weight-bold btn-block" to="/i/web/whats-new"><i class="fal fa-exclamation-circle mr-1"></i> New in Metro UI 2</router-link>
		</p> -->

		<notifications :profile="profile" />

		<!-- <div class="d-none card shadow-sm mb-3" style="border-radius: 15px;">
			<div class="card-body">
				<div class="d-flex justify-content-between">
					<p class="text-muted">{{ $t('timeline.peopleYouMayKnow') }}</p>
					<p class="text-lighter"><i class="far fa-cog"></i></p>
				</div>

				<div class="media-list mb-n4">
					<div v-for="(account, index) in recommended" class="media align-items-center mb-3">
						<img :src="account.avatar" class="avatar shadow-sm mr-3" width="40" height="40">
						<div class="media-body">
							<p class="lead font-weight-bold username primary">&commat;{{ account.username }}</p>
							<p class="text-muted mb-0 display-name">{{ account.display_name }}</p>
						</div>

						<button class="btn btn-primary btn-sm follow">
							{{ $t('profile.follow') }}
						</button>
					</div>
				</div>
			</div>
		</div>

		<div class="d-none card shadow-sm mb-3" style="border-radius: 15px;">
			<div class="card-body">
				<div class="d-flex justify-content-between">
					<p class="text-muted">Trending</p>
					<p class="text-lighter"><i class="far fa-cog"></i></p>
				</div>

				<div class="media-list row mb-n3">
					<div v-for="(post, index) in trending" class="col-6 mb-1 p-1">
						<img :src="post.url" width="100%" height="100" class="bg-white p-1 shadow-sm" style="object-fit: cover;border-radius: 15px;">
					</div>

					<div class="col-6 mb-1 p-1 d-flex justify-content-center align-items-center">
						<button class="btn btn-link text-dark">
							<i class="fal fa-plus-circle fa-lg"></i>
						</button>
					</div>
				</div>
			</div>
		</div> -->
	</div>
</template>

<script type="text/javascript">
	import Notifications from './../sections/Notifications.vue';

	export default {
		components: {
            "notifications": Notifications
		},

		data() {
			return {
				profile: {},
			}
		},

		mounted() {
			this.profile = window._sharedData.user;
		}
	}
</script>

<style lang="scss" scoped>
	.avatar {
		border-radius: 15px;
	}

	.username {
		font-size: 15px;
		margin-bottom: -6px;
	}

	.display-name {
		font-size: 12px;
	}

	.follow {
		background-color: var(--primary);
		border-radius: 18px;
		font-weight: 600;
		padding: 5px 15px;
	}

	.btn-white {
		background-color: #fff;
		border: 1px solid #F3F4F6;
	}
</style>
