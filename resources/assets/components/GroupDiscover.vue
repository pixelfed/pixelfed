<template>
    <div class="group-discover-component">
        <div class="row border-bottom m-0 p-0">
            <sidebar />

            <div class="col-12 col-md-9 px-md-0">
                <loader v-if="!loaded" :loaded="loaded" />

                <template v-else>
                    <div class="container-fluid">
                        <div class="py-5">
                            <h1>Discover</h1>
                        </div>

                        <div class="popular row">
                            <group-card
                                v-for="(popular, idx) in popularGroups"
                                :key="idx"
                                :group="popular"
                            />
                        </div>
                    </div>
                </template>
            </div>
        </div>
    </div>
</template>

<script type="text/javascript">
    import SidebarComponent from '@/groups/sections/Sidebar.vue';
    import LoaderComponent from '@/groups/sections/Loader.vue';
    import GroupCard from '@/groups/partials/GroupCard.vue';

    export default {
        components: {
            "sidebar": SidebarComponent,
            "loader": LoaderComponent,
            "group-card": GroupCard
        },

        data() {
            return {
                loaded: false,
                loadTimeout: undefined,
                popularGroups: [],
                newGroups: [],
            }
        },

        methods: {
            fetchPopular() {
                axios.get('/api/v0/groups/discover/popular')
                .then(res => this.popularGroups = res.data)
                .finally(() => this.fetchNewGroups())
            },

            fetchNewGroups() {
                axios.get('/api/v0/groups/discover/new')
                .then(res => this.newGroups = res.data)
                .finally(() => this.loaded = true)
            },
        },

        created() {
            this.fetchPopular()
        },

        beforeUnmount() {
            clearTimeout(this.loadTimeout);
        }
    }
</script>

<style lang="scss" scoped>
    .group-discover-component {
        font-family: var(--font-family-sans-serif);

        .jumbotron {
            background-color: #fff;
            border-radius: 0px;
        }
    }
</style>
