<template>
    <div class="groups-home-component w-100 h-100">
        <div v-if="initialLoad" class="row border-bottom m-0 p-0">
            <sidebar />

            <div class="col-12 col-md-10">
                <self-notifications :profile="profile" />
            </div>
        </div>
        <div v-else class="row justify-content-center mt-5">
            <b-spinner />
        </div>
    </div>
</template>

<script type="text/javascript">
    import GroupStatus from '@/groups/partials/GroupStatus.vue';
    import SelfFeed from '@/groups/partials/SelfFeed.vue';
    import SelfDiscover from '@/groups/partials/SelfDiscover.vue';
    import SelfGroups from '@/groups/partials/SelfGroups.vue';
    import SelfNotifications from '@/groups/partials/SelfNotifications.vue';
    import SelfInvitations from '@/groups/partials/SelfInvitations.vue';
    import SelfRemoteSearch from '@/groups/partials/SelfRemoteSearch.vue';
    import CreateGroup from '@/groups/CreateGroup.vue';
    import SidebarComponent from '@/groups/sections/Sidebar.vue';
    import Autocomplete from '@trevoreyre/autocomplete-vue'
    import '@trevoreyre/autocomplete-vue/dist/style.css'

    export default {
        data() {
            return {
                initialLoad: false,
                config: {},
                groups: [],
                profile: {},
                tab: null,
                searchQuery: undefined,
            };
        },

        components: {
            'autocomplete-input': Autocomplete,
            'group-status': GroupStatus,
            'self-discover': SelfDiscover,
            'self-groups': SelfGroups,
            'self-feed': SelfFeed,
            'self-notifications': SelfNotifications,
            'self-invitations': SelfInvitations,
            'self-remote-search': SelfRemoteSearch,
            "create-group": CreateGroup,
            "sidebar": SidebarComponent
        },

        mounted() {
            this.fetchConfig();
        },

        methods: {
            init() {
                document.querySelectorAll("footer").forEach(e => e.parentNode.removeChild(e));
                document.querySelectorAll(".mobile-footer-spacer").forEach(e => e.parentNode.removeChild(e));
                document.querySelectorAll(".mobile-footer").forEach(e => e.parentNode.removeChild(e));
                this.initialLoad = true;
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
        }
    }
</script>

<style lang="scss">
    .groups-home-component {
        font-family: var(--font-family-sans-serif);
    }
</style>
