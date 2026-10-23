<template>
    <div class="dm-chat-message chat-msg">
        <div
            class="media d-inline-flex mb-0"
            :class="{ isAuthor: convo.isAuthor }"
            >
            <img v-if="!convo.isAuthor && !hideAvatars" class="mr-3 shadow msg-avatar" :src="thread.avatar" alt="avatar" width="50" onerror="this.onerror=null;this.src='/storage/avatars/default.jpg';">

            <div class="media-body">
                <!-- GLITCH MODIFIED START: DM message body as stacked divs (media, story media + reaction/comment bubble, text); fixes invalid <p> nesting -->
                <div v-if="convo.type == 'photo'" class="p-0">
                    <img
                        :src="convo.media"
                        class="media-embed shadow"
                        style="cursor: pointer;"
                        onerror="this.onerror=null;this.src='/storage/no-preview.png';"
                        @click.prevent="expandMedia">
                </div>
                <div v-else-if="convo.type == 'video'" class="p-0 mb-0" style="line-height: 0;">
                    <video :src="convo.media" class="media-embed shadow" style="border-radius:20px;" controls>
                    </video>
                    <!-- <span class="d-block bg-primary d-flex align-items-center justify-content-center" style="width:200px;height: 110px;border-radius: 20px;">
                        <div class="text-center">
                            <p class="mb-1">
                                <i class="fas fa-play fa-2x text-white"></i>
                            </p>
                            <p class="mb-0 small font-weight-bold text-white">
                                Play
                            </p>
                        </div>
                    </span> -->
                </div>
                <div v-else-if="convo.type == 'story:react' || convo.type == 'story:comment'" class="p-0">
                    <img :src="convo.meta.story_media_url" class="media-embed shadow" width="140" onerror="this.onerror=null;this.src='/storage/no-preview.png';">
                </div>
                <div v-if="convo.type == 'story:react' && convo.meta.reaction" :class="[largerText ? 'pill-to shadow larger-text text-break':'pill-to shadow text-break']">
                    {{convo.meta.reaction}}
                </div>
                <div v-else-if="convo.type == 'story:comment' && convo.meta.caption" :class="[largerText ? 'pill-to shadow larger-text text-break':'pill-to shadow text-break']">
                    {{convo.meta.caption}}
                </div>
                <div v-else-if="convo.type == 'link'" class="d-inline-flex mb-0 cursor-pointer">
                    <div class="card shadow border" style="width:240px;border-radius: 18px;">
                        <div class="card-body p-0" :title="convo.text">
                                <div class="media align-items-center">
                                    <div v-if="convo.meta.local" class="bg-primary mr-3 p-3" style="border-radius: 18px;">
                                        <i class="fas fa-link text-white fa-2x"></i>
                                    </div>
                                    <div v-else class="bg-light mr-3 p-3" style="border-radius: 18px;">
                                        <i class="fas fa-link text-lighter fa-2x"></i>
                                    </div>
                                    <div class="media-body text-muted small text-truncate pr-2 font-weight-bold">
                                        {{convo.meta.local ? convo.text.substr(8) : convo.meta.domain}}
                                    </div>
                                </div>
                        </div>
                    </div>
                </div>
                <div v-else-if="convo.type == 'emoji'" class="p-0 emoji-msg">
                    {{convo.text}}
                </div>
                <div v-else-if="convo.text" :class="[largerText ? 'pill-to shadow larger-text text-break':'pill-to shadow text-break']">
                    {{convo.text}}
                </div>
                <div>
                    <p v-if="convo.type == 'story:react'" class="small text-muted mb-0 ml-0">
                        <span class="font-weight-bold">{{ convo.meta.story_actor_username }}</span> reacted your story
                    </p>
                    <p v-if="convo.type == 'story:comment'" class="small text-muted mb-0 ml-0">
                        <span class="font-weight-bold">{{ convo.meta.story_actor_username }}</span> replied to your story
                    </p>

                    <p
                        class="msg-timestamp small text-muted font-weight-bold d-flex align-items-center justify-content-start"
                        data-timestamp="timestamp">
                        <span
                            v-if="convo.hidden"
                            class="small pr-2"
                            title="Filtered Message"
                            data-toggle="tooltip"
                            data-placement="bottom">
                            <i class="fas fa-lock"></i>
                        </span>

                        <span v-if="!hideTimestamps">
                            {{convo.timeAgo}}
                        </span>

                        <button
                            v-if="convo.isAuthor"
                            class="btn btn-link btn-sm text-lighter pl-2 font-weight-bold"
                            @click="confirmDelete">
                            <i class="far fa-trash-alt"></i>
                        </button>
                    </p>
                </div>
                <!-- GLITCH MODIFIED END -->
            </div>

            <img v-if="convo.isAuthor && !hideAvatars" class="ml-3 shadow msg-avatar" :src="profile.avatar" alt="avatar" width="50" onerror="this.onerror=null;this.src='/storage/avatars/default.jpg';">
        </div>
    </div>
</template>

<script type="text/javascript">
    import BigPicture from 'bigpicture';

    export default {
        props: {
            thread: {
                type: Object
            },

            convo: {
                type: Object
            },

            hideAvatars: {
                type: Boolean,
                default: false
            },

            hideTimestamps: {
                type: Boolean,
                default: false
            },

            largerText: {
                type: Boolean,
                default: false
            }
        },

        data() {
            return {
                profile: window._sharedData.user
            }
        },

        methods: {
            truncate(t) {
                return _.truncate(t);
            },

            viewOriginal() {
                let url = this.ctxContext.media;
                window.location.href = url;
                return;
            },

            isEmoji(text) {
                const onlyEmojis = text.replace(new RegExp('[\u0000-\u1eeff]', 'g'), '')
                const visibleChars = text.replace(new RegExp('[\n\r\s]+|( )+', 'g'), '')
                return onlyEmojis.length === visibleChars.length
            },

            copyText() {
                window.App.util.clipboard(this.ctxContext.text);
                this.closeCtxMenu();
                return;
            },

            clickLink() {
                let url = this.ctxContext.text;
                if(this.ctxContext.meta.local != true) {
                    url = '/i/redirect?url=' + encodeURI(this.ctxContext.text);
                }
                window.location.href = url;
            },

            formatCount(val) {
                return window.App.util.format.count(val);
            },

            confirmDelete() {
                this.$emit('confirm-delete');
            },

            expandMedia(e) {
                BigPicture({
                    el: e.target
                })
            }
        }
    }
</script>

<style lang="scss" scoped>
    .chat-msg {
        padding-top: 0;
        padding-bottom: 0;
    }

    .reply-btn {
        position: absolute;
        bottom: 54px;
        right: 20px;
        width: 90px;
        text-align: center;
        border-radius: 0 3px 3px 0;
    }

    .media-body .bg-primary {
        background: linear-gradient(135deg, #2EA2F4 0%, #0B93F6 100%) !important;
    }

    .pill-to {
        background: var(--bg-light);
        font-weight: 500;
        /* GLITCH MODIFIED: drop !important so grouping rules can override corners */
        border-radius: 20px;
        padding-left: 1rem;
        padding-right: 1rem;
        padding-top: 0.5rem;
        padding-bottom: 0.5rem;
        margin-right: 3rem;
        margin-bottom: 0.25rem;
    }
    .pill-from {
        color: white !important;
        text-align: right !important;
        background: linear-gradient(135deg, #2EA2F4 0%, #0B93F6 100%) !important;
        font-weight: 500;
        /* GLITCH MODIFIED: drop !important so grouping rules can override corners */
        border-radius: 20px;
        padding-left: 1rem;
        padding-right: 1rem;
        padding-top: 0.5rem;
        padding-bottom: 0.5rem;
        margin-left: 3rem;
        margin-bottom: 0.25rem;
    }

    /* GLITCH MODIFIED START: DM bubble grouping: stacked parts of one message share corners */
    .dm-chat-message > .isAuthor .media-body > * {
        justify-content: flex-end;
        display: flex;
    }

    .dm-chat-message > .isAuthor .media-body > *:not(:nth-last-child(2)) {
        border-bottom-right-radius: 0px !important;
        margin-bottom: 0;
    }

    .dm-chat-message > .isAuthor .media-body > *:not(:first-child) {
        border-top-right-radius: 0px !important;
        margin-top: 0;
    }

    .dm-chat-message > :not(.isAuthor) .media-body > *:not(:nth-last-child(2)) {
        border-bottom-left-radius: 0px !important;
        margin-bottom: 0;
    }

    .dm-chat-message > :not(.isAuthor) .media-body > *:not(:first-child) {
        border-top-left-radius: 0px !important;
        margin-top: 0;
    }

    .dm-chat-message > .isAuthor .media-body > *:not(:nth-last-child(2)) .media-embed {
        border-bottom-right-radius: 0px !important;
    }

    .dm-chat-message > .isAuthor .media-body > *:not(:first-child) .media-embed {
        border-top-right-radius: 0px !important;
    }

    .dm-chat-message > :not(.isAuthor) .media-body > *:not(:nth-last-child(2)) .media-embed {
        border-bottom-left-radius: 0px !important;
    }

    .dm-chat-message > :not(.isAuthor) .media-body > *:not(:first-child) .media-embed {
        border-top-left-radius: 0px !important;
    }
    /* GLITCH MODIFIED END */

    .chat-smsg:hover {
        background: var(--light-hover-bg);
    }
    .no-focus {
        border: none !important;
    }
    .no-focus:focus {
        outline: none !important;
        outline-width: 0 !important;
        box-shadow: none;
        -moz-box-shadow: none;
        -webkit-box-shadow: none;
    }
    .emoji-msg {
        font-size: 4rem !important;
        /* GLITCH MODIFIED: emoji message line-height */
        line-height: 4.2rem !important;
    }
    .larger-text {
        font-size: 22px;
    }

    .dm-chat-message {

        .isAuthor {
            float: right;
            margin-right: 0.5rem !important;

            .pill-to {
                color: white !important;
                text-align: right !important;
                background: linear-gradient(135deg, #2EA2F4 0%, #0B93F6 100%) !important;
                font-weight: 500;
                /* GLITCH MODIFIED: drop !important so grouping rules can override corners */
                border-radius: 20px;
                padding-left: 1rem;
                padding-right: 1rem;
                padding-top: 0.5rem;
                padding-bottom: 0.5rem;
                margin-left: 3rem;
                margin-right: 0;
                margin-bottom: 0.25rem;
            }

            .msg-timestamp {
                display: block !important;
                text-align: right;
                margin-bottom: 0;
            }
        }

        .msg-avatar {
            width: 50px;
            height: 50px;
            border-radius: 14px;
        }

        .media-embed {
            width: 140px;
            border-radius: 20px;

            @media (min-width: 450px) {
                width: 200px;
            }
        }
    }
</style>
