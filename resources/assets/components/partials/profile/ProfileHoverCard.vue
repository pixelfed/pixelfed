<template>
	<div class="profile-hover-card">
		<div class="profile-hover-card-inner">
			<div class="d-flex justify-content-between align-items-start" style="max-width: 240px;">
				<a
					:href="profile.url"
					@click.prevent="goToProfile()">
					<img
						:src="profile.avatar"
						width="50"
						height="50"
						class="avatar"
						onerror="this.onerror=null;this.src='/storage/avatars/default.png?v=0';">
				</a>

				<div v-if="user.id == profile.id">
					<a class="btn btn-outline-primary px-3 py-1 font-weight-bold rounded-pill" href="/settings/home">Edit Profile</a>
				</div>

				<div v-if="user.id != profile.id && relationship">
					<button
						v-if="relationship.following"
						class="btn btn-outline-primary px-3 py-1 font-weight-bold rounded-pill"
						:disabled="isLoading"
						@click="performUnfollow()">
							<span v-if="isLoading"><b-spinner small /></span>
							<span v-else>Following</span>
						</button>
					<div v-else>
						<button
							v-if="!relationship.requested"
							class="btn btn-primary primary px-3 py-1 font-weight-bold rounded-pill"
							:disabled="isLoading"
							@click="performFollow()">
							<span v-if="isLoading"><b-spinner small /></span>
							<span v-else>Follow</span>
						</button>
						<button v-else class="btn btn-primary primary px-3 py-1 font-weight-bold rounded-pill" disabled>Follow Requested</button>
					</div>
				</div>
			</div>

			<p class="display-name">
				<a
					:href="profile.url"
					@click.prevent="goToProfile()"
					v-html="getDisplayName()">
				</a>
			</p>

			<div class="username">
				<a
					:href="profile.url"
					class="username-link"
					@click.prevent="goToProfile()">
					&commat;{{ getUsername() }}
				</a>

				<p v-if="user.id != profile.id && relationship && relationship.followed_by" class="username-follows-you">
					<span>Follows You</span>
				</p>
			</div>

			<p
				v-if="profile.hasOwnProperty('pronouns') && profile.pronouns && profile.pronouns.length"
				class="pronouns">
				{{ profile.pronouns.join(', ') }}
			</p>


			<p class="bio" v-html="bio"></p>

			<p class="stats">
				<span class="stats-following">
					<span class="following-count">{{ formatCount(profile.following_count) }}</span> Following
				</span>
				<span class="stats-followers">
					<span class="followers-count">{{ formatCount(profile.followers_count) }}</span> Followers
				</span>
			</p>
		</div>
	</div>
</template>

<script type="text/javascript">
	import ReadMore from './../post/ReadMore.vue';
	import { mapGetters } from 'vuex';

	export default {
		props: {
			profile: {
				type: Object
			},

			// relationship: {
			// 	type: Object
			// }
		},

		components: {
			ReadMore
		},

		data() {
			return {
				user: window._sharedData.user,
				bio: undefined,
				isLoading: false,
				relationship: undefined
			};
		},

		mounted() {
			this.rewriteLinks();
			this.relationship = this.$store.getters.getRelationship(this.profile.id);
			if(!this.relationship && this.profile.id != this.user.id) {
				axios.get('/api/pixelfed/v1/accounts/relationships', {
					params: {
						'id[]': this.profile.id
					}
				})
				.then(res => {
					this.relationship = res.data[0];
					this.$store.commit('updateRelationship', res.data);
				})
			}
		},

		computed: {
			...mapGetters([
				'getCustomEmoji'
			])
		},

		methods: {
			getDisplayName() {
				let self = this;
				let profile = this.profile;
				let dn = profile.display_name;
				if(!dn) {
					return profile.username;
				}

				if(dn.includes(':')) {
					let re = /(<a?)?:\w+:(\d{18}>)?/g;
					let un = dn.replaceAll(re, function(em) {
						let shortcode = em.slice(1, em.length - 1);
						let emoji = self.getCustomEmoji.filter(e => {
							return e.shortcode == shortcode;
						});
						return emoji.length ? `<img draggable="false" class="emojione custom-emoji" alt="${emoji[0].shortcode}" title="${emoji[0].shortcode}" src="${emoji[0].url}" data-original="${emoji[0].url}" data-static="${emoji[0].static_url}" width="16" height="16" onerror="this.onerror=null;this.src='/storage/emoji/missing.png';" />`: em;
					});
					return un;
				} else {
					return dn;
				}
			},

			getUsername() {
				let profile = this.profile;
				// if(profile.hasOwnProperty('local') && profile.local) {
				// 	return profile.acct + '@' + window.location.hostname;
				// }
				return profile.acct;
			},

			formatCount(val) {
				return App.util.format.count(val);
			},

			goToProfile() {
				this.$router.push({
					name: 'profile',
					path: `/i/web/profile/${this.profile.id}`,
					params: {
						id: this.profile.id,
						cachedProfile: this.profile,
						cachedUser: this.user
					}
				})
			},

			rewriteLinks() {
				let content = this.profile.note;
				let el = document.createElement('div');
				el.innerHTML = content;
				el.querySelectorAll('a[class*="hashtag"]')
				.forEach(elr => {
					let tag = elr.innerText;
					if(tag.substr(0, 1) == '#') {
						tag = tag.substr(1);
					}
					elr.removeAttribute('target');
					elr.setAttribute('href', '/i/web/hashtag/' + tag);
				})
				el.querySelectorAll('a:not(.hashtag)[class*="mention"], a:not(.hashtag)[class*="list-slug"]')
				.forEach(elr => {
					let name = elr.innerText;
					if(name.substr(0, 1) == '@') {
						name = name.substr(1);
					}
					if(this.profile.local == false && !name.includes('@')) {
						let domain = document.createElement('a');
						domain.href = this.profile.url;
						name = name + '@' + domain.hostname;
					}
					elr.removeAttribute('target');
					elr.setAttribute('href', '/i/web/username/' + name);
				})
				this.bio = el.outerHTML;
			},

			performFollow() {
				this.isLoading = true;
				this.$emit('follow');
				setTimeout(() => {
					this.relationship.following = true;
					this.isLoading = false;
				}, 1000);
			},

			performUnfollow() {
				this.isLoading = true;
				this.$emit('unfollow');
				setTimeout(() => {
					this.relationship.following = false;
					this.isLoading = false;
				}, 1000);
			}
		}
	}
</script>

<style lang="scss">
	.profile-hover-card {
		display: block;
		width: 300px;
		overflow: hidden;
		padding: 0.5rem;
		border: none;
		font-family: -apple-system,BlinkMacSystemFont,"Segoe UI",Roboto,Helvetica,Arial,sans-serif;

		.avatar {
			border-radius: 15px;
			box-shadow: 0 0.5rem 1rem rgb(0 0 0 / 15%) !important;
			margin-bottom: 0.5rem;
		}

		.display-name {
			max-width: 240px;
			word-break: break-word;
			font-weight: 800;
			margin-top: 5px;
			margin-bottom: 2px;
			line-height: 0.8;
			font-size: 16px;
			font-weight: 800 !important;
			user-select: all;
			font-family: -apple-system,BlinkMacSystemFont,"Segoe UI",Roboto,Helvetica,Arial,sans-serif;

			a {
				color: var(--body-color);
				text-decoration: none;
			}
		}

		.username {
			max-width: 240px;
			word-break: break-word;
			font-size: 12px;
			margin-top: 0;
			margin-bottom: 0.6rem;
			user-select: all;
			font-weight: 700;
			overflow: hidden;

			&-link {
				color: var(--text-lighter);
				text-decoration: none;
				margin-right: 4px;
			}

			&-follows-you {
				margin: 4px 0;

				span {
					color: var(--dropdown-item-color);
					background-color: var(--comment-bg);
					font-size: 12px;
					font-family: -apple-system,BlinkMacSystemFont,"Segoe UI",Roboto,Helvetica,Arial,sans-serif;
					font-weight: 500;
					padding: 2px 4px;
					line-height: 16px;
					border-radius: 6px;
				}
			}
		}


		.pronouns {
			font-size: 11px;
			color: #9CA3AF;
			margin-top: -0.8rem;
			margin-bottom: 0.6rem;
			font-weight: 600;
		}

		.bio {
			max-width: 240px;
			max-height: 60px;
			word-break: break-word;
			margin-bottom: 0;
			overflow: hidden;
			text-overflow: ellipsis;
			line-height: 1.2;
			font-size: 12px;
			color: var(--body-color);

			.invisible {
				display: none;
			}
		}

		.stats {
			margin-top: 0.5rem;
			margin-bottom: 0;
			font-size: 14px;
			user-select: none;
			color: var(--body-color);

			.stats-following {
				margin-right: 0.8rem;
			}

			.following-count,
			.followers-count {
				font-weight: 800;
			}
		}

		.btn {
			&.rounded-pill {
				min-width: 80px;
			}
		}
	}
</style>
