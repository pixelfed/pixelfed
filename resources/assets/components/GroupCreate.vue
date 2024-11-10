<template>
    <div class="group-notifications-component">
        <div class="row border-bottom m-0 p-0">
            <sidebar />

            <create-group />
        </div>
    </div>
</template>

<script type="text/javascript">
    import SidebarComponent from '@/groups/sections/Sidebar.vue';
    import LoaderComponent from '@/groups/sections/Loader.vue';
    import CreateGroup from '@/groups/CreateGroup.vue';

    export default {
        components: {
            "sidebar": SidebarComponent,
            "loader": LoaderComponent,
            "create-group": CreateGroup
        },

        data() {
            return {
                loaded: false,
                loadTimeout: undefined,
            }
        },

        created() {
            this.loadTimeout = setTimeout(() => {
                this.loaded = true;
            }, 1000);
        },

        beforeUnmount() {
            clearTimeout(this.loadTimeout);
        }
    }
</script>

<style lang="scss" scoped>
    .group-notifications-component {
        font-family: var(--font-family-sans-serif);

        .jumbotron {
            background-color: #fff;
            border-radius: 0px;
        }
    }
</style>
