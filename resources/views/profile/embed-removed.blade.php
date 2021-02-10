<!DOCTYPE html>
<html lang="en">
<head>

	<meta charset="utf-8">
	<meta http-equiv="X-UA-Compatible" content="IE=edge">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<meta name="mobile-web-app-capable" content="yes">

	<title>Pixelfed | 404 Embed Not Found</title>

	<meta property="og:site_name" content="{{ config('app.name', 'pixelfed') }}">
	<meta property="og:title" content="{{ $title ?? config('app.name', 'pixelfed') }}">
	<meta name="medium" content="image">
	<meta name="theme-color" content="#10c5f8">
	<meta name="apple-mobile-web-app-capable" content="yes">
	<link rel="shortcut icon" type="image/png" href="/img/favicon.png?v=2">
	<link rel="apple-touch-icon" type="image/png" href="/img/favicon.png?v=2">
	<link href="{{ mix('css/app.css') }}" rel="stylesheet">
	<style type="text/css">
		.embed-card {
			display: flex;
			align-items: center;
			justify-content: center;
			width: 100vw;
			height: 100vh;
			min-height: 573px;
			background: #fff !important;
			margin: 0;
			padding-bottom: 0;
		}
		.status-card-embed {
			box-shadow: none;
			border-radius: 4px;
			overflow: hidden;
		}
	</style>
</head>
<body class="bg-white">
	<div class="embed-card border">
		<div class="card status-card-embed card-md-rounded-0 card-body shadow-none rounded-0">
			<div class="text-center p-5">
				<img src="/img/pixelfed-icon-color.svg" width="40px" height="40px">
				<p class="h2 py-3 font-weight-bold">Pixelfed</p>
				<p style="font-size:14px;font-weight: 500;" class="px-2 py-4">Cannot display profile embed, it may be deleted or set to private.</p>
				<p><a href="{{config('app.url')}}" class="font-weight-bold" target="_blank">Visit Pixelfed</a></p>
			</div>
		</div>
	</div>
	<script type="text/javascript">window.addEventListener("message",e=>{const t=e.data||{};window.parent&&"setHeight"===t.type&&window.parent.postMessage({type:"setHeight",id:t.id,height:document.getElementsByTagName("html")[0].scrollHeight},"*")});</script>
</body>
</html>
