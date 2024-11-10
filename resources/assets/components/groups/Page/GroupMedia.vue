<template>
    <div class="group-feed-component">
        <div class="row border-bottom m-0 p-0">
            <sidebar />

            <div class="col-12 col-md-9 px-md-0">
                <div class="bg-white mb-3 border-bottom">
                    <div>
                        <group-banner :group="group" />
                        <group-header-details
                            :group="group"
                            :isAdmin="isAdmin"
                            :isMember="isMember"
                            @refresh="handleRefresh"
                        />
                        <group-nav-tabs
                            :group="group"
                            :isAdmin="isAdmin"
                            :isMember="isMember"
                            :atabs="atabs"
                        />
                    </div>
                </div>

                <div v-if="!initialLoad">
                    <p class="text-center mt-5 pt-5 font-weight-bold">Loading...</p>
                </div>

                <template v-else>
                    <div class="container-xl group-feed-component-body">
                        <template v-if="initialLoad && group.self.is_member">
                            <group-media :key="renderIdx" :group="group" :profile="profile" />
                        </template>

                        <member-only-warning v-else />
                    </div>
                </template>
            </div>
        </div>
    </div>
</template>

<script type="text/javascript">
    import StatusCard from '~/partials/StatusCard.vue';
    import GroupMembers from '@/groups/partials/GroupMembers.vue';
    import GroupCompose from '@/groups/partials/GroupCompose.vue';
    import GroupStatus from '@/groups/partials/GroupStatus.vue';
    import GroupAbout from '@/groups/partials/GroupAbout.vue';
    import GroupMedia from '@/groups/partials/GroupMedia.vue';
    import GroupModeration from '@/groups/partials/GroupModeration.vue';
    import GroupTopics from '@/groups/partials/GroupTopics.vue';
    import GroupInfoCard from '@/groups/partials/GroupInfoCard.vue';
    import LeaveGroup from '@/groups/partials/LeaveGroup.vue';
    import GroupInsights from '@/groups/partials/GroupInsights.vue';
    import SearchModal from '@/groups/partials/GroupSearchModal.vue';
    import InviteModal from '@/groups/partials/GroupInviteModal.vue';
    import SidebarComponent from '@/groups/sections/Sidebar.vue';
    import GroupBanner from '@/groups/partials/Page/GroupBanner.vue';
    import GroupHeaderDetails from '@/groups/partials/Page/GroupHeaderDetails.vue';
    import GroupNavTabs from '@/groups/partials/Page/GroupNavTabs.vue';
    import MemberOnlyWarning from '@/groups/partials/Membership/MemberOnlyWarning.vue';

    export default {
        props: {
            groupId: {
                type: String
            },

            path: {
                type: String
            }
        },

        components: {
            'status-card': StatusCard,
            'group-about': GroupAbout,
            'group-status': GroupStatus,
            'group-members': GroupMembers,
            'group-compose': GroupCompose,
            'group-topics': GroupTopics,
            'group-info-card': GroupInfoCard,
            'group-media': GroupMedia,
            'group-moderation': GroupModeration,
            'leave-group': LeaveGroup,
            'group-insights': GroupInsights,
            'search-modal': SearchModal,
            'invite-modal': InviteModal,
            'sidebar': SidebarComponent,
            'group-banner': GroupBanner,
            'group-header-details': GroupHeaderDetails,
            'group-nav-tabs': GroupNavTabs,
            'member-only-warning': MemberOnlyWarning
        },

        data() {
            return {
                initialLoad: false,
                profile: undefined,
                group: {},
                isMember: false,
                isAdmin: false,
                renderIdx: 1,
                atabs: {
                    moderation_count: 0,
                    request_count: 0
                }
            };
        },

        created() {
            this.init();
        },

        methods: {
            init() {
                this.initialLoad = false;
                axios.get('/api/pixelfed/v1/accounts/verify_credentials')
                .then(res => {
                    this.profile = res.data;
                    this.fetchGroup();
                })
                .catch(err => {
                    window.location.href = '/login?_next=' + encodeURIComponent(window.location.href);
                });
            },

            handleRefresh() {
                this.initialLoad = false;
                this.init();
                this.renderIdx++;
            },

            fetchGroup() {
                axios.get('/api/v0/groups/' + this.groupId)
                .then(res => {
                    this.group = res.data;
                    this.isMember = res.data.self.is_member;
                    this.isAdmin = ['founder', 'admin'].includes(res.data.self.role);

                    if(this.isAdmin) {
                        this.fetchAdminTabs();
                    }

                    this.initialLoad = true;
                })
                .catch(err => {
                    //window.location.href = '/groups/unavailable';
                    alert('error');
                });
            },

            fetchAdminTabs() {
                axios.get('/api/v0/groups/' + this.groupId + '/atabs')
                .then(res => {
                    this.atabs = res.data;
                })
            }
        }
    }
</script>

<style lang="scss">
    .group-feed-component {
        &-body {
            min-height: 40vh;
        }
    }
</style>
