<template>
<div>
    <div class="header bg-primary pb-3 mt-n4">
        <div class="container-fluid">
            <div class="header-body">
                <div class="row align-items-center py-4">
                    <div class="col-xl-4 col-lg-6 col-md-4">
                        <p class="display-1 text-white d-inline-block mb-0">Autospam</p>
                        <p class="text-lighter">The automated spam detection system</p>
                    </div>
					<div class="col-xl-4 col-lg-3 col-md-4">
						<div class="card card-stats mb-lg-0">
							<div class="card-body">
								<div class="row">
									<div class="col">
										<h5 class="card-title text-uppercase text-muted mb-0">Active Autospam</h5>
										<span class="h2 font-weight-bold mb-0">{{ formatCount(config.open) }}</span>
									</div>
									<div class="col-auto">
										<div class="icon icon-shape bg-gradient-primary text-white rounded-circle shadow">
											<i class="far fa-sensor-alert"></i>
										</div>
									</div>
								</div>
							</div>
						</div>
					</div>
					<div class="col-xl-4 col-lg-3 col-md-4">
						<div class="card card-stats bg-dark mb-lg-0">
							<div class="card-body">
								<div class="row">
									<div class="col">
										<h5 class="card-title text-uppercase text-muted mb-0">Closed Autospam</h5>
										<span class="h2 font-weight-bold text-muted mb-0">{{ formatCount(config.closed) }}</span>
									</div>
									<div class="col-auto">
										<div class="icon icon-shape bg-gradient-primary text-white rounded-circle shadow">
											<i class="far fa-shield-alt"></i>
										</div>
									</div>
								</div>
							</div>
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
                            <button :class="['nav-link', { active: tabIndex == 0}]" @click.prevent="toggleTab(0)">Dashboard</button>
                        </li>
                        <li class="nav-item">
                            <button :class="['nav-link', { active: tabIndex == 'about'}]" @click.prevent="toggleTab('about')">About / How to Use Autospam</button>
                        </li>
                        <li class="nav-item">
                            <button :class="['nav-link', { active: tabIndex == 'train'}]" @click.prevent="toggleTab('train')">Train Autospam</button>
                        </li>
                        <li class="nav-item">
                            <button :class="['nav-link', { active: tabIndex == 'closed_reports'}]" @click.prevent="toggleTab('closed_reports')">Closed Reports</button>
                        </li>
                        <li class="nav-item">
                            <button :class="['nav-link', { active: tabIndex == 'manage_tokens'}]" @click.prevent="toggleTab('manage_tokens')">Manage Tokens</button>
                        </li>
                        <li class="nav-item">
                            <button :class="['nav-link', { active: tabIndex == 'import_export'}]" @click.prevent="toggleTab('import_export')">Import/Export</button>
                        </li>
                    </ul>
                </div>
            </div>

            <div v-if="this.tabIndex === 0" class="row">
            	<div class="col-12 col-md-4">
            		<div v-if="config.autospam_enabled === null">
            		</div>
            		<div v-else-if="config.autospam_enabled" class="card bg-dark" style="min-height: 209px;">
            			<div class="card-body text-center">
            				<p><i class="far fa-check-circle fa-5x text-success"></i></p>
            				<p class="lead text-light mb-0">Autospam Service Operational</p>
            			</div>
            		</div>
            		<div v-else class="card bg-dark" style="min-height: 209px;">
            			<div class="card-body text-center">
            				<p><i class="far fa-exclamation-circle fa-5x text-danger"></i></p>
            				<p class="lead text-danger font-weight-bold mb-0">Autospam Service Inactive</p>
            				<p class="small text-light mb-0">To activate, <a href="/i/admin/settings">click here</a> and enable <span class="font-weight-bold">Spam detection</span></p>
            			</div>
            		</div>

            		<div v-if="config.nlp_enabled === null">
            		</div>
            		<div v-else-if="config.nlp_enabled" class="card bg-dark" style="min-height: 209px;">
            			<div class="card-body text-center">
            				<p><i class="far fa-check-circle fa-5x text-success"></i></p>
            				<p class="lead text-light">Advanced (NLP) Detection Active</p>
            				<a class="btn btn-outline-danger btn-block font-weight-bold" :class="{ disabled: config.autospam_enabled != true}" href="#" :disabled="config.autospam_enabled != true" @click.prevent="disableAdvanced">Disable Advanced Detection</a>
            			</div>
            		</div>
            		<div v-else class="card bg-dark" style="min-height: 209px;">
            			<div class="card-body text-center">
            				<p><i class="far fa-exclamation-circle fa-5x text-danger"></i></p>
            				<p class="lead text-danger font-weight-bold">Advanced (NLP) Detection Inactive</p>
            				<a class="btn btn-primary btn-block font-weight-bold" :class="{ disabled: config.autospam_enabled != true}" href="#" :disabled="config.autospam_enabled != true" @click.prevent="enableAdvanced">Enable Advanced Detection</a>
            			</div>
            		</div>
            	</div>
            	<div class="col-12 col-md-8">
            		<div class="card bg-default">
            			<div class="card-header bg-transparent">
            				<div class="row align-items-center">
            					<div class="col">
            						<h6 class="text-light text-uppercase ls-1 mb-1">Stats</h6>
            						<h5 class="h3 text-white mb-0">Autospam Detections</h5>
            					</div>
            				</div>
            			</div>
            			<div class="card-body">
            				<div class="chart">
            					<canvas id="c1-dark" class="chart-canvas"></canvas>
            				</div>
            			</div>
            		</div>
            	</div>
            </div>

            <div v-else-if="this.tabIndex === 'about'">
            	<div class="row">
            		<div class="col-12">
            			<div class="card card-body">
	            			<h1>About Autospam</h1>

	            			<p class="mb-0">To detect and mitigate spam, we built Autospam, an internal tool that uses NLP and other behavioural metrics to classify potential spam posts.</p>

	            			<hr />

	            			<h2>Standard Detection</h2>

	            			<p>Standard or "Classic" detection works by evaluating several "signals" from the post and it's associated account.</p>

	            			<p>Some of the following "signals" may trigger a positive detection from public posts:</p>

	            			<ul>
	            				<li>Account is less than 6 months old</li>
	            				<li>Account has less than 100 followers</li>
	            				<li>Post contains one or more of: <span class="badge badge-primary">https://</span> <span class="badge badge-primary">http://</span> <span class="badge badge-primary">hxxps://</span> <span class="badge badge-primary">hxxp://</span> <span class="badge badge-primary">www.</span> <span class="badge badge-primary">.com</span> <span class="badge badge-primary">.net</span> <span class="badge badge-primary">.org</span> </li>
	            			</ul>

	            			<p>If you've marked atleast one positive detection from an account as <span class="font-weight-bold">Not spam</span>, any future posts they create will skip detection.</p>

	            			<hr />

	            			<h2>Advanced Detection</h2>

	            			<p>Advanced Detection works by using a statistical method that combines prior knowledge and observed data to estimate an average value. It assigns weights to both the prior knowledge and the observed data, allowing for a more informed and reliable estimation that adapts to new information.</p>

	            			<p>When you train Spam or Not Spam data, the caption is broken up into words (tokens) and are counted (weights) and then stored in the appropriate category (Spam or Not Spam).</p>

	            			<p>The training data is then used to classify spam on future posts (captions) by calculating each token and associated weights and comparing it to known categories (Spam or Not Spam).</p>

            			</div>
            		</div>
            	</div>
            </div>

            <div v-else-if="this.tabIndex === 'train'">
            	<div class="row">
					<div class="col-12">
	            		<div class="card card-body">
	            			<p class="mb-0">
	            				In order for Autospam to be effective, you need to train it by classifying data as spam or not-spam.
	            			</p>

	            			<p class="mb-0 small">
	            				We recommend atleast 200 classifications for both spam and not-spam, it is important to train Autospam on both so you get more accurate results.
	            			</p>
	            		</div>
	            	</div>
	            </div>
            	<div class="row">
            		<div class="col-12 col-md-6">
            			<div class="card bg-dark">
            				<div class="card-header bg-gradient-primary text-white font-weight-bold">Train Spam Posts</div>
            				<div class="card-body">
            					<div class="d-flex flex-column align-items-center justify-content-center py-4" style="gap:1rem;">
	            					<p class="mb-0">
	            						<i class="far fa-sensor-alert fa-5x text-danger"></i>
	            					</p>

	            					<p class="lead text-lighter">Use existing posts marked as spam to train Autospam</p>

	            					<button
	            						class="btn btn-primary btn-lg font-weight-bold btn-block"
	            						:class="{ disabled: config.files.spam.exists}"
	            						:disabled="config.files.spam.exists"
	            						@click.prevent="autospamTrainSpam">
	            						{{ config.files.spam.exists ? 'Already trained' : 'Train Spam' }}
	            					</button>
            					</div>
            				</div>
            			</div>
            		</div>

					<div class="col-12 col-md-6">
            			<div class="card bg-dark">
            				<div class="card-header bg-gradient-primary text-white font-weight-bold">Train Non-Spam Posts</div>
            				<div class="card-body">
            					<div class="d-flex flex-column align-items-center justify-content-center py-4" style="gap:1rem;">
	            					<p class="mb-0">
	            						<i class="far fa-check-circle fa-5x text-success"></i>
	            					</p>

	            					<p class="lead text-lighter">Use posts from trusted users to train non-spam posts</p>

	            					<button
	            						class="btn btn-primary btn-lg font-weight-bold btn-block"
	            						:class="{ disabled: config.files.ham.exists}"
	            						:disabled="config.files.ham.exists"
	            						@click.prevent="autospamTrainNonSpam">
	            						{{ config.files.ham.exists ? 'Already trained' : 'Train Non-Spam' }}
	            					</button>
            					</div>
            				</div>
            			</div>
            		</div>
            	</div>
            </div>

            <div v-else-if="this.tabIndex === 'closed_reports'">
            	<template v-if="closedReportsFetched">
	            	<div class="table-responsive rounded">
						<table class="table table-dark">
							<thead class="thead-dark">
							    <tr>
							        <th scope="col">ID</th>
							        <th scope="col">Type</th>
			                        <th scope="col">Reported Account</th>
							        <th scope="col">Created</th>
			                        <th scope="col">View Report</th>
							    </tr>
							</thead>
							<tbody>
		                        <tr v-for="(report, idx) in closedReports.data" :key="'closed_reports' + report.id + idx">
		                            <td class="font-weight-bold text-monospace text-muted align-middle">
		                            	{{ report.id}}
		                            </td>
		                            <td class="align-middle">
		                            	<p class="text-capitalize font-weight-bold mb-0">Autospam Post</p>
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
	                </div>

		            <div v-if="closedReportsFetched && closedReports && closedReports.data.length" class="d-flex align-items-center justify-content-center">
		              	<button
		                    class="btn btn-primary rounded-pill"
		                    :disabled="!closedReports.links.prev"
		                    @click="autospamPaginate('prev')">
		                    Prev
		                </button>
		                <button
		                    class="btn btn-primary rounded-pill"
		                    :disabled="!closedReports.links.next"
		                    @click="autospamPaginate('next')">
		                    Next
		                </button>
		            </div>
				</template>

                <template v-else>
                	<div class="d-flex justify-content-center align-items-center py-5">
                		<b-spinner />
                	</div>
                </template>
            </div>

            <div v-else-if="this.tabIndex === 'manage_tokens'">
            	<div class="row align-items-center mb-3">
	            	<div class="col-12 col-md-9">
	            		<div class="card card-body mb-0">
	            			<p class="mb-0">
	            				Tokens are used to split paragraphs and sentences into smaller units that can be more easily assigned meaning.
	            			</p>
	            		</div>
	            	</div>
	            	<div class="col-12 col-md-3">
            			<a class="btn btn-primary btn-lg btn-block" href="#" @click.prevent="showCreateTokenModal = true">
            				<i class="far fa-plus fa-lg mr-1"></i>
            				Create New Token
            			</a>
	            	</div>
            	</div>

            	<template v-if="customTokensFetched">
            		<template v-if="customTokens && customTokens.data && customTokens.data.length">
		            	<div class="table-responsive rounded">
							<table class="table table-dark">
								<thead class="thead-dark">
								    <tr>
								        <th scope="col">ID</th>
								        <th scope="col">Token</th>
								        <th scope="col">Category</th>
								        <th scope="col">Weight</th>
								        <th scope="col">Created</th>
								        <th scope="col">Edit</th>
								    </tr>
								</thead>
								<tbody>
			                        <tr v-for="(token, idx) in customTokens.data" :key="'ct' + token.id + idx">
			                            <td class="font-weight-bold text-monospace text-muted align-middle">
			                            	{{ token.id}}
			                            </td>
			                            <td class="align-middle">
			                            	<p class="font-weight-bold mb-0">{{ token.token }}</p>
			                            </td>
			                            <td class="align-middle">
			                            	<p class="text-capitalize mb-0">{{ token.category }}</p>
			                            </td>
			                            <td class="align-middle">
			                            	<p class="text-capitalize mb-0">{{ token.weight }}</p>
			                            </td>
			                            <td class="font-weight-bold align-middle">{{ timeAgo(token.created_at) }}</td>
			                            <td class="font-weight-bold align-middle">
			                            	<a class="btn btn-primary btn-sm font-weight-bold" href="#" @click.prevent="openEditTokenModal(token)">Edit</a>
			                            </td>
			                        </tr>
			                    </tbody>
			                </table>
		                </div>

			            <div v-if="customTokensFetched && customTokens && customTokens.data.length" class="d-flex align-items-center justify-content-center">
			              	<button
			                    class="btn btn-primary rounded-pill"
			                    :disabled="!customTokens.prev_page_url"
			                    @click="autospamTokenPaginate('prev')">
			                    Prev
			                </button>
			                <button
			                    class="btn btn-primary rounded-pill"
			                    :disabled="!customTokens.next_page_url"
			                    @click="autospamTokenPaginate('next')">
			                    Next
			                </button>
			            </div>
			        </template>

			        <div v-else>
			        	<div class="card">
			        		<div class="card-body text-center py-5">
			        			<p class="pt-5">
			        				<i class="far fa-inbox fa-4x text-light"></i>
			        			</p>
			        			<p class="lead mb-5">No custom tokens found!</p>
			        		</div>
			        	</div>
			        </div>
				</template>

                <template v-else>
                	<div class="d-flex justify-content-center align-items-center py-5">
                		<b-spinner />
                	</div>
                </template>
            </div>

            <div v-else-if="this.tabIndex === 'import_export'">
            	<div class="row">
					<div class="col-12">
	            		<div class="card card-body">
	            			<p class="mb-0">
	            				You can import and export Spam training data
	            			</p>

	            			<p class="mb-0 small">
	            				We recommend exercising caution when importing training data from untrusted parties!
	            			</p>
	            		</div>
	            	</div>
	            </div>
            	<div class="row">
            		<div class="col-12 col-md-6">
            			<div class="card bg-dark">
            				<div class="card-header font-weight-bold">Import Training Data</div>
            				<div class="card-body">
            					<div class="d-flex flex-column align-items-center justify-content-center py-4" style="gap:1rem;">
	            					<p class="mb-0">
	            						<i class="far fa-plus-circle fa-5x text-light"></i>
	            					</p>

	            					<p class="lead text-lighter">Make sure the file you are importing is a valid training data export!</p>

	            					<button class="btn btn-primary btn-lg font-weight-bold btn-block" @click.prevent="handleImport">Upload Import</button>
            					</div>
            				</div>
            			</div>
            		</div>
					<div class="col-12 col-md-6">
            			<div class="card bg-dark">
            				<div class="card-header font-weight-bold">Export Training Data</div>
            				<div class="card-body">
            					<div class="d-flex flex-column align-items-center justify-content-center py-4" style="gap:1rem;">
	            					<p class="mb-0">
	            						<i class="far fa-download fa-5x text-light"></i>
	            					</p>

	            					<p class="lead text-lighter">Only share training data with people you trust. It can be used by spammers to bypass detection!</p>

	            					<button class="btn btn-primary btn-lg font-weight-bold btn-block" @click.prevent="downloadExport">Download Export</button>
            					</div>
            				</div>
            			</div>
            		</div>
            	</div>
            </div>
        </div>
	</div>

    <b-modal v-model="showSpamReportModal" title="Autospam Post" :ok-only="true" ok-title="Close" ok-variant="outline-primary">
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
	    </template>
    </b-modal>

    <b-modal v-model="showNonSpamModal" title="Train Non-Spam" :ok-only="true" ok-title="Close" ok-variant="outline-primary">
    	<p class="small font-weight-bold">Select trusted accounts to train non-spam posts against!</p>
        <autocomplete
        	v-if="!nonSpamAccounts || nonSpamAccounts.length < 10"
            :search="composeSearch"
            :disabled="searchLoading"
            placeholder="Search by username"
            aria-label="Search by username"
            :get-result-value="getTagResultValue"
            @submit="onSearchResultClick"
            ref="autocomplete"
            >
                <template #result="{ result, props }">
                    <li
                    v-bind="props"
                    class="autocomplete-result d-flex align-items-center"
                    style="gap: 0.5rem"
                    >
                    <img :src="result.avatar" width="32" height="32" class="rounded-circle" onerror="this.src='/storage/avatars/default.png';this.error=null;">
                    <div class="font-weight-bold">
                        {{ result.username }}
                    </div>
                </li>
            </template>
        </autocomplete>
		<div class="list-group mt-3">
			<div
				v-for="(acct, idx) in nonSpamAccounts"
				class="list-group-item">
				<div class="d-flex align-items-center justify-content-between">
                    <div class="d-flex flex-row align-items-center" style="gap: 0.5rem">
	                   	<img :src="acct.avatar" width="32" height="32" class="rounded-circle" onerror="this.src='/storage/avatars/default.png';this.error=null;">
	                    <div class="font-weight-bold">
	                        {{ acct.username }}
	                    </div>
                    </div>
                    <a class="text-danger" href="#" @click.prevent="autospamTrainNonSpamRemove(idx)">
                    	<i class="fas fa-trash"></i>
                    </a>
                </div>
			</div>
		</div>

		<div
			v-if="nonSpamAccounts && nonSpamAccounts.length"
			class="mt-3">
			<a class="btn btn-primary btn-lg font-weight-bold btn-block" href="#" @click.prevent="autospamTrainNonSpamSubmit">Train non-spam posts on trusted accounts</a>
		</div>
    </b-modal>

    <b-modal
    	v-model="showCreateTokenModal"
    	title="Create New Token"
    	cancel-title="Close"
    	cancel-variant="outline-primary"
    	ok-title="Save"
    	ok-variant="primary"
    	v-on:ok="handleSaveToken">
    	<div class="list-group mt-3">
    		<div class="list-group-item">
    			<div class="row align-items-center">
    				<div class="col-4">
    					<p class="mb-0 font-weight-bold small">Token</p>
    				</div>
    				<div class="col-8">
    					<input class="form-control" v-model="customTokenForm.token" />
    				</div>
    			</div>
    		</div>
    		<div class="list-group-item">
    			<div class="row align-items-center">
    				<div class="col-4">
    					<p class="mb-0 font-weight-bold small">Weight</p>
    				</div>
    				<div class="col-8">
    					<input type="number" class="form-control" min="-128" max="128" step="1" v-model="customTokenForm.weight" />
    				</div>
    			</div>
    		</div>
    		<div class="list-group-item">
    			<div class="row align-items-center">
    				<div class="col-4">
    					<p class="mb-0 font-weight-bold small">Category</p>
    				</div>
    				<div class="col-8">
    					<select class="form-control" v-model="customTokenForm.category">
    						<option value="spam">Is Spam</option>
    						<option value="ham">Is NOT Spam</option>
    					</select>
    				</div>
    			</div>
    		</div>
    		<div class="list-group-item">
    			<div class="row align-items-center">
    				<div class="col-4">
    					<p class="mb-0 font-weight-bold small">Note</p>
    				</div>
    				<div class="col-8">
    					<textarea class="form-control" v-model="customTokenForm.note"></textarea>
    				</div>
    			</div>
    		</div>
			<div class="list-group-item">
    			<div class="row align-items-center">
    				<div class="col-4">
    					<p class="mb-0 font-weight-bold small">Active</p>
    				</div>
    				<div class="col-8 text-right">
						<div class="custom-control custom-checkbox">
							<input type="checkbox" class="custom-control-input" id="customCheck1" v-model="customTokenForm.active">
							<label class="custom-control-label" for="customCheck1"></label>
						</div>
    				</div>
    			</div>
    		</div>
    	</div>
    </b-modal>

    <b-modal
    	v-model="showEditTokenModal"
    	title="Edit Token"
    	cancel-title="Close"
    	cancel-variant="outline-primary"
    	ok-title="Update"
    	ok-variant="primary"
    	v-on:ok="handleUpdateToken">
    	<div class="list-group mt-3">
    		<div class="list-group-item">
    			<div class="row align-items-center">
    				<div class="col-4">
    					<p class="mb-0 font-weight-bold small">Token</p>
    				</div>
    				<div class="col-8">
    					<input class="form-control" :value="editCustomTokenForm.token" disabled/>
    				</div>
    			</div>
    		</div>
    		<div class="list-group-item">
    			<div class="row align-items-center">
    				<div class="col-4">
    					<p class="mb-0 font-weight-bold small">Weight</p>
    				</div>
    				<div class="col-8">
    					<input type="number" class="form-control" min="-128" max="128" step="1" v-model="editCustomTokenForm.weight" />
    				</div>
    			</div>
    		</div>
    		<div class="list-group-item">
    			<div class="row align-items-center">
    				<div class="col-4">
    					<p class="mb-0 font-weight-bold small">Category</p>
    				</div>
    				<div class="col-8">
    					<select class="form-control" v-model="editCustomTokenForm.category">
    						<option value="spam">Is Spam</option>
    						<option value="ham">Is NOT Spam</option>
    					</select>
    				</div>
    			</div>
    		</div>
    		<div class="list-group-item">
    			<div class="row align-items-center">
    				<div class="col-4">
    					<p class="mb-0 font-weight-bold small">Note</p>
    				</div>
    				<div class="col-8">
    					<textarea class="form-control" v-model="editCustomTokenForm.note"></textarea>
    				</div>
    			</div>
    		</div>
			<div class="list-group-item">
    			<div class="row align-items-center">
    				<div class="col-4">
    					<p class="mb-0 font-weight-bold small">Active</p>
    				</div>
    				<div class="col-8 text-right">
						<div class="custom-control custom-checkbox">
							<input type="checkbox" class="custom-control-input" id="customCheck1" v-model="editCustomTokenForm.active">
							<label class="custom-control-label" for="customCheck1"></label>
						</div>
    				</div>
    			</div>
    		</div>
    	</div>
    </b-modal>
</div>
</template>

<script type="text/javascript">
    import Autocomplete from '@trevoreyre/autocomplete-vue'
    import '@trevoreyre/autocomplete-vue/dist/style.css'

    export default {
        components: {
            Autocomplete,
        },

        data() {
            return {
                loaded: false,
                tabIndex: 0,
                config: {
                	autospam_enabled: null,
                	open: 0,
                	closed: 0
                },
                closedReports: [],
                closedReportsFetched: false,
                closedReportsCursor: null,
                closedReportsCanLoadMore: false,
                showSpamReportModal: false,
                showSpamReportModalLoading: true,
                viewingSpamReport: undefined,
                viewingSpamReportLoading: false,
                showNonSpamModal: false,
                nonSpamAccounts: [],
                searchLoading: false,

                customTokens: [],
                customTokensFetched: false,
                customTokensCanLoadMore: false,
                showCreateTokenModal: false,
                customTokenForm: {
                	token: undefined,
                	weight: 1,
                	category: 'spam',
                	note: undefined,
                	active: true
                },
                showEditTokenModal: false,
                editCustomToken: {},
                editCustomTokenForm: {
                	token: undefined,
                	weight: 1,
                	category: 'spam',
                	note: undefined,
                	active: true
                }
            }
        },

        mounted() {
        	setTimeout(() => {
        		this.loaded = true;
        		this.fetchConfig();
        	}, 1000);
        },

        methods: {
        	toggleTab(idx) {
        		this.tabIndex = idx;

        		if(idx == 0) {
	        		setTimeout(() => {
	        			this.initChart();
	        		}, 500);
        		}

        		if(idx === 'closed_reports' && !this.closedReportsFetched) {
        			this.fetchClosedReports();
        		}

        		if(idx === 'manage_tokens' && !this.customTokensFetched) {
        			this.fetchCustomTokens();
        		}
        	},

        	formatCount(ct) {
        		return App.util.format.count(ct);
        	},

            timeAgo(str) {
                if(!str) {
                    return str;
                }
                return App.util.format.timeAgo(str);
            },

        	fetchConfig() {
        		axios.post('/i/admin/api/autospam/config')
        		.then(res => {
        			this.config = res.data;
        			this.loaded = true;
        		})
        		.finally(() => {
        			setTimeout(() => {
        				this.initChart();
        			}, 100);
        		})
        	},

        	initChart() {
			    var usersChart = new Chart(document.querySelector('#c1-dark'), {
			      type: 'line',
			      options: {
			        scales: {
			          yAxes: [{
			            gridLines: {
			              lineWidth: 1,
			              color: '#212529',
			              zeroLineColor: '#212529'
			            },
			          }]
			        },
			      },
			      data: {
			        datasets: [{
			        	data: this.config.graph
			        }],
			        labels: this.config.graphLabels
			      }
			    });
        	},

        	fetchClosedReports(url = '/i/admin/api/autospam/reports/closed') {
        		axios.post(url)
        		.then(res => {
        			this.closedReports = res.data;
        		})
        		.finally(() => {
        			this.closedReportsFetched = true;
        		})
        	},

            viewSpamReport(report) {
            	this.viewingSpamReportLoading = false;
            	this.viewingSpamReport = report;
            	this.showSpamReportModal = true;
            	setTimeout(() => {
            		pixelfed.readmore()
            	}, 500)
            },

            autospamPaginate(dir) {
                event.currentTarget.blur();
                let url = dir == 'next' ? this.closedReports.links.next : this.closedReports.links.prev;
                this.fetchClosedReports(url);
            },

            autospamTrainSpam() {
                event.currentTarget.blur();
            	axios.post('/i/admin/api/autospam/train')
            	.then(res => {
            		swal('Training Autospam!', 'A background job has been dispatched to train Autospam!', 'success');
            		setTimeout(() => {
	            		window.location.reload();
	            	}, 10000);
            	})
            	.catch(error => {
            		if(error.response.status === 422) {
            			swal('Error', error.response.data.error, 'error');
            		} else {
            			swal('Error', 'Oops, an error occured, please try again later', 'error');
            		}
            	})
            },

            autospamTrainNonSpam() {
            	this.showNonSpamModal = true;
            },

            composeSearch(input) {
                if (input.length < 1) { return []; };
                return axios.post('/i/admin/api/autospam/search/non-spam', {
                   q: input,
                }).then(res => {
                	let data = res.data.filter(a => {
                		if(!this.nonSpamAccounts || !this.nonSpamAccounts.length) {
                			return true;
                		}
                		return this.nonSpamAccounts && this.nonSpamAccounts.map(a => a.id).indexOf(a.id) == -1;
                	})
                    return data;
                });
            },

            getTagResultValue(result) {
                return result.username;
            },

            onSearchResultClick(result) {
            	if(this.nonSpamAccounts.map(a => a.id).indexOf(result.id) != -1) {
            		return;
            	}
            	this.nonSpamAccounts.push(result);
                return;
            },

            autospamTrainNonSpamRemove(idx) {
            	this.nonSpamAccounts.splice(idx, 1);
            },

            autospamTrainNonSpamSubmit() {
            	this.showNonSpamModal = false;
            	axios.post('/i/admin/api/autospam/train/non-spam', {
            		accounts: this.nonSpamAccounts
            	})
            	.then(res => {
            		swal('Training Autospam!', 'A background job has been dispatched to train Autospam!', 'success');

	            	setTimeout(() => {
	            		window.location.reload();
	            	}, 10000);
            	})
            	.catch(error => {
            		if(error.response.status === 422) {
            			swal('Error', error.response.data.error, 'error');
            		} else {
            			swal('Error', 'Oops, an error occured, please try again later', 'error');
            		}
            	})
            },

            fetchCustomTokens(url = '/i/admin/api/autospam/tokens/custom') {
            	axios.post(url)
            	.then(res => {
            		this.customTokens = res.data;
            	})
            	.finally(() => {
            		this.customTokensFetched = true;
            	})
            },

            handleSaveToken() {
            	axios.post('/i/admin/api/autospam/tokens/store', this.customTokenForm)
            	.then(res => {
            		console.log(res.data);
            	})
            	.catch(err => {
            		swal('Oops! An Error Occured', err.response.data.message, 'error');
            	})
            	.finally(() => {
            		this.customTokenForm = {
	                	token: undefined,
	                	weight: 1,
	                	category: 'spam',
	                	note: undefined,
	                	active: true
	                }

	                this.fetchCustomTokens();
            	})
            },

            openEditTokenModal(token) {
            	event.currentTarget.blur();
            	this.editCustomToken = token;
            	this.editCustomTokenForm = token;
            	this.showEditTokenModal = true;
            },

            handleUpdateToken() {
            	axios.post('/i/admin/api/autospam/tokens/update', this.editCustomTokenForm)
            	.then(res => {
            		console.log(res.data);
            	})

            },

			autospamTokenPaginate(dir) {
                event.currentTarget.blur();
                let url = dir == 'next' ? this.customTokens.next_page_url : this.customTokens.prev_page_url;
                this.fetchCustomTokens(url);
            },

            downloadExport() {
                event.currentTarget.blur();

            	axios.post('/i/admin/api/autospam/tokens/export', {}, {
            		responseType: 'blob'
            	})
				.then(res => {
					const aElement = document.createElement('a');
					aElement.setAttribute('download', 'pixelfed-autospam-export.json');
					const href = URL.createObjectURL(res.data);
					aElement.href = href;
					aElement.setAttribute('target', '_blank');
					aElement.click();
					URL.revokeObjectURL(href);
				})
				.catch(async(error) => {
					let errorString = error.response.data
					if (
					  error.request.responseType === 'blob' &&
					  error.response.data instanceof Blob &&
					  error.response.data.type &&
					  error.response.data.type.toLowerCase().indexOf('json') != -1
					) {
					    errorString = JSON.parse(await error.response.data.text());
						swal('Export Error', errorString.error, 'error');
					};
				});
            },

            enableAdvanced() {
                event.currentTarget.blur();

                if(
                	!this.config.files.spam.exists ||
                	!this.config.files.ham.exists ||
                	!this.config.files.combined.exists ||
                	this.config.files.spam.size < 1000 ||
                	this.config.files.ham.size < 1000 ||
                	this.config.files.combined.size < 1000
                ) {
                	swal('Training Required', 'Before you can enable Advanced Detection, you need to train the models.\n\n Click on the "Train Autospam" tab and train both categories before proceeding', 'error');
                	return;
                }
				swal({
				  title: "Confirm",
				  text: "Are you sure you want to enable Advanced Detection?",
				  icon: "warning",
				  dangerMode: true,
				  buttons: {
				    cancel: "Cancel",
				    confirm: {
				      text: "Enable",
				      value: "enable",
				    }
				  },
				})
				.then((res) => {
				  if (res === 'enable') {
				  	axios.post('/i/admin/api/autospam/config/enable')
				  	.then(res => {
						swal("Success! Advanced Detection is now enabled!\n\n This page will reload in a few seconds!", {
						  icon: "success",
						});

						setTimeout(() => {
							window.location.reload();
						}, 5000);
				  	})
				  	.catch(err => {
				  		swal('Oops!', 'An error occured, please try again later', 'error');
				  	})
				  } else {
				  }
				});
            },

			disableAdvanced() {
                event.currentTarget.blur();

               	swal({
				  title: "Confirm",
				  text: "Are you sure you want to disable Advanced Detection?",
				  icon: "warning",
				  dangerMode: true,
				  buttons: {
				    cancel: "Cancel",
				    confirm: {
				      text: "Disable",
				      value: "disable",
				    }
				  },
				})
				.then((res) => {
				  if (res === 'disable') {
				  	axios.post('/i/admin/api/autospam/config/disable')
				  	.then(res => {
						swal("Success! Advanced Detection is now disabled!\n\n This page will reload in a few seconds!", {
						  icon: "success",
						});

						setTimeout(() => {
							window.location.reload();
						}, 5000);
				  	})
				  	.catch(err => {
				  		swal('Oops!', 'An error occured, please try again later', 'error');
				  	})
				  }
				})
            },

            handleImport() {
                event.currentTarget.blur();

                swal('Error', 'You do not have enough data to support importing.', 'error');
            }
        }
    }
</script>
