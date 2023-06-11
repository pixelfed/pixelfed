<template>
    <div class="web-wrapper">
        <div v-if="isLoaded" class="container-fluid mt-3">
            <div class="row">
                <div class="col-md-4 col-lg-3">
                    <sidebar
                        :user="profile"
                        @refresh="shouldRefresh = true" />
                </div>

                <div class="col-md-8 col-lg-6 px-0">
                    <story-carousel
                        v-if="storiesEnabled"
                        :profile="profile" />

                    <timeline
                        :profile="profile"
                        :scope="scope"
                        :key="scope"
                        v-on:update-profile="updateProfile"
                        :refresh="shouldRefresh"
                        @refreshed="shouldRefresh = false" />
                </div>

                <div class="d-none d-lg-block col-lg-3">
                    <rightbar class="sticky-top sidebar" />
                </div>
            </div>

            <drawer />
        </div>

        <div v-else class="d-flex justify-content-center align-items-center" style="height:calc(100vh - 58px);">
            <b-spinner />
        </div>
    </div>
</template>

<script type="text/javascript">
    import Drawer from './partials/drawer.vue';
    import Sidebar from './partials/sidebar.vue';
    import Rightbar from './partials/rightbar.vue';
    import Timeline from './sections/Timeline.vue';
    import Notifications from './sections/Notifications.vue';
    import StoryCarousel from './partials/timeline/StoryCarousel.vue';

    export default {
        props: {
            scope: {
                type: String,
                default: 'home'
            }
        },

        components: {
            "drawer": Drawer,
            "sidebar": Sidebar,
            "timeline": Timeline,
            "rightbar": Rightbar,
            "story-carousel": StoryCarousel,
        },

        data() {
            return {
                isLoaded: false,
                profile: undefined,
                recommended: [],
                trending: [],
                storiesEnabled: false,
                shouldRefresh: false
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
                this.profile = window._sharedData.user;
                this.isLoaded = true;
                this.storiesEnabled = window.App?.config?.features?.hasOwnProperty('stories') ? window.App.config.features.stories : false;
            },

            updateProfile(delta) {
                this.profile = Object.assign(this.profile, delta);
            }
        }
    }
</script>

<style lang="scss" scoped>
    .avatar {
        border-radius: 15px;
    }

    .username {
        margin-bottom: -6px;
    }

    .btn-white {
        background-color: #fff;
        border: 1px solid #F3F4F6;
    }

    .sidebar {
        top: 90px;
    }
</style>
