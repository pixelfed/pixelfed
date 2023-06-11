<template>
	<div class="media mb-2 align-items-center px-3 shadow-sm py-2 bg-white" style="border-radius: 15px;">
		<a href="#" @click.prevent="getProfileUrl(n.account)" v-b-tooltip.hover :title="n.account.acct">
			<img class="mr-3 shadow-sm" style="border-radius:8px" :src="n.account.avatar" alt="" width="40" height="40" onerror="this.onerror=null;this.src='/storage/avatars/default.jpg';">
		</a>

		<div class="media-body font-weight-light">
			<div v-if="n.type == 'favourite'">
				<p class="my-0">
					<a :href="displayProfileUrl(n.account)" @click.prevent="getProfileUrl(n.account)" class="font-weight-bold text-dark text-break" :title="n.account.acct">&commat;{{n.account.acct}}</a> {{ $t('notifications.liked') }} <a class="font-weight-bold" :href="displayPostUrl(n.status)" @click.prevent="getPostUrl(n.status)">post</a>.
				</p>
			</div>

			<div v-else-if="n.type == 'comment'">
				<p class="my-0">
					<a :href="displayProfileUrl(n.account)" @click.prevent="getProfileUrl(n.account)" class="font-weight-bold text-dark text-break" :title="n.account.acct">&commat;{{n.account.acct}}</a> {{ $t('notifications.commented') }} <a class="font-weight-bold" :href="displayPostUrl(n.status)" @click.prevent="getPostUrl(n.status)">post</a>.
				</p>
			</div>

			<!-- <div v-else-if="n.type == 'group:comment'">
				<p class="my-0">
					<a href="#" @click.prevent="getProfileUrl(n.account)" class="font-weight-bold text-dark text-break" :title="n.account.acct">&commat;{{n.account.acct}}</a> {{ $t('notifications.commented') }} <a class="font-weight-bold" v-bind:href="n.group_post_url">{{ $('notifications.groupPost') }}</a>.
				</p>
			</div> -->

			<div v-else-if="n.type == 'story:react'">
				<p class="my-0">
					<a :href="displayProfileUrl(n.account)" @click.prevent="getProfileUrl(n.account)" class="font-weight-bold text-dark text-break" :title="n.account.acct">&commat;{{n.account.acct}}</a> {{ $t('notifications.reacted') }} <a class="font-weight-bold" v-bind:href="'/account/direct/t/'+n.account.id">story</a>.
				</p>
			</div>

			<div v-else-if="n.type == 'story:comment'">
				<p class="my-0">
					<a :href="displayProfileUrl(n.account)" @click.prevent="getProfileUrl(n.account)" class="font-weight-bold text-dark text-break" :title="n.account.acct">&commat;{{n.account.acct}}</a> {{ $t('notifications.commented') }} <a class="font-weight-bold" v-bind:href="'/account/direct/t/'+n.account.id">{{ $t('notifications.story') }}</a>.
				</p>
			</div>

			<div v-else-if="n.type == 'mention'">
				<p class="my-0">
					<a :href="displayProfileUrl(n.account)" @click.prevent="getProfileUrl(n.account)" class="font-weight-bold text-dark text-break" :title="n.account.acct">&commat;{{n.account.acct}}</a> <a class="font-weight-bold" v-bind:href="mentionUrl(n.status)">{{ $t('notifications.mentioned') }}</a> {{ $t('notifications.you') }}.
				</p>
			</div>

			<div v-else-if="n.type == 'follow'">
				<p class="my-0">
					<a :href="displayProfileUrl(n.account)" @click.prevent="getProfileUrl(n.account)" class="font-weight-bold text-dark text-break" :title="n.account.acct">&commat;{{n.account.acct}}</a> {{ $t('notifications.followed') }} {{ $t('notifications.you') }}.
				</p>
			</div>

			<div v-else-if="n.type == 'share'">
				<p class="my-0">
					<a :href="displayProfileUrl(n.account)" @click.prevent="getProfileUrl(n.account)" class="font-weight-bold text-dark text-break" :title="n.account.acct">&commat;{{n.account.acct}}</a> {{ $t('notifications.shared') }} <a class="font-weight-bold" :href="displayPostUrl(n.status)" @click.prevent="getPostUrl(n.status)">{{ $t('notifications.post') }}</a>.
				</p>
			</div>

			<div v-else-if="n.type == 'modlog'">
				<p class="my-0">
					<a :href="displayProfileUrl(n.account)" @click.prevent="getProfileUrl(n.account)" class="font-weight-bold text-dark text-break" :title="n.account.acct">{{truncate(n.account.username)}}</a> {{ $t('notifications.updatedA') }} <a class="font-weight-bold" v-bind:href="n.modlog.url">{{ $t('notifications.modlog') }}</a>.
				</p>
			</div>

			<div v-else-if="n.type == 'tagged'">
				<p class="my-0">
					<a :href="displayProfileUrl(n.account)" @click.prevent="getProfileUrl(n.account)" class="font-weight-bold text-dark text-break" :title="n.account.acct">&commat;{{n.account.acct}}</a> {{ $t('notifications.tagged') }} <a class="font-weight-bold" v-bind:href="n.tagged.post_url">{{ $t('notifications.post') }}</a>.
				</p>
			</div>

			<div v-else-if="n.type == 'direct'">
				<p class="my-0">
					<a :href="displayProfileUrl(n.account)" @click.prevent="getProfileUrl(n.account)" class="font-weight-bold text-dark text-break" :title="n.account.acct">&commat;{{n.account.acct}}</a> {{ $t('notifications.sentA') }} <router-link class="font-weight-bold" :to="'/i/web/direct/thread/'+n.account.id">{{ $t('notifications.dm') }}</router-link>.
				</p>
			</div>

			<div v-else-if="n.type == 'group.join.approved'">
				<p class="my-0">
					{{ $t('notifications.yourApplication') }} <a :href="n.group.url" class="font-weight-bold text-dark text-break" :title="n.group.name">{{truncate(n.group.name)}}</a> {{ $t('notifications.applicationApproved') }}
				</p>
			</div>

			<div v-else-if="n.type == 'group.join.rejected'">
				<p class="my-0">
					{{ $t('notifications.yourApplication') }} <a :href="n.group.url" class="font-weight-bold text-dark text-break" :title="n.group.name">{{truncate(n.group.name)}}</a> {{ $t('notifications.applicationRejected') }}
				</p>
			</div>

			<div v-else>
				<p class="my-0 d-flex justify-content-between align-items-center">
					<span class="font-weight-bold">Notification</span>
					<span style="font-size:8px;">e_{{ n.type }}::{{ n.id }}</span>
				</p>
			</div>

			<div class="align-items-center">
				<span class="small text-muted" data-toggle="tooltip" data-placement="bottom" :title="n.created_at">{{timeAgo(n.created_at)}}</span>
			</div>
		</div>
		<div>
			<div v-if="n.status && n.status && n.status.media_attachments && n.status.media_attachments.length">
				<a href="#" @click.prevent="getPostUrl(n.status)">
					<img :src="n.status.media_attachments[0].preview_url" width="32px" height="32px">
				</a>
			</div>
			<div v-else-if="n.status && n.status.parent && n.status.parent.media_attachments && n.status.parent.media_attachments.length">
				<a :href="n.status.parent.url">
					<img :src="n.status.parent.media_attachments[0].preview_url" width="32px" height="32px">
				</a>
			</div>
			<!-- <div v-else-if="n.status && n.status.parent && n.status.parent.media_attachments && n.status.parent.media_attachments.length">
				<a :href="n.status.parent.url">
					<img :src="n.status.parent.media_attachments[0].preview_url" width="32px" height="32px">
				</a>
			</div> -->

			<!-- <div v-else>
				<a v-if="viewContext(n) != '/'" class="btn btn-outline-primary py-0 font-weight-bold" href="#" @click.prevent="viewContext(n)">View</a>
			</div> -->
		</div>
	</div>
</template>

<script type="text/javascript">
	export default {
		props: {
			n: {
				type: Object
			}
		},

		data() {
			return {
				profile: window._sharedData.user
			};
		},

		methods: {
			truncate(text, limit = 30) {
				if(text.length <= limit) {
					return text;
				}

				return text.slice(0, limit) + '...'
			},

			timeAgo(ts) {
				let date = Date.parse(ts);
				let seconds = Math.floor((new Date() - date) / 1000);
				let interval = Math.floor(seconds / 31536000);
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

			mentionUrl(status) {
				let username = status.account.username;
				let id = status.id;
				return '/p/' + username + '/' + id;
			},

			viewContext(n) {
				switch(n.type) {
					case 'follow':
						return this.getProfileUrl(n.account);
						return n.account.url;
					break;
					case 'mention':
						return n.status.url;
					break;
					case 'like':
					case 'favourite':
					case 'comment':
						return this.getPostUrl(n.status);
						// return n.status.url;
					break;
					case 'tagged':
						return n.tagged.post_url;
					break;
					case 'direct':
						return '/account/direct/t/'+n.account.id;
					break
				}
				return '/';
			},

			displayProfileUrl(account) {
				return `/i/web/profile/${account.id}`;
			},

			displayPostUrl(status) {
				return `/i/web/post/${status.id}`;
			},

			getProfileUrl(account) {
				this.$router.push({
					name: 'profile',
					path: `/i/web/profile/${account.id}`,
					params: {
						id: account.id,
						cachedProfile: account,
						cachedUser: this.profile
					}
				});
			},

			getPostUrl(status) {
				this.$router.push({
					name: 'post',
					path: `/i/web/post/${status.id}`,
					params: {
						id: status.id,
						cachedStatus: status,
						cachedProfile: this.profile
					}
				});
			}
		}
	}
</script>
