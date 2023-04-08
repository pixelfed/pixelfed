<template>
	<div class="py-5">
		<p class="text-center text-uppercase font-weight-bold small text-justify">
			<a href="/site/help" class="text-bluegray-400 p-2">Help</a>
			<span class="mx-2 text-muted">·</span>
			<a href="/site/terms" class="text-bluegray-400 p-2">Terms</a>
			<span class="mx-2 text-muted">·</span>
			<a href="/site/privacy" class="text-bluegray-400 p-2">Privacy</a>
			<span class="mx-2 text-muted">·</span>
			<a href="https://pixelfed.org/mobile-apps" class="text-bluegray-400 p-2" target="_blank">Mobile Apps</a>
		</p>
		<p class="text-center text-bluegray-500 small mb-0">
			<span class="text-bluegray-500">© {{ getYear() }} {{config.domain}}</span>
			<span class="mx-2 text-muted">·</span>
			<a href="https://pixelfed.org" class="text-bluegray-500 font-weight-bold">Powered by Pixelfed</a>
			<span class="mx-2 text-muted">·</span>
			<span class="text-bluegray-500">v{{config.version}}</span>
		</p>
	</div>
</template>

<script type="text/javascript">
	export default {
		data() {
			return {
				config: window.pfl
			}
		},

		methods: {
			getYear() {
				return (new Date().getFullYear());
			}
		}
	}
</script>
