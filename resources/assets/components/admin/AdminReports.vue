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
                        		href="/i/admin/reports/appeals"
                        		class="nav-link d-flex align-items-center">
                        		<span>Appeal Requests</span>
                        		<span
                            		v-if="stats.appeals"
                            		class="badge badge-sm badge-floating badge-secondary border-white ml-2"
                            		style="font-size:11px;">
                        			{{ prettyCount(stats.appeals) }}
                            	</span>
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
                            	<a :href="`/i/web/profile/${report.reporter.id}`" target="_blank" class="text-white">
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

					<a v-if="viewingReport.reporter && viewingReport.reporter.id" :href="`/i/web/profile/${viewingReport.reporter.id}`" target="_blank" class="text-primary">
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

	    		<div v-if="viewingReport && viewingReport.object_type === 'App\\Status'">
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
</div>
</template>

<script type="text/javascript">
	export default {
		data() {
			return {
				loaded: false,
				stats: {
					total: 0,
					open: 0,
					closed: 0,
					autospam: 0,
					autospam_open: 0,
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
				viewingSpamReportLoading: false
			}
		},

		mounted() {
			let u = new URLSearchParams(window.location.search);
			if(u.has('tab') && u.has('id') && u.get('tab') === 'autospam') {
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
					break;

					case 1:
						this.fetchStats('/i/admin/api/reports/all?filter=closed')
					break;

					case 2:
						this.fetchStats(null, '/i/admin/api/reports/spam/all');
					break;
				}
				window.history.pushState(null, null, '/i/admin/reports');
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
            }
		}
	}
</script>
