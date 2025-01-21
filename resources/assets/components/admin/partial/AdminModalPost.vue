<template>
<div class="mb-3">
    <div v-if="status.media_attachments && status.media_attachments.length" class="list-group-item" style="gap:1rem;overflow:hidden;">
        <div class="text-center text-muted small font-weight-bold mb-3">Reported Post Media</div>
        <div v-if="status.media_attachments && status.media_attachments.length" class="d-flex flex-grow-1" style="gap: 1rem;overflow-x:auto;">
            <template
                v-for="media in status.media_attachments">
                <img
                    v-if="media.type === 'image'"
                    :src="media.url"
                    width="70"
                    height="70"
                    class="rounded"
                    style="object-fit: cover;"
                    @click="toggleLightbox"
                    onerror="this.src='/storage/no-preview.png';this.error=null;" />

                <video
                    v-else-if="media.type === 'video'"
                    width="140"
                    height="90"
                    playsinline
                    @click.prevent="toggleVideoLightbox($event, media.url)"
                    class="rounded"
                    >
                    <source :src="media.url" :type="media.mime">
                </video>
            </template>
        </div>
    </div>
    <div class="list-group-item d-flex flex-row flex-grow-1" style="gap:1rem;">
        <div class="flex-grow-1">
            <div v-if="status && status.in_reply_to_id && status.parent && status.parent.account" class="mb-3">
                <template v-if="showInReplyTo">
                    <div class="mt-n1 text-center text-muted small font-weight-bold mb-1">Reply to</div>
                    <div class="media" style="gap: 1rem;">
                        <img
                            :src="status.parent.account.avatar"
                            width="40"
                            height="40"
                            class="rounded-lg"
                            onerror="this.onerror=null;this.src='/storage/avatars/default.jpg?v=0';">
                        <div class="d-flex flex-column">
                            <p class="font-weight-bold mb-0" style="font-size: 11px;">
                                <a :href="`/i/web/profile/${status.parent.account.id}`" target="_blank">{{ status.parent.account.acct }}</a>
                            </p>
                            <admin-read-more :content="status.parent.content_text" />
                            <p class="mb-1">
                                <a :href="`/i/web/post/${status.parent.id}`" target="_blank" class="text-muted" style="font-size: 11px;">
                                    <i class="far fa-link mr-1"></i> {{ formatDate(status.parent.created_at)}}
                                </a>
                            </p>
                        </div>
                    </div>
                    <hr class="my-1">
                </template>
                <a v-else class="btn btn-dark font-weight-bold btn-block btn-sm" href="#" @click.prevent="showInReplyTo = true">Show parent post</a>
            </div>

            <div>
                <div class="mt-n1 text-center text-muted small font-weight-bold mb-1">Reported Post</div>
                <div class="media" style="gap: 1rem;">
                    <img
                        :src="status.account.avatar"
                        width="40"
                        height="40"
                        class="rounded-lg"
                        onerror="this.onerror=null;this.src='/storage/avatars/default.jpg?v=0';">
                    <div class="d-flex flex-column">
                        <p class="font-weight-bold mb-0" style="font-size: 11px;">
                            <a :href="`/i/web/profile/${status.account.id}`" target="_blank">{{ status.account.acct }}</a>
                        </p>
                        <template v-if="status && status.content_text && status.content_text.length">
                            <admin-read-more :content="status.content_text" />
                        </template>
                        <template v-else>
                            <admin-read-more content="EMPTY CAPTION" class="font-weight-bold text-muted" />
                        </template>
                        <p class="mb-0">
                            <a :href="`/i/web/post/${status.id}`" target="_blank" class="text-muted" style="font-size: 11px;">
                                <i class="far fa-link mr-1"></i> {{ formatDate(status.created_at)}}
                            </a>
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
</template>

<script>
    import BigPicture from 'bigpicture';
    import AdminReadMore from './AdminReadMore.vue';

    export default {
        props: {
            status: {
                type: Object
            }
        },

        data() {
            return {
                showInReplyTo: false,
            }
        },

        components: {
            "admin-read-more": AdminReadMore
        },

        methods: {
            toggleLightbox(e) {
                BigPicture({
                    el: e.target
                })
            },

            toggleVideoLightbox($event, src) {
                BigPicture({
                    el: event.target,
                    vidSrc: src
                })
            },

            formatDate(str) {
                let date = new Date(str);
                return new Intl.DateTimeFormat('default', {
                    month: 'long',
                    day: 'numeric',
                    year: 'numeric',
                    hour: 'numeric',
                    minute: 'numeric'
                }).format(date);
            },
        }
    }
</script>
