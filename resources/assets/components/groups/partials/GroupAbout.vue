<template>
	<div class="group-about-component">
		<div class="row justify-content-center">
			<div class="col-12 col-md-7">
				<div class="card shadow-none border mt-3 rounded-lg">
					<div class="card-header bg-white">
						<h5 class="mb-0">About This Group</h5>
					</div>
					<div class="card-body">
						<p v-if="group.description && group.description.length > 1" class="description" v-html="group.description"></p>
						<p v-else class="description">This group does not have a description.</p>
						<p class="mb-0 font-weight-light text-lighter">Created: {{ timestampFormat(group.created_at) }}</p>
					</div>
				</div>
			</div>
			<div class="col-12 col-md-5">
				<div class="card card-body mt-3 shadow-none border rounded-lg">
					<div v-if="group.membership == 'all'" class="fact">
						<div class="fact-icon">
							<i class="fal fa-globe fa-lg"></i>
						</div>
						<div class="fact-body">
							<p class="fact-title">Public</p>
							<p class="fact-subtitle">Anyone can see who's in the group and what they post.</p>
						</div>
					</div>

					<div v-if="group.membership == 'private'" class="fact">
						<div class="fact-icon">
							<i class="fal fa-lock fa-lg"></i>
						</div>
						<div class="fact-body">
							<p class="fact-title">Private</p>
							<p class="fact-subtitle">Only members can see who's in the group and what they post.</p>
						</div>
					</div>

					<div class="fact">
						<div class="fact-icon">
							<i class="fal fa-eye fa-lg"></i>
						</div>
						<div class="fact-body">
							<p class="fact-title">Visible</p>
							<p class="fact-subtitle">Anyone can find this group.</p>
						</div>
					</div>

					<div class="fact">
						<div class="fact-icon">
							<i class="fal fa-map-marker-alt fa-lg"></i>
						</div>
						<div class="fact-body">
							<p class="fact-title">Fediverse</p>
							<p class="fact-subtitle">This group has not specified a location.</p>
						</div>
					</div>

					<div class="fact mb-0">
						<div class="fact-icon">
							<i class="fal fa-users fa-lg"></i>
						</div>
						<div class="fact-body">
							<p class="fact-title"">General</p>
							<p class="fact-subtitle">This group has not specified a category.</p>
						</div>
					</div>
				</div>
			</div>
		</div>
	</div>
</template>

<script type="text/javascript">
	export default {
		props: {
			group: {
				type: Object
			}
		},

		methods: {
			timestampFormat(date, showTime = false) {
				let ts = new Date(date);
				return showTime ? ts.toDateString() + ' · ' + ts.toLocaleTimeString() : ts.toDateString();
			}
		}
	}
</script>

<style lang="scss" scoped>
	.group-about-component {
		margin-bottom: 50vh;

		.title {
			font-size: 16px;
			font-weight: bold;
		}

		.description {
			font-size: 15px;
			font-weight:400;
			color: #6c757d;
			margin-bottom: 30px;
			white-space: break-spaces;
		}

		.fact {
			display: flex;
			align-items: center;
			margin-bottom: 1.5rem;

			&-body {
				flex: 1;
			}

			&-icon {
				width: 50px;
				text-align: center;
			}

			&-title {
				font-size: 17px;
				font-weight: 500;
				margin-bottom: 0;
			}

			&-subtitle {
				font-size: 14px;
				margin-bottom: 0;
				color: #6c757d;
			}
		}
	}
</style>
