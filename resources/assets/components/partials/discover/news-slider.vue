<template>
	<div class="rounded-3 overflow-hidden discover-news-slider">
		<div class="row align-items-center">
			<div class="col-xl-4 col-md-5 offset-lg-1">
				<div class="pt-5 pb-3 pb-md-5 px-4 px-lg-0">
					<p class="lead mb-3" style="font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;letter-spacing: -0.7px;font-weight:300;font-size:20px;">Introducing</p>
					<h2 class="h1 pb-0 mb-3" style="font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;letter-spacing: -1px;font-weight:700;">Emoji <span class="primary">Reactions</span></h2>
					<p class="lead" style="font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;letter-spacing: -0.7px;font-weight:400;font-size:17px;line-height:15px;">
						A new way to interact with content,<br /> now available!
					</p>
					<a href="#" class="btn btn-primary primary btn-sm">Learn more <i class="far fa-chevron-right fa-sm ml-2"></i></a>
				</div>
			</div>
			<div class="col-lg-6 col-md-7 offset-xl-1">
				<div class="position-relative d-flex flex-column align-items-center justify-content-center h-100">
					<svg class="d-none d-md-block position-absolute top-50 start-0 translate-middle-y" width="868" height="868" style="min-width: 868px;" viewBox="0 0 868 868" fill="none" xmlns="http://www.w3.org/2000/svg"><circle opacity="0.15" cx="434" cy="434" r="434" fill="#7dd3fc"></circle></svg>
					<div class="d-flex">
						<img src="/img/remoji/hushed_face.gif" class="position-relative zindex-3 mb-2 my-lg-4" width="100" alt="Illustration">
						<img src="/img/remoji/thumbs_up.gif" class="position-relative zindex-3 mb-2 my-lg-4" width="100" alt="Illustration">
						<img src="/img/remoji/sparkling_heart.gif" class="position-relative zindex-3 mb-2 my-lg-4" width="100" alt="Illustration">
					</div>
				</div>
			</div>
		</div>
		<div style="position: absolute;left: 50%;transform: translateX(-50%);bottom:10px;">
			<div class="d-flex">
				<button class="btn btn-link p-0">
					<i class="far fa-dot-circle"></i>
				</button>

				<button class="btn btn-link p-0 mx-2">
					<i class="far fa-circle"></i>
				</button>

				<button class="btn btn-link p-0">
					<i class="far fa-circle"></i>
				</button>
			</div>
		</div>
	</div>
</template>

<script type="text/javascript">
	export default {

	}
</script>

<style lang="scss">
	.discover-news-slider {
		position: relative;
		background-color: #e0f2fe;
	}
</style>
