<template>
	<div class="app-drawer-component">
		<div class="mobile-footer-spacer d-block d-sm-none mt-5"></div>

		<div class="mobile-footer d-block d-sm-none fixed-bottom">
			<div class="card card-body rounded-0 px-0 pt-2 pb-3 box-shadow" style="border-top:1px solid var(--border-color)">
				<ul class="nav nav-pills nav-fill d-flex align-items-middle">
					<li class="nav-item">
						<router-link class="nav-link text-dark" to="/i/web">
							<p>
								<i class="far fa-home fa-lg"></i>
							</p>
							<p class="nav-link-label">
								<span>Home</span>
							</p>
						</router-link>
					</li>

					<li class="nav-item">
						<router-link class="nav-link text-dark" to="/i/web/timeline/local">
							<p>
								<i class="far fa-stream fa-lg"></i>
							</p>
							<p class="nav-link-label">
								<span>Local</span>
							</p>
						</router-link>
					</li>

					<li class="nav-item">
						<router-link class="nav-link text-dark" to="/i/web/compose">
							<p>
								<i class="far fa-plus-circle fa-lg"></i>
							</p>
							<p class="nav-link-label">
								<span>New</span>
							</p>
						</router-link>
					</li>

					<li class="nav-item">
						<router-link class="nav-link text-dark" to="/i/web/notifications">
							<p>
								<i class="far fa-bell fa-lg"></i>
							</p>
							<p class="nav-link-label">
								<span>Alerts</span>
							</p>
						</router-link>
					</li>

					<li class="nav-item">
						<router-link class="nav-link text-dark" :to="'/i/web/profile/' + user.id">
							<p>
								<i class="far fa-user fa-lg"></i>
							</p>
							<p class="nav-link-label">
								<span>Profile</span>
							</p>
						</router-link>
					</li>
				</ul>
			</div>
		</div>
	</div>
</template>

<script type="text/javascript">
	export default {
		data() {
			return {
				user: window._sharedData.user
			}
		}
	}
</script>

<style lang="scss">
	.app-drawer-component {
		.nav-link {
			padding: 0.5rem 0.1rem;

			&.active {
				background-color: transparent;
			}

			&.router-link-exact-active {
				background-color: transparent;
				color: var(--primary) !important;
			}

			p {
				margin-bottom: 0;
			}

			&-label {
				font-family: -apple-system,BlinkMacSystemFont,"Segoe UI",Roboto,Helvetica,Arial,sans-serif;
				margin-top: 0;
				font-size: 10px;
				text-transform: uppercase;
				font-weight: 700;
				opacity: 0.6;
			}
		}
	}
</style>
