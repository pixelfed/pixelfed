<template>
    <div v-if="status.sensitive == true" class="content-label-wrapper">
        <div class="text-light content-label">
            <p class="text-center">
                <i class="far fa-eye-slash fa-2x"></i>
            </p>
            <p class="h4 font-weight-bold text-center">
                {{ isFiltered ? 'Filtered Content' : 'Sensitive Content' }}
            </p>
            <p class="text-center py-2 content-label-text">
                {{ status.spoiler_text ? status.spoiler_text : 'This post may contain sensitive content.' }}
            </p>
            <p class="mb-0">
                <button class="btn btn-outline-light btn-block btn-sm font-weight-bold" @click="toggleContentWarning()">See Post</button>
            </p>
        </div>
        <blur-hash-image
            width="32"
            height="32"
            :punch="1"
            :hash="status.media_attachments[0].blurhash"
            :alt="altText(status)"
        />
    </div>
    <div v-else>
        <div
            :title="status.media_attachments[0].description"
            style="position: relative;">

            <!-- 360° Equirectangular Panorama Viewer -->
            <div
                v-if="is360Photo"
                :id="'panorama-' + status.id"
                class="panorama-viewer"
                role="img"
                :aria-label="altText(status)"
                @click.prevent="toggle360Lightbox"
            >
                <div v-if="!viewer360Loaded" class="panorama-loading">
                    <p>Loading 360° viewer...</p>
                </div>
            </div>

            <!-- Regular Image -->
            <img
                v-else
                class="card-img-top"
                :src="status.media_attachments[0].url"
                loading="lazy"
                :alt="altText(status)"
                :width="width()"
                :height="height()"
                onerror="this.onerror=null;this.src='/storage/no-preview.png'"
                @click.prevent="toggleLightbox"
            />

            <p
                v-if="!status.sensitive && sensitive"
                class="sensitive-curtain"
                @click="status.sensitive = true">
                <i class="fas fa-eye-slash fa-lg"></i>
            </p>

            <p
                v-if="is360Photo"
                class="panorama-hint">
                <i class="fas fa-sync-alt"></i> Drag to explore 360° view
            </p>

            <p
                v-if="status.media_attachments[0].license"
                class="photo-license">
                <a :href="status.url" class="font-weight-bold text-light">Photo</a> by <a :href="status.account.url" class="font-weight-bold text-light">&commat;{{ status.account.username }}</a> licensed under <a :href="status.media_attachments[0].license.url" class="font-weight-bold text-light">{{ status.media_attachments[0].license.title }}</a>
            </p>
        </div>
    </div>
</template>

<script type="text/javascript">
    import BigPicture from "bigpicture";

    export default {
        props: {
            status: {
                type: Object
            },
            isFiltered: {
                type: Boolean,
                default: false
            }
        },

        data() {
            return {
                sensitive: this.status.sensitive,
                viewer360Loaded: false,
                viewer360Instance: null,
                photoSphereLibraryLoaded: false
            };
        },

        computed: {
            // Check if current photo is a 360° equirectangular image
            is360Photo() {
                return this.status.media_attachments[0]?.is_equirectangular === true;
            }
        },

        mounted() {
            // Load 360° viewer if needed
            if (this.is360Photo) {
                this.init360Viewer();
            }
        },

        beforeUnmount() {
            // Clean up 360° viewer instance
            if (this.viewer360Instance) {
                this.viewer360Instance.destroy();
                this.viewer360Instance = null;
            }
        },

        methods: {
            altText(status) {
              let desc = status.media_attachments[0].description;

              if (desc) {
                return desc;
            }

            return "Photo was not tagged with any alt text.";
            },

            toggleContentWarning(status) {
                this.$emit("togglecw");
            },

            toggleLightbox(e) {
                BigPicture({
                    el: e.target
                });
            },

            toggle360Lightbox() {
                // For 360° images, open in fullscreen mode
                if (this.viewer360Instance) {
                    this.viewer360Instance.enterFullscreen();
                }
            },

            // Dynamically load Photo Sphere Viewer library
            async loadPhotoSphereViewer() {
                if (this.photoSphereLibraryLoaded || window.PhotoSphereViewer) {
                    return true;
                }

                return new Promise((resolve, reject) => {
                    // Load CSS
                    const cssLink = document.createElement('link');
                    cssLink.rel = 'stylesheet';
                    cssLink.href = 'https://cdn.jsdelivr.net/npm/photo-sphere-viewer@5/index.min.css';
                    document.head.appendChild(cssLink);

                    // Load JS
                    const script = document.createElement('script');
                    script.src = 'https://cdn.jsdelivr.net/npm/photo-sphere-viewer@5/index.min.js';
                    script.async = true;
                    script.onload = () => {
                        this.photoSphereLibraryLoaded = true;
                        resolve(true);
                    };
                    script.onerror = () => {
                        console.error('Failed to load Photo Sphere Viewer library');
                        reject(false);
                    };
                    document.head.appendChild(script);
                });
            },

            // Initialize the 360° viewer
            async init360Viewer() {
                try {
                    // Load library if not already loaded
                    await this.loadPhotoSphereViewer();

                    // Wait for next tick to ensure DOM is ready
                    await this.$nextTick();

                    const containerId = 'panorama-' + this.status.id;
                    const container = document.getElementById(containerId);

                    if (!container || !window.PhotoSphereViewer) {
                        // Fallback: show regular image if viewer fails to load
                        console.warn('360° viewer not available, falling back to regular image');
                        return;
                    }

                    // Initialize Photo Sphere Viewer
                    this.viewer360Instance = new window.PhotoSphereViewer.Viewer({
                        container: container,
                        panorama: this.status.media_attachments[0].url,
                        navbar: [
                            'zoom',
                            'moveDown',
                            'moveUp',
                            'moveLeft',
                            'moveRight',
                            'fullscreen'
                        ],
                        defaultZoomLvl: 0,
                        fisheye: false,
                        mousewheel: true,
                        touchmoveTwoFingers: false,
                        loadingTxt: 'Loading panorama...',
                        lang: {
                            zoom: 'Zoom',
                            zoomIn: 'Zoom in',
                            zoomOut: 'Zoom out',
                            moveUp: 'Move up',
                            moveDown: 'Move down',
                            moveLeft: 'Move left',
                            moveRight: 'Move right',
                            fullscreen: 'Fullscreen'
                        }
                    });

                    this.viewer360Instance.addEventListener('ready', () => {
                        this.viewer360Loaded = true;
                    });

                } catch (error) {
                    console.error('Failed to initialize 360° viewer:', error);
                    // Fallback: component will show regular image since is360Photo will remain true
                    // but viewer won't render, so we could add a flag to force regular display
                }
            },

            width() {
                if (!this.status.media_attachments[0].meta ||
                    !this.status.media_attachments[0].meta.original ||
                    !this.status.media_attachments[0].meta.original.width) {
                    return;
                }
                return this.status.media_attachments[0].meta.original.width;
            },

            height() {
                if (!this.status.media_attachments[0].meta ||
                    !this.status.media_attachments[0].meta.original ||
                    !this.status.media_attachments[0].meta.original.height) {
                    return;
                }
                return this.status.media_attachments[0].meta.original.height;
            }
        }
    };
</script>

<style type="text/css" scoped>
.card-img-top {
    border-top-left-radius: 0 !important;
    border-top-right-radius: 0 !important;
}
.content-label-wrapper {
    position: relative;
}
.content-label {
    margin: 0;
    position: absolute;
    top:50%;
    left:50%;
    transform: translate(-50%, -50%);
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    width: 100%;
    height: 100%;
    z-index: 2;
    background: rgba(0, 0, 0, 0.2)
}
.sensitive-curtain {
    margin-top: 0;
    padding: 10px;
    color: #fff;
    font-size: 10px;
    text-align: right;
    position: absolute;
    top: 0;
    right: 0;
    border-top-left-radius: 5px;
    cursor: pointer;
    background: linear-gradient(0deg, rgba(0,0,0,0.5), rgba(0,0,0,0.5));
}
.photo-license {
    margin-bottom: 0;
    padding: 0 5px;
    color: #fff;
    font-size: 10px;
    text-align: right;
    position: absolute;
    bottom: 0;
    right: 0;
    border-top-left-radius: 5px;
    background: linear-gradient(0deg, rgba(0,0,0,0.5), rgba(0,0,0,0.5));
}

/* 360° Panorama Viewer Styles */
.panorama-viewer {
    width: 100%;
    height: 500px;
    background: #000;
    cursor: grab;
    position: relative;
}

.panorama-viewer:active {
    cursor: grabbing;
}

.panorama-loading {
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    color: #fff;
    text-align: center;
    z-index: 10;
}

.panorama-hint {
    margin-bottom: 0;
    padding: 5px 10px;
    color: #fff;
    font-size: 11px;
    text-align: center;
    position: absolute;
    top: 10px;
    left: 50%;
    transform: translateX(-50%);
    border-radius: 15px;
    background: rgba(0, 0, 0, 0.6);
    pointer-events: none;
    animation: fadeInOut 3s ease-in-out;
}

@keyframes fadeInOut {
    0%, 100% { opacity: 0; }
    10%, 90% { opacity: 1; }
}

/* Mobile responsive adjustments */
@media (max-width: 768px) {
    .panorama-viewer {
        height: 300px;
    }

    .panorama-hint {
        font-size: 10px;
        padding: 4px 8px;
    }
}

/* Ensure Photo Sphere Viewer canvas is responsive */
.panorama-viewer canvas {
    max-width: 100%;
    height: auto !important;
}
</style>
