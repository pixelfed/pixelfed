<template>
	<div class="profile-sidebar-component">
		<div>
			<div class="d-block d-md-none">
				<div class="media user-card user-select-none">
					<div style="position: relative;">
						<img :src="profile.avatar" class="avatar shadow cursor-pointer" draggable="false" onerror="this.onerror=null;this.src='/storage/avatars/default.png?v=0';">
					</div>
					<div class="media-body">
						<p class="display-name" v-html="getDisplayName()"></p>
						<p class="username" :class="{ remote: !profile.local }">
							<a v-if="!profile.local" :href="profile.url" class="primary">&commat;{{ profile.acct }}</a>
							<span v-else>&commat;{{ profile.acct }}</span>
							<span v-if="profile.locked">
								<i class="fal fa-lock ml-1 fa-sm text-lighter"></i>
							</span>
						</p>
						<div class="stats">
							<div class="stats-posts" @click="toggleTab('index')">
								<div class="posts-count">{{ formatCount(profile.statuses_count) }}</div>
								<div class="stats-label">
									{{ $t('profile.posts') }}
								</div>
							</div>
							<div class="stats-followers" @click="toggleTab('followers')">
								<div class="followers-count">{{ formatCount(profile.followers_count) }}</div>
								<div class="stats-label">
									{{ $t('profile.followers') }}
								</div>
							</div>
							<div class="stats-following" @click="toggleTab('following')">
								<div class="following-count">{{ formatCount(profile.following_count) }}</div>
								<div class="stats-label">
									{{ $t('profile.following') }}
								</div>
							</div>
						</div>
					</div>
				</div>
			</div>

			<div class="d-none d-md-flex justify-content-between align-items-center">
				<button class="btn btn-link" @click="goBack()">
					<i class="far fa-chevron-left fa-lg text-lighter"></i>
				</button>
				<div>
					<img :src="getAvatar()" class="avatar img-fluid shadow border" onerror="this.onerror=null;this.src='/storage/avatars/default.png?v=0';">
					<p v-if="profile.is_admin" class="text-right" style="margin-top: -30px;"><span class="admin-label">Admin</span></p>
				</div>
				<!-- <button class="btn btn-link">
					<i class="far fa-lg fa-cog text-lighter"></i>
				</button> -->

				<b-dropdown
					variant="link"
					right
					no-caret>
					<template #button-content>
						<i class="far fa-lg fa-cog text-lighter"></i>
					</template>

					<b-dropdown-item v-if="profile.local" href="#" link-class="font-weight-bold" @click.prevent="goToOldProfile()">View in old UI</b-dropdown-item>
					<b-dropdown-item href="#" link-class="font-weight-bold" @click.prevent="copyTextToClipboard(profile.url)">Copy Link</b-dropdown-item>


					<b-dropdown-item v-if="profile.local" :href="'/users/' + profile.username + '.atom'" link-class="font-weight-bold">Atom feed</b-dropdown-item>

					<div v-if="profile.id == user.id">
						<b-dropdown-divider></b-dropdown-divider>
						<b-dropdown-item href="/settings/home" link-class="font-weight-bold">
							<i class="far fa-cog mr-1"></i> Settings
						</b-dropdown-item>
					</div>

					<div v-else>
						<b-dropdown-item v-if="!profile.local" :href="profile.url" link-class="font-weight-bold">View Remote Profile</b-dropdown-item>
						<b-dropdown-item :href="'/i/web/direct/thread/' + profile.id" link-class="font-weight-bold">Direct Message</b-dropdown-item>
					</div>

					<div v-if="profile.id !== user.id">
						<b-dropdown-divider></b-dropdown-divider>

						<b-dropdown-item link-class="font-weight-bold" @click="handleMute()">
							{{ relationship.muting ? 'Unmute' : 'Mute' }}
						</b-dropdown-item>

						<b-dropdown-item link-class="font-weight-bold" @click="handleBlock()">
							{{ relationship.blocking ? 'Unblock' : 'Block' }}
						</b-dropdown-item>

						<b-dropdown-item :href="'/i/report?type=user&id=' + profile.id" link-class="text-danger font-weight-bold">Report</b-dropdown-item>
					</div>
				</b-dropdown>
			</div>

			<div class="d-none d-md-block text-center">
				<p v-html="getDisplayName()" class="display-name"></p>

				<p class="username" :class="{ remote: !profile.local }">
					<a v-if="!profile.local" :href="profile.url" class="primary">&commat;{{ profile.acct }}</a>
					<span v-else>&commat;{{ profile.acct }}</span>
					<span v-if="profile.locked">
						<i class="fal fa-lock ml-1 fa-sm text-lighter"></i>
					</span>
				</p>

				<p v-if="user.id != profile.id && (relationship.followed_by || relationship.muting || relationship.blocking)" class="mt-n3 text-center">
					<span v-if="relationship.followed_by" class="badge badge-primary p-1">Follows you</span>
					<span v-if="relationship.muting" class="badge badge-dark p-1 ml-1">Muted</span>
					<span v-if="relationship.blocking" class="badge badge-danger p-1 ml-1">Blocked</span>
				</p>
			</div>

			<div class="d-none d-md-block stats py-2">
				<div class="d-flex justify-content-between">
					<button
						class="btn btn-link stat-item"
						@click="toggleTab('index')">
						<strong :title="profile.statuses_count">{{ formatCount(profile.statuses_count) }}</strong>
						<span>{{ $t('profile.posts') }}</span>
					</button>

					<button
						class="btn btn-link stat-item"
						@click="toggleTab('followers')">
						<strong :title="profile.followers_count">{{ formatCount(profile.followers_count) }}</strong>
						<span>{{ $t('profile.followers') }}</span>
					</button>

					<button
						class="btn btn-link stat-item"
						@click="toggleTab('following')">
						<strong :title="profile.following_count">{{ formatCount(profile.following_count) }}</strong>
						<span>{{ $t('profile.following') }}</span>
					</button>
				</div>
			</div>

			<div class="d-flex align-items-center mb-3 mb-md-0">
				<div v-if="user.id === profile.id" style="flex-grow: 1;">
					<!-- <router-link
						class="btn btn-light font-weight-bold btn-block follow-btn"
						to="/i/web/settings">
						{{ $t('profile.editProfile') }}
					</router-link> -->
                    <a class="btn btn-light font-weight-bold btn-block follow-btn" href="/settings/home">{{ $t('profile.editProfile') }}</a>
					<a v-if="!profile.locked" class="btn btn-light font-weight-bold btn-block follow-btn mt-md-n4" href="/i/web/my-portfolio">
                        My Portfolio
                        <span class="badge badge-success ml-1">NEW</span>
                    </a>
				</div>

				<div v-else-if="profile.locked" style="flex-grow: 1;">
					<template v-if="!relationship.following && !relationship.requested">
						<button
							class="btn btn-primary font-weight-bold btn-block follow-btn"
							@click="follow"
							:disabled="relationship.blocking">
							Request Follow
						</button>
						<p v-if="relationship.blocking" class="mt-n4 text-lighter" style="font-size: 11px">You need to unblock this account before you can request to follow.</p>
					</template>

					<div v-else-if="relationship.requested">
						<button class="btn btn-primary font-weight-bold btn-block follow-btn" disabled>
							{{ $t('profile.followRequested') }}
						</button>

						<p class="small font-weight-bold text-center mt-n4">
							<a href="#" @click.prevent="cancelFollowRequest()">Cancel Follow Request</a>
						</p>
					</div>

					<button
						v-else-if="relationship.following"
						class="btn btn-primary font-weight-bold btn-block unfollow-btn"
						@click="unfollow">
						{{ $t('profile.unfollow') }}
					</button>
				</div>

				<div v-else style="flex-grow: 1;">
					<template v-if="!relationship.following">
						<button
							class="btn btn-primary font-weight-bold btn-block follow-btn"
							@click="follow"
							:disabled="relationship.blocking">
							{{ $t('profile.follow') }}
						</button>
						<p v-if="relationship.blocking" class="mt-n4 text-lighter" style="font-size: 11px">You need to unblock this account before you can follow.</p>
					</template>

					<button
						v-else
						class="btn btn-primary font-weight-bold btn-block unfollow-btn"
						@click="unfollow">
						{{ $t('profile.unfollow') }}
					</button>
				</div>

				<div class="d-block d-md-none ml-3">
					<b-dropdown
						variant="link"
						right
						no-caret>
						<template #button-content>
							<i class="far fa-lg fa-cog text-lighter"></i>
						</template>

						<b-dropdown-item v-if="profile.local" href="#" link-class="font-weight-bold" @click.prevent="goToOldProfile()">View in old UI</b-dropdown-item>
						<b-dropdown-item href="#" link-class="font-weight-bold" @click.prevent="copyTextToClipboard(profile.url)">Copy Link</b-dropdown-item>


						<b-dropdown-item v-if="profile.local" :href="'/users/' + profile.username + '.atom'" link-class="font-weight-bold">Atom feed</b-dropdown-item>

						<div v-if="profile.id == user.id">
							<b-dropdown-divider></b-dropdown-divider>
							<b-dropdown-item href="/settings/home" link-class="font-weight-bold">
								<i class="far fa-cog mr-1"></i> Settings
							</b-dropdown-item>
						</div>

						<div v-else>
							<b-dropdown-item v-if="!profile.local" :href="profile.url" link-class="font-weight-bold">View Remote Profile</b-dropdown-item>

							<b-dropdown-item :href="'/i/web/direct/thread/' + profile.id" link-class="font-weight-bold">Direct Message</b-dropdown-item>
						</div>

						<div v-if="profile.id !== user.id">
							<b-dropdown-divider></b-dropdown-divider>

							<b-dropdown-item link-class="font-weight-bold" @click="handleMute()">
								{{ relationship.muting ? 'Unmute' : 'Mute' }}
							</b-dropdown-item>

							<b-dropdown-item link-class="font-weight-bold" @click="handleBlock()">
								{{ relationship.blocking ? 'Unblock' : 'Block' }}
							</b-dropdown-item>

							<b-dropdown-item :href="'/i/report?type=user&id=' + profile.id" link-class="text-danger font-weight-bold">Report</b-dropdown-item>
						</div>
					</b-dropdown>
				</div>
			</div>

			<div v-if="profile.note && renderedBio && renderedBio.length" class="bio-wrapper card shadow-none">
				<div class="card-body">
					<div class="bio-body">
						<div v-html="renderedBio"></div>
					</div>
				</div>
			</div>

			<div class="d-none d-md-block card card-body shadow-none py-2">
				<p v-if="profile.website" class="small">
					<span class="text-lighter mr-2">
						<i class="far fa-link"></i>
					</span>

					<span>
						<a :href="profile.website" class="font-weight-bold">{{ profile.website }}</a>
					</span>
				</p>

				<p class="mb-0 small">
					<span class="text-lighter mr-2">
						<i class="far fa-clock"></i>
					</span>

					<span v-if="profile.local">
						{{ $t('profile.joined') }} {{ getJoinedDate() }}
					</span>
					<span v-else>
						{{ $t('profile.joined') }} {{ getJoinedDate() }}

						<span class="float-right primary">
							<i class="far fa-info-circle" v-b-tooltip.hover title="This user is from a remote server and may have created their account before this date"></i>
						</span>
					</span>
				</p>
			</div>

			<div class="d-none d-md-flex sidebar-sitelinks">
				<a href="/site/about">{{ $t('navmenu.about') }}</a>
				<router-link to="/i/web/help">{{ $t('navmenu.help') }}</router-link>
				<router-link to="/i/web/language">{{ $t('navmenu.language') }}</router-link>
				<a href="/site/terms">{{ $t('navmenu.privacy') }}</a>
				<a href="/site/terms">{{ $t('navmenu.terms') }}</a>
			</div>

			<div class="d-none d-md-block sidebar-attribution">
				<a href="https://pixelfed.org" class="font-weight-bold">Powered by Pixelfed</a>
			</div>
		</div>

		<b-modal
			ref="fullBio"
			centered
			hide-footer
			ok-only
			ok-title="Close"
			ok-variant="light"
			:scrollable="true"
			body-class="p-md-5"
			title="Bio"
			>
			<div v-html="profile.note"></div>
		</b-modal>
	</div>
</template>

<script type="text/javascript">
	import { mapGetters } from 'vuex'

	export default {
		props: {
			profile: {
				type: Object
			},

			relationship: {
				type: Object,
				default: (function() {
					return {
						following: false,
						followed_by: false
					};
				})
			},

			user: {
				type: Object
			}
		},

		computed: {
			...mapGetters([
				'getCustomEmoji'
			])
		},

		data() {
			return {
				'renderedBio': ''
			};
		},

		mounted() {
			this.$nextTick(() => {
				this.setBio();
			});
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
					// let re = /:(::|[^:\n])+:/g;
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

			formatCount(val) {
				return App.util.format.count(val);
			},

			goBack() {
				this.$emit('back');
			},

			showFullBio() {
				this.$refs.fullBio.show();
			},

			toggleTab(tab) {
				event.currentTarget.blur();
                if(['followers', 'following'].includes(tab)) {
                    this.$router.push('/i/web/profile/' + this.profile.id + '/' + tab);
                    return;
                } else {
				    this.$emit('toggletab', tab);
                }
			},

			getJoinedDate() {
				let d = new Date(this.profile.created_at);
				let month = new Intl.DateTimeFormat("en-US", { month: "long" }).format(d);
				let year = d.getFullYear();
				return `${month} ${year}`;
			},

			follow() {
				event.currentTarget.blur();
				this.$emit('follow');
			},

			unfollow() {
				event.currentTarget.blur();
				this.$emit('unfollow');
			},

			setBio() {
				if(!this.profile.note.length) {
					return;
				}
				if(this.profile.local) {
					let content = this.profile.hasOwnProperty('note_text') ?
						this.profile.note_text :
						this.profile.note.replace(/(<([^>]+)>)/gi, "");
					this.renderedBio = window.pftxt.autoLink(content, {
						usernameUrlBase: '/i/web/profile/@',
						hashtagUrlBase: '/i/web/hashtag/'
					})
				} else {
					if(this.profile.note === '<p></p>') {
						this.renderedBio = null;
						return;
					}
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
					this.renderedBio = el.outerHTML;
				}
			},

			getAvatar() {
				if(this.profile.id == this.user.id) {
					return window._sharedData.user.avatar;
				}

				return this.profile.avatar;
			},

			copyTextToClipboard(val) {
				App.util.clipboard(val);
			},

			goToOldProfile() {
				if(this.profile.local) {
					location.href = this.profile.url + '?fs=1';
				} else {
					location.href = '/i/web/profile/_/' + this.profile.id;
				}
			},

			handleMute() {
				let msg = this.relationship.muting ? 'unmuted' : 'muted';
				let url = this.relationship.muting == true ? '/i/unmute' : '/i/mute';
				axios.post(url, {
					type: 'user',
					item: this.profile.id
				}).then(res => {
					this.$emit('updateRelationship', res.data);
					swal('Success', 'You have successfully '+ msg +' ' + this.profile.acct, 'success');
				}).catch(err => {
					if(err.response.status === 422) {
						swal({
							title: 'Error',
							text: err.response?.data?.error,
							icon: "error",
							buttons: {
								review: {
									text: "Review muted accounts",
									value: "review",
									className: "btn-primary"
								},
								cancel: true,
							}
						})
						.then((val) => {
							if(val && val == 'review') {
								location.href = '/settings/privacy/muted-users';
								return;
							}
						});
					} else {
						swal('Error', 'Something went wrong. Please try again later.', 'error');
					}
				});
			},

			handleBlock() {
				let msg = this.relationship.blocking ? 'unblock' : 'block';
				let url = this.relationship.blocking == true ? '/i/unblock' : '/i/block';
				axios.post(url, {
					type: 'user',
					item: this.profile.id
				}).then(res => {
					this.$emit('updateRelationship', res.data);
					swal('Success', 'You have successfully '+ msg +'ed ' + this.profile.acct, 'success');
				}).catch(err => {
					if(err.response.status === 422) {
						swal({
							title: 'Error',
							text: err.response?.data?.error,
							icon: "error",
							buttons: {
								review: {
									text: "Review blocked accounts",
									value: "review",
									className: "btn-primary"
								},
								cancel: true,
							}
						})
						.then((val) => {
							if(val && val == 'review') {
								location.href = '/settings/privacy/blocked-users';
								return;
							}
						});
					} else {
						swal('Error', 'Something went wrong. Please try again later.', 'error');
					}
				});
			},

			cancelFollowRequest() {
				if(!window.confirm('Are you sure you want to cancel your follow request?')) {
					return;
				}
				event.currentTarget.blur();
				this.$emit('unfollow');
			}
		}
	}
</script>

<style lang="scss">
	.profile-sidebar-component {
		margin-bottom: 1rem;

		.avatar {
			width: 140px;
			margin-bottom: 1rem;
			border-radius: 15px;
		}

		.display-name {
			font-size: 20px;
			margin-bottom: 0;
			word-break: break-word;
			font-size: 15px;
			font-weight: 800 !important;
			user-select: all;
			line-height: 0.8;
			font-family: -apple-system,BlinkMacSystemFont,"Segoe UI",Roboto,Helvetica,Arial,sans-serif;
		}

		.username {
			color: var(--primary);
			font-size: 14px;
			font-weight: 600;
			font-family: -apple-system,BlinkMacSystemFont,"Segoe UI",Roboto,Helvetica,Arial,sans-serif;

			&.remote {
				font-size: 11px;
			}
		}

		.stats {
			margin-bottom: 1rem;

			.stat-item {
				max-width: 33%;
				flex: 0 0 33%;
				text-align: center;
				margin: 0;
				padding: 0;
				text-decoration: none;

				strong {
					display: block;
					color: var(--body-color);
					font-size: 18px;
					line-height: 0.9;
				}

				span {
					display: block;
					font-size: 12px;
					color: #B8C2CC;
				}
			}
		}

		.follow-btn {
			@media (min-width: 768px) {
				margin-bottom: 2rem;
			}

			&.btn-primary {
				background-color: var(--primary);
			}

			&.btn-light {
				border-color: var(--input-border);
			}
		}

		.unfollow-btn {
			@media (min-width: 768px) {
				margin-bottom: 2rem;
			}

			background-color: rgba(59, 130, 246, 0.7);
		}

		.bio-wrapper {
			margin-bottom: 1rem;

			.bio-body {
				display: block;
				position: relative;
				font-size: 12px !important;
				white-space: pre-wrap;

				.username {
					font-size: 12px !important;
				}

				&.long {
					max-height: 80px;
					overflow: hidden;

					&:after {
						content: '';
						width: 100%;
						height: 100%;
						position: absolute;
						top: 0;
						left: 0;
						background: linear-gradient(180deg, transparent 0, rgba(255, 255, 255, .9) 60%, #fff 90%);
						z-index: 2;
					}
				}

				p {
					margin-bottom: 0 !important;
				}
			}

			.bio-more {
				position: relative;
				z-index: 3;
			}
		}

		.admin-label {
			padding: 1px 5px;
			font-size: 12px;
			color: #B91C1C;
			background: #FEE2E2;
			border: 1px solid #FCA5A5;
			font-weight: 600;
			text-transform: capitalize;
			display: inline-block;
			border-radius: 8px;
		}

		.sidebar-sitelinks {
			margin-top: 1rem;
			justify-content: space-between;
			padding: 0;

			a {
				font-size: 12px;
				color: #B8C2CC;
			}

			.active {
				color: #212529;
				font-weight: 600;
			}
		}

		.sidebar-attribution {
			margin-top: 0.5rem;
			font-size: 12px;
			color: #B8C2CC !important;

			a {
				color: #B8C2CC !important;
			}
		}

		.user-card {
			align-items: center;

			.avatar {
				width: 80px;
				height: 80px;
				border-radius: 15px;
				margin-right: 0.8rem;
				border: 1px solid #E5E7EB;

				@media (min-width: 390px) {
					width: 100px;
					height: 100px;
				}
			}

			.avatar-update-btn {
				position: absolute;
				right: 12px;
				bottom: 0;
				width: 20px;
				height: 20px;
				background: rgba(255,255,255,0.9);
				border: 1px solid #dee2e6 !important;
				padding: 0;
				border-radius: 50rem;

				&-icon {
					font-family: 'Font Awesome 5 Free';
					font-weight: 400;
					-webkit-font-smoothing: antialiased;
					display: inline-block;
					font-style: normal;
					font-variant: normal;
					text-rendering: auto;
					line-height: 1;

					&:before {
						content: "\F013";
					}
				}
			}

			.username {
				font-weight: 600;
				font-size: 13px;
				margin: 4px 0;
				word-break: break-word;
				line-height: 12px;
				user-select: all;

				@media (min-width: 390px) {
					margin: 8px 0;
					font-size: 16px;
				}
			}

			.display-name {
				color: var(--body-color);
				line-height: 0.8;
				font-size: 20px;
				font-weight: 800 !important;
				word-break: break-word;
				user-select: all;
				font-family: -apple-system,BlinkMacSystemFont,"Segoe UI",Roboto,Helvetica,Arial,sans-serif;
				margin-bottom: 0;

				@media (min-width: 390px) {
					font-size: 24px;
				}
			}

			.stats {
				display: flex;
				justify-content: space-between;
				flex-direction: row;
				margin-top: 0;
				margin-bottom: 0;
				font-size: 16px;
				user-select: none;

				.posts-count,
				.following-count,
				.followers-count {
					display: flex;
					font-weight: 800;
				}

				.stats-label {
					color: #94a3b8;
					font-size: 11px;
					margin-top: -5px;
				}
			}
		}
	}
</style>
