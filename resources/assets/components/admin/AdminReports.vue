<template>
<div>
    <div class="header bg-primary pb-3 mt-n4">
        <div class="container-fluid">
            <div class="header-body">
                <div class="row align-items-center py-4">
                    <div class="col-lg-6 col-7">
                        <p class="display-1 text-white d-inline-block mb-0">Moderation</p>
                    </div>
                </div>
                <div class="row">
                    <div class="col-12 col-sm-6 col-lg-3">
                        <div class="mb-3">
                            <h5 class="text-light text-uppercase mb-0">Active Reports</h5>
                            <span
                                class="text-white h2 font-weight-bold mb-0 human-size"
                                data-toggle="tooltip"
                                data-placement="bottom"
                                :title="stats.open + ' open reports'">
                                {{ prettyCount(stats.open) }}
                            </span>
                        </div>
                    </div>

                    <div class="col-12 col-sm-6 col-lg-3">
                        <div class="mb-3">
                            <h5 class="text-light text-uppercase mb-0">Active Spam Detections</h5>
                            <span
                                class="text-white h2 font-weight-bold mb-0 human-size"
                                data-toggle="tooltip"
                                data-placement="bottom"
                                :title="stats.autospam_open + ' open spam detections'"
                                >{{ prettyCount(stats.autospam_open) }}</span>
                        </div>
                    </div>

                    <div class="col-12 col-sm-6 col-lg-3">
                        <div class="mb-3">
                            <h5 class="text-light text-uppercase mb-0">Total Reports</h5>
                            <span
                                class="text-white h2 font-weight-bold mb-0 human-size"
                                data-toggle="tooltip"
                                data-placement="bottom"
                                :title="stats.total + ' total reports'"
                                >{{ prettyCount(stats.total) }}
                            </span>
                        </div>
                    </div>

                    <div class="col-12 col-sm-6 col-lg-3">
                        <div class="mb-3">
                            <h5 class="text-light text-uppercase mb-0">Total Spam Detections</h5>
                            <span
                                class="text-white h2 font-weight-bold mb-0 human-size"
                                data-toggle="tooltip"
                                data-placement="bottom"
                                :title="stats.autospam + ' total spam detections'">
                                {{ prettyCount(stats.autospam) }}
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div v-if="!loaded" class="my-5 text-center">
        <b-spinner />
    </div>

    <div v-else class="m-n2 m-lg-4">
        <div class="container-fluid mt-4">
            <div class="row mb-3 justify-content-between">
                <div class="col-12">
                    <ul class="nav nav-pills">
                        <li class="nav-item">
                            <a
                                :class="['nav-link d-flex align-items-center', { active: tabIndex == 0}]"
                                href="#"
                                @click.prevent="toggleTab(0)">

                                <span>Open Reports</span>
                                <span
                                    v-if="stats.open"
                                    class="badge badge-sm badge-floating badge-danger border-white ml-2"
                                    style="background-color: red;color:white;font-size:11px;">
                                    {{prettyCount(stats.open)}}
                                </span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a
                                :class="['nav-link d-flex align-items-center', { active: tabIndex == 2}]"
                                href="#"
                                @click.prevent="toggleTab(2)">

                                <span>Spam Detections</span>
                                <span
                                    v-if="stats.autospam_open"
                                    class="badge badge-sm badge-floating badge-danger border-white ml-2"
                                    style="background-color: red;color:white;font-size:11px;">
                                    {{prettyCount(stats.autospam_open)}}
                                </span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a
                                :class="['nav-link d-flex align-items-center', { active: tabIndex == 3}]"
                                href="#"
                                @click.prevent="toggleTab(3)">

                                <span>Remote Reports</span>
                                <span
                                    v-if="stats.remote_open"
                                    class="badge badge-sm badge-floating badge-danger border-white ml-2"
                                    style="background-color: red;color:white;font-size:11px;">
                                    {{prettyCount(stats.remote_open)}}
                                </span>
                            </a>
                        </li>
                        <li class="d-none d-md-block nav-item">
                            <a
                                :class="['nav-link d-flex align-items-center', { active: tabIndex == 1}]"
                                href="#"
                                @click.prevent="toggleTab(1)">
                                <span>Closed Reports</span>
                                <span
                                    v-if="stats.autospam_open"
                                    class="badge badge-sm badge-floating badge-secondary border-white ml-2"
                                    style="font-size:11px;">
                                    {{prettyCount(stats.closed)}}
                                </span>
                            </a>
                        </li>
                        <li class="d-none d-md-block nav-item">
                            <a
                                href="/i/admin/reports/email-verifications"
                                class="nav-link d-flex align-items-center">
                                <span>Email Verification Requests</span>
                                <span
                                    v-if="stats.email_verification_requests"
                                    class="badge badge-sm badge-floating badge-secondary border-white ml-2"
                                    style="font-size:11px;">
                                    {{prettyCount(stats.email_verification_requests)}}
                                </span>
                            </a>
                        </li>
                        <li class="d-none d-md-block nav-item">
                            <a
                                :class="['nav-link d-flex align-items-center', { active: tabIndex == 4}]"
                                href="#"
                                @click.prevent="toggleTab(4)">
                                <span>Moderated Profiles</span>
                            </a>
                        </li>
                    </ul>
                </div>
            </div>

            <div v-if="[0, 1].includes(this.tabIndex)" class="table-responsive rounded">
                <table v-if="reports && reports.length" class="table table-dark">
                    <thead class="thead-dark">
                        <tr>
                            <th scope="col">ID</th>
                            <th scope="col">Report</th>
                            <th scope="col">Reported Account</th>
                            <th scope="col">Reported By</th>
                            <th scope="col">Created</th>
                            <th scope="col">View Report</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="(report, idx) in reports">
                            <td class="font-weight-bold text-monospace text-muted align-middle">
                                <a href="#" @click.prevent="viewReport(report)">
                                    {{ report.id }}
                                </a>
                            </td>
                            <td class="align-middle">
                                <p class="text-capitalize font-weight-bold mb-0" v-html="reportLabel(report)"></p>
                            </td>
                            <td class="align-middle">
                                <a v-if="report.reported && report.reported.id" :href="`/i/web/profile/${report.reported.id}`" target="_blank" class="text-white">
                                    <div class="d-flex align-items-center" style="gap:0.61rem;">
                                        <img
                                            :src="report.reported.avatar"
                                            width="30"
                                            height="30"
                                            style="object-fit: cover;border-radius:30px;"
                                            onerror="this.src='/storage/avatars/default.png';this.error=null;">

                                        <div class="d-flex flex-column">
                                            <p class="font-weight-bold mb-0" style="font-size: 14px;">@{{report.reported.username}}</p>
                                            <div class="d-flex small text-muted mb-0" style="gap: 0.5rem;">
                                                <span>{{report.reported.followers_count}} Followers</span>
                                                <span>·</span>
                                                <span>Joined {{ timeAgo(report.reported.created_at) }}</span>
                                            </div>
                                        </div>
                                    </div>
                                </a>
                            </td>
                            <td class="align-middle">
                                <a
                                    v-if="report && report.reporter && report.reporter.id"
                                    :href="`/i/web/profile/${report.reporter.id}`"
                                    target="_blank"
                                    class="text-white">
                                    <div class="d-flex align-items-center" style="gap:0.61rem;">
                                        <img
                                            :src="report.reporter.avatar"
                                            width="30"
                                            height="30"
                                            style="object-fit: cover;border-radius:30px;"
                                            onerror="this.src='/storage/avatars/default.png';this.error=null;">

                                        <div class="d-flex flex-column">
                                            <p class="font-weight-bold mb-0" style="font-size: 14px;">@{{report.reporter.username}}</p>
                                            <div class="d-flex small text-muted mb-0" style="gap: 0.5rem;">
                                                <span>{{report.reporter.followers_count}} Followers</span>
                                                <span>·</span>
                                                <span>Joined {{ timeAgo(report.reporter.created_at) }}</span>
                                            </div>
                                        </div>
                                    </div>
                                </a>
                            </td>
                            <td class="font-weight-bold align-middle">{{ timeAgo(report.created_at) }}</td>
                            <td class="align-middle"><a href="#" class="btn btn-primary btn-sm" @click.prevent="viewReport(report)">View</a></td>
                        </tr>
                    </tbody>
                </table>

                <div v-else>
                    <div class="card card-body p-5">
                        <div class="d-flex justify-content-between align-items-center flex-column">
                            <p class="mt-3 mb-0"><i class="far fa-check-circle fa-5x text-success"></i></p>
                            <p class="lead">{{ tabIndex === 0 ? 'No Active Reports Found!' : 'No Closed Reports Found!' }}</p>
                        </div>
                    </div>
                </div>
            </div>

            <div v-if="[0, 1].includes(this.tabIndex) && reports.length && (pagination.prev || pagination.next)" class="d-flex align-items-center justify-content-center">
                <button
                    class="btn btn-primary rounded-pill"
                    :disabled="!pagination.prev"
                    @click="paginate('prev')">
                    Prev
                </button>
                <button
                    class="btn btn-primary rounded-pill"
                    :disabled="!pagination.next"
                    @click="paginate('next')">
                    Next
                </button>
            </div>

            <div v-if="this.tabIndex === 2" class="table-responsive rounded">
                <template v-if="autospamLoaded">
                    <table v-if="autospam && autospam.length" class="table table-dark">
                        <thead class="thead-dark">
                            <tr>
                                <th scope="col">ID</th>
                                <th scope="col">Report</th>
                                <th scope="col">Reported Account</th>
                                <th scope="col">Created</th>
                                <th scope="col">View Report</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="(report, idx) in autospam">
                                <td class="font-weight-bold text-monospace text-muted align-middle">
                                    <a href="#" @click.prevent="viewSpamReport(report)">
                                        {{ report.id }}
                                    </a>
                                </td>
                                <td class="align-middle">
                                    <p class="text-capitalize font-weight-bold mb-0">Spam Post</p>
                                </td>
                                <td class="align-middle">
                                    <a v-if="report.status && report.status.account" :href="`/i/web/profile/${report.status.account.id}`" target="_blank" class="text-white">
                                        <div class="d-flex align-items-center" style="gap:0.61rem;">
                                            <img
                                                :src="report.status.account.avatar"
                                                width="30"
                                                height="30"
                                                style="object-fit: cover;border-radius:30px;"
                                                onerror="this.src='/storage/avatars/default.png';this.error=null;">

                                            <div class="d-flex flex-column">
                                                <p class="font-weight-bold mb-0" style="font-size: 14px;">@{{report.status.account.username}}</p>
                                                <div class="d-flex small text-muted mb-0" style="gap: 0.5rem;">
                                                    <span>{{report.status.account.followers_count}} Followers</span>
                                                    <span>·</span>
                                                    <span>Joined {{ timeAgo(report.status.account.created_at) }}</span>
                                                </div>
                                            </div>
                                        </div>
                                    </a>
                                </td>
                                <td class="font-weight-bold align-middle">{{ timeAgo(report.created_at) }}</td>
                                <td class="align-middle"><a href="#" class="btn btn-primary btn-sm" @click.prevent="viewSpamReport(report)">View</a></td>
                            </tr>
                        </tbody>
                    </table>

                    <div v-else>
                        <div class="card card-body p-5">
                            <div class="d-flex justify-content-between align-items-center flex-column">
                                <p class="mt-3 mb-0"><i class="far fa-check-circle fa-5x text-success"></i></p>
                                <p class="lead">No Spam Reports Found!</p>
                            </div>
                        </div>
                    </div>
                </template>

                <div v-else class="d-flex align-items-center justify-content-center" style="min-height: 300px;">
                    <b-spinner />
                </div>
            </div>

            <div v-if="this.tabIndex === 2 && autospamLoaded && autospam && autospam.length" class="d-flex align-items-center justify-content-center">
                <button
                    class="btn btn-primary rounded-pill"
                    :disabled="!autospamPagination.prev"
                    @click="autospamPaginate('prev')">
                    Prev
                </button>
                <button
                    class="btn btn-primary rounded-pill"
                    :disabled="!autospamPagination.next"
                    @click="autospamPaginate('next')">
                    Next
                </button>
            </div>

            <div v-if="this.tabIndex === 3" class="table-responsive rounded">
                <table v-if="reports && reports.length" class="table table-dark">
                    <thead class="thead-dark">
                        <tr>
                            <th scope="col">ID</th>
                            <th scope="col">Instance</th>
                            <th scope="col">Reported Account</th>
                            <th scope="col">Comment</th>
                            <th scope="col">Created</th>
                            <th scope="col">View Report</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr
                            v-for="(report, idx) in reports"
                            :key="`remote-reports-${report.id}-${idx}`">
                            <td class="font-weight-bold text-monospace text-muted align-middle">
                                <a href="#" @click.prevent="showRemoteReport(report)">
                                    {{ report.id }}
                                </a>
                            </td>
                            <td class="align-middle">
                                <p class="font-weight-bold mb-0">{{ report.instance }}</p>
                            </td>
                            <td class="align-middle">
                                <a v-if="report.reported && report.reported.id" :href="`/i/web/profile/${report.reported.id}`" target="_blank" class="text-white">
                                    <div class="d-flex align-items-center" style="gap:0.61rem;">
                                        <img
                                            :src="report.reported.avatar"
                                            width="30"
                                            height="30"
                                            style="object-fit: cover;border-radius:30px;"
                                            onerror="this.src='/storage/avatars/default.png';this.error=null;">

                                        <div class="d-flex flex-column">
                                            <p class="font-weight-bold mb-0" style="font-size: 14px;">@{{report.reported.username}}</p>
                                            <div class="d-flex small text-muted mb-0" style="gap: 0.5rem;">
                                                <span>{{report.reported.followers_count}} Followers</span>
                                                <span>·</span>
                                                <span>Joined {{ timeAgo(report.reported.created_at) }}</span>
                                            </div>
                                        </div>
                                    </div>
                                </a>
                            </td>
                            <td class="align-middle">
                                <p class="small mb-0 text-wrap" style="max-width: 300px;word-break: break-all;">{{ report.message && report.message.length > 120 ? report.message.slice(0, 120) + '...' : report.message }}</p>
                            </td>
                            <td class="font-weight-bold align-middle">{{ timeAgo(report.created_at) }}</td>
                            <td class="align-middle"><a href="#" class="btn btn-primary btn-sm" @click.prevent="showRemoteReport(report)">View</a></td>
                        </tr>
                    </tbody>
                </table>

                <div v-else>
                    <div class="card card-body p-5">
                        <div class="d-flex justify-content-between align-items-center flex-column">
                            <p class="mt-3 mb-0"><i class="far fa-check-circle fa-5x text-success"></i></p>
                            <p class="lead">{{ tabIndex === 0 ? 'No Active Reports Found!' : 'No Closed Reports Found!' }}</p>
                        </div>
                    </div>
                </div>
            </div>

            <div v-if="this.tabIndex === 3 && remoteReportsLoaded && reports && reports.length" class="d-flex align-items-center justify-content-center">
                <button
                    class="btn btn-primary rounded-pill"
                    :disabled="!pagination.prev"
                    @click="remoteReportPaginate('prev')">
                    Prev
                </button>
                <button
                    class="btn btn-primary rounded-pill"
                    :disabled="!pagination.next"
                    @click="remoteReportPaginate('next')">
                    Next
                </button>
            </div>

            <div v-if="this.tabIndex === 4" class="table-responsive rounded">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <form class="navbar-search navbar-search-dark form-inline mr-sm-3" @submit.prevent="handleModeratedProfileSearch">
                        <div class="form-group mb-0">
                            <div class="input-group input-group-alternative input-group-merge">
                                <div class="input-group-prepend">
                                    <span class="input-group-text">
                                        <i class="fas fa-search"></i>
                                    </span>
                                </div>
                                <input
                                    type="text"
                                    name="username"
                                    placeholder="Search by username"
                                    class="form-control"
                                    v-model="moderatedProfilesSearchInput">
                            </div>
                        </div>
                    </form>
                    <div class="d-flex gap-1">
                        <button type="button" class="btn btn-outline-primary fw-bold" @click="exportModeratedProfiles()">Export</button>
                        <button type="button" class="btn btn-primary fw-bold" @click.prevent="addModeratedProfile()">Add Moderated Profile</button>
                    </div>
                </div>

                <table v-if="moderatedProfiles && moderatedProfiles.length" class="table table-dark">
                    <thead class="thead-dark">
                        <tr>
                            <th scope="col">ID</th>
                            <th scope="col">Username</th>
                            <th scope="col">Moderation</th>
                            <th scope="col">Comment</th>
                            <th scope="col">Created</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr
                            v-for="(report, idx) in moderatedProfiles"
                            :key="`remote-reports-${report.id}-${idx}`">
                            <td class="font-weight-bold text-monospace text-muted align-middle">
                                <button class="btn btn-primary btn-sm" @click.prevent="openModeratedProfileModal(report)">
                                    {{ report.id }}
                                </button>
                            </td>
                            <td class="align-middle">
                                <p v-if="report.profile.name" class="small mb-0 text-muted">
                                    {{ truncateText(report.profile.name, 40) }}
                                </p>
                                <p
                                    class="font-weight-bold mb-0"
                                    data-toggle="tooltip"
                                    data-placement="bottom"
                                    :title="report.profile.username">
                                    {{ truncateText(report.profile.username, 40) }}
                                </p>
                            </td>
                            <td class="align-middle">
                                <p class="mb-0" v-html="getModerationLabels(report)"></p>
                            </td>
                            <td class="align-middle">
                                <p class="small mb-0 text-wrap" style="max-width: 200px;word-break: break-word;">{{ truncateText(report.note, 140) }}</p>
                            </td>
                            <td class="font-weight-bold align-middle">
                                <span
                                    data-toggle="tooltip"
                                    data-placement="bottom"
                                    :title="report.created_at">
                                    {{ timeAgo(report.created_at) }}
                                </span>
                            </td>
                        </tr>
                    </tbody>
                </table>

                <div v-else>
                    <div class="card card-body p-5">
                        <div class="d-flex justify-content-between align-items-center flex-column">

                            <template v-if="moderatedProfilesSearchInput">
                                <p class="mt-3 mb-0"><i class="far fa-times fa-5x text-danger"></i></p>
                                <p class="lead">No results found!</p>
                                <button class="btn btn-primary" @click.prevent="clearModeratedProfileSearch()">Go back</button>
                            </template>

                            <template v-else>
                                <p class="mt-3 mb-0"><i class="far fa-check-circle fa-5x text-success"></i></p>
                                <p class="lead">No active moderation accounts found!</p>
                            </template>
                        </div>
                    </div>
                </div>

                <div v-if="moderatedProfiles && moderatedProfiles.length && (moderatedProfilesPagination.prev || moderatedProfilesPagination.next)" class="mt-3 d-flex align-items-center justify-content-center">
                    <button
                        class="btn btn-primary rounded-pill"
                        :disabled="!moderatedProfilesPagination.prev"
                        @click="paginateModeratedAccounts('prev')">
                        Prev
                    </button>
                    <button
                        class="btn btn-primary rounded-pill"
                        :disabled="!moderatedProfilesPagination.next"
                        @click="paginateModeratedAccounts('next')">
                        Next
                    </button>
                </div>
            </div>
        </div>
    </div>

    <b-modal v-model="showReportModal" :title="tabIndex === 0 ? 'View Report' : 'Viewing Closed Report'" :ok-only="true" ok-title="Close" ok-variant="outline-primary">
        <div v-if="viewingReportLoading" class="d-flex align-items-center justify-content-center">
            <b-spinner />
        </div>

        <template v-else>
            <div v-if="viewingReport" class="list-group">
                <div class="list-group-item d-flex align-items-center justify-content-between">
                    <div class="text-muted small">Type</div>
                    <div class="font-weight-bold text-capitalize" v-html="reportLabel(viewingReport)"></div>
                </div>
                <div v-if="viewingReport.admin_seen_at" class="list-group-item d-flex align-items-center justify-content-between">
                    <div class="text-muted small">Report Closed</div>
                    <div class="font-weight-bold text-capitalize">{{ formatDate(viewingReport.admin_seen_at) }}</div>
                </div>
                <div v-if="viewingReport.reporter_message" class="list-group-item d-flex flex-column" style="gap:10px;">
                    <div class="text-muted small">Message</div>
                    <p class="mb-0 read-more" style="font-size: 12px;overflow-y: hidden;">{{ viewingReport.reporter_message }}</p>
                </div>
            </div>

            <div class="list-group list-group-horizontal mt-3">
                <div v-if="viewingReport && viewingReport.reported" class="list-group-item d-flex align-items-center justify-content-between flex-column flex-grow-1" style="gap:0.4rem;">
                    <div class="text-muted small font-weight-bold mt-n1">Reported Account</div>

                    <a v-if="viewingReport.reported && viewingReport.reported.id" :href="`/i/web/profile/${viewingReport.reported.id}`" target="_blank" class="text-primary">
                        <div class="d-flex align-items-center" style="gap:0.61rem;">
                            <img
                                :src="viewingReport.reported.avatar"
                                width="30"
                                height="30"
                                style="object-fit: cover;border-radius:30px;"
                                onerror="this.src='/storage/avatars/default.png';this.error=null;">

                            <div class="d-flex flex-column">
                                <p class="font-weight-bold mb-0 text-break" style="font-size: 12px;max-width: 140px;line-height: 16px;" :class="[ viewingReport.reported.is_admin ? 'text-danger': '']">@{{viewingReport.reported.acct}}</p>
                                <div class="d-flex text-muted mb-0" style="font-size: 10px;gap: 0.5rem;">
                                    <span>{{viewingReport.reported.followers_count}} Followers</span>
                                    <span>·</span>
                                    <span>Joined {{ timeAgo(viewingReport.reported.created_at) }}</span>
                                </div>
                            </div>
                        </div>
                    </a>
                </div>

                <div v-if="viewingReport && viewingReport.reporter" class="list-group-item d-flex align-items-center justify-content-between flex-column flex-grow-1" style="gap:0.4rem;">
                    <div class="text-muted small font-weight-bold mt-n1">Reporter Account</div>

                    <a v-if="viewingReport.reporter && viewingReport.reporter?.id" :href="`/i/web/profile/${viewingReport.reporter?.id}`" target="_blank" class="text-primary">
                        <div class="d-flex align-items-center" style="gap:0.61rem;">
                            <img
                                :src="viewingReport.reporter.avatar"
                                width="30"
                                height="30"
                                style="object-fit: cover;border-radius:30px;"
                                onerror="this.src='/storage/avatars/default.png';this.error=null;">

                            <div class="d-flex flex-column">
                                <p class="font-weight-bold mb-0 text-break" style="font-size: 12px;max-width: 140px;line-height: 16px;">@{{viewingReport.reporter.acct}}</p>
                                <div class="d-flex text-muted mb-0" style="font-size: 10px;gap: 0.5rem;">
                                    <span>{{viewingReport.reporter.followers_count}} Followers</span>
                                    <span>·</span>
                                    <span>Joined {{ timeAgo(viewingReport.reporter.created_at) }}</span>
                                </div>
                            </div>
                        </div>
                    </a>
                </div>
            </div>

            <div v-if="viewingReport && viewingReport.object_type === 'App\\Status' && viewingReport.status" class="list-group mt-3">
                <div v-if="viewingReport && viewingReport.status && viewingReport.status.media_attachments.length" class="list-group-item d-flex flex-column flex-grow-1" style="gap:0.4rem;">
                    <div class="d-flex justify-content-between mt-n1 text-muted small font-weight-bold">
                        <div>Reported Post</div>
                        <a class="font-weight-bold" :href="viewingReport.status.url" target="_blank">View</a>
                    </div>

                    <img
                        v-if="viewingReport.status.media_attachments[0].type === 'image'"
                        :src="viewingReport.status.media_attachments[0].url"
                        height="140"
                        class="rounded"
                        style="object-fit: cover;"
                        onerror="this.src='/storage/no-preview.png';this.error=null;" />

                    <video
                        v-else-if="viewingReport.status.media_attachments[0].type === 'video'"
                        height="140"
                        controls
                        :src="viewingReport.status.media_attachments[0].url"
                        onerror="this.src='/storage/no-preview.png';this.onerror=null;"
                        ></video>
                </div>

                <div v-if="viewingReport && viewingReport.status" class="list-group-item d-flex flex-column flex-grow-1" style="gap:0.4rem;">
                    <div class="d-flex justify-content-between mt-n1 text-muted small font-weight-bold">
                        <div>Reported Post Caption</div>
                        <a class="font-weight-bold" :href="viewingReport.status.url" target="_blank">View</a>
                    </div>
                    <p class="mb-0 read-more" style="font-size:12px;overflow-y: hidden">{{ viewingReport.status.content_text }}</p>
                </div>
            </div>

            <div v-else-if="viewingReport && viewingReport.object_type === 'App\\Story' && viewingReport.story" class="list-group mt-3">
                <div v-if="viewingReport && viewingReport.story" class="list-group-item d-flex flex-column flex-grow-1" style="gap:0.4rem;">
                    <div class="d-flex justify-content-between mt-n1 text-muted small font-weight-bold">
                        <div>Reported Story</div>
                        <a class="font-weight-bold" :href="viewingReport.story.url" target="_blank">View</a>
                    </div>

                    <img
                        v-if="viewingReport.story.type === 'photo'"
                        :src="viewingReport.story.media_src"
                        height="140"
                        class="rounded"
                        style="object-fit: cover;"
                        onerror="this.src='/storage/no-preview.png';this.error=null;" />

                    <video
                        v-else-if="viewingReport.story.type === 'video'"
                        height="140"
                        controls
                        :src="viewingReport.story.media_src"
                        onerror="this.src='/storage/no-preview.png';this.onerror=null;"
                        ></video>
                </div>
            </div>

            <div v-if="viewingReport && viewingReport.admin_seen_at === null" class="mt-4">
                <div v-if="viewingReport && viewingReport.object_type === 'App\\Profile'">
                    <button class="btn btn-dark btn-block rounded-pill" @click="handleAction('profile', 'ignore')">Ignore Report</button>
                    <hr v-if="viewingReport.reported && viewingReport.reported.id && !viewingReport.reported.is_admin" class="mt-3 mb-1">
                    <div
                        v-if="viewingReport.reported && viewingReport.reported.id && !viewingReport.reported.is_admin"
                        class="d-flex flex-row mt-2"
                        style="gap:0.3rem;">
                        <button
                            class="btn btn-outline-danger btn-block btn-sm rounded-pill mt-0"
                            @click="handleAction('profile', 'nsfw')">
                            Mark all Posts NSFW
                        </button>
                        <button
                            class="btn btn-outline-danger btn-block btn-sm rounded-pill mt-0"
                            @click="handleAction('profile', 'unlist')">
                            Unlist all Posts
                        </button>
                    </div>
                    <button
                        v-if="viewingReport.reported && viewingReport.reported.id && !viewingReport.reported.is_admin"
                        class="btn btn-outline-danger btn-block btn-sm rounded-pill mt-2"
                        @click="handleAction('profile', 'delete')">
                        Delete Profile
                    </button>
                </div>

                <div v-else-if="viewingReport && viewingReport.object_type === 'App\\Status'">
                    <button class="btn btn-dark btn-block rounded-pill" @click="handleAction('post', 'ignore')">Ignore Report</button>
                    <hr v-if="viewingReport && viewingReport.reported && !viewingReport.reported.is_admin" class="mt-3 mb-1">
                    <div
                        v-if="viewingReport && viewingReport.reported && !viewingReport.reported.is_admin"
                        class="d-flex flex-row mt-2"
                        style="gap:0.3rem;">
                        <button class="btn btn-outline-danger btn-block btn-sm rounded-pill mt-0" @click="handleAction('post', 'nsfw')">Mark Post NSFW</button>
                        <button v-if="viewingReport.status.visibility === 'public'" class="btn btn-outline-danger btn-block btn-sm rounded-pill mt-0" @click="handleAction('post', 'unlist')">Unlist Post</button>
                        <button v-else-if="viewingReport.status.visibility === 'unlisted'" class="btn btn-outline-danger btn-block btn-sm rounded-pill mt-0" @click="handleAction('post', 'private')">Make Post Private</button>
                    </div>
                    <div
                        v-if="viewingReport && viewingReport.reported && !viewingReport.reported.is_admin"
                        class="d-flex flex-row mt-2"
                        style="gap:0.3rem;">
                        <button class="btn btn-outline-danger btn-block btn-sm rounded-pill mt-0" @click="handleAction('profile', 'nsfw')">Make all NSFW</button>
                        <button class="btn btn-outline-danger btn-block btn-sm rounded-pill mt-0" @click="handleAction('profile', 'unlist')">Make all Unlisted</button>
                        <button class="btn btn-outline-danger btn-block btn-sm rounded-pill mt-0" @click="handleAction('profile', 'private')">Make all Private</button>
                    </div>
                    <div v-if="viewingReport && viewingReport.reported && !viewingReport.reported.is_admin">
                        <hr class="my-2">
                        <div class="d-flex flex-row mt-2" style="gap:0.3rem;">
                            <button class="btn btn-outline-danger btn-block btn-sm rounded-pill mt-0" @click="handleAction('post', 'delete')">Delete Post</button>
                            <button class="btn btn-outline-danger btn-block btn-sm rounded-pill mt-0" @click="handleAction('profile', 'delete')">Delete Account</button>
                        </div>
                    </div>
                </div>

                <div v-else-if="viewingReport && viewingReport.object_type === 'App\\Story'">
                    <button class="btn btn-dark btn-block rounded-pill" @click="handleAction('story', 'ignore')">Ignore Report</button>
                    <hr v-if="viewingReport && viewingReport.reported && !viewingReport.reported.is_admin" class="mt-3 mb-1">
                    <div v-if="viewingReport && viewingReport.reported && !viewingReport.reported.is_admin">
                        <div class="d-flex flex-row mt-2" style="gap:0.3rem;">
                            <button class="btn btn-danger btn-block rounded-pill mt-0" @click="handleAction('story', 'delete')">Delete Story</button>
                            <button class="btn btn-outline-danger btn-block rounded-pill mt-0" @click="handleAction('story', 'delete-all')">Delete All Stories</button>
                        </div>
                    </div>

                    <div v-if="viewingReport && viewingReport.reported && !viewingReport.reported.is_admin">
                        <hr class="my-2">
                        <div class="d-flex flex-row mt-2" style="gap:0.3rem;">
                            <button class="btn btn-outline-danger btn-sm btn-block rounded-pill mt-0" @click="handleAction('profile', 'delete')">Delete Account</button>
                        </div>
                    </div>
                </div>
            </div>
        </template>
    </b-modal>

    <b-modal v-model="showSpamReportModal" title="Potential Spam Post Detected" :ok-only="true" ok-title="Close" ok-variant="outline-primary">
        <div v-if="viewingSpamReportLoading" class="d-flex align-items-center justify-content-center">
            <b-spinner />
        </div>

        <template v-else>
            <div class="list-group list-group-horizontal mt-3">
                <div v-if="viewingSpamReport && viewingSpamReport.status && viewingSpamReport.status.account" class="list-group-item d-flex align-items-center justify-content-between flex-column flex-grow-1" style="gap:0.4rem;">
                    <div class="text-muted small font-weight-bold mt-n1">Reported Account</div>

                    <a v-if="viewingSpamReport.status.account && viewingSpamReport.status.account.id" :href="`/i/web/profile/${viewingSpamReport.status.account.id}`" target="_blank" class="text-primary">
                        <div class="d-flex align-items-center" style="gap:0.61rem;">
                            <img
                                :src="viewingSpamReport.status.account.avatar"
                                width="30"
                                height="30"
                                style="object-fit: cover;border-radius:30px;"
                                onerror="this.src='/storage/avatars/default.png';this.error=null;">

                            <div class="d-flex flex-column">
                                <p class="font-weight-bold mb-0 text-break" style="font-size: 12px;max-width: 140px;line-height: 16px;" :class="[ viewingSpamReport.status.account.is_admin ? 'text-danger': '']">@{{viewingSpamReport.status.account.acct}}</p>
                                <div class="d-flex text-muted mb-0" style="font-size: 10px;gap: 0.5rem;">
                                    <span>{{viewingSpamReport.status.account.followers_count}} Followers</span>
                                    <span>·</span>
                                    <span>Joined {{ timeAgo(viewingSpamReport.status.account.created_at) }}</span>
                                </div>
                            </div>
                        </div>
                    </a>
                </div>
            </div>

            <div v-if="viewingSpamReport && viewingSpamReport.status" class="list-group mt-3">
                <div v-if="viewingSpamReport && viewingSpamReport.status && viewingSpamReport.status.media_attachments.length" class="list-group-item d-flex flex-column flex-grow-1" style="gap:0.4rem;">
                    <div class="d-flex justify-content-between mt-n1 text-muted small font-weight-bold">
                        <div>Reported Post</div>
                        <a class="font-weight-bold" :href="viewingSpamReport.status.url" target="_blank">View</a>
                    </div>

                    <img
                        v-if="viewingSpamReport.status.media_attachments[0].type === 'image'"
                        :src="viewingSpamReport.status.media_attachments[0].url"
                        height="140"
                        class="rounded"
                        style="object-fit: cover;"
                        onerror="this.src='/storage/no-preview.png';this.error=null;" />

                    <video
                        v-else-if="viewingSpamReport.status.media_attachments[0].type === 'video'"
                        height="140"
                        controls
                        :src="viewingSpamReport.status.media_attachments[0].url"
                        onerror="this.src='/storage/no-preview.png';this.onerror=null;"
                        ></video>
                </div>

                <div
                    v-if="viewingSpamReport &&
                        viewingSpamReport.status &&
                        viewingSpamReport.status.content_text &&
                        viewingSpamReport.status.content_text.length"
                    class="list-group-item d-flex flex-column flex-grow-1"
                    style="gap:0.4rem;">
                    <div class="d-flex justify-content-between mt-n1 text-muted small font-weight-bold">
                        <div>Reported Post Caption</div>
                        <a class="font-weight-bold" :href="viewingSpamReport.status.url" target="_blank">View</a>
                    </div>
                    <p class="mb-0 read-more" style="font-size:12px;overflow-y: hidden">{{ viewingSpamReport.status.content_text }}</p>
                </div>
            </div>

            <div class="mt-4">
                <div>
                    <button
                        type="button"
                        class="btn btn-dark btn-block rounded-pill"
                        @click="handleSpamAction('mark-read')">
                        Mark as Read
                    </button>

                    <button
                        type="button"
                        class="btn btn-danger btn-block rounded-pill"
                        @click="handleSpamAction('mark-not-spam')">
                        Mark As Not Spam
                    </button>

                    <hr class="mt-3 mb-1">

                    <div
                        class="d-flex flex-row mt-2"
                        style="gap:0.3rem;">
                        <button
                            type="button"
                            class="btn btn-dark btn-block btn-sm rounded-pill mt-0"
                            @click="handleSpamAction('mark-all-read')">
                            Mark All As Read
                        </button>

                        <button
                            type="button"
                            class="btn btn-dark btn-block btn-sm rounded-pill mt-0"
                            @click="handleSpamAction('mark-all-not-spam')">
                            Mark All As Not Spam
                        </button>
                    </div>

                    <div>
                        <hr class="my-2">
                        <div class="d-flex flex-row mt-2" style="gap:0.3rem;">
                            <button
                                type="button"
                                class="btn btn-outline-danger btn-block btn-sm rounded-pill mt-0"
                                @click="handleSpamAction('delete-profile')">
                                Delete Account
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </template>
    </b-modal>

    <template v-if="showRemoteReportModal">
        <admin-report-modal
            :open="showRemoteReportModal"
            :model="remoteReportModalModel"
            v-on:close="handleCloseRemoteReportModal()"
            v-on:refresh="refreshRemoteReports()" />
    </template>

    <div
        class="modal fade"
        id="moderatedProfileView"
        tabindex="-1"
        role="dialog"
        aria-labelledby="moderatedProfileViewLabel"
        aria-hidden="true"
        data-backdrop="static"
        ref="moderatedProfileModal">
      <div class="modal-dialog modal-dialog-centered" role="document">
        <div v-if="modModalData" class="modal-content">
          <div class="modal-header">
            <div class="w-100 d-flex justify-content-between align-items-center">
                <div class="flex-grow-1">
                    <i class="far fa-shield-alt"></i>
                </div>
                <h5 class="mb-0 lead mt-0 font-weight-bold">Moderated Profile</h5>
                <div class="flex-grow-1">
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close" @click="closeModeratedProfileModal()">
                      <span aria-hidden="true">&times;</span>
                    </button>
                </div>
            </div>
          </div>
          <div class="modal-body">
            <div class="card mb-0">
                <div class="card-body bg-lighter text-dark p-3 font-weight-bold d-flex align-items-center justify-content-center flex-column">
                    <p v-if="modModalData?.profile?.name" class="mb-0 small text-muted">{{ modModalData?.profile?.name }}</p>
                    <p class="mb-0 font-weight-bold">
                        {{ modModalData?.profile?.username }}
                    </p>
                </div>
            </div>
            <p v-if="modModalData?.profile?.remote_url" class="small text-muted text-right mb-1">
                <a :href="modModalData?.profile?.remote_url" rel="noreferrer" target="_blank">
                    View remote profile
                </a>
            </p>

            <div class="list-group mpl-form">
                <div class="list-group-item d-flex justify-content-between align-items-center">
                    <div class="mp-form-label">
                        <div class="d-flex flex-column">
                            <p class="mb-0 font-weight-bold">
                                Banned
                            </p>
                            <p class="mb-0 small text-muted">
                                Ban any activities from this account.
                            </p>
                        </div>
                    </div>
                    <div class="custom-control custom-checkbox">
                        <input type="checkbox" class="custom-control-input" id="mp-form-is_banned" v-model="modModalModel.is_banned">
                        <label class="custom-control-label" for="mp-form-is_banned"></label>
                    </div>
                </div>

                <div class="list-group-item d-none justify-content-between align-items-center">
                    <div class="mp-form-label">
                        <div class="d-flex flex-column">
                            <p class="mb-0 font-weight-bold">
                                No Autolink
                            </p>
                            <p class="mb-0 small text-muted">
                                Disable hashtag, mention and url autolinking from this account.
                            </p>
                        </div>
                    </div>
                    <div class="custom-control custom-checkbox">
                        <input type="checkbox" class="custom-control-input" id="mp-form-is_noautolink" v-model="modModalModel.is_noautolink">
                        <label class="custom-control-label" for="mp-form-is_noautolink"></label>
                    </div>
                </div>

                <div class="list-group-item d-none justify-content-between align-items-center">
                    <div class="mp-form-label">
                        <div class="d-flex flex-column">
                            <p class="mb-0 font-weight-bold">
                                No DMs
                            </p>
                            <p class="mb-0 small text-muted">
                                Ignore DMs from this account.
                            </p>
                        </div>
                    </div>
                    <div class="custom-control custom-checkbox">
                        <input type="checkbox" class="custom-control-input" id="mp-form-is_nodms" v-model="modModalModel.is_nodms">
                        <label class="custom-control-label" for="mp-form-is_nodms"></label>
                    </div>
                </div>

                <div class="list-group-item d-none justify-content-between align-items-center">
                    <div class="mp-form-label">
                        <div class="d-flex flex-column">
                            <p class="mb-0 font-weight-bold">
                                No Trending
                            </p>
                            <p class="mb-0 small text-muted">
                                Prevent posts from this account from appearing in trending lists or feeds.
                            </p>
                        </div>
                    </div>
                    <div class="custom-control custom-checkbox">
                        <input type="checkbox" class="custom-control-input" id="mp-form-is_notrending" v-model="modModalModel.is_notrending">
                        <label class="custom-control-label" for="mp-form-is_notrending"></label>
                    </div>
                </div>

                <div class="list-group-item d-none justify-content-between align-items-center">
                    <div class="mp-form-label">
                        <div class="d-flex flex-column">
                            <p class="mb-0 font-weight-bold">
                                Mark NSFW
                            </p>
                            <p class="mb-0 small text-muted">
                                Mark all posts as sensitive, and apply CWs to future posts.
                            </p>
                        </div>
                    </div>
                    <div class="custom-control custom-checkbox">
                        <input type="checkbox" class="custom-control-input" id="mp-form-is_nsfw" v-model="modModalModel.is_nsfw">
                        <label class="custom-control-label" for="mp-form-is_nsfw"></label>
                    </div>
                </div>

                <div class="list-group-item d-none justify-content-between align-items-center">
                    <div class="mp-form-label">
                        <div class="d-flex flex-column">
                            <p class="mb-0 font-weight-bold">
                                Mark Unlisted
                            </p>
                            <p class="mb-0 small text-muted">
                                Mark all future posts as unlisted, hidden from global/tag feeds.
                            </p>
                        </div>
                    </div>
                    <div class="custom-control custom-checkbox">
                        <input type="checkbox" class="custom-control-input" id="mp-form-is_unlisted" v-model="modModalModel.is_unlisted">
                        <label class="custom-control-label" for="mp-form-is_unlisted"></label>
                    </div>
                </div>
            </div>

            <div class="py-3">
                <label class="small text-muted">Account Notes (only visible to admins)</label>
                <textarea class="form-control" v-model="modModalData.note" placeholder="Add an optional note" maxlength="500"></textarea>
            </div>
          </div>
          <div class="modal-footer d-flex justify-content-between align-items-center">
            <button type="button" class="btn btn-link text-dark" data-dismiss="modal" @click="closeModeratedProfileModal()">Close</button>
            <div class="d-flex flex-grow-1 align-items-center gap-1">
                <button type="button" class="btn btn-danger" @click.prevent="handleModProfileModalDelete()">Delete</button>
                <button type="button" class="btn btn-primary btn-block" @click.prevent="handleModProfileModalUpdate()">Save</button>
            </div>
          </div>
        </div>
      </div>
    </div>
</div>
</template>

<script type="text/javascript">
    import AdminRemoteReportModal from "./partial/AdminRemoteReportModal.vue";

    export default {
        components: {
            "admin-report-modal": AdminRemoteReportModal
        },

        data() {
            return {
                loaded: false,
                stats: {
                    total: 0,
                    open: 0,
                    closed: 0,
                    autospam: 0,
                    autospam_open: 0,
                    remote_open: 0,
                },
                tabIndex: 0,
                reports: [],
                pagination: {},
                showReportModal: false,
                viewingReport: undefined,
                viewingReportLoading: false,
                autospam: [],
                autospamPagination: {},
                autospamLoaded: false,
                showSpamReportModal: false,
                viewingSpamReport: undefined,
                viewingSpamReportLoading: false,
                remoteReportsLoaded: false,
                showRemoteReportModal: undefined,
                remoteReportModalModel: {},
                moderatedProfiles: [],
                moderatedProfilesPagination: {},
                moderatedProfilesSearchInput: undefined,
                modModalData: undefined,
                modModalModel: {},
            }
        },

        mounted() {
            let u = new URLSearchParams(window.location.search);
            if(u.has('tab') && u.get('tab') === 'moderated-profiles' && u.has('action') && u.has('id') && u.get('action') === 'view') {
                this.tabIndex = 4;
                this.fetchModeratedAccounts();
                this.fetchModeratedProfile(u.get('id'));
            } else if(u.has('tab') && u.get('tab') === 'autospam' && !u.has('id')) {
                this.tabIndex = 2;
                this.fetchStats(null, '/i/admin/api/reports/spam/all');
            } else if(u.has('tab') && u.get('tab') === 'closed') {
                this.tabIndex = 1;
                this.fetchStats('/i/admin/api/reports/all?filter=closed')
            } else if(u.has('tab') && u.get('tab') === 'closed') {
                this.tabIndex = 3;
                this.fetchStats('/i/admin/api/reports/all?filter=remote')
            } else if(u.has('tab') && u.get('tab') === 'moderated-profiles') {
                this.tabIndex = 4;
                this.fetchModeratedAccounts();
            } else if(u.has('tab') && u.has('id') && u.get('tab') === 'autospam') {
                this.fetchStats(null, '/i/admin/api/reports/spam/all');
                this.fetchSpamReport(u.get('id'));
            } else if(u.has('tab') && u.has('id') && u.get('tab') === 'report') {
                this.fetchStats();
                this.fetchReport(u.get('id'));
            } else {
                window.history.pushState(null, null, '/i/admin/reports');
                this.fetchStats();
            }

            this.$root.$on('bv::modal::hide', (bvEvent, modalId) => {
                window.history.pushState(null, null, '/i/admin/reports');
            })
        },

        methods: {
            toggleTab(idx) {
                switch(idx) {
                    case 0:
                        this.fetchStats('/i/admin/api/reports/all');
                        window.history.pushState(null, null, '/i/admin/reports');
                    break;

                    case 1:
                        this.fetchStats('/i/admin/api/reports/all?filter=closed')
                        window.history.pushState(null, null, '/i/admin/reports?tab=closed');
                    break;

                    case 2:
                        this.fetchStats(null, '/i/admin/api/reports/spam/all');
                        window.history.pushState(null, null, '/i/admin/reports?tab=autospam');
                    break;

                    case 3:
                        this.fetchRemoteReports();
                        window.history.pushState(null, null, '/i/admin/reports?tab=remote');
                    break;

                    case 4:
                        this.fetchModeratedAccounts();
                        window.history.pushState(null, null, '/i/admin/reports?tab=moderated-profiles');
                    break;
                }
                this.tabIndex = idx;
            },

            prettyCount(str) {
                if(str) {
                   return str.toLocaleString('en-CA', { compactDisplay: "short", notation: "compact"});
                }
                return str;
            },

            timeAgo(str) {
                if(!str) {
                    return str;
                }
                return App.util.format.timeAgo(str);
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

            reportLabel(report) {
                switch(report.object_type) {
                    case 'App\\Profile':
                        return `${report.type} Profile`;
                    break;
                    case 'App\\Status':
                        return `${report.type} Post`;
                    break;
                    case 'App\\Story':
                        return `${report.type} Story`;
                    break;
                }
            },

            fetchStats(fetchReportsUrl = '/i/admin/api/reports/all', fetchSpamUrl = null) {
                axios.get('/i/admin/api/reports/stats')
                .then(res => {
                    this.stats = res.data;
                })
                .finally(() => {
                    if(fetchReportsUrl) {
                        this.fetchReports(fetchReportsUrl);
                    } else if(fetchSpamUrl) {
                        this.fetchAutospam(fetchSpamUrl);
                    }
                    $('[data-toggle="tooltip"]').tooltip()
                });
            },

            fetchModeratedAccounts(apiUrl = '/i/admin/api/reports/moderated-profiles') {
                axios.get(apiUrl)
                .then(res => {
                    this.moderatedProfiles = res.data.data;
                    this.moderatedProfilesPagination = {
                        prev: res.data.links.prev,
                        next: res.data.links.next
                    };
                })
                .finally(() => {
                    this.loaded = true;
                    $('[data-toggle="tooltip"]').tooltip()
                })
            },

            paginateModeratedAccounts(dir) {
                event.currentTarget.blur();
                let url = dir == 'next' ? this.moderatedProfilesPagination.next : this.moderatedProfilesPagination.prev;
                this.fetchModeratedAccounts(url);
            },

            fetchReports(url = '/i/admin/api/reports/all') {
                axios.get(url)
                .then(res => {
                    this.reports = res.data.data;
                    this.pagination = {
                        next: res.data.links.next,
                        prev: res.data.links.prev
                    };
                })
                .finally(() => {
                    this.loaded = true;
                });
            },

            fetchRemoteReports(url = '/i/admin/api/reports/remote') {
                axios.get(url)
                .then(res => {
                    this.reports = res.data.data;
                    this.pagination = {
                        next: res.data.links.next,
                        prev: res.data.links.prev
                    };
                })
                .finally(() => {
                    this.loaded = true;
                    this.remoteReportsLoaded = true;
                });
            },

            remoteReportPaginate(dir) {
                event.currentTarget.blur();
                let url = dir == 'next' ? this.pagination.next : this.pagination.prev;
                this.fetchRemoteReports(url);
            },

            handleCloseRemoteReportModal() {
                this.showRemoteReportModal = false;
            },

            showRemoteReport(report) {
                this.remoteReportModalModel = report;
                this.showRemoteReportModal = true;
            },

            refreshRemoteReports() {
                this.fetchStats('');
                this.$nextTick(() => {
                    this.toggleTab(3);
                })
            },

            paginate(dir) {
                event.currentTarget.blur();
                let url = dir == 'next' ? this.pagination.next : this.pagination.prev;
                this.fetchReports(url);
            },

            viewReport(report) {
                this.viewingReportLoading = false;
                this.viewingReport = report;
                this.showReportModal = true;
                window.history.pushState(null, null, '/i/admin/reports?tab=report&id=' + report.id);
                setTimeout(() => {
                    pixelfed.readmore()
                }, 1000)
            },

            handleAction(type, action) {
                event.currentTarget.blur();

                this.viewingReportLoading = true;

                if(action !== 'ignore' && !window.confirm(this.getActionLabel(type, action))) {
                    this.viewingReportLoading = false;
                    return;
                }

                this.loaded = false;

                axios.post('/i/admin/api/reports/handle', {
                    id: this.viewingReport.id,
                    object_id: this.viewingReport.object_id,
                    object_type: this.viewingReport.object_type,
                    action: action,
                    action_type: type
                })
                .catch(err => {
                    swal('Error', err.response.data.error, 'error');
                })
                .finally(() => {
                    this.viewingReportLoading = true;
                    this.viewingReport = false;
                    this.showReportModal = false;
                    setTimeout(() => {
                        this.fetchStats();
                    }, 1000);
                })
            },

            getActionLabel(type, action) {
                if(type === 'profile') {
                    switch(action) {
                        case 'ignore':
                            return 'Are you sure you want to ignore this profile report?';
                        break;

                        case 'nsfw':
                            return 'Are you sure you want to mark this profile as NSFW?';
                        break;

                        case 'unlist':
                            return 'Are you sure you want to mark all posts by this profile as unlisted?';
                        break;

                        case 'private':
                            return 'Are you sure you want to mark all posts by this profile as private?';
                        break;

                        case 'delete':
                            return 'Are you sure you want to delete this profile?';
                        break;
                    }
                } else if(type === 'post') {
                    switch(action) {
                        case 'ignore':
                            return 'Are you sure you want to ignore this post report?';
                        break;

                        case 'nsfw':
                            return 'Are you sure you want to mark this post as NSFW?';
                        break;

                        case 'unlist':
                            return 'Are you sure you want to mark this post as unlisted?';
                        break;

                        case 'private':
                            return 'Are you sure you want to mark this post as private?';
                        break;

                        case 'delete':
                            return 'Are you sure you want to delete this post?';
                        break;
                    }
                } else if(type === 'story') {
                    switch(action) {
                        case 'ignore':
                            return 'Are you sure you want to ignore this story report?';
                        break;

                        case 'delete':
                            return 'Are you sure you want to delete this story?';
                        break;

                        case 'delete-all':
                            return 'Are you sure you want to delete all stories by this account?';
                        break;
                    }
                }
            },

            fetchAutospam(url = '/i/admin/api/reports/spam/all') {
                axios.get(url)
                .then(res => {
                    this.autospam = res.data.data;
                    this.autospamPagination = {
                        next: res.data.links.next,
                        prev: res.data.links.prev
                    }
                })
                .finally(() => {
                    this.autospamLoaded = true;
                    this.loaded = true;
                })
            },

            autospamPaginate(dir) {
                event.currentTarget.blur();
                let url = dir == 'next' ? this.autospamPagination.next : this.autospamPagination.prev;
                this.fetchAutospam(url);
            },

            viewSpamReport(report) {
                this.viewingSpamReportLoading = false;
                this.viewingSpamReport = report;
                this.showSpamReportModal = true;
                window.history.pushState(null, null, '/i/admin/reports?tab=autospam&id=' + report.id);
                setTimeout(() => {
                    pixelfed.readmore()
                }, 1000)
            },

            getSpamActionLabel(action) {
                switch(action) {
                    case 'mark-all-read':
                        return 'Are you sure you want to mark all spam reports by this account as read?';
                    break;

                    case 'mark-all-not-spam':
                        return 'Are you sure you want to mark all spam reports by this account as not spam?';
                    break;

                    case 'delete-profile':
                        return 'Are you sure you want to delete this profile?';
                    break;
                }
            },

            handleSpamAction(action) {
                event.currentTarget.blur();

                this.viewingSpamReportLoading = true;

                if(action !== 'mark-not-spam' && action !== 'mark-read' && !window.confirm(this.getSpamActionLabel(action))) {
                    this.viewingSpamReportLoading = false;
                    return;
                }

                this.loaded = false;
                axios.post('/i/admin/api/reports/spam/handle', {
                    id: this.viewingSpamReport.id,
                    action: action,
                })
                .catch(err => {
                    swal('Error', err.response.data.error, 'error');
                })
                .finally(() => {
                    this.viewingSpamReportLoading = true;
                    this.viewingSpamReport = false;
                    this.showSpamReportModal = false;
                    setTimeout(() => {
                        this.fetchStats(null, '/i/admin/api/reports/spam/all');
                    }, 500);
                })
            },

            fetchReport(id) {
                axios.get('/i/admin/api/reports/get/' + id)
                .then(res => {
                    this.tabIndex = 0;
                    this.viewReport(res.data.data);
                })
                .catch(err => {
                    this.fetchStats();
                    window.history.pushState(null, null, '/i/admin/reports');
                })
            },

            fetchSpamReport(id) {
                axios.get('/i/admin/api/reports/spam/get/' + id)
                .then(res => {
                    this.tabIndex = 2;
                    this.viewSpamReport(res.data.data);
                })
                .catch(err => {
                    this.fetchStats();
                    window.history.pushState(null, null, '/i/admin/reports');
                })
            },

            truncateText(text, maxLength, appendEllipsis = true) {
                if(!text || !text.length) {
                    return
                }

                if (text.length <= maxLength) {
                    return text;
                }

                const truncated = text.slice(0, maxLength).trim();
                return appendEllipsis ? truncated + '...' : truncated;
            },

            getModerationLabels(acct) {
                if(acct.is_banned) {
                    return `<span class="badge badge-danger">Banned</span>`
                }

                let labels = [];

                if(acct.is_banned) labels.push('Banned')
                if(acct.is_noautolink) labels.push('No Autolink')
                if(acct.is_nodms) labels.push('No DMS')
                if(acct.is_notrending) labels.push('No Trending')
                if(acct.is_nsfw) labels.push('NSFW')
                if(acct.is_unlisted) labels.push('Unlisted')

                return labels.map((item, index) => {
                    const colorClass = item === 'Banned' ? 'danger' : 'primary';
                    return `<span class="badge badge-${colorClass}">${item}</span>`;
                }).join(' ');
            },

            handleModeratedProfileSearch(event) {
                event.currentTarget.blur()
                let url = `/i/admin/api/reports/moderated-profiles?search=${this.moderatedProfilesSearchInput}`
                this.fetchModeratedAccounts(url)
            },

            clearModeratedProfileSearch() {
                this.moderatedProfilesSearchInput = undefined;
                this.fetchModeratedAccounts();
            },

            openModeratedProfileModal(report) {
                this.modModalData = report;
                this.modModalModel = {
                    is_banned: report.is_banned,
                    is_noautolink: report.is_noautolink,
                    is_nodms: report.is_nodms,
                    is_notrending: report.is_notrending,
                    is_nsfw: report.is_nsfw,
                    is_unlisted: report.is_unlisted,
                }
                $(this.$refs.moderatedProfileModal).modal('show');
                window.history.pushState(null, null, `/i/admin/reports?tab=moderated-profiles&action=view&id=${report.id}`)
            },

            handleModProfileModalUpdate() {
                axios.post(
                    '/i/admin/api/reports/moderated-profiles/update',
                    {...this.modModalData, ...this.modModalModel}
                ).then(res => {
                    window.history.pushState(null, null, `/i/admin/reports?tab=moderated-profiles`)
                    window.location.reload();
                }).catch(error => {
                    let errorMessage = 'An error occurred';
                    if (error.response) {
                        errorMessage = `Error ${error.response.status}: ${error.response.data.error || error.response.data.message || error.response.statusText}`;
                    } else if (error.request) {
                        errorMessage = 'No response received from server';
                    } else {
                        errorMessage = error.message;
                    }
                    swal('Error', errorMessage, 'error')
                }).finally(() => {
                    $(this.$refs.moderatedProfileModal).modal('hide');
                })
            },

            handleModProfileModalDelete() {
                swal({
                    title: 'Confirm Delete',
                    text: 'Are you sure you want to delete this moderated profile ruleset?',
                    buttons: {
                        cancel: "Cancel",
                        danger: {
                            text: "Delete",
                            value: 'delete',
                        }
                    }
                }).then((val) => {
                    if(val === 'delete') {
                        axios.post('/i/admin/api/reports/moderated-profiles/delete', { id: this.modModalData.id})
                        .then(res => {
                            window.history.pushState(null, null, '/i/admin/reports?tab=moderated-profiles');
                            window.location.reload();
                        })
                    }
                    $(this.$refs.moderatedProfileModal).modal('hide');
                    swal.close()
                })
            },

            fetchModeratedProfile(id) {
                axios.get(`/i/admin/api/reports/moderated-profiles/show?id=${id}`)
                .then(res => {
                    this.modModalData = res.data.data;
                    let report = res.data.data;

                    this.modModalModel = {
                        is_banned: report.is_banned,
                        is_noautolink: report.is_noautolink,
                        is_nodms: report.is_nodms,
                        is_notrending: report.is_notrending,
                        is_nsfw: report.is_nsfw,
                        is_unlisted: report.is_unlisted,
                    }

                    $(this.$refs.moderatedProfileModal).modal('show');
                }).catch(err => {
                    window.history.pushState(null, null, '/i/admin/reports?tab=moderated-profiles');
                    swal('Error', 'Invalid moderated profile id!', 'error');
                })
            },

            addModeratedProfile() {
                swal({
                    text: 'Enter profile URL (ie: https://mastodon.social/@Mastodon)',
                    content: "input",
                    button: {
                        text: "Add",
                        closeModal: false,
                    },
                }).then(val => {
                    if (!val) throw null;

                    if(val.startsWith('@')) {
                        swal('Error', 'Invalid URL, webfinger is not supported yet.', 'error');
                        throw null;
                    }

                    if(!val.startsWith('http')) {
                        swal('Error', 'Invalid URL', 'error');
                        throw null;
                    }

                    if(val.indexOf('.') === -1) {
                        swal('Error', 'Invalid URL', 'error');
                        throw null;
                    }

                    let params = {
                        url: val
                    }

                    return axios.post('/i/admin/api/reports/moderated-profiles/create', params);
                }).then(json => {
                    if(json && json.data && json.data?.id) {
                        window.location.href = `/i/admin/reports?tab=moderated-profiles&action=view&id=${json.data?.id}`
                        return;
                    }
                    swal.stopLoading();
                    swal.close();
                }).catch(err => {
                    if (err) {
                        if(err?.response?.data?.error) {
                            swal("Error", err?.response?.data?.error, "error");
                        } else {
                            swal("Error", "Something went wrong!", "error");
                        }
                    } else {
                        swal.stopLoading();
                        swal.close();
                    }
                });
            },

            closeModeratedProfileModal() {
                window.history.pushState(null, null, '/i/admin/reports?tab=moderated-profiles');
            },

            exportModeratedProfiles() {
                axios.get('/i/admin/api/reports/moderated-profiles/export', {
                    responseType: "blob"
                })
                .then(res => {
                    let host = new URL(window.location.href)
                    let date = new Date();
                    let dateStamp = `${date.getMonth()}-${date.getDate()}-${date.getFullYear()}-${Date.now()}`;
                    let filename = host.host + '-moderated-profiles-' + dateStamp + '.json';
                    let el = document.createElement('a');
                    el.setAttribute('download', filename)
                    const href = URL.createObjectURL(res.data);
                    el.href = href;
                    el.setAttribute('target', '_blank');
                    el.click();

                    swal(
                        'Success!',
                        'You have successfully exported the moderated profile backup.',
                        'success'
                    )
                })
            }
        }
    }
</script>

<style lang="scss" scoped>
    .mpl-form {
        p {
            line-height: 1;

            &:first-child {
                font-size: 14px;
                line-height: 1.6;
            }
        }
    }
</style>
