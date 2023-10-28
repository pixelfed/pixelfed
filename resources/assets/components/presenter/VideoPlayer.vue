<template>
    <div>
        <div v-if="status.sensitive == true" class="content-label-wrapper">
            <div class="text-light content-label">
                <p class="text-center">
                    <i class="far fa-eye-slash fa-2x"></i>
                </p>
                <p class="h4 font-weight-bold text-center">
                    Sensitive Content
                </p>
                <p class="text-center py-2 content-label-text">
                    {{ status.spoiler_text ? status.spoiler_text : 'This post may contain sensitive content.'}}
                </p>
                <p class="mb-0">
                    <button @click="status.sensitive = false" class="btn btn-outline-light btn-block btn-sm font-weight-bold">See Post</button>
                </p>
            </div>
        </div>

        <template v-else>
            <div v-if="!shouldPlay" class="content-label-wrapper" :style="{ background: `linear-gradient(rgba(0, 0, 0, 0.2),rgba(0, 0, 0, 0.8)),url(${getPoster(status)})`, backgroundSize: 'cover'}">
                <div class="text-light content-label">
                    <p class="mb-0">
                        <button @click.prevent="handleShouldPlay" class="btn btn-link btn-block btn-sm font-weight-bold">
                            <i class="fas fa-play fa-5x text-white"></i>
                        </button>
                    </p>
                </div>
            </div>

            <template v-else>
                <video v-if="hasHls" ref="video" :class="{ fixedHeight: fixedHeight }" style="margin:0" playsinline controls autoplay="false" :poster="getPoster(status)">
                </video>

                <video v-else class="card-img-top shadow" :class="{ fixedHeight: fixedHeight }" style="border-radius:15px;object-fit: contain;background-color: #000;" autoplay="false" controls :poster="getPoster(status)">
                    <source :src="status.media_attachments[0].url" :type="status.media_attachments[0].mime">
                </video>
            </template>
        </template>

    </div>
</template>

<script type="text/javascript">
    import Hls from 'hls.js';
    import "plyr/dist/plyr.css";
    import Plyr from 'plyr';
    import { p2pml } from '@peertube/p2p-media-loader-core'
    import { Engine, initHlsJsPlayer } from '@peertube/p2p-media-loader-hlsjs'

    export default {
        props: ['status', 'fixedHeight'],

        data() {
            return {
                shouldPlay: false,
                hasHls: undefined,
                hlsConfig: window.App.config.features.hls,
                liveSyncDurationCount: 7,
                isHlsSupported: false,
                isP2PSupported: false,
                engine: undefined,
            }
        },

        mounted() {
            this.$nextTick(() => {
                this.init();
            })
        },

        methods: {
            handleShouldPlay(){
                this.shouldPlay = true;
                this.isHlsSupported = this.hlsConfig.enabled && Hls.isSupported();
                this.isP2PSupported = this.hlsConfig.enabled && this.hlsConfig.p2p && Engine.isSupported();
                this.$nextTick(() => {
                    this.init();
                })
            },

            init() {
                if(!this.status.sensitive && this.status.media_attachments[0]?.hls_manifest && this.isHlsSupported) {
                    this.hasHls = true;
                    this.$nextTick(() => {
                        this.initHls();
                    })
                } else {
                    this.hasHls = false;
                }
            },

            initHls() {
                let loader;
                if(this.isP2PSupported) {
                    const config = {
                        loader: {
                            trackerAnnounce: [this.hlsConfig.tracker],
                            rtcConfig: {
                                iceServers: [
                                    {
                                        urls: [this.hlsConfig.ice]
                                    }
                                ],
                            }
                        }
                    };
                    var engine = new Engine(config);
                    if(this.hlsConfig.p2p_debug) {
                        engine.on("peer_connect", peer => console.log("peer_connect", peer.id, peer.remoteAddress));
                        engine.on("peer_close", peerId => console.log("peer_close", peerId));
                        engine.on("segment_loaded", (segment, peerId) => console.log("segment_loaded from", peerId ? `peer ${peerId}` : "HTTP", segment.url));
                    }
                    loader = engine.createLoaderClass();
                } else {
                    loader = Hls.DefaultConfig.loader;
                }
                const video = this.$refs.video;
                const source = this.status.media_attachments[0].hls_manifest;
                const player = new Plyr(video, {
                    captions: {
                        active: true,
                        update: true,
                    },
                });

                const hls = new Hls({
                    liveSyncDurationCount: this.liveSyncDurationCount,
                    loader: loader,
                });
                let self = this;
                initHlsJsPlayer(hls);
                hls.loadSource(source);
                hls.attachMedia(video);

                hls.on(Hls.Events.MANIFEST_PARSED, function(event, data) {
                    if(this.hlsConfig.debug) {
                        console.log(event);
                        console.log(data);
                    }
                    const defaultOptions = {};

                    const availableQualities = hls.levels.map((l) => l.height)
                    if(this.hlsConfig.debug) {
                        console.log(availableQualities);
                    }
                    availableQualities.unshift(0);

                    defaultOptions.quality = {
                        default: 0,
                        options: availableQualities,
                        forced: true,
                        onChange: (e) => self.updateQuality(e),
                    }
                    defaultOptions.i18n = {
                        qualityLabel: {
                            0: 'Auto',
                        },
                    }

                    hls.on(Hls.Events.LEVEL_SWITCHED, function(event, data) {
                        var span = document.querySelector(".plyr__menu__container [data-plyr='quality'][value='0'] span")
                        if (hls.autoLevelEnabled) {
                            span.innerHTML = `Auto (${hls.levels[data.level].height}p)`
                        } else {
                            span.innerHTML = `Auto`
                        }
                    })

                    var player = new Plyr(video, defaultOptions);
                });
            },

             updateQuality(newQuality) {
                if (newQuality === 0) {
                    window.hls.currentLevel = -1;
                } else {
                    window.hls.levels.forEach((level, levelIndex) => {
                        if (level.height === newQuality) {
                            if(this.hlsConfig.debug) {
                                console.log("Found quality match with " + newQuality);
                            }
                            window.hls.currentLevel = levelIndex;
                        }
                    });
                }
            },

            getPoster(status) {
                let url = status.media_attachments[0].preview_url;
                if(url.endsWith('no-preview.jpg') || url.endsWith('no-preview.png')) {
                    return;
                }
                return url;
            }
        }
    }
</script>
