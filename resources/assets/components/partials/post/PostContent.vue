<template>
    <div class="timeline-status-component-content">
        <div v-if="status.pf_type === 'poll'" class="postPresenterContainer" style="background: #000;">
        </div>

        <div v-else-if="!fixedHeight" class="postPresenterContainer" style="background: #000;">
            <div v-if="status.pf_type === 'photo'" class="w-100">
                <photo-presenter
                    :status="status"
                    :is-filtered="isFiltered"
                    @lightbox="toggleLightbox"
                    @togglecw="toggleContentWarning" />
            </div>

            <div v-else-if="status.pf_type === 'video'" class="w-100">
                <video-player
                    :status="statusRender"
                    :fixed-height="fixedHeight"
                    @togglecw="toggleContentWarning" />
            </div>

            <div v-else-if="status.pf_type === 'photo:album'" class="w-100">
                <photo-album-presenter
                    :status="status"
                    @lightbox="toggleLightbox"
                    @togglecw="toggleContentWarning" />
            </div>

            <div v-else-if="status.pf_type === 'video:album'" class="w-100">
                <video-album-presenter
                    :status="status"
                    @togglecw="toggleContentWarning" />
            </div>

            <div v-else-if="status.pf_type === 'photo:video:album'" class="w-100">
                <mixed-album-presenter
                    :status="status"
                    @lightbox="toggleLightbox"
                    @togglecw="toggleContentWarning" />
            </div>
        </div>

        <div v-else class="card-body p-0">
            <div v-if="status.pf_type === 'photo'" :class="{ fixedHeight: fixedHeight }">
                <div v-if="statusRender.sensitive == true" class="content-label-wrapper">
                    <div class="text-light content-label">
                        <p class="text-center">
                            <i class="far fa-eye-slash fa-2x"></i>
                        </p>
                        <p class="h4 font-weight-bold text-center">
                            {{ isFiltered ? 'Filtered Content' : $t('common.sensitiveContent') }}
                        </p>
                        <p class="text-center py-2 content-label-text">
                            {{ status.spoiler_text ? status.spoiler_text : $t('common.sensitiveContentWarning') }}
                        </p>
                        <p class="mb-0">
                            <button class="btn btn-outline-light btn-block btn-sm font-weight-bold" @click="toggleContentWarning()">See Post</button>
                        </p>
                    </div>

                    <blur-hash-image
                        width="32"
                        height="32"
                        :punch="1"
                        class="blurhash-wrapper"
                        :hash="status.media_attachments[0].blurhash"
                        />
                </div>
                <div
                    v-else
                    class="content-label-wrapper"
                    @click.prevent="toggleLightbox"
                    >

                    <img
                        :src="status.media_attachments[0].url"
                        class="content-label-wrapper-img" />

                    <blur-hash-image
                        :key="key"
                        width="32"
                        height="32"
                        :punch="1"
                        :hash="status.media_attachments[0].blurhash"
                        :src="status.media_attachments[0].url"
                        class="blurhash-wrapper"
                        :alt="status.media_attachments[0].description"
                        :title="status.media_attachments[0].description"
                        style="width: 100%;position: absolute;z-index:9;top:0:left:0"
                        />

                    <p
                        v-if="!status.sensitive && sensitive"
                        class="sensitive-curtain"
                        @click="status.sensitive = true">
                        <i class="fas fa-eye-slash fa-lg"></i>
                    </p>
                </div>
            </div>

            <video-player
                v-else-if="status.pf_type === 'video'"
                :status="status"
                :fixed-height="fixedHeight"
            />

            <div v-else-if="status.pf_type === 'photo:album'" class="card-img-top shadow" style="border-radius: 15px;">
                <photo-album-presenter
                    :status="status"
                    class="photo-presenter"
                    :class="{ fixedHeight: fixedHeight }"
                    @lightbox="toggleLightbox"
                    @togglecw="toggleContentWarning()" />
            </div>

            <div v-else-if="status.pf_type === 'photo:video:album'" class="card-img-top shadow" style="border-radius: 15px;">
                <mixed-album-presenter
                    :status="status"
                    class="mixed-presenter"
                    :class="{ fixedHeight: fixedHeight }"
                    @lightbox="toggleLightbox"
                    @togglecw="status.sensitive = false" />

            </div>

            <div v-else-if="status.pf_type === 'text'">
                <div v-if="status.sensitive" class="border m-3 p-5 rounded-lg">
                    <p class="text-center">
                        <i class="far fa-eye-slash fa-2x"></i>
                    </p>
                    <p class="text-center lead font-weight-bold mb-0">Sensitive Content</p>
                    <p class="text-center">{{ status.spoiler_text && status.spoiler_text.length ? status.spoiler_text : 'This post may contain sensitive content' }}</p>
                    <p class="text-center mb-0">
                        <button class="btn btn-primary btn-sm font-weight-bold" @click="status.sensitive = false">See post</button>
                    </p>
                </div>
            </div>

            <div v-else class="bg-light rounded-lg d-flex align-items-center justify-content-center" style="height: 400px;">
                <div>
                    <p class="text-center">
                        <i class="fas fa-exclamation-triangle fa-4x"></i>
                    </p>

                    <p class="lead text-center mb-0">
                        Cannot display post
                    </p>

                    <p class="small text-center mb-0">
                        {{ status.pf_type }}:{{ status.id }}
                    </p>
                </div>
            </div>
        </div>

        <div
            v-if="status.content && !status.sensitive"
            class="card-body status-text"
            :class="[ status.pf_type === 'text' ? 'py-0' : 'pb-0']">
            <p>
                <read-more :status="status" :cursor-limit="300" />
            </p>
        </div>
    </div>
</template>

<script type="text/javascript">
    import BigPicture from "bigpicture";
    import ReadMore from "./ReadMore.vue";
    import VideoPlayer from "@/presenter/VideoPlayer.vue";

    export default {

        components: {
            "read-more": ReadMore,
            "video-player": VideoPlayer
        },
        props: {

            status: {
                type: Object
            },
            isFiltered: {
                type: Boolean
            },
            filters: {
                type: Array
            }
        },

        data() {
            return {
                key: 1,
                sensitive: false
            };
        },

        computed: {
            statusRender: {
                get() {
                    if (this.isFiltered) {
                        this.status.spoiler_text = "Filtered because it contains the following keywords: " + this.status.filtered.map(f => f.keyword_matches).flat(1).join(", ");
                        this.status.sensitive = true;
                    }
                    return this.status;
                }
            },
            fixedHeight: {
                get() {
                    return this.$store.state.fixedHeight == true;
                }
            }
        },

        methods: {
            toggleLightbox(e) {
                BigPicture({
                    el: e.target
                });
            },

            toggleContentWarning() {
                this.key++;
                this.sensitive = true;
                this.status.sensitive = !this.status.sensitive;
            },

            getPoster(status) {
                let url = status.media_attachments[0].preview_url;

                if (url.endsWith("no-preview.jpg") || url.endsWith("no-preview.png")) {
                    return;
                }
                return url;
            }
        }
    };
</script>

<style scoped>
    .sensitive-curtain {
        margin-top: 0;
        padding: 10px;
        color: #000;
        font-size: 10px;
        text-align: right;
        position: absolute;
        top: 0;
        right: 0;
        border-radius: 11px;
        cursor: pointer;
        background: rgba(255, 255, 255,.5);
    }

    .content-label-wrapper {
        position: relative;
        width:100%;
        height: 400px;
        overflow: hidden;
        z-index:1
    }

    .content-label-wrapper-img {
        position: absolute;
        width: 105%;height: 410px;
        object-fit: cover;
        z-index: 1;
        top:0;
        left:0;
        filter: brightness(0.35) blur(6px);
        margin:-5px;
    }

    .photo-presenter {
        border-radius:15px !important;
        object-fit: contain;
        background-color: #000;
        overflow: hidden;
    }

    .mixed-presenter {
        border-radius:15px !important;
        object-fit: contain;
        background-color: #000;
        overflow: hidden;
        align-items:center;
    }
</style>
