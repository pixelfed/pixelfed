<template>
	<div class="discover-spotlight">
		<div class="card bg-dark text-white">
			<div class="card-body my-5 p-5 w-100 h-100 d-flex justify-content-center align-items-center">
				<transition enter-active-class="animate__animated animate__fadeInDownBig" leave-active-class="animate__animated animate__fadeOutDownBig" mode="out-in">
					<div v-if="isLoaded" class="row">
						<div class="col-5">
							<h1 class="display-3 font-default mb-3" style="line-height: 1;font-weight: 600;">
								Spotlight
							</h1>
							<h1 class="display-5 font-default" style="line-height: 1;">
								<span class="text-muted" style="line-height: 0.8;font-weight: 200;letter-spacing: -1.2px;">
									Community curated
									collection of creators
								</span>
							</h1>

							<p class="lead font-default mt-4">This weeks collection is curated by <span class="primary">@dansup</span></p>
						</div>
						<div class="col-7 d-flex justify-content-between">
							<div class="text-center mr-4">
								<h5 class="font-default mb-2">@dansup</h5>
								<img src="https://pixelfed.test/storage/avatars/321493203255693312/skvft7.jpg?v=33" draggable="false" onerror="this.onerror=null;this.src='/storage/avatars/default.png?v=0';" class="avatar shadow cursor-pointer" width="160" height="160">
								<button class="btn btn-outline-light btn-sm rounded-pill py-1 mt-2 font-default">View Profile</button>
							</div>

							<div class="text-center mr-4">
								<h5 class="font-default mb-2">@dansup</h5>
								<img src="https://pixelfed.test/storage/avatars/321493203255693312/skvft7.jpg?v=33" draggable="false" onerror="this.onerror=null;this.src='/storage/avatars/default.png?v=0';" class="avatar shadow cursor-pointer" width="160" height="160">
								<button class="btn btn-outline-light btn-sm rounded-pill py-1 mt-2 font-default">View Profile</button>
							</div>

							<div class="text-center">
								<h5 class="font-default mb-2">@dansup</h5>
								<img src="https://pixelfed.test/storage/avatars/321493203255693312/skvft7.jpg?v=33" draggable="false" onerror="this.onerror=null;this.src='/storage/avatars/default.png?v=0';" class="avatar shadow cursor-pointer" width="160" height="160">
								<button class="btn btn-outline-light btn-sm rounded-pill py-1 mt-2 font-default">View Profile</button>
							</div>
						</div>
					</div>
				</transition>

				<div v-if="!isLoaded" class="">
					<b-spinner type="grow" />
				</div>
			</div>
		</div>
	</div>
</template>

<script type="text/javascript">
	export default {
		data() {
			return {
				isLoaded: false
			}
		},

		mounted() {
			setTimeout(() => {
				this.isLoaded = true;
			}, 1000);
		}
	}
</script>

<style lang="scss">
.discover-spotlight {
	overflow: hidden;

	.card-body {
		min-height: 322px;
	}

	.font-default {
		font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
		letter-spacing: -0.7px;
	}

	.bg-stellar {
		background: #7474BF;
		background: -webkit-linear-gradient(to right, #348AC7, #7474BF);
		background: linear-gradient(to right, #348AC7, #7474BF);
	}

	.bg-berry {
		background: #5433FF;
		background: -webkit-linear-gradient(to right, #acb6e5, #86fde8);
		background: linear-gradient(to right, #acb6e5, #86fde8);
	}

	.bg-midnight {
		background: #232526;
		background: -webkit-linear-gradient(to right, #414345, #232526);
		background: linear-gradient(to right, #414345, #232526);
	}
}
</style>
