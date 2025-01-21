<template>
    <div class="profile-timeline-component">
        <div v-if="isLoaded" class="container-fluid mt-3">
            <div class="row">
                <div class="col-12 col-md-8 offset-md-2 px-md-5">
                    <profile-following
                        :profile="profile"
                        :relationship="relationship"
                        @back="goBack()"
                    />
                </div>
            </div>

            <drawer />
        </div>
    </div>
</template>

<script type="text/javascript">
    import Drawer from './partials/drawer.vue';
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
            "profile-following": ProfileFollowing
        },

        data() {
            return {
                isLoaded: false,
                curUser: undefined,
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
                this.isLoaded = false;
                this.relationship = undefined;
                this.owner = false;

                if(this.cachedProfile && this.cachedUser) {
                    this.curUser = this.cachedUser;
                    this.profile = this.cachedProfile;
                    this.fetchRelationship();
                } else {
                    this.curUser = window._sharedData.user;
                    this.fetchProfile();
                }
            },

            fetchProfile() {
                let id = this.profileId ? this.profileId : this.id;
                axios.get('/api/pixelfed/v1/accounts/' + id)
                .then(res => {
                    this.profile = res.data;
                    if(res.data.id == this.curUser.id) {
                        this.owner = true;
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

            goBack() {
                this.$router.push('/i/web/profile/' + this.profile.id);
            }
        }
    }
</script>
