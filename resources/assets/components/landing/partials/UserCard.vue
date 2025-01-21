<template>
	<div class="card bg-bluegray-800 landing-user-card">
		<div class="card-body">
			<div class="d-flex" style="gap: 15px;">
				<div class="flex-shrink-1">
					<a :href="account.url" target="_blank">
						<img class="rounded-circle" :src="account.avatar" onerror="this.src='/storage/avatars/default.jpg';this.onerror=null;" width="50" height="50">
					</a>
				</div>

				<div class="flex-grow-1">
					<div v-if="account.name" class="display-name">
						<a :href="account.url" target="_blank">{{ account.name }}</a>
					</div>
					<p class="username">
						<a :href="account.url" target="_blank">&commat;{{ account.username }}</a>
					</p>

					<div class="user-stats">
						<div class="user-stats-item user-select-none">{{ formatCount(account.statuses_count) }} Posts</div>
						<div class="user-stats-item user-select-none">{{ formatCount(account.followers_count) }} Followers</div>
						<div class="user-stats-item user-select-none">{{ formatCount(account.following_count) }} Following</div>
					</div>

					<div v-if="account.bio" class="user-bio">
						<p class="small text-bluegray-400 mb-0">{{ truncate(account.bio) }}</p>
					</div>
				</div>
			</div>
		</div>
	</div>
</template>

<script type="text/javascript">
	export default {
		props: ['account'],

		methods: {
			formatCount(val) {
				if(!val) {
					return 0;
				}

				return val.toLocaleString('en-CA', { compactDisplay: "short", notation: "compact"});
			},

			truncate(val, limit = 120) {
				if(!val || val.length < limit) {
					return val;
				}

				return val.slice(0, limit) + '...'
			}
		}
	}
</script>
