<template>
    <div class="group-component">
        <div v-if="tab === 'home'">
            <groups-home />
        </div>

        <div v-if="tab === 'createGroup'">
            <!-- <div class="col-12 group-component-hero">
                <h3 class="font-weight-bold">Create Group</h3>
                <button class="btn btn-outline-primary px-3 rounded-pill font-weight-bold" @click="switchTab('home')">Back to Groups</button>
            </div> -->

            <create-group />
        </div>

        <div v-if="tab === 'show'">
            <group-feed :group-id="groupId" :path="path" />
        </div>
    </div>
</template>

<script type="text/javascript">
    import GroupsHome from '@/groups/GroupsHome.vue';
    import GroupFeed from '@/groups/GroupFeed.vue';
    import CreateGroup from '@/groups/CreateGroup.vue';

    export default {
        props: {
            groupId: {
                type: String
            },

            path: {
                type: String
            }
        },

        data() {
            return {
                tab: 'home'
            }
        },

        components: {
            "groups-home": GroupsHome,
            "create-group": CreateGroup,
            "group-feed": GroupFeed,
        },

        mounted() {
            if(this.groupId) {
                this.tab = 'show';
            }
        },

        methods: {
            switchTab(newTab) {
                this.tab = newTab;
            }
        }
    }
</script>

<style lang="scss">
    .group-component {
        &-hero {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 1rem;
            border: 1px solid #dee2e6;
            border-top: 0;
            background-color: #fff;

            h3 {
                margin-bottom: 0;
            }
        }
    }
</style>
