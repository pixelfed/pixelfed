<template>
    <div>
    <div class="col-12 border-top group-feed-component-menu px-5">
        <ul class="nav font-weight-bold group-feed-component-menu-nav">
            <li class="nav-item">
                <router-link :to="`/groups/${group.id}/about`" class="nav-link">About</router-link>
            </li>
            <li class="nav-item">
                <router-link :to="`/groups/${group.id}`" class="nav-link" exact>Feed</router-link>
            </li>
            <li v-if="group?.self && group.self.is_member" class="nav-item">
                <router-link :to="`/groups/${group.id}/topics`" class="nav-link">Topics</router-link>
            </li>
            <li v-if="group?.self && group.self.is_member" class="nav-item">
                <router-link :to="`/groups/${group.id}/members`" class="nav-link">
                    Members
                    <span v-if="group.self.is_member && isAdmin && atabs.request_count" class="badge badge-danger rounded-pill ml-2" style="height: 20px;padding:4px 8px;font-size: 11px;">{{atabs.request_count}}</span>
                </router-link>
            </li>
            <!-- <li v-if="group.self.is_member" class="nav-item">
                <a :class="{active: tab == 'events'}" class="nav-link" href="#" @click.prevent="switchTab('events')">Events</a>
            </li> -->
            <li v-if="group?.self && group.self.is_member" class="nav-item">
                <router-link :to="`/groups/${group.id}/media`" class="nav-link">Media</router-link>
            </li>
            <!-- <li v-if="group.self.is_member" class="nav-item">
                <a class="nav-link" href="#">Popular</a>
            </li> -->
            <!-- <li v-if="group.self.is_member" class="nav-item">
                <a :class="{active: tab == 'polls'}" class="nav-link" href="#" @click.prevent="switchTab('polls')">Polls</a>
            </li> -->

            <!-- <li v-if="group.self.is_member && isAdmin" class="nav-item">
                <a class="nav-link" href="#">Messages</a>
            </li> -->

            <!-- <li v-if="group.self.is_member && isAdmin" class="nav-item">
                <a :class="{active: tab == 'insights'}" class="nav-link" href="#" @click.prevent="switchTab('insights')">Insights</a>
            </li> -->
            <!-- <li v-if="group.self.is_member && isAdmin && group.membership != 'all'" class="nav-item">
                <a :class="{active: tab == 'requests'}" class="nav-link" href="#" @click.prevent="switchTab('requests')">
                    <span class="mr-2">
                        <i class="far fa-user-plus mr-1"></i>
                        Requests
                    </span>
                    <span v-if="atabs.request_count" class="badge badge-danger rounded-pill" style="height: 20px;padding:4px 8px;font-size: 11px;">{{atabs.request_count}}</span>
                    <span v-if="atabs.request_count" class="badge badge-danger rounded-pill" style="height: 20px;padding:4px 8px;font-size: 11px;">99+</span>
                </a>
            </li> -->
            <li v-if="group?.self && group.self.is_member && isAdmin" class="nav-item">
                <router-link :to="`/groups/${group.id}/moderation`" class="nav-link d-flex align-items-top">
                    <span class="mr-2">Moderation</span>
                    <span v-if="atabs.moderation_count" class="badge badge-danger rounded-pill" style="height: 20px;padding:4px 8px;font-size: 11px;">{{atabs.moderation_count}}</span>
                </router-link>
            </li>
        </ul>
        <div>
            <button
                v-if="group?.self && group.self.is_member"
                class="btn btn-light btn-sm border px-3 rounded-pill mr-2"
                @click="showSearchModal">
                <i class="far fa-search"></i>
            </button>
            <div class="dropdown d-inline">
                <button class="btn btn-light btn-sm border px-3 rounded-pill dropdown-toggle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                    <i class="far fa-cog"></i>
                </button>
                <div class="dropdown-menu dropdown-menu-right">
                    <a class="dropdown-item" href="#" @click.prevent="copyLink">
                        Copy Group Link
                    </a>

                    <a class="dropdown-item" href="#" @click.prevent="showInviteModal">
                        Invite friends
                    </a>

                    <a v-if="!isAdmin" class="dropdown-item" href="#" @click.prevent="reportGroup">
                        Report Group
                    </a>

                    <a v-if="isAdmin" class="dropdown-item" :href="group.url + '/settings'">
                        Settings
                    </a>
                </div>
            </div>
        </div>

    </div>
        <search-modal
            ref="searchModal"
            :group="group"
            :profile="profile"
        />
    </div>
</template>

<script>
    import SearchModal from '@/groups/partials/GroupSearchModal.vue';
    export default {
        props: {
            group: {
                type: Object
            },
            isAdmin: {
                type: Boolean,
                default: false
            },
            isMember: {
                type: Boolean,
                default: false
            },
            atabs: {
                type: Object
            },
            profile: {
                type: Object
            }
        },

        components: {
            'search-modal': SearchModal,
        },

        methods: {
            showSearchModal() {
                event.currentTarget.blur();
                this.$refs.searchModal.open();
            },

            // showInviteModal() {
            //     event.currentTarget.blur();
            //     this.$refs.inviteModal.open();
            // },
        }
    }
</script>
<style lang="scss">
    .group-feed-component {
        &-menu {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0;

            &-nav {
                .nav-item {
                    .nav-link {
                        padding-top: 1rem;
                        padding-bottom: 1rem;
                        color: #6c757d;

                        &.active {
                            color: #2c78bf;
                            border-bottom: 2px solid #2c78bf;
                        }
                    }
                }

                &:not(last-child) {
                    .nav-item {
                        margin-right: 14px;
                    }
                }
            }
        }
    }
</style>
