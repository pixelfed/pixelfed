<template>
	<div class="timeline-status-component">
		<div class="card bg-bluegray-800 landing-post-card" style="border-radius: 15px;">
			<div class="card-header border-0 bg-bluegray-700" style="border-top-left-radius: 15px;border-top-right-radius: 15px;">
				<div class="media align-items-center">
					<a :href="post.account.url" class="mr-2" target="_blank">
						<img :src="post.account.avatar" style="border-radius:30px;" width="30" height="30" onerror="this.src='/storage/avatars/default.jpg?v=0';this.onerror=null;">
					</a>

					<div class="media-body d-flex justify-content-between align-items-center">
						<p class="font-weight-bold username mb-0">
							<a :href="post.account.url" class="text-white" target="_blank">&commat;{{ post.account.username }}</a>
						</p>

						<p class="font-weight-bold mb-0">
							<a v-if="range === 'daily'" :href="post.url" class="text-bluegray-500" target="_blank">Posted {{ timeago(post.created_at) }} ago</a>
							<a v-else :href="post.url" class="text-bluegray-400" target="_blank">View Post</a>
						</p>
					</div>
				</div>
			</div>
			<div class="card-body m-0 p-0">
				<post-content :status="post" />
			</div>
		</div>
	</div>
</template>

<script type="text/javascript">
	import PostContent from './../../partials/post/PostContent';

	export default {
		props: [
			'post',
			'range'
		],

		components: {
			'post-content': PostContent,
		},

		methods: {
			timestampToAgo(ts) {
				let date = Date.parse(ts);
				let seconds = Math.floor((new Date() - date) / 1000);
				let interval = Math.floor(seconds / 63072000);
				if (interval < 0) {
					return "0s";
				}
				if (interval >= 1) {
					return interval + "y";
				}
				interval = Math.floor(seconds / 604800);
				if (interval >= 1) {
					return interval + "w";
				}
				interval = Math.floor(seconds / 86400);
				if (interval >= 1) {
					return interval + "d";
				}
				interval = Math.floor(seconds / 3600);
				if (interval >= 1) {
					return interval + "h";
				}
				interval = Math.floor(seconds / 60);
				if (interval >= 1) {
					return interval + "m";
				}
				return Math.floor(seconds) + "s";
			},

			timeago(ts) {
				let short = this.timestampToAgo(ts);
				return short;
				if(
					short.endsWith('s') ||
					short.endsWith('m') ||
					short.endsWith('h')
				) {
					return short;
				}
				const intl = new Intl.DateTimeFormat(undefined, {
					year:  'numeric',
					month: 'short',
					day:   'numeric',
					hour: 'numeric',
					minute: 'numeric'
				});
				return intl.format(new Date(ts));
			},
		}
	}
</script>
