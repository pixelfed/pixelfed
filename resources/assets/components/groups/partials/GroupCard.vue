<template>
    <div class="col-12 col-md-6 col-xl-4 group-card">
        <div class="group-card-inner">
            <img
                v-if="group.metadata && group.metadata.hasOwnProperty('header')"
                :src="group.metadata.header.url"
                class="group-header-img" />

            <div
                v-else
                class="group-header-img"
                :class="{ compact: compact }">
                <div
                    class="bg-light d-flex align-items-center justify-content-center rounded"
                    style="width: 100%; height:100%;">
                </div>
            </div>

            <div class="group-card-inner-copy">
                <p class="font-weight-bold mb-0 text-dark" style="font-size: 16px;">
                    {{ truncate(group.name || 'Untitled Group', titleLength) }}
                </p>

                <p class="text-muted mb-1" style="font-size: 12px;">
                    {{ truncate(group.short_description, descriptionLength) }}
                </p>
                <p v-if="showStats" class="mb-0 small text-lighter">
                    <span>
                        <i class="fal fa-users"></i>
                        <span class="small font-weight-bold">{{ prettyCount(group.member_count) }}</span>
                    </span>

                    <span v-if="!group.local" class="remote-label ml-3">
                        <i class="fal fa-globe"></i> Remote
                    </span>
                </p>
            </div>

            <div class="group-card-inner-foaf">
            </div>

            <div class="group-card-inner-cta">
                <router-link :to="`/groups/${group.id}`" class="btn btn-light btn-block font-weight-bold">
                    Join Group
                </router-link>
            </div>
        </div>
    </div>
</template>

<script>
    export default {
        props: {
            group: {
                type: Object
            },

            compact: {
                type: Boolean,
                default: false
            },

            showStats: {
                type: Boolean,
                default: true
            },

            truncateTitleLength: {
                type: Number,
                default: 19
            },

            truncateDescriptionLength: {
                type: Number,
                default: 22
            }
        },

        data() {
            return {
                titleLength: 40,
                descriptionLength: 60
            }
        },

        mounted() {
            if(this.compact) {
                this.titleLength = 19;
                this.descriptionLength = 22;
            }

            if(this.truncateTitleLength != 19) {
                this.titleLength = this.truncateTitleLength;
            }

            if(this.truncateDescriptionLength != 22) {
                this.descriptionLength = this.truncateDescriptionLength;
            }
        },

        methods: {
            prettyCount(val) {
                return App.util.format.count(val);
            },

            truncate(str, limit = 140) {
                if(str.length <= limit) {
                    return str;
                }

                return str.substr(0, limit) + ' ...';
            }
        }
    }
</script>

<style lang="scss" scoped>
    .group-card {
        margin-bottom: 15px;

        &-inner {
            display: flex;
            flex-direction: column;
            border: 1px solid var(--border-color);
            border-radius: 10px;
            overflow: hidden;

            &-copy {
                background-color: var(--light);
                padding: 1rem;
            }

            &-foaf {
                background-color: var(--light);
                height: 30px;
            }

            &-cta {
                background-color: var(--light);
                padding: 1rem;
            }
        }

        .member-label {
            padding: 2px 5px;
            font-size: 9px;
            color: rgba(75, 119, 190, 1);
            background: rgba(137, 196, 244, 0.2);
            border: 1px solid rgba(137, 196, 244, 0.3);
            font-weight: 500;
            text-transform: capitalize;
            border-radius: 3px;
        }

        .remote-label {
            padding: 2px 5px;
            font-size: 9px;
            color: #B45309;
            background: #FEF3C7;
            border: 1px solid #FCD34D;
            font-weight: 500;
            text-transform: capitalize;
            border-radius: 3px;
        }

        .group-header-img {
            width: 100%;
            height: 150px;
            object-fit: cover;
            padding: 0px;
            overflow: hidden;
        }
    }
</style>
