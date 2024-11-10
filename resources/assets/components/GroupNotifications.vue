<template>
    <div class="group-notifications-component">
        <div class="row border-bottom m-0 p-0">
            <sidebar />

            <div class="col-12 col-md-9 px-0 mx-0">
                <loader v-if="!loaded" :loaded="loaded" />

                <template v-else>
                    <div class="px-5 pt-4 pb-2">
                        <h2 class="fw-bold">Group Notifications</h2>
                    </div>
                </template>
            </div>
        </div>
    </div>
</template>

<script type="text/javascript">
    import SidebarComponent from '@/groups/sections/Sidebar.vue';
    import LoaderComponent from '@/groups/sections/Loader.vue';

    export default {
        components: {
            "sidebar": SidebarComponent,
            "loader": LoaderComponent
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
