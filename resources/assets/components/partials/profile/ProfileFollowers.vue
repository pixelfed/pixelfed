<template>
	<div class="profile-followers-component">
		<div class="row justify-content-center">
			<div class="col-12 col-md-8">
                <div v-if="isLoaded" class="d-flex justify-content-between align-items-center mb-4">
                    <div>
                        <button
                            class="btn btn-outline-dark rounded-pill font-weight-bold"
                            @click="goBack()">
                            Back
                        </button>
                    </div>
    				<div class="d-flex align-items-center justify-content-center flex-column w-100 overflow-hidden">
    					<p class="small text-muted mb-0 text-uppercase font-weight-light cursor-pointer text-truncate text-center" style="width: 70%;" @click="goBack()">&commat;{{ profile.acct }}</p>
                        <p class="lead font-weight-bold mt-n1 mb-0">{{ $t('profile.followers') }}</p>
    				</div>
                    <div>
                        <a class="btn btn-dark rounded-pill font-weight-bold spacer-btn" href="#">Back</a>
                    </div>
                </div>

				<div v-if="isLoaded" class="list-group scroll-card">
					<div v-for="(account, idx) in feed" class="list-group-item">
						<a
							:id="'apop_'+account.id"
							:href="account.url"
							@click.prevent="goToProfile(account)"
							class="text-decoration-none">
							<div class="media">
								<img
									:src="account.avatar"
									width="40"
									height="40"
									style="border-radius: 8px;"
									class="mr-3 shadow-sm"
									draggable="false"
									loading="lazy"
									onerror="this.src='/storage/avatars/default.jpg?v=0';this.onerror=null;">

								<div class="media-body">
									<p class="mb-0 text-truncate">
										<span class="text-dark font-weight-bold text-decoration-none" v-html="getUsername(account)"></span>
									</p>
									<p class="mb-0 mt-n1 text-muted small text-break">&commat;{{ account.acct }}</p>
								</div>
							</div>
						</a>

						<b-popover :target="'apop_'+account.id" triggers="hover" placement="left" delay="1000" custom-class="shadow border-0 rounded-px">
							<profile-hover-card :profile="account" />
						</b-popover>
					</div>

					<div v-if="canLoadMore">
						<intersect @enter="enterIntersect">
							<placeholder />
						</intersect>
					</div>

					<div v-if="!canLoadMore && !feed.length">
						<div class="list-group-item text-center">
                            <div v-if="isWarmingCache" class="px-4">
                                <p class="mb-0 lead font-weight-bold">Loading Followers...</p>
                                <div class="py-3">
                                    <b-spinner variant="primary" style="width: 1.5rem; height: 1.5rem;" />
                                </div>
                                <p class="small text-muted mb-0">Please wait while we collect followers of this account, this shouldn't take long!</p>
                            </div>
							<p v-else class="mb-0 font-weight-bold">No followers yet!</p>
						</div>
					</div>
				</div>

				<div v-else class="list-group">
					<placeholder />
				</div>
			</div>
		</div>
	</div>
</template>

<script type="text/javascript">
	import Intersect from 'vue-intersect'
	import Placeholder from './../post/LikeListPlaceholder.vue';
	import ProfileHoverCard from './ProfileHoverCard.vue';
	import { mapGetters } from 'vuex';
	import { parseLinkHeader } from '@web3-storage/parse-link-header';

	export default {
		props: {
			profile: {
				type: Object
			}
		},

		components: {
			ProfileHoverCard,
			Intersect,
			Placeholder
		},

		computed: {
			...mapGetters([
				'getCustomEmoji'
			])
		},

		data() {
			return {
				isLoaded: false,
				feed: [],
				page: 1,
				cursor: null,
				canLoadMore: true,
				isFetchingMore: false,
                isWarmingCache: false,
                cacheWarmTimeout: undefined,
                cacheWarmInterations: 0,
			}
		},

		mounted() {
			this.fetchFollowers();
		},

        beforeDestroy() {
            clearTimeout(this.cacheWarmTimeout);
        },

		methods: {
			fetchFollowers() {
				axios.get('/api/v1/accounts/'+this.profile.id+'/followers', {
					params: {
						cursor: this.cursor
					}
				}).then(res => {
					if(!res.data.length) {
						this.canLoadMore = false;
						this.isLoaded = true;
                        if(this.cursor == null && this.profile.followers_count) {
                            this.isWarmingCache = true;
                            this.setCacheWarmTimeout();
                        }
						return;
					}
					if(res.headers && res.headers.link) {
						const links = parseLinkHeader(res.headers.link);
						if(links.prev) {
							this.cursor = links.prev.cursor;
							this.canLoadMore = true;
						} else {
							this.canLoadMore = false;
						}
					} else {
						this.canLoadMore = false;
					}
					this.feed.push(...res.data);
					this.isLoaded = true;
					this.isFetchingMore = false;
                    if(this.isWarmingCache || this.cacheWarmTimeout) {
                        this.isWarmingCache = false;
                        clearTimeout(this.cacheWarmTimeout);
                        this.cacheWarmTimeout = undefined;
                    }
				})
                .catch(err => {
                    this.canLoadMore = false;
                    this.isLoaded = true;
                    this.isFetchingMore = false;
                })
			},

			enterIntersect() {
				if(this.isFetchingMore) {
					return;
				}
				this.isFetchingMore = true;
				this.fetchFollowers();
			},

			getUsername(profile) {
				let self = this;
				let dn = profile.display_name;
				if(!dn || !dn.trim().length) {
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

			goToProfile(account) {
				this.$router.push({
					path: `/i/web/profile/${account.id}`,
					params: {
						id: account.id,
						cachedProfile: account,
						cachedUser: this.profile
					}
				})
			},

            goBack() {
                this.$emit('back');
            },

            setCacheWarmTimeout() {
                if(this.cacheWarmInterations >= 5) {
                    this.isWarmingCache = false;
                    swal('Oops', 'Its taking longer than expected to collect this account followers. Please try again later', 'error');
                    return;
                }
                this.cacheWarmTimeout = setTimeout(() => {
                    this.cacheWarmInterations++;
                    this.fetchFollowers();
                }, 45000);
            }
		}
	}
</script>

<style lang="scss">
	.profile-followers-component {
		.list-group-item {
			border: none;

			&:not(:last-child) {
				border-bottom: 1px solid rgba(0, 0, 0, 0.125);
			}
		}

        .scroll-card {
            max-height: calc(100vh - 250px);
            overflow-y: auto;
            -ms-overflow-style: none;
            scrollbar-width: none;
            scroll-behavior: smooth;

            &::-webkit-scrollbar {
                display: none;
            }
        }

        .spacer-btn {
            opacity: 0;
            pointer-events: none;
        }
	}
</style>
