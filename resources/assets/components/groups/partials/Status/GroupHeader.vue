<template>
    <div class="group-post-header media">
        <div v-if="showGroupHeader" style="position: relative;" class="mb-1">
            <img
                v-if="group.hasOwnProperty('metadata') && (group.metadata.hasOwnProperty('avatar') || group.metadata.hasOwnProperty('header'))"
                class="rounded-lg box-shadow mr-2"
                :src="group.metadata.hasOwnProperty('header') ? group.metadata.header.url : group.metadata.avatar.url"
                width="52"
                height="52"
                onerror="this.onerror=null;this.src='/storage/avatars/default.png?v=2'"
                alt="avatar"
                style="object-fit:cover;"
            />
            <span
                v-else
                class="d-block rounded-lg box-shadow mr-2 bg-primary"
                style="width: 52px;height:52px;"
            ></span>
            <img
                class="rounded-circle box-shadow border mr-2"
                :src="status.account.avatar"
                width="36"
                height="36"
                onerror="this.onerror=null;this.src='/storage/avatars/default.png?v=2'"
                alt="avatar"
                style="position: absolute; bottom:-4px; right:-4px;"
            />
        </div>
        <img
            v-else
            class="rounded-circle box-shadow mr-2"
            :src="status.account.avatar"
            width="42"
            height="42"
            onerror="this.onerror=null;this.src='/storage/avatars/default.png?v=2'"
            alt="avatar"
        />
        <div class="media-body">
            <div class="pl-2 d-flex align-items-top">
                <div>
                    <p class="mb-0">
                        <router-link
                            v-if="showGroupHeader && group"
                            :to="`/groups/${status.gid}`"
                            class="group-name-link username"
                        >
                            {{ group.name }}
                        </router-link>

                        <router-link
                            v-else
                            :to="`/groups/${status.gid}/user/${status?.account.id}`"
                            class="group-name-link username"
                            v-html="statusCardUsernameFormat(status)"
                        >
                            Loading...
                        </router-link>

                        <span v-if="showGroupChevron">
                            <span class="text-muted" style="padding-left:2px;padding-right:2px;">
                                <i class="fas fa-caret-right"></i>
                            </span>
                            <span>
                                <router-link
                                    :to="`/groups/${status.gid}`"
                                    class="group-name-link"
                                >
                                    {{ group.name }}
                                </router-link>
                            </span>
                        </span>
                    </p>
                    <p class="mb-0 mt-n1">
                        <span
                            v-if="showGroupHeader && group"
                            style="font-size:13px"
                        >
                            <router-link
                                :to="`/groups/${status.gid}/user/${status?.account.id}`"
                                class="group-name-link-small username"
                                v-html="statusCardUsernameFormat(status)"
                            >
                                Loading...
                            </router-link>
                            <span class="text-lighter" style="padding-left:2px;padding-right:2px;">·</span>
                            <router-link
                                :to="`/groups/${status.gid}/p/${status.id}`"
                                class="font-weight-light text-muted"
                            >
                                {{shortTimestamp(status.created_at)}}
                            </router-link>
                            <span class="text-lighter" style="padding-left:2px;padding-right:2px;">·</span>
                            <span class="text-muted"><i class="fas fa-globe"></i></span>
                        </span>
                        <span v-else>
                            <router-link
                                :to="`/groups/${status.gid}/p/${status.id}`"
                                class="font-weight-light text-muted small"
                            >
                                {{shortTimestamp(status.created_at)}}
                            </router-link>
                            <span class="text-lighter" style="padding-left:2px;padding-right:2px;">·</span>
                            <span class="text-muted small"><i class="fas fa-globe"></i></span>
                        </span>
                    </p>
                </div>
                <div v-if="profile" class="text-right" style="flex-grow:1;">
                    <div class="dropdown">
                      <button class="btn btn-link dropdown-toggle" type="button" data-toggle="dropdown" aria-expanded="false">
                        <span class="fas fa-ellipsis-h text-lighter"></span>
                      </button>
                      <div class="dropdown-menu  dropdown-menu-right">
                        <a class="dropdown-item" :href="statusUrl()">View Post</a>
                        <a class="dropdown-item" :href="profileUrl()">View Profile</a>
                        <!-- <a class="dropdown-item" href="#">Copy Link</a> -->
                        <a class="dropdown-item" href="#" @click.prevent="sendReport()">Report</a>
                        <div class="dropdown-divider"></div>
                        <a class="dropdown-item text-danger" href="#" @click.prevent="onDelete()">Delete</a>
                      </div>
                    </div>
                    <!-- <button class="btn btn-link text-dark py-0" type="button" @click="ctxMenu()">
                        <span class="fas fa-ellipsis-h text-lighter"></span>
                        <span class="sr-only">Post Menu</span>
                    </button> -->
                </div>
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
            status: {
                type: Object
            },
            profile: {
                type: Object
            },
            showGroupHeader: {
                type: Boolean,
                default: false
            },
            showGroupChevron: {
                type: Boolean,
                default: false
            }
        },

        data() {
            return {
                reportTypes: [
                    { key: "spam", title: "It's spam" },
                    { key: "sensitive", title: "Nudity or sexual activity" },
                    { key: "abusive", title: "Bullying or harassment" },
                    { key: "underage", title: "I think this account is underage" },
                    { key: "violence", title: "Violence or dangerous organizations" },
                    { key: "copyright", title: "Copyright infringement" },
                    { key: "impersonation", title: "Impersonation" },
                    { key: "scam", title: "Scam or fraud" },
                    { key: "terrorism", title: "Terrorism or terrorism-related content" }
                ]
            }
        },

        methods: {
            formatCount(count) {
                return App.util.format.count(count);
            },

            statusUrl() {
                return '/groups/' + this.status.gid + '/p/' + this.status.id;
            },

            profileUrl() {
                return '/groups/' + this.status.gid + '/user/' + this.status.account.id;
            },

            timestampFormat(timestamp) {
                let ts = new Date(timestamp);
                return ts.toDateString() + ' ' + ts.toLocaleTimeString();
            },

            shortTimestamp(ts) {
                return window.App.util.format.timeAgo(ts);
            },

            statusCardUsernameFormat(status) {
                if(status.account.local == true) {
                    return status.account.username;
                }

                let fmt = window.App.config.username.remote.format;
                let txt = window.App.config.username.remote.custom;
                let usr = status.account.username;
                let dom = document.createElement('a');
                dom.href = status.account.url;
                dom = dom.hostname;

                switch(fmt) {
                    case '@':
                    return usr + '<span class="text-lighter font-weight-bold">@' + dom + '</span>';
                    break;

                    case 'from':
                    return usr + '<span class="text-lighter font-weight-bold"> <span class="font-weight-normal">from</span> ' + dom + '</span>';
                    break;

                    case 'custom':
                    return usr + '<span class="text-lighter font-weight-bold"> ' + txt + ' ' + dom + '</span>';
                    break;

                    default:
                    return usr + '<span class="text-lighter font-weight-bold">@' + dom + '</span>';
                    break;
                }
            },

            sendReport(type) {
                let el = document.createElement('div');
                el.classList.add('list-group');
                this.reportTypes.forEach(rt => {
                    let button = document.createElement('button');
                    button.classList.add('list-group-item', 'small');
                    button.innerHTML = rt.title;
                    button.onclick = () => {
                        document.dispatchEvent(new CustomEvent('reportOption', {
                            detail: { key: rt.key, title: rt.title }
                        }));
                    };
                    el.appendChild(button);
                });

                let wrapper = document.createElement('div');
                wrapper.appendChild(el);

                swal({
                    title: "Report Content",
                    icon: "warning",
                    content: wrapper,
                    buttons: false
                });

                document.addEventListener('reportOption', (event) => {
                    console.log(event.detail);
                    this.showConfirmation(event.detail);
                }, { once: true });
            },
            showConfirmation(option) {
                console.log(option)
              swal({
                title: "Confirmation",
                text: `You selected ${option.title}. Do you want to proceed?`,
                icon: "info",
                buttons: true
              }).then((confirm) => {
                if (confirm) {
                     axios.post(`/api/v0/groups/${this.status.gid}/report/create`, {
                        'type': option.key,
                        'id': this.status.id,
                    }).then(res => {
                        swal("Confirmed!", "Your report has been submitted.", "success");
                    })
                } else {
                  swal("Cancelled", "Your report was not submitted.", "error");
                }
              });
            },
            onDelete() {
              swal({
                title: "Delete Post Confirmation",
                text: 'Are you sure you want to delete this post?',
                icon: "warning",
                dangerMode: true,
                buttons: true
              }).then((confirm) => {
                if(confirm) {
                    axios.post('/api/v0/groups/status/delete', {
                        id: this.status.id,
                        gid: this.status.gid,
                    })
                    .then(res => {
                        this.$emit('delete', this.status)
                    })
                    .catch(err => {
                        console.log(err)
                    })
                }
              });
            }
        }
    }
</script>

<style lang="scss" scoped>
.group-post-header {
    .btn::focus {
        box-shadow: none;
    }

    .dropdown-toggle::after {
        display: none;
    }

    .group-name-link {
        color: var(--body-color) !important;
        word-break: break-word !important;
        word-wrap: break-word !important;
        text-decoration: none;
        font-size: 16px;
        font-weight: 600;
    }

    .group-name-link-small {
        color: var(--body-color) !important;
        word-break: break-word !important;
        word-wrap: break-word !important;
        text-decoration: none;
        font-size: 14px;
        font-weight: 600;
    }
}
</style>
