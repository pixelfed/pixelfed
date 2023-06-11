<template>
    <div class="profile-timeline-component">
        <div v-if="isLoaded" class="container-fluid mt-3">
            <div class="row">
                <div class="col-md-3 d-md-block px-md-3 px-xl-5">
                    <profile-sidebar
                        :profile="profile"
                        :relationship="relationship"
                        :user="curUser"
                        v-on:back="goBack"
                        v-on:toggletab="toggleTab"
                        v-on:updateRelationship="updateRelationship"
                        @follow="follow"
                        @unfollow="unfollow" />
                </div>

                <div class="col-md-8 px-md-5">
                    <component
                        v-bind:is="getTabComponentName()"
                        :key="getTabComponentName() + profile.id"
                        :profile="profile"
                        :relationship="relationship" />
                </div>
            </div>

            <drawer />
        </div>
    </div>
</template>

<script type="text/javascript">
    import Drawer from './partials/drawer.vue';
    import ProfileFeed from './partials/profile/ProfileFeed.vue';
    import ProfileSidebar from './partials/profile/ProfileSidebar.vue';
    import ProfileFollowers from './partials/profile/ProfileFollowers.vue';
    import ProfileFollowing from './partials/profile/ProfileFollowing.vue';

    export default {
        props: {
            id: {
                type: String
            },

            profileId: {
                type: String
            },

            username: {
                type: String
            },

            cachedProfile: {
                type: Object
            },

            cachedUser: {
                type: Object
            }
        },

        components: {
            "drawer": Drawer,
            "profile-feed": ProfileFeed,
            "profile-sidebar": ProfileSidebar,
            "profile-followers": ProfileFollowers,
            "profile-following": ProfileFollowing
        },

        data() {
            return {
                isLoaded: false,
                curUser: undefined,
                tab: "index",
                profile: undefined,
                relationship: undefined
            }
        },

        mounted() {
            this.init();
        },

        watch: {
            '$route': 'init'
        },

        methods: {
            init() {
                this.tab = 'index';
                this.isLoaded = false;
                this.relationship = undefined;
                this.owner = false;

                if(this.cachedProfile && this.cachedUser) {
                    this.curUser = this.cachedUser;
                    this.profile = this.cachedProfile;
                    // this.fetchPosts();
                    // this.isLoaded = true;
                    this.fetchRelationship();
                } else {
                    this.curUser = window._sharedData.user;
                    this.fetchProfile();
                }
            },

            getTabComponentName() {
                switch(this.tab) {
                    case 'index':
                        return "profile-feed";
                    break;

                    default:
                        return `profile-${this.tab}`;
                    break;
                }
            },

            fetchProfile() {
                let id = this.profileId ? this.profileId : this.id;
                axios.get('/api/pixelfed/v1/accounts/' + id)
                .then(res => {
                    this.profile = res.data;
                    if(res.data.id == this.curUser.id) {
                        this.owner = true;
                        // this.isLoaded = true;
                        // this.loaded();
                        // this.fetchPosts();
                        this.fetchRelationship();
                    } else {
                        this.owner = false;
                        this.fetchRelationship();
                    }
                })
                .catch(err => {
                    this.$router.push('/i/web/404');
                });
            },

            fetchRelationship() {
                if(this.owner) {
                    this.relationship = {};
                    this.isLoaded = true;
                    return;
                }

                axios.get('/api/v1/accounts/relationships', {
                    params: {
                        'id[]': this.profile.id
                    }
                }).then(res => {
                    this.relationship = res.data[0];
                    this.isLoaded = true;
                })
            },

            toggleTab(tab) {
                this.tab = tab;
            },

            goBack() {
                this.$router.go(-1);
            },

            unfollow() {
                axios.post('/api/v1/accounts/' + this.profile.id + '/unfollow')
                .then(res => {
                    this.$store.commit('updateRelationship', [res.data])
                    this.relationship = res.data;
                    if(this.profile.locked) {
                        location.reload();
                    }
                    this.profile.followers_count--;
                }).catch(err => {
                    swal('Oops!', 'An error occured when attempting to unfollow this account.', 'error');
                    this.relationship.following = true;
                });
            },

            follow() {
                axios.post('/api/v1/accounts/' + this.profile.id + '/follow')
                .then(res => {
                    this.$store.commit('updateRelationship', [res.data])
                    this.relationship = res.data;
                    if(this.profile.locked) {
                        this.relationship.requested = true;
                    }
                    this.profile.followers_count++;
                }).catch(err => {
                    swal('Oops!', 'An error occured when attempting to follow this account.', 'error');
                    this.relationship.following = false;
                });
            },

            updateRelationship(val) {
                this.relationship = val;
            }
        }
    }
</script>

<style lang="scss" scoped>
    .profile-timeline-component {
        margin-bottom: 10rem;
    }
</style>
