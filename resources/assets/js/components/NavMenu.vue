<template>
	<div>
		<nav class="navbar navbar-expand navbar-light navbar-laravel shadow-none border-bottom sticky-top py-1">
			<div class="container">
				<a class="navbar-brand d-flex align-items-center" href="/" title="Logo">
					<img src="/img/pixelfed-icon-color.svg" height="30px" class="px-2" loading="eager">
					<span class="font-weight-bold mb-0 d-none d-sm-block" style="font-size:20px;">{{ config.site.name }}</span>
				</a>
				<div v-if="loaded && loggedIn" class="collapse navbar-collapse">
					<ul class="navbar-nav d-none d-md-block mx-auto">
						<form class="form-inline search-bar" method="get" action="/i/results">
							<input class="form-control form-control-sm" name="q" placeholder="Search ..." aria-label="search" autocomplete="off" required style="line-height: 0.6;width:200px">
						</form>
					</ul>

					<div class="ml-auto">
						<ul class="navbar-nav">
							<div class="d-none d-md-block">
								<li class="nav-item px-md-2">
									<a class="nav-link font-weight-bold text-muted" href="/discover" title="Discover" data-toggle="tooltip" data-placement="bottom">
										<i class="far fa-compass fa-lg"></i>
									</a>
								</li>
							</div>
							<div class="d-none d-md-block">
								<li class="nav-item px-md-2">
									<a class="nav-link font-weight-bold text-muted" href="/account/activity" title="Notifications" data-toggle="tooltip" data-placement="bottom">
										<span class="fa-layers fa-fw">
											<i class="far fa-bell fa-lg"></i>
											<span class="fa-layers-counter mr-n2 mt-n1" style="background:Tomato"></span>
										</span>
									</a>
								</li>
							</div>
							<li class="nav-item dropdown ml-2">
								<a id="navbarDropdown" class="nav-link dropdown-toggle" href="#" role="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false" title="User Menu" data-placement="bottom">
									<i class="far fa-user fa-lg text-muted"></i>
								</a>

								<div class="dropdown-menu dropdown-menu-right" aria-labelledby="navbarDropdown">
									<div class="dropdown-item font-weight-bold cursor-pointer" onclick="App.util.compose.post()">
										<span class="fas fa-plus-square pr-2 text-lighter"></span>
										New Post
									</div>
									<div class="dropdown-divider"></div>
									<a class="dropdown-item font-weight-bold" href="/">
										<span class="fas fa-home pr-2 text-lighter"></span>
										Home
									</a>
									<a class="dropdown-item font-weight-bold" href="/timeline/public">
										<span class="fas fa-stream pr-2 text-lighter"></span>
										Local
									</a>
									<div class="dropdown-divider"></div>
									<a class="dropdown-item font-weight-bold" href="/i/me">
										<span class="far fa-user pr-2 text-lighter"></span>
										My Profile
									</a>
									<a class="d-block d-md-none dropdown-item font-weight-bold" href="/discover">
										<span class="far fa-compass pr-2 text-lighter"></span>
										Discover
									</a>
									<a class="dropdown-item font-weight-bold" href="/notifications">
										<span class="far fa-bell pr-2 text-lighter"></span>
										Notifications
									</a>
									<a class="dropdown-item font-weight-bold" href="/settings/home">
										<span class="fas fa-cog pr-2 text-lighter"></span>
										Settings
									</a>
									<div v-if="curUser.is_admin">
										<div class="dropdown-divider"></div>
										<a class="dropdown-item font-weight-bold" href="/i/admin/dashboard">
											<span class="fas fa-shield-alt fa-sm pr-2 text-lighter"></span>
											Admin
										</a>
									</div>
									<div class="dropdown-divider"></div>
									<a class="dropdown-item font-weight-bold" href="/logout" @click="logout">
										<span class="fas fa-sign-out-alt pr-2"></span>
										Logout
									</a>
								</div>
							</li>
						</ul>
					</div>
				</div>
				<div v-if="loaded && !loggedIn" class="collapse navbar-collapse">
					<ul class="navbar-nav ml-auto">
						<li>
							<a class="nav-link font-weight-bold text-primary" href="/login" title="Login">
								Login
							</a>
						</li>
						<li v-if="config.open_registration">
							<a class="nav-link font-weight-bold" href="/register" title="Register">
								Register
							</a>
						</li>
					</ul>
				</div>
			</div>
		</nav>

	</div>
</template>

<style type="text/css" scoped>
.fa-layers-counter, .fa-layers-text {
	display: inline-block;
	position: absolute;
	text-align: center;
}
.fa-layers .far {
	bottom: 0;
	left: 0;
	margin: auto;
	position: absolute;
	right: 0;
	top: 0;
}
.fa-fw {
	text-align: center;
	width: 1.25em;
}
.fa-layers {
	display: inline-block;
	height: 1em;
	position: relative;
	text-align: center;
	vertical-align: -.125em;
	width: 1em;
}
.fa-layers-counter {
	background-color: #ff253a;
	border-radius: 1em;
	-webkit-box-sizing: border-box;
	box-sizing: border-box;
	color: #fff;
	height: 1.5em;
	line-height: 1;
	max-width: 5em;
	min-width: 1.5em;
	overflow: hidden;
	padding: .25em;
	right: 0;
	text-overflow: ellipsis;
	top: 0;
	-webkit-transform: scale(.25);
	transform: scale(.25);
	-webkit-transform-origin: top right;
	transform-origin: top right;
}
</style>

<script type="text/javascript">
	export default {
		data() {
			return {
				config: window.App.config,
				curUser: {},
				loggedIn: false,
				loaded: false
			}
		},

		mounted() {
			this.timeout();
		},

		methods: {
			logout() {
				axios.post('/logout')
				.then(res => {
					window.location.href = '/';
				});
			},

			timeout() {
				let self = this;
				setTimeout(function() {
					self.curUser = window._sharedData.curUser;
					self.loggedIn = self.curUser.hasOwnProperty('username');
					self.loaded = true;
				}, 1000);
			}
		}
	}
</script>