<template>
    <div class="group-status-permalink-component">
        <div class="row border-bottom m-0 p-0">
            <sidebar />

            <div class="col-12 col-md-9 px-md-0">
                <group-feed :group-id="gid" :permalinkMode="true" :permalinkId="sid" />
            </div>
        </div>
    </div>
</template>

<script type="text/javascript">
    import GroupFeed from '@/groups/GroupFeed.vue';
    import SidebarComponent from '@/groups/sections/Sidebar.vue';

    export default {
        props: {
            gid: {
                type: String
            },

            sid: {
                type: String
            }
        },

        components: {
            "group-feed": GroupFeed,
            "sidebar": SidebarComponent
        }
    }
</script>
