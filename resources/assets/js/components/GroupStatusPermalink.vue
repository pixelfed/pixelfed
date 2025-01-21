<template>
	<div class="group-status-permalink-component">
		<group-feed :group-id="gid" :permalinkMode="true" :permalinkId="sid" />
	</div>
</template>

<script type="text/javascript">
	import GroupFeed from '@/groups/GroupFeed.vue';

	export default {
		props: {
			gid: {
				type: String
			},

			sid: {
				type: String
			}
		},

		components: {
			"group-feed": GroupFeed
		}
	}
</script>
