<template>
    <div class="modal-stack">
        <b-modal ref="ctxModal"
            id="ctx-modal"
            hide-header
            hide-footer
            centered
            rounded
            size="sm"
            body-class="list-group-flush p-0 rounded">

            <div class="list-group text-center">
                <div v-if="status.visibility !== 'archived'" class="list-group-item d-flex p-0 m-0">
                    <div class="border-right p-2 w-50">
                        <a
                            v-if="status"
                            class="menu-option"
                            :href="status.url"
                            @click.prevent="ctxMenuGoToPost()">
                            <div class="action-icon-link">
                                <div class="icon"><i class="fal fa-images fa-lg"></i></div>
                                <p class="mb-0">{{ $t('menu.viewPost') }}</p>
                            </div>
                        </a>
                    </div>

                    <div class="p-2 flex-grow-1">
                        <a
                            v-if="status"
                            class="menu-option"
                            :href="status.account.url"
                            @click.prevent="ctxMenuGoToProfile()">
                            <div class="action-icon-link">
                                <div class="icon"><i class="fal fa-user fa-lg"></i></div>
                                <p class="mb-0">{{ $t('menu.viewProfile') }}</p>
                            </div>
                        </a>
                    </div>
                </div>

                <template v-if="ctxMenuRelationship">
                    <a
                        v-if="ctxMenuRelationship.following"
                        class="list-group-item menu-option text-danger"
                        href="#"
                        @click.prevent="handleUnfollow">
                        {{ $t('profile.unfollow') }}
                    </a>

                    <div v-else class="d-flex">
                        <div class="p-3 border-right w-50 text-center">
                            <a
                                class="small menu-option text-muted"
                                href="#"
                                @click.prevent="handleMute">
                                <div class="action-icon-link-inline">
                                    <div class="icon"><i class="far" :class="[ ctxMenuRelationship.muting ? 'fa-eye' : 'fa-eye-slash' ]"></i></div>
                                    <p class="text-muted mb-0">{{ ctxMenuRelationship.muting ? 'Unmute' : 'Mute' }}</p>
                                </div>
                            </a>
                        </div>
                        <div class="p-3 w-50">
                            <a
                                class="small menu-option text-danger"
                                href="#"
                                @click.prevent="handleBlock">
                                <div class="action-icon-link-inline">
                                    <div class="icon"><i class="far fa-shield-alt"></i></div>
                                    <p class="text-danger mb-0">Block</p>
                                </div>
                            </a>
                        </div>
                    </div>
                </template>

                <a
                    v-if="status.visibility !== 'archived'"
                    class="list-group-item menu-option"
                    href="#"
                    @click.prevent="ctxMenuShare()">
                    {{ $t('common.share') }}
                </a>

                <a
                    v-if="status && profile && profile.is_admin == true && status.visibility !== 'archived'"
                    class="list-group-item menu-option"
                    href="#"
                    @click.prevent="ctxModMenuShow()">
                    {{ $t('menu.moderationTools') }}
                </a>

                <a
                    v-if="status && status.account.id != profile.id"
                    class="list-group-item menu-option text-danger"
                    href="#"
                    @click.prevent="ctxMenuReportPost()">
                    {{ $t('menu.report') }}
                </a>

                <a
                    v-if="status && profile.id == status.account.id && status.visibility !== 'archived'"
                    class="list-group-item menu-option text-danger"
                    href="#"
                    @click.prevent="archivePost(status)">
                    {{ $t('menu.archive') }}
                </a>

                <a
                    v-if="status && profile.id == status.account.id && status.visibility == 'archived'"
                    class="list-group-item menu-option text-danger"
                    href="#"
                    @click.prevent="unarchivePost(status)">
                    {{ $t('menu.unarchive') }}
                </a>
                <a
                    v-if="status && profile.id == status.account.id && !status.pinned"
                    class="list-group-item menu-option text-danger"
                    href="#"
                    @click.prevent="pinPost(status)">
                    {{ $t('menu.pin') }}
                </a>

                <a
                    v-if="status && profile.id == status.account.id && status.pinned"
                    class="list-group-item menu-option text-danger"
                    href="#"
                    @click.prevent="unpinPost(status)">
                    {{ $t('menu.unpin') }}
                </a>

                <a
                    v-if="config.ab.pue && status && profile.id == status.account.id && status.visibility !== 'archived'"
                    class="list-group-item menu-option text-danger"
                    href="#"
                    @click.prevent="editPost(status)">
                    Edit
                </a>

                <a
                    v-if="status && (profile.is_admin || profile.id == status.account.id) && status.visibility !== 'archived'"
                    class="list-group-item menu-option text-danger"
                    href="#"
                    @click.prevent="deletePost(status)">
                    <div v-if="isDeleting" class="spinner-border spinner-border-sm" role="status">
                        <span class="sr-only">Loading...</span>
                    </div>
                    <div v-else>
                       {{ $t('common.delete') }}
                    </div>
                </a>

                <a
                    class="list-group-item menu-option text-lighter"
                    href="#"
                    @click.prevent="closeCtxMenu()">
                    {{ $t('common.cancel') }}
                </a>
            </div>
        </b-modal>

        <b-modal ref="ctxModModal"
            id="ctx-mod-modal"
            hide-header
            hide-footer
            centered
            rounded
            size="sm"
            body-class="list-group-flush p-0 rounded">

            <div class="list-group text-center">
                <p class="py-2 px-3 mb-0">
                    <div
                        class="text-center menu-option text-danger">
                        {{ $t('menu.moderationTools') }}
                    </div>

                    <div class="small text-center text-muted">
                        {{ $t('menu.selectOneOption') }}
                    </div>
                </p>

                <a
                    class="list-group-item menu-option"
                    href="#"
                    @click.prevent="moderatePost(status, 'unlist')">
                    {{ $t('menu.unlistFromTimelines') }}
                </a>

                <a
                    v-if="status.sensitive"
                    class="list-group-item menu-option"
                    href="#"
                    @click.prevent="moderatePost(status, 'remcw')">
                    {{ $t('menu.removeCW') }}
                </a>

                <a
                    v-else
                    class="list-group-item menu-option"
                    href="#"
                    @click.prevent="moderatePost(status, 'addcw')">
                    {{ $t('menu.addCW') }}
                </a>

                <a
                    class="list-group-item menu-option"
                    href="#"
                    @click.prevent="moderatePost(status, 'spammer')">
                    {{ $t('menu.markAsSpammer') }}<br />
                    <span class="small">{{ $t('menu.markAsSpammerText') }}</span>
                </a>

                <a
                    class="list-group-item menu-option text-lighter"
                    href="#"
                    @click.prevent="ctxModMenuClose()">
                    {{ $t('common.cancel') }}
                </a>
            </div>
        </b-modal>

        <b-modal ref="ctxModOtherModal"
            id="ctx-mod-other-modal"
            hide-header
            hide-footer
            centered
            rounded
            size="sm"
            body-class="list-group-flush p-0 rounded">
            <div class="list-group text-center">
                <p class="py-2 px-3 mb-0">
                    <div class="text-center font-weight-bold text-danger">{{ $t('menu.moderationTools') }}</div>
                    <div class="small text-center text-muted">{{ $t('menu.selectOneOption') }}</div>
                </p>
                <div class="list-group-item rounded cursor-pointer font-weight-bold" @click="confirmModal()">Unlist Posts</div>
                <div class="list-group-item rounded cursor-pointer font-weight-bold" @click="confirmModal()">Moderation Log</div>
                <div class="list-group-item rounded cursor-pointer text-lighter" @click="ctxModOtherMenuClose()">{{ $t('common.cancel') }}</div>
            </div>
        </b-modal>

        <b-modal ref="ctxShareModal"
            id="ctx-share-modal"
            title="Share"
            hide-footer
            hide-header
            centered
            rounded
            size="sm"
            body-class="list-group-flush p-0 rounded text-center">
            <a
                class="list-group-item menu-option"
                href="#"
                @click.prevent="ctxMenuCopyLink()">
                {{ $t('common.copyLink') }}
            </a>

            <a
                v-if="status && status.local == true && !status.in_reply_to_id"
                class="list-group-item menu-option"
                @click.prevent="ctxMenuEmbed()">
                {{ $t('menu.embed') }}
            </a>

            <a
                class="list-group-item menu-option text-lighter"
                href="#"
                @click.prevent="closeCtxShareMenu()">
                {{ $t('common.cancel') }}
            </a>
        </b-modal>

        <b-modal ref="ctxEmbedModal"
            id="ctx-embed-modal"
            hide-header
            hide-footer
            centered
            rounded
            size="md"
            body-class="p-2 rounded">
            <div>
                <div class="form-group">
                    <textarea class="form-control disabled text-monospace" rows="8" style="overflow-y:hidden;border: 1px solid #efefef; font-size: 12px; line-height: 18px; margin: 0 0 7px;resize:none;" v-model="ctxEmbedPayload" disabled=""></textarea>
                </div>
                <div class="form-group pl-2 d-flex justify-content-center">
                    <div class="form-check mr-3">
                        <input class="form-check-input" type="checkbox" v-model="ctxEmbedShowCaption" :disabled="ctxEmbedCompactMode == true">
                        <label class="form-check-label font-weight-light">
                            {{ $t('menu.showCaption') }}
                        </label>
                    </div>
                    <div class="form-check mr-3">
                        <input class="form-check-input" type="checkbox" v-model="ctxEmbedShowLikes" :disabled="ctxEmbedCompactMode == true">
                        <label class="form-check-label font-weight-light">
                            {{ $t('menu.showLikes') }}
                        </label>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" v-model="ctxEmbedCompactMode">
                        <label class="form-check-label font-weight-light">
                            {{ $t('menu.compactMode') }}
                        </label>
                    </div>
                </div>
                <hr>
                <button :class="copiedEmbed ? 'btn btn-primary btn-block btn-sm py-1 font-weight-bold disabed': 'btn btn-primary btn-block btn-sm py-1 font-weight-bold'" @click="ctxCopyEmbed" :disabled="copiedEmbed">{{copiedEmbed ? 'Embed Code Copied!' : 'Copy Embed Code'}}</button>
                <p class="mb-0 px-2 small text-muted">{{ $t('menu.embedConfirmText') }} <a href="/site/terms">{{ $t('site.terms') }}</a></p>
            </div>
        </b-modal>

        <b-modal ref="ctxReport"
            id="ctx-report"
            hide-header
            hide-footer
            centered
            rounded
            size="sm"
            body-class="list-group-flush p-0 rounded">
            <p class="py-2 px-3 mb-0">
                <div class="text-center font-weight-bold text-danger">{{ $t('menu.report') }}</div>
                <div class="small text-center text-muted">{{ $t('menu.selectOneOption') }}</div>
            </p>
            <div class="list-group text-center">
                <div class="list-group-item rounded cursor-pointer font-weight-bold" @click="sendReport('spam')">{{ $t('menu.spam') }}</div>
                <div class="list-group-item rounded cursor-pointer font-weight-bold" @click="sendReport('sensitive')">{{ $t('menu.sensitive') }}</div>
                <div class="list-group-item rounded cursor-pointer font-weight-bold" @click="sendReport('abusive')">{{ $t('menu.abusive') }}</div>
                <div class="list-group-item rounded cursor-pointer font-weight-bold" @click="openCtxReportOtherMenu()">{{ $t('common.other') }}</div>
                <!-- <div class="list-group-item rounded cursor-pointer" @click="ctxReportMenuGoBack()">Go Back</div> -->
                <div class="list-group-item rounded cursor-pointer text-lighter" @click="ctxReportMenuGoBack()">{{ $t('common.cancel') }}</div>
            </div>
        </b-modal>

        <b-modal ref="ctxReportOther"
            id="ctx-report-other"
            hide-header
            hide-footer
            centered
            rounded
            size="sm"
            body-class="list-group-flush p-0 rounded">
            <p class="py-2 px-3 mb-0">
                <div class="text-center font-weight-bold text-danger">{{ $t('menu.report') }}</div>
                <div class="small text-center text-muted">{{ $t('menu.selectOneOption') }}</div>
            </p>
            <div class="list-group text-center">
                <div class="list-group-item rounded cursor-pointer font-weight-bold" @click="sendReport('underage')">{{ $t('menu.underageAccount') }}</div>
                <div class="list-group-item rounded cursor-pointer font-weight-bold" @click="sendReport('copyright')">{{ $t('menu.copyrightInfringement') }}</div>
                <div class="list-group-item rounded cursor-pointer font-weight-bold" @click="sendReport('impersonation')">{{ $t('menu.impersonation') }}</div>
                <div class="list-group-item rounded cursor-pointer font-weight-bold" @click="sendReport('scam')">{{ $t('menu.scamOrFraud') }}</div>
                <div class="list-group-item rounded cursor-pointer text-lighter" @click="ctxReportOtherMenuGoBack()">{{ $t('common.cancel') }}</div>
            </div>
        </b-modal>

        <b-modal ref="ctxConfirm"
            id="ctx-confirm"
            hide-header
            hide-footer
            centered
            rounded
            size="sm"
            body-class="list-group-flush p-0 rounded">
            <div class="d-flex align-items-center justify-content-center py-3">
                <div>{{ this.confirmModalTitle }}</div>
            </div>
            <div class="d-flex border-top btn-group btn-group-block rounded-0" role="group">
                <button type="button" class="btn btn-outline-lighter border-left-0 border-top-0 border-bottom-0 border-right py-2" style="color: rgb(0,122,255) !important;" @click.prevent="confirmModalCancel()">{{ $t('common.cancel') }}</button>
                <button type="button" class="btn btn-outline-lighter border-0" style="color: rgb(0,122,255) !important;" @click.prevent="confirmModalConfirm()">Confirm</button>
            </div>
        </b-modal>

    </div>
</template>

<script type="text/javascript">
    export default {
        props: [
            'status',
            'profile'
        ],

        data() {
            return {
                config: window.App.config,
                ctxMenuStatus: false,
                ctxMenuRelationship: false,
                ctxEmbedPayload: false,
                copiedEmbed: false,
                replySending: false,
                ctxEmbedShowCaption: true,
                ctxEmbedShowLikes: false,
                ctxEmbedCompactMode: false,
                confirmModalTitle: 'Are you sure?',
                confirmModalIdentifer: null,
                confirmModalType: false,
                isDeleting: false
            }
        },

        watch: {
            ctxEmbedShowCaption: function (n,o) {
                if(n == true) {
                    this.ctxEmbedCompactMode = false;
                }
                let mode = this.ctxEmbedCompactMode ? 'compact' : 'full';
                this.ctxEmbedPayload = window.App.util.embed.post(this.ctxMenuStatus.url, this.ctxEmbedShowCaption, this.ctxEmbedShowLikes, mode);
            },
            ctxEmbedShowLikes: function (n,o) {
                if(n == true) {
                    this.ctxEmbedCompactMode = false;
                }
                let mode = this.ctxEmbedCompactMode ? 'compact' : 'full';
                this.ctxEmbedPayload = window.App.util.embed.post(this.ctxMenuStatus.url, this.ctxEmbedShowCaption, this.ctxEmbedShowLikes, mode);
            },
            ctxEmbedCompactMode: function (n,o) {
                if(n == true) {
                    this.ctxEmbedShowCaption = false;
                    this.ctxEmbedShowLikes = false;
                }
                let mode = this.ctxEmbedCompactMode ? 'compact' : 'full';
                this.ctxEmbedPayload = window.App.util.embed.post(this.ctxMenuStatus.url, this.ctxEmbedShowCaption, this.ctxEmbedShowLikes, mode);
            }
        },

        methods: {
            open() {
                this.ctxMenu();
            },

            openModMenu() {
                this.$refs.ctxModModal.show();
            },

            ctxMenu() {
                this.ctxMenuStatus = this.status;
                this.ctxEmbedPayload = window.App.util.embed.post(this.status.url);
                if(this.status.account.id == this.profile.id) {
                    this.ctxMenuRelationship = false;
                    this.$refs.ctxModal.show();
                } else {
                 axios.get('/api/v1/accounts/relationships', {
                     params: {
                         'id[]': this.status.account.id
                     }
                 }).then(res => {
                     this.ctxMenuRelationship = res.data[0];
                     this.$refs.ctxModal.show();
                 });
                }
            },

            closeCtxMenu() {
                this.copiedEmbed = false;
                this.ctxMenuStatus = false;
                this.ctxMenuRelationship = false;
                this.$refs.ctxModal.hide();
                this.$refs.ctxReport.hide();
                this.$refs.ctxReportOther.hide();
                this.closeModals();
            },

            ctxMenuCopyLink() {
                let status = this.ctxMenuStatus;
                navigator.clipboard.writeText(status.url);
                this.closeModals();
                return;
            },

            ctxMenuGoToPost() {
                let status = this.ctxMenuStatus;
                this.statusUrl(status);
                this.closeCtxMenu();
                return;
            },

            ctxMenuGoToProfile() {
                let status = this.ctxMenuStatus;
                this.profileUrl(status);
                this.closeCtxMenu();
                return;
            },

            ctxMenuReportPost() {
                this.$refs.ctxModal.hide();
                // this.$refs.ctxReport.show();
                this.$emit('report-modal', this.ctxMenuStatus);
                return;
            },

            ctxMenuEmbed() {
                this.closeModals();
                this.$refs.ctxEmbedModal.show();
            },

            ctxMenuShare() {
                this.$refs.ctxModal.hide();
                this.$refs.ctxShareModal.show();
            },

            closeCtxShareMenu() {
                this.$refs.ctxShareModal.hide();
                this.$refs.ctxModal.show();
            },

            ctxCopyEmbed() {
                navigator.clipboard.writeText(this.ctxEmbedPayload);
                this.ctxEmbedShowCaption = true;
                this.ctxEmbedShowLikes = false;
                this.ctxEmbedCompactMode = false;
                this.$refs.ctxEmbedModal.hide();
            },

            ctxModMenuShow() {
                this.$refs.ctxModal.hide();
                this.$refs.ctxModModal.show();
            },

            ctxModOtherMenuShow() {
                this.$refs.ctxModal.hide();
                this.$refs.ctxModModal.hide();
                this.$refs.ctxModOtherModal.show();
            },

            ctxModMenu() {
                this.$refs.ctxModal.hide();
            },

            ctxModMenuClose() {
                this.closeModals();
            },

            ctxModOtherMenuClose() {
                this.closeModals();
                this.$refs.ctxModModal.show();
            },

            formatCount(count) {
                return App.util.format.count(count);
            },

            openCtxReportOtherMenu() {
                let s = this.ctxMenuStatus;
                this.closeCtxMenu();
                this.ctxMenuStatus = s;
                this.$refs.ctxReportOther.show();
            },

            ctxReportMenuGoBack() {
                this.$refs.ctxReportOther.hide();
                this.$refs.ctxReport.hide();
                this.$refs.ctxModal.show();
            },

            ctxReportOtherMenuGoBack() {
                this.$refs.ctxReportOther.hide();
                this.$refs.ctxModal.hide();
                this.$refs.ctxReport.show();
            },

            sendReport(type) {
                let id = this.ctxMenuStatus.id;

                swal({
                    'title': this.$t('menu.confirmReport'),
                    'text': this.$t('menu.confirmReportText'),
                    'icon': 'warning',
                    'buttons': true,
                    'dangerMode': true
                }).then((res) => {
                    if(res) {
                        axios.post('/i/report/', {
                            'report': type,
                            'type': 'post',
                            'id': id,
                        }).then(res => {
                            this.closeCtxMenu();
                            swal(this.$t('menu.reportSent'), this.$t('menu.reportSentText'), 'success');
                        }).catch(err => {
                            swal(this.$t('common.oops'), this.$t('menu.reportSentError'), 'error');
                        })
                    } else {
                        this.closeCtxMenu();
                    }
                });
            },

            closeModals() {
                this.$refs.ctxModal.hide();
                this.$refs.ctxModModal.hide();
                this.$refs.ctxModOtherModal.hide();
                this.$refs.ctxShareModal.hide();
                this.$refs.ctxEmbedModal.hide();
                this.$refs.ctxReport.hide();
                this.$refs.ctxReportOther.hide();
                this.$refs.ctxConfirm.hide();
            },

            openCtxStatusModal() {
                this.closeModals();
                this.$refs.ctxStatusModal.show();
            },

            openConfirmModal() {
                this.closeModals();
                this.$refs.ctxConfirm.show();
            },

            closeConfirmModal() {
                this.closeModals();
                this.confirmModalTitle = 'Are you sure?';
                this.confirmModalType = false;
                this.confirmModalIdentifer = null;
            },

            confirmModalConfirm() {
                switch(this.confirmModalType) {
                    case 'post.delete':
                        axios.post('/i/delete', {
                            type: 'status',
                            item: this.confirmModalIdentifer
                        }).then(res => {
                            this.feed = this.feed.filter(s => {
                                return s.id != this.confirmModalIdentifer;
                            });
                            this.closeConfirmModal();
                        }).catch(err => {
                            this.closeConfirmModal();
                            swal(this.$t('common.error'), this.$t('common.errorMsg'), 'error');
                        });
                    break;
                }

                this.closeConfirmModal();
            },

            confirmModalCancel() {
                this.closeConfirmModal();
            },

            moderatePost(status, action, $event) {
                let username = status.account.username;
                let pid = status.id;
                let msg = '';
                let self = this;
                switch(action) {
                    case 'addcw':
                        msg = this.$t('menu.modAddCWConfirm');
                        swal({
                            title: 'Confirm',
                            text: msg,
                            icon: 'warning',
                            buttons: true,
                            dangerMode: true
                        }).then(res =>  {
                            if(res) {
                                axios.post('/api/v2/moderator/action', {
                                    action: action,
                                    item_id: status.id,
                                    item_type: 'status'
                                }).then(res => {
                                    swal(this.$t('common.success'), this.$t('menu.modCWSuccess'), 'success');
                                    // status.sensitive = true;
                                    this.$emit('moderate', 'addcw');
                                    self.closeModals();
                                    self.ctxModMenuClose();
                                }).catch(err => {
                                    self.closeModals();
                                    self.ctxModMenuClose();
                                    swal(this.$t('common.error'), this.$t('common.errorMsg'), 'error');
                                });
                            }
                        });
                    break;

                    case 'remcw':
                        msg = this.$t('menu.modRemoveCWConfirm');
                        swal({
                            title: 'Confirm',
                            text: msg,
                            icon: 'warning',
                            buttons: true,
                            dangerMode: true
                        }).then(res =>  {
                            if(res) {
                                axios.post('/api/v2/moderator/action', {
                                    action: action,
                                    item_id: status.id,
                                    item_type: 'status'
                                }).then(res => {
                                    swal(this.$t('common.success'), this.$t('menu.modRemoveCWSuccess'), 'success');
                                    // status.sensitive = false;
                                    this.$emit('moderate', 'remcw');
                                    self.closeModals();
                                    self.ctxModMenuClose();
                                }).catch(err => {
                                    self.closeModals();
                                    self.ctxModMenuClose();
                                    swal(this.$t('common.error'), this.$t('common.errorMsg'), 'error');
                                });
                            }
                        });
                    break;

                    case 'unlist':
                        msg = this.$t('menu.modUnlistConfirm');
                        swal({
                            title: 'Confirm',
                            text: msg,
                            icon: 'warning',
                            buttons: true,
                            dangerMode: true
                        }).then(res =>  {
                            if(res) {
                                axios.post('/api/v2/moderator/action', {
                                    action: action,
                                    item_id: status.id,
                                    item_type: 'status'
                                }).then(res => {
                                    // this.feed = this.feed.filter(f => {
                                    //  return f.id != status.id;
                                    // });
                                    this.$emit('moderate', 'unlist');
                                    swal(this.$t('common.success'), this.$t('menu.modUnlistSuccess'), 'success');
                                    self.closeModals();
                                    self.ctxModMenuClose();
                                }).catch(err => {
                                    self.closeModals();
                                    self.ctxModMenuClose();
                                    swal(this.$t('common.error'), this.$t('common.errorMsg'), 'error');
                                });
                            }
                        });
                    break;

                    case 'spammer':
                        msg = this.$t('menu.modMarkAsSpammerConfirm');
                        swal({
                            title: 'Confirm',
                            text: msg,
                            icon: 'warning',
                            buttons: true,
                            dangerMode: true
                        }).then(res =>  {
                            if(res) {
                                axios.post('/api/v2/moderator/action', {
                                    action: action,
                                    item_id: status.id,
                                    item_type: 'status'
                                }).then(res => {
                                    this.$emit('moderate', 'spammer');
                                    swal(this.$t('common.success'), this.$t('menu.modMarkAsSpammerSuccess'), 'success');
                                    self.closeModals();
                                    self.ctxModMenuClose();
                                }).catch(err => {
                                    self.closeModals();
                                    self.ctxModMenuClose();
                                    swal(this.$t('common.error'), this.$t('common.errorMsg'), 'error');
                                });
                            }
                        });
                    break;
                }
            },

            statusUrl(status) {
                if(status.account.local == true) {
                    this.$router.push({
                        name: 'post',
                        path: `/i/web/post/${status.id}`,
                        params: {
                            id: status.id,
                            cachedStatus: status,
                            cachedProfile: this.profile
                        }
                    });
                    return;
                }

                let permalink = this.$route.params.hasOwnProperty('id');
                if(permalink) {
                    location.href = status.url;
                    return;
                } else {
                    this.$router.push({
                        name: 'post',
                        path: `/i/web/post/${status.id}`,
                        params: {
                            id: status.id,
                            cachedStatus: status,
                            cachedProfile: this.profile
                        }
                    });
                    return;
                }
            },

            profileUrl(status) {
                this.$router.push({
                    name: 'profile',
                    path: `/i/web/profile/${status.account.id}`,
                    params: {
                        id: status.account.id,
                        cachedProfile: status.account,
                        cachedUser: this.profile
                    }
                });
                return;
            },

            deletePost(status) {
                this.isDeleting = true;

                if(this.ownerOrAdmin(status) == false) {
                    return;
                }

                swal({
                    title: 'Confirm Delete',
                    text: 'Are you sure you want to delete this post?',
                    icon: "warning",
                    buttons: true,
                    dangerMode: true,
                })
                .then(res => {
                    if(!res) {
                        this.closeModals();
                        this.isDeleting = false;
                    } else {
                        axios.post('/i/delete', {
                            type: 'status',
                            item: status.id
                        }).then(res => {
                            this.$emit('delete');
                            this.closeModals();
                            this.isDeleting = false;
                        }).catch(err => {
                            this.closeModals();
                            this.isDeleting = false;
                            swal(this.$t('common.error'), this.$t('common.errorMsg'), 'error');
                        });
                    }
                })
            },

            owner(status) {
                return this.profile.id === status.account.id;
            },

            admin() {
                return this.profile.is_admin == true;
            },

            ownerOrAdmin(status) {
                return this.owner(status) || this.admin();
            },

            archivePost(status) {
                if(window.confirm(this.$t('menu.archivePostConfirm')) == false) {
                    return;
                }

                axios.post('/api/pixelfed/v2/status/' + status.id + '/archive')
                .then(res => {
                    this.$emit('delete', status.id);
                    this.$emit('archived', status.id);
                    this.closeModals();
                });
            },

            unarchivePost(status) {
                if(window.confirm(this.$t('menu.unarchivePostConfirm')) == false) {
                    return;
                }

                axios.post('/api/pixelfed/v2/status/' + status.id + '/unarchive')
                .then(res => {
                    this.$emit('unarchived', status.id);
                    this.closeModals();
                });
            },

            editPost(status) {
                this.closeModals();
                this.$emit('edit', status);
            },

            handleMute() {
                if(!this.ctxMenuRelationship) {
                    return;
                }
                let curState = this.ctxMenuRelationship.muting;

                swal({
                    title: curState ? 'Confirm Unmute' : 'Confirm Mute',
                    text: curState ? 'Are you sure you want to unmute this account?' : 'Are you sure you want to mute this account?',
                    icon: "warning",
                    buttons: true,
                    dangerMode: true,
                })
                .then(res => {
                    if(!res) {
                        this.closeModals();
                    } else {
                        let url = curState ?
                            `/api/v1/accounts/${this.status.account.id}/unmute` :
                            `/api/v1/accounts/${this.status.account.id}/mute`;
                        axios.post(url)
                        .then(res => {
                            this.closeModals();
                            this.$emit('muted', this.status);
                            this.$store.commit('updateRelationship', [res.data]);
                        })
                        .catch(err => {
                            this.closeModals();
                            if(err && err.response && err.response.data && err.response.data.error) {
                                swal('Error', err.response.data.error, 'error');
                            } else {
                                swal('Oops!', 'An error occured, please try again later.', 'error');
                            }
                        })
                    }
                })
            },

            handleBlock() {
                if(!this.ctxMenuRelationship) {
                    return;
                }
                let curState = this.ctxMenuRelationship.blocking;

                swal({
                    title: curState ? 'Confirm Unblock' : 'Confirm Block',
                    text: curState ? 'Are you sure you want to unblock this account?' : 'Are you sure you want to block this account?',
                    icon: "warning",
                    buttons: true,
                    dangerMode: true,
                })
                .then(res => {
                    if(!res) {
                        this.closeModals();
                    } else {
                        let url = curState ?
                            `/api/v1/accounts/${this.status.account.id}/unblock` :
                            `/api/v1/accounts/${this.status.account.id}/block`;
                        axios.post(url)
                        .then(res => {
                            this.closeModals();
                            this.$store.commit('updateRelationship', [res.data]);
                            this.$emit('muted', this.status);
                        })
                        .catch(err => {
                            this.closeModals();
                            if(err && err.response && err.response.data && err.response.data.error) {
                                swal('Error', err.response.data.error, 'error');
                            } else {
                                swal('Oops!', 'An error occured, please try again later.', 'error');
                            }
                        })
                    }
                })
            },

            handleUnfollow() {
                if(!this.ctxMenuRelationship) {
                    return;
                }

                swal({
                    title: 'Unfollow',
                    text: 'Are you sure you want to unfollow ' + this.status.account.username + '?',
                    icon: "warning",
                    buttons: true,
                    dangerMode: true,
                })
                .then(res => {
                    if(!res) {
                        this.closeModals();
                    } else {
                        axios.post(`/api/v1/accounts/${this.status.account.id}/unfollow`)
                        .then(res => {
                            this.closeModals();
                            this.$store.commit('updateRelationship', [res.data]);
                            this.$emit('unfollow', this.status);
                        })
                        .catch(err => {
                            this.closeModals();
                            if(err && err.response && err.response.data && err.response.data.error) {
                                swal('Error', err.response.data.error, 'error');
                            } else {
                                swal('Oops!', 'An error occured, please try again later.', 'error');
                            }
                        })
                    }
                })
            },

            pinPost(status) {
                if(window.confirm(this.$t('menu.pinPostConfirm')) == false) {
                    return;
                }

                this.closeModals();

                axios.post('/api/pixelfed/v1/statuses/' + status.id.toString() + '/pin')
                .then(res => {
                    const data = res.data;
                    if(data.id && data.pinned) {
                        this.$emit('pinned');
                        swal('Pinned', 'Successfully pinned post to your profile', 'success');
                    } else {
                        swal('Error', 'An error occured when attempting to pin', 'error');
                    }
                })
                .catch(err => {
                    this.closeModals();
                    if(err.response?.data?.error) {
                        swal('Error', err.response?.data?.error, 'error');
                    }
                });
            },

            unpinPost(status) {
                if(window.confirm(this.$t('menu.unpinPostConfirm')) == false) {
                    return;
                }
                this.closeModals();

                axios.post('/api/pixelfed/v1/statuses/' + status.id.toString() + '/unpin')
                .then(res => {
                    const data = res.data;
                    if(data.id) {
                        this.$emit('unpinned');
                        swal('Unpinned', 'Successfully unpinned post from your profile', 'success');
                    } else {
                        swal('Error', data.error, 'error');
                    }
                })
                .catch(err => {
                    this.closeModals();
                    if(err.response?.data?.error) {
                        swal('Error', err.response?.data?.error, 'error');
                    } else {
                        window.location.reload()
                    }
                });
            },
        }
    }
</script>

<style lang="scss" scoped>
    .menu-option {
        font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
        text-decoration: none;
        font-weight: 500;
        color: var(--dark);
    }

    .list-group-item {
        border-color: var(--border-color);
    }

    .action-icon-link {
        display: flex;
        flex-direction: column;

        .icon {
            opacity: 0.5;
            margin-bottom: 5px;
        }

        p {
            font-weight: 600;
            font-size: 11px;
        }

        &-inline {
            display: flex;
            flex-direction: row;
            justify-content: center;
            align-items: center;
            gap: 8px;

            p {
                font-weight: bold;
            }
        }
    }
</style>
