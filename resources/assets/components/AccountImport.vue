<template>
    <div class="h-100 pf-import">
        <div v-if="!loaded" class="d-flex justify-content-center align-items-center h-100">
            <b-spinner />
        </div>
        <template v-else>
            <input type="file" name="file" class="d-none" ref="zipInput" @change="zipInputChanged" />
            <template v-if="page === 1">
                <div class="title">
                    <h3 class="font-weight-bold">Import</h3>
                </div>
                <hr>
                <section>
                    <p class="lead">Account Import allows you to import your data from a supported service.</p>
                </section>
                <section class="mt-4">
                    <ul class="list-group">
                        <li class="list-group-item d-flex justify-content-between flex-column" style="gap:1rem">
                            <div class="d-flex justify-content-between align-items-center" style="gap: 1rem;">
                                <div>
                                    <p class="font-weight-bold mb-1">Import from Instagram</p>
                                    <p v-if="showDisabledWarning" class="small mb-0">This feature has been disabled by the administrators.</p>
                                    <p v-else-if="showNotAllowedWarning" class="small mb-0">You have not been permitted to use this feature, or have reached the maximum limits. For more info, view the <a href="/site/kb/import" class="font-weight-bold">Import Help Center</a> page.</p>
                                    <p v-else class="small mb-0">Upload the JSON export from Instagram in .zip format.<br />For more information click <a href="/site/kb/import">here</a>.</p>
                                </div>
                                <div v-if="!showDisabledWarning && !showNotAllowedWarning">
                                    <button
                                        v-if="step === 1 || invalidArchive"
                                        type="button"
                                        class="font-weight-bold btn btn-primary rounded-pill px-4 btn-lg"
                                        @click="selectArchive()"
                                        :disabled="showDisabledWarning">
                                        Import
                                    </button>

                                    <template v-else-if="step === 2">
                                        <div class="d-flex justify-content-center align-items-center flex-column">
                                            <b-spinner v-if="showUploadLoader" small />
                                            <button v-else type="button" class="font-weight-bold btn btn-outline-primary btn-sm btn-block" @click="reviewImports()">Review Imports</button>
                                            <p v-if="zipName" class="small font-weight-bold mt-2 mb-0">{{ zipName }}</p>
                                        </div>
                                    </template>
                                </div>
                            </div>
                        </li>
                    </ul>
                    <ul class="list-group mt-3">

                        <li v-if="processingCount" class="list-group-item d-flex justify-content-between flex-column" style="gap:1rem">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <p class="font-weight-bold mb-1">Processing Imported Posts</p>
                                    <p class="small mb-0">These are posts that are in the process of being imported.</p>
                                </div>
                                <div>
                                    <span class="btn btn-danger rounded-pill py-0 font-weight-bold" disabled>{{ processingCount }}</span>
                                </div>
                            </div>
                        </li>

                        <li v-if="finishedCount" class="list-group-item d-flex justify-content-between flex-column" style="gap:1rem">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <p class="font-weight-bold mb-1">Imported Posts</p>
                                    <p class="small mb-0">These are posts that have been successfully imported.</p>
                                </div>
                                <div>
                                    <button
                                        type="button"
                                        class="font-weight-bold btn btn-primary btn-sm rounded-pill px-4 btn-block"
                                        @click="handleReviewPosts()"
                                        :disabled="!finishedCount">
                                        Review {{ finishedCount }} Posts
                                    </button>
                                </div>
                            </div>
                        </li>
                    </ul>

                </section>
            </template>

            <template v-else-if="page === 2">
                <div class="d-flex justify-content-between align-items-center">

                    <div class="title">
                        <h3 class="font-weight-bold">Import from Instagram</h3>
                    </div>

                    <button
                        class="btn btn-primary font-weight-bold rounded-pill px-4"
                        :class="{ disabled: !selectedMedia || !selectedMedia.length }"
                        :disabled="!selectedMedia || !selectedMedia.length || importButtonLoading"
                        @click="handleImport()"
                        >
                        <b-spinner v-if="importButtonLoading" small />
                        <span v-else>Import</span>
                    </button>
                </div>
                <hr>
                <section>
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <div v-if="!selectedMedia || !selectedMedia.length">
                            <p class="lead mb-0">Review posts you'd like to import.</p>
                            <p class="small text-muted mb-0">Tap on posts to include them in your import.</p>
                        </div>
                        <p v-else class="lead mb-0"><span class="font-weight-bold">{{ selectedPostsCounter }}</span> posts selected for import</p>

                        <button v-if="selectedMedia.length" class="btn btn-outline-danger font-weight-bold rounded-pill btn-sm my-1" @click="handleClearAll()">Clear all selected</button>
                        <button v-else class="btn btn-outline-primary font-weight-bold rounded-pill" @click="handleSelectAll()">Select first 100 posts</button>
                    </div>
                </section>
                <section class="row mb-n5 media-selector" style="max-height: 600px;overflow-y: auto;">
                    <div v-for="media in postMeta" class="col-12 col-md-4">
                        <div
                            class="square cursor-pointer"
                            @click="toggleSelectedPost(media)">
                            <div
                                v-if="media.media[0].uri.endsWith('.mp4')"
                                :class="{ selected: selectedMedia.indexOf(media.media[0].uri) != -1 }"
                                class="info-overlay-text-label rounded">
                                <h5 class="text-white m-auto font-weight-bold">
                                    <span>
                                        <span class="far fa-video fa-2x p-2 d-flex-inline"></span>
                                    </span>
                                </h5>
                            </div>
                            <div
                                v-else
                                class="square-content"
                                :class="{ selected: selectedMedia.indexOf(media.media[0].uri) != -1 }"
                                :style="{ borderRadius: '5px', backgroundImage: 'url(' + getFileNameUrl(media.media[0].uri) + ')'}">
                            </div>
                        </div>
                        <div class="d-flex mt-1 justify-content-between align-items-center">
                            <p class="small"><i class="far fa-clock"></i> {{ formatDate(media.media[0].creation_timestamp) }}</p>
                            <p class="small font-weight-bold"><a href="#" @click.prevent="showDetailsModal(media)"><i class="far fa-info-circle"></i> Details</a></p>
                        </div>
                    </div>
                </section>
            </template>

            <template v-else-if="page === 'reviewImports'">
                <div class="d-flex justify-content-between align-items-center">

                    <div class="title">
                        <h3 class="font-weight-bold">Posts Imported from Instagram</h3>
                    </div>
                </div>
                <hr>
                <section class="row mb-n5 media-selector" style="max-height: 600px;overflow-y: auto;">
                    <div v-for="media in importedPosts.data" class="col-12 col-md-4">
                        <div
                            class="square cursor-pointer">
                            <div
                                v-if="media.media_attachments[0].url.endsWith('.mp4')"
                                class="info-overlay-text-label rounded">
                                <h5 class="text-white m-auto font-weight-bold">
                                    <span>
                                        <span class="far fa-video fa-2x p-2 d-flex-inline"></span>
                                    </span>
                                </h5>
                            </div>
                            <div
                                v-else
                                class="square-content"
                                :style="{ borderRadius: '5px', backgroundImage: 'url(' + media.media_attachments[0].url + ')'}">
                            </div>
                        </div>
                        <div class="d-flex mt-1 justify-content-between align-items-center">
                            <p class="small"><i class="far fa-clock"></i> {{ formatDate(media.created_at, false) }}</p>
                            <p class="small font-weight-bold"><a :href="media.url"><i class="far fa-info-circle"></i> View</a></p>
                        </div>
                    </div>

                    <div class="col-12 my-3">
                        <button
                            v-if="importedPosts.meta && importedPosts.meta.next_cursor"
                            class="btn btn-primary btn-block font-weight-bold"
                            @click="loadMorePosts()">
                            Load more
                        </button>
                    </div>
                </section>
            </template>
        </template>

        <b-modal
            id="detailsModal"
            title="Post Details"
            v-model="detailsModalShow"
            :ok-only="true"
            ok-title="Close"
            centered>
            <div class="">
                <div v-for="(media, idx) in modalData.media" class="mb-3">
                    <div class="list-group">
                        <div class="list-group-item d-flex justify-content-between align-items-center">
                            <p class="text-center font-weight-bold mb-0">Media #{{idx + 1}}</p>
                            <img :src="getFileNameUrl(media.uri)" width="30" height="30" style="object-fit: cover; border-radius: 5px;">
                        </div>
                        <div class="list-group-item">
                            <p class="small text-muted">Caption</p>
                            <p class="mb-0 small read-more" style="font-size: 12px;overflow-y: hidden;">{{ media.title ? media.title : modalData.title }}</p>
                        </div>
                        <div class="list-group-item">
                            <div class="d-flex justify-content-between align-items-center">
                                <p class="small mb-0 text-muted">Timestamp</p>
                                <p class="font-weight-bold mb-0">{{ formatDate(media.creation_timestamp) }}</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </b-modal>
    </div>
</template>

<script type="text/javascript">
    import * as zip from "@zip.js/zip.js";

    export default {
        data() {
            return {
                page: 1,
                step: 1,
                toggleLimit: 100,
                config: {},
                showDisabledWarning: false,
                showNotAllowedWarning: false,
                invalidArchive: false,
                loaded: false,
                existing: [],
                zipName: undefined,
                zipFiles: [],
                postMeta: [],
                imageCache: [],
                includeArchives: false,
                selectedMedia: [],
                selectedPostsCounter: 0,
                detailsModalShow: false,
                modalData: {},
                importedPosts: [],
                finishedCount: undefined,
                processingCount: undefined,
                showUploadLoader: false,
                importButtonLoading: false,
            }
        },

        mounted() {
            this.fetchConfig();
        },

        methods: {
            fetchConfig() {
                axios.get('/api/local/import/ig/config')
                .then(res => {
                    this.config = res.data;

                    if(res.data.enabled == false) {
                        this.showDisabledWarning = true;
                        this.loaded = true;
                    } else if(res.data.allowed == false) {
                        this.showNotAllowedWarning = true;
                        this.loaded = true;
                    } else {
                        this.fetchExisting();
                    }
                })
            },

            fetchExisting() {
                axios.post('/api/local/import/ig/existing')
                .then(res => {
                    this.existing = res.data;
                })
                .finally(() => {
                    this.fetchProcessing();
                })
            },

            fetchProcessing() {
                axios.post('/api/local/import/ig/processing')
                .then(res => {
                    this.processingCount = res.data.processing_count;
                    this.finishedCount = res.data.finished_count;
                })
                .finally(() => {
                    this.loaded = true;
                })
            },

            selectArchive() {
                event.currentTarget.blur();
                swal({
                    title: 'Upload Archive',
                    icon: 'success',
                    text: 'The .zip archive is probably named something like username_20230606.zip, and was downloaded from the Instagram.com website.',
                    buttons: {
                        cancel: "Cancel",
                        danger: {
                            text: "Upload zip archive",
                            value: "upload"
                        }
                    }
                })
                .then(res => {
                    this.$refs.zipInput.click();
                })
            },

            zipInputChanged(event) {
                this.step = 2;
                this.zipName = event.target.files[0].name;
                this.showUploadLoader = true;
                setTimeout(() => {
                    this.reviewImports();
                }, 1000);
                setTimeout(() => {
                    this.showUploadLoader = false;
                }, 3000);
            },

            reviewImports() {
                this.invalidArchive = false;
                this.checkZip();
            },

            model(file, options = {}) {
                return (new zip.ZipReader(new zip.BlobReader(file))).getEntries(options);
            },

            formatDate(ts, unixt = true) {
                let date = unixt ? new Date(ts * 1000) : new Date(ts);
                return date.toLocaleDateString()
            },

            getFileNameUrl(filename) {
                return this.imageCache.filter(e => e.filename === filename).map(e => e.blob);
            },

            showDetailsModal(entry) {
                this.modalData = entry;
                this.detailsModalShow = true;
                setTimeout(() => {
                    pixelfed.readmore();
                }, 500);
            },

            async fixFacebookEncoding(string) {
                // Facebook and Instagram are encoding UTF8 characters in a weird way in their json
                // here is a good explanation what's going wrong https://sorashi.github.io/fix-facebook-json-archive-encoding
                // See https://github.com/pixelfed/pixelfed/pull/4726 for more info
                const replaced = string.replace(/\\u00([a-f0-9]{2})/g, (x) => String.fromCharCode(parseInt(x.slice(2), 16)));
                const buffer = Array.from(replaced, (c) => c.charCodeAt(0));
                return new TextDecoder().decode(new Uint8Array(buffer));
            },

            async filterPostMeta(media) {
            	let fbfix = await this.fixFacebookEncoding(media);
                let json = JSON.parse(fbfix);
                let res = json.filter(j => {
                    let ids = j.media.map(m => m.uri).filter(m => {
                        if(this.config.allow_video_posts == true) {
                            return m.endsWith('.png') || m.endsWith('.jpg') || m.endsWith('.mp4');
                        } else {
                            return m.endsWith('.png') || m.endsWith('.jpg');
                        }
                    });
                    return ids.length;
                }).filter(j => {
                    let ids = j.media.map(m => m.uri);
                    return !this.existing.includes(ids[0]);
                })
                this.postMeta = res;
                return res;
            },

            async checkZip() {
                let file = this.$refs.zipInput.files[0];
                let entries = await this.model(file);
                if (entries && entries.length) {
                    let files = await entries.filter(e => e.filename === 'content/posts_1.json');

                    if(!files || !files.length) {
                        this.contactModal(
                            'Invalid import archive',
                            "The .zip archive you uploaded is corrupted, or is invalid. We cannot process your import at this time.\n\nIf this issue persists, please contact an administrator.",
                            'error'
                        )
                        this.invalidArchive = true;
                        return;
                    } else {
                        this.readZip();
                    }
                }
            },

            async readZip() {
                let file = this.$refs.zipInput.files[0];
                let entries = await this.model(file);
                if (entries && entries.length) {
                    this.zipFiles = entries;
                    let media = await entries.filter(e => e.filename === 'content/posts_1.json')[0].getData(new zip.TextWriter());
                    this.filterPostMeta(media);

                    let imgs = await Promise.all(entries.filter(entry => {
                        return (entry.filename.startsWith('media/posts/') || entry.filename.startsWith('media/other/')) && (entry.filename.endsWith('.png') || entry.filename.endsWith('.jpg') || entry.filename.endsWith('.mp4'));
                    })
                    .map(async entry => {
                        if(
                            (
                                entry.filename.startsWith('media/posts/') ||
                                entry.filename.startsWith('media/other/')
                            ) && (
                                entry.filename.endsWith('.png') ||
                                entry.filename.endsWith('.jpg') ||
                                entry.filename.endsWith('.mp4')
                            )
                        ) {
                            let types = {
                                'png': 'image/png',
                                'jpg': 'image/jpeg',
                                'jpeg': 'image/jpeg',
                                'mp4': 'video/mp4'
                            }
                            let type = types[entry.filename.split('/').pop().split('.').pop()];
                            let blob = await entry.getData(new zip.BlobWriter(type));
                            let url = URL.createObjectURL(blob);
                            return {
                                filename: entry.filename,
                                blob: url,
                                file: blob
                            }
                        } else {
                            return;
                        }
                    }));
                    this.imageCache = imgs.flat(2);
                }
                setTimeout(() => {
                    this.page = 2;
                }, 500);
            },

            toggleLimitReached() {
                this.contactModal(
                    'Limit reached',
                    "You can only import " + this.toggleLimit + " posts at a time.\nYou can import more posts after you finish importing these posts.",
                    'error'
                )
            },

            toggleSelectedPost(media) {
                let filename;
                let self = this;
                if(media.media.length === 1) {
                    filename = media.media[0].uri
                    if(this.selectedMedia.indexOf(filename) == -1) {
                        if(this.selectedPostsCounter >= this.toggleLimit) {
                            this.toggleLimitReached();
                            return;
                        }
                        this.selectedMedia.push(filename);
                        this.selectedPostsCounter++;
                    } else {
                        let idx = this.selectedMedia.indexOf(filename);
                        this.selectedMedia.splice(idx, 1);
                        this.selectedPostsCounter--;
                    }
                } else {
                    filename = media.media[0].uri
                    if(this.selectedMedia.indexOf(filename) == -1) {
                        if(this.selectedPostsCounter >= this.toggleLimit) {
                            this.toggleLimitReached();
                            return;
                        }
                        this.selectedPostsCounter++;
                    } else {
                        this.selectedPostsCounter--;
                    }
                    media.media.forEach(function(m) {
                        filename = m.uri
                        if(self.selectedMedia.indexOf(filename) == -1) {
                            self.selectedMedia.push(filename);
                        } else {
                            let idx = self.selectedMedia.indexOf(filename);
                            self.selectedMedia.splice(idx, 1);
                        }
                    })
                }
            },

            sliceIntoChunks(arr, chunkSize) {
                const res = [];
                for (let i = 0; i < arr.length; i += chunkSize) {
                    const chunk = arr.slice(i, i + chunkSize);
                    res.push(chunk);
                }
                return res;
            },

            handleImport() {
                swal('Importing...', "Please wait while we upload your imported posts.\n Keep this page open and do not navigate away.", 'success');
                this.importButtonLoading = true;
                let ic = this.imageCache.filter(e => {
                    return this.selectedMedia.indexOf(e.filename) != -1;
                })
                let chunks = this.sliceIntoChunks(ic, 10);
                chunks.forEach(c => {
                    let formData = new FormData();
                    c.map((e, idx) => {
                        let file = new File([e.file], e.filename);
                        formData.append('file['+ idx +']', file, e.filename.split('/').pop());
                    })
                    axios.post(
                        '/api/local/import/ig/media',
                        formData,
                        {
                            headers: {
                                'Content-Type': `multipart/form-data`,
                            },
                        }
                    )
                    .catch(err => {
                        this.contactModal(
                            'Error',
                            err.response.data.message,
                            'error'
                        )
                    });
                })
                axios.post('/api/local/import/ig', {
                    files: this.postMeta.filter(e => this.selectedMedia.includes(e.media[0].uri)).map(e => {
                        if(e.hasOwnProperty('title')) {
                            return {
                                title: e.title,
                                'creation_timestamp': e.creation_timestamp,
                                uri: e.uri,
                                media: e.media
                            }
                        } else {
                            return {
                                title: null,
                                'creation_timestamp': null,
                                uri: null,
                                media: e.media
                            }
                        }
                    })
                }).then(res => {
                    if(res) {
                        setTimeout(() => {
                            window.location.reload()
                        }, 5000);
                    }
                }).catch(err => {
                    this.contactModal(
                        'Error',
                        err.response.data.error,
                        'error'
                    )
                })
            },

            handleReviewPosts() {
                this.page = 'reviewImports';

                axios.post('/api/local/import/ig/posts')
                .then(res => {
                    this.importedPosts = res.data;
                })
            },

            loadMorePosts() {
                event.currentTarget.blur();

                axios.post('/api/local/import/ig/posts', {
                    cursor: this.importedPosts.meta.next_cursor
                })
                .then(res => {
                    let data = res.data;
                    data.data = [...this.importedPosts.data, ...res.data.data];
                    this.importedPosts = data;
                })
            },

            contactModal(title = 'Error', text, icon, closeButton = 'Close') {
                swal({
                    title: title,
                    text: text,
                    icon: icon,
                    dangerMode: true,
                    buttons: {
                        ok: closeButton,
                        danger: {
                            text: 'Contact Support',
                            value: 'contact'
                        }
                    }
                })
                .then(res => {
                    if(res === 'contact') {
                        window.location.href = '/site/contact'
                    }
                });
            },

            handleSelectAll() {
                let medias = this.postMeta.slice(0, 100);
                for (var i = medias.length - 1; i >= 0; i--) {
                    let m = medias[i];
                    this.toggleSelectedPost(m);
                }
            },

            handleClearAll() {
                this.selectedMedia = []
                this.selectedPostsCounter = 0;
            }
        }
    }
</script>

<style lang="scss" scoped>
    .pf-import {
        .media-selector {
            .selected {
                border: 5px solid red;
            }
        }
    }
</style>
