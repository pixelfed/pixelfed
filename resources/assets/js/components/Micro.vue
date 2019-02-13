<template>
	<div></div>
</template>

<style type="text/css" scoped>
	body {
		background-color: #ECECEC;
	}
	.bg-alt {
		background-color: #2C2E3B;
	}
	.cursor-pointer {
		cursor: pointer;
	}
	.ql-container {
		font-size: 14px;
	}
</style>

<script type="text/javascript">

export default {
	data() {
		return {
			footer: {
				about: '/site/about',
				help: '/site/help',
				classic: '/'
			},
			loading: true,
			profile: {},
			page: 1,
			activeFeed: 'home',
			homeFeed: [],
			notifications: {},
			composeType: false,
			notes: {
				active: false,
				status_id: null,
				username: null,
				like_count: 0,
				share_count: 0,
				replies: [],
				page: 1
			},
			profileCard: {
				account: {
					avatar: null
				}
			},
			quill: false,
			searching: false
		};
	}
}
</script>