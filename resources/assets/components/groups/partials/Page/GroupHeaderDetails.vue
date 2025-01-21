<template>
    <div class="col-12 group-feed-component-header px-3 px-md-5">
        <div class="media align-items-end">
            <img
                v-if="group.metadata && group.metadata.hasOwnProperty('avatar')"
                :src="group.metadata.avatar.url"
                width="169"
                height="169"
                class="bg-white mx-4 rounded-circle border shadow p-1"
                style="object-fit: cover;"
                :style="{ 'margin-top': group.metadata && group.metadata.hasOwnProperty('header') && group.metadata.header.url ? '-100px' : '0' }"
            />

            <div v-if="!group || !group.name" class="media-body">
                <h3 class="d-flex align-items-start">
                    <span>Loading...</span>
                </h3>
            </div>
            <div v-else class="media-body px-3">
                <h3 class="d-flex align-items-start">
                    <span>{{ group.name.slice(0,118) }}</span>
                    <sup v-if="group.verified" class="fa-stack ml-n2" title="Verified Group" data-toggle="tooltip">
                        <i class="fas fa-circle fa-stack-1x fa-lg" style="color: #22a7f0cc;font-size:18px"></i>
                        <i class="fas fa-check fa-stack-1x text-white" style="font-size:10px"></i>
                    </sup>
                </h3>
                <p class="text-muted mb-0" style="font-weight: 300;">
                    <span>
                        <i class="fas fa-globe mr-1"></i>
                        {{ group.membership == 'all' ? 'Public Group' : 'Private Group'}}
                    </span>
                    <span class="mx-2">
                        ·
                    </span>
                    <span>{{ group.member_count == 1 ? group.member_count + ' Member' : group.member_count + ' Members' }}</span>
                    <span class="mx-2">
                        ·
                    </span>
                    <span v-if="group.local" class="rounded member-label">Local</span>
                    <span v-else class="rounded remote-label">Remote</span>
                    <span v-if="group.self && group.self.hasOwnProperty('role') && group.self.role">
                        <span class="mx-2">
                            ·
                        </span>
                        <span class="rounded member-label">{{ group.self.role }}</span>
                    </span>
                </p>
            </div>
        </div>
        <div v-if="group && group.self">
            <button v-if="!isMember && !group.self.is_requested" class="btn btn-primary cta-btn font-weight-bold" @click="joinGroup" :disabled="requestingMembership">
                <span v-if="!requestingMembership">
                    {{ group.membership == 'all' ? 'Join' : 'Request Membership' }}
                </span>
                <div
                    v-else
                    class="spinner-border spinner-border-sm"
                    role="status">
                    <span class="sr-only">Loading...</span>
                </div>
            </button>

            <button
                v-else-if="!isMember && group.self.is_requested"
                class="btn btn-light border cta-btn font-weight-bold"
                @click.prevent="cancelJoinRequest">
                <i class="fas fa-user-clock mr-1"></i> Requested to Join
            </button>

            <button
                v-else-if="!isAdmin && isMember && !group.self.is_requested"
                type="button"
                class="btn btn-light border cta-btn font-weight-bold"
                @click.prevent="leaveGroup">
                <i class="fas sign-out-alt mr-1"></i> Leave Group
            </button>

            <!-- <div v-if="isAdmin">
                <a
                    class="btn btn-light border cta-btn font-weight-bold"
                    :href="group.url + '/settings'">
                    Settings
                </a>
            </div> -->
        </div>
    </div>
</template>

<script>
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
            }
        },

        data() {
            return {
                requestingMembership: false,
            }
        },

        methods: {
            joinGroup() {
                this.requestingMembership = true;

                axios.post('/api/v0/groups/'+this.group.id+'/join')
                .then(res => {
                    this.requestingMembership = false;
                    this.$emit('refresh');
                }).catch(err => {
                    let body = err.response;

                    if(body.status == 422) {
                        this.requestingMembership = false;
                        swal('Oops!', body.data.error, 'error');
                    }
                });
            },

            cancelJoinRequest() {
                if(!window.confirm('Are you sure you want to cancel your request to join this group?')) {
                    return;
                }

                axios.post('/api/v0/groups/'+this.group.id+'/cjr')
                .then(res => {
                    this.requestingMembership = false;
                    this.$emit('refresh');
                }).catch(err => {
                    let body = err.response;

                    if(body.status == 422) {
                        swal('Oops!', body.data.error, 'error');
                    }
                });
            },

            leaveGroup() {
                if(!window.confirm('Are you sure you want to leave this group? Any content you shared will remain accessible. You won\'t be able to rejoin for 24 hours.')) {
                    return;
                }

                axios.post('/api/v0/groups/'+this.group.id+'/leave')
                .then(res => {
                    this.$emit('refresh');
                });
            },
        }
    }
</script>

<style lang="scss">
    .group-feed-component {
        &-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-end;
            padding: 1rem 0;
            background-color: transparent;

            .cta-btn {
                width: 190px;
            }
        }

        .member-label {
            padding: 2px 5px;
            font-size: 12px;
            color: rgba(75, 119, 190, 1);
            background:rgba(137, 196, 244, 0.2);
            border:1px solid rgba(137, 196, 244, 0.3);
            font-weight:400;
            text-transform: capitalize;
        }

        .dropdown-item {
            font-weight: 600;
        }

        .remote-label {
            padding: 2px 5px;
            font-size: 12px;
            color: #B45309;
            background: #FEF3C7;
            border: 1px solid #FCD34D;
            font-weight: 400;
            text-transform: capitalize;
        }
    }
</style>
