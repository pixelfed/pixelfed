<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="utf-8">
	<meta http-equiv="X-UA-Compatible" content="IE=edge">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<meta name="mobile-web-app-capable" content="yes">
	<title>Pixelfed Installer</title>
	<link rel="manifest" href="/manifest.json">
	<link href="/css/app.css" rel="stylesheet" data-stylesheet="light">
	<style type="text/css">
		body {
			background: linear-gradient(rgba(29, 38, 113, 0.8), rgba(195, 55, 100, 0.8));
		}
		.btn {
			min-width: 100px;
		}

		.btn-primary {
			min-width: 200px;
			font-weight: bold;
		}

		.form-group {
			margin-bottom: 1.5rem;
		}

		.form-group label {
			font-weight: bold;
		}

		.form-control::placeholder {
			color: #cbd5e1;
		}

		.form-control {
			background:#f5f8fa;
			border-color:#f5f8fa;
		}

		.form-control:focus {
			color: #000;
			background:#f5f8fa;
			border-color:#f5f8fa;
			box-shadow: none
		}
	</style>
</head>
	<body class="loggedIn">
		<main id="content">
			<noscript>
				<div class="container">
					<p class="pt-5 text-center lead">Please enable javascript to view this content.</p>
				</div>
			</noscript>
			<div class="container w-100 h-100">
				<div class="row w-100 h-100 d-flex align-items-center justify-content-center">
					<div class="col-12">
						<p class="text-center py-3">
							<a href="/installer">
								<img src="/img/pixelfed-icon-color.svg" width="60" height="60">
							</a>
						</p>

						<router-view></router-view>

						<p class="d-flex justify-content-between text-white pt-2 px-2">
							<a class="font-weight-bold text-white" href="https://docs.pixelfed.org/running-pixelfed/installation.html" target="_blank">Help</a>
							<span class="font-weight-bold">v{{config('pixelfed.version')}}</span>
						</p>
					</div>
				</div>
			</div>
		</main>
		<script type="text/javascript" src="/js/manifest.js"></script>
		<script type="text/javascript" src="/js/vendor.js"></script>
		<script type="text/javascript" src="/js/installer.js"></script>
	</body>
</html>
