<template>
    <div class="group-joins-component">
        <div class="row border-bottom m-0 p-0">
            <sidebar />

            <div class="col-12 col-md-9 px-0 mx-0">
                <loader v-if="!loaded" :loaded="loaded" />

                <template v-else>
                    <div class="px-5 pt-4 pb-2">
                        <h2 class="fw-bold">My Groups</h2>
                        <self-groups :profile="profile" />
                    </div>
                </template>
            </div>
        </div>
    </div>
</template>

<script type="text/javascript">
    import SidebarComponent from '@/groups/sections/Sidebar.vue';
    import LoaderComponent from '@/groups/sections/Loader.vue';
    import SelfGroups from '@/groups/partials/SelfGroups.vue';

    export default {
        components: {
            "sidebar": SidebarComponent,
            "loader": LoaderComponent,
            "self-groups": SelfGroups
        },

        data() {
            return {
                loaded: false,
                loadTimeout: undefined,
                config: {},
                groups: [],
                profile: {},
            }
        },

        methods: {
            init() {
                document.querySelectorAll("footer").forEach(e => e.parentNode.removeChild(e));
                document.querySelectorAll(".mobile-footer-spacer").forEach(e => e.parentNode.removeChild(e));
                document.querySelectorAll(".mobile-footer").forEach(e => e.parentNode.removeChild(e));
                this.loaded = true;
            },

            fetchConfig() {
                axios.get('/api/v0/groups/config')
                .then(res => {
                    this.config = res.data;
                    this.fetchProfile();
                });
            },

            fetchProfile() {
                axios.get('/api/pixelfed/v1/accounts/verify_credentials')
                .then(res => {
                    this.profile = res.data;
                    this.init();
                    window._sharedData.curUser = res.data;
                    window.App.util.navatar();
                })
            },
        },

        created() {
            this.fetchConfig();
        }
    }
</script>

<style lang="scss" scoped>
    .group-joins-component {
        font-family: var(--font-family-sans-serif);
    }
</style>
