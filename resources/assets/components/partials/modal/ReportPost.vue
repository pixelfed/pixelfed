<template>
	<b-modal
		ref="modal"
		centered
		hide-header
		hide-footer
		scrollable
		body-class="p-md-5 user-select-none"
	>
		<div v-if="tabIndex === 0">
			<h2 class="text-center font-weight-bold">{{ $t('report.report') }}</h2>

			<p class="text-center">{{ $t('menu.confirmReportText') }}</p>

			<div v-if="status && status.hasOwnProperty('account')" class="card shadow-none rounded-lg border my-4">
				<div class="card-body">
					<div class="media">
						<img
							:src="status.account.avatar"
							class="mr-3 rounded"
							width="40"
							height="40"
							style="border-radius: 8px;"
							onerror="this.onerror=null;this.src='/storage/avatars/default.jpg?v=0';">

						<div class="media-body">
							<p class="h5 primary font-weight-bold mb-1">
								&commat;{{ status.account.acct }}
							</p>

							<div v-if="status.hasOwnProperty('pf_type') && status.pf_type == 'text'">
								<p v-if="status.content_text.length <= 140" class="mb-0">
									{{ status.content_text}}
								</p>

								<p v-else class="mb-0">
									<span v-if="showFull">
										{{ status.content_text}}
										<a class="font-weight-bold primary ml-1" href="#" @click.prevent="showFull = false">Show less</a>
									</span>
									<span v-else>
										{{ status.content_text.substr(0, 140) + ' ...' }}
										<a class="font-weight-bold primary ml-1" href="#" @click.prevent="showFull = true">Show full post</a>
									</span>
								</p>
							</div>

							<div v-else-if="status.hasOwnProperty('pf_type') && status.pf_type == 'photo'">
								<div class="w-100 rounded-lg d-flex justify-content-center mt-3" style="background: #000;max-height: 150px">
									<img :src="status.media_attachments[0].url" class="rounded-lg shadow" style="width: 100%;max-height: 150px;object-fit:contain;">
								</div>

								<p v-if="status.content_text" class="mt-3 mb-0">
									<span v-if="showFull">
										{{ status.content_text}}
										<a class="font-weight-bold primary ml-1" href="#" @click.prevent="showFull = false">Show less</a>
									</span>
									<span v-else>
										{{ status.content_text.substr(0, 80) + ' ...' }}
										<a class="font-weight-bold primary ml-1" href="#" @click.prevent="showFull = true">Show full post</a>
									</span>
								</p>
							</div>
						</div>
					</div>
				</div>
			</div>

			<p class="text-right mb-0 mb-md-n3">
				<button class="btn btn-light px-3 py-2 mr-3 font-weight-bold" @click="close">{{ $t('common.cancel')}}</button>
				<button class="btn btn-primary px-3 py-2 font-weight-bold" style="background-color: #3B82F6;" @click="tabIndex = 1">{{ $t('common.proceed') }}</button>
			</p>
		</div>

		<div v-else-if="tabIndex === 1">
			<h2 class="text-center font-weight-bold">{{ $t('report.report') }}</h2>

			<p class="text-center">
				{{ $t('report.selectReason') }}
			</p>

			<div class="mt-4">
				<!-- <button class="btn btn-light btn-block rounded-pill font-weight-bold" @click="handleReason('notinterested')">I'm not interested in it</button> -->
				<button class="btn btn-light btn-block rounded-pill font-weight-bold text-danger" @click="handleReason('spam')">{{ $t('menu.spam')}}</button>
				<button v-if="status.sensitive == false" class="btn btn-light btn-block rounded-pill font-weight-bold text-danger" @click="handleReason('sensitive')">Adult or {{ $t('menu.sensitive')}}</button>
				<button class="btn btn-light btn-block rounded-pill font-weight-bold text-danger" @click="handleReason('abusive')">{{ $t('menu.abusive')}}</button>
				<button class="btn btn-light btn-block rounded-pill font-weight-bold" @click="handleReason('underage')">{{ $t('menu.underageAccount')}}</button>
				<button class="btn btn-light btn-block rounded-pill font-weight-bold" @click="handleReason('copyright')">{{ $t('menu.copyrightInfringement')}}</button>
				<button class="btn btn-light btn-block rounded-pill font-weight-bold" @click="handleReason('impersonation')">{{ $t('menu.impersonation')}}</button>
				<!-- <button class="btn btn-light btn-block rounded-pill font-weight-bold" @click="handleReason('scam')">{{ $t('menu.scamOrFraud')}}</button> -->
				<button class="btn btn-light btn-block rounded-pill mt-md-5" @click="tabIndex = 0">Go back</button>
			</div>
		</div>

		<div v-else-if="tabIndex === 2">
			<div class="my-4 text-center">
				<b-spinner />

				<p class="small mb-0">{{ $t('report.sendingReport') }} ...</p>
			</div>
		</div>

		<div v-else-if="tabIndex === 3">
			<div class="my-4">
				<h2 class="text-center font-weight-bold mb-3">{{ $t('report.reported') }}</h2>
				<p class="text-center py-2">
					<span class="fa-stack fa-4x text-success">
						<i class="far fa-check fa-stack-1x"></i>
						<i class="fal fa-circle fa-stack-2x"></i>
					</span>
				</p>
				<p class="lead text-center">{{ $t('report.thanksMsg') }}</p>
				<hr>
				<p class="text-center">{{ $t('report.contactAdminMsg') }}, <a href="/site/contact" class="font-weight-bold primary">{{ $t('common.clickHere') }}</a>.</p>
			</div>

			<p class="text-center mb-0 mb-md-n3">
				<button class="btn btn-light btn-block rounded-pill px-3 py-2 mr-3 font-weight-bold" @click="close">{{ $t('common.close') }}</button>
			</p>
		</div>

		<div v-else-if="tabIndex === 5">
			<div class="my-4">
				<h2 class="text-center font-weight-bold mb-3">{{ $t('common.oops') }}</h2>
				<p class="text-center py-2">
					<span class="fa-stack fa-3x text-danger">
						<i class="far fa-times fa-stack-1x"></i>
						<i class="fal fa-circle fa-stack-2x"></i>
					</span>
				</p>
				<p class="lead text-center">{{ $t('common.errorMsg') }}</p>
				<hr>
				<p class="text-center">{{ $t('report.contactAdminMsg') }}, <a href="/site/contact" class="font-weight-bold primary">{{ $t('common.clickHere') }}</a>.</p>
			</div>

			<p class="text-center mb-0 mb-md-n3">
				<button class="btn btn-light btn-block rounded-pill px-3 py-2 mr-3 font-weight-bold" @click="close">{{ $t('common.close') }}</button>
			</p>
		</div>
	</b-modal>
</template>

<script type="text/javascript">
	export default {
		props: {
			status: {
				type: Object,
				default: {}
			}
		},

		data() {
			return {
				statusId: undefined,
				tabIndex: 0,
				showFull: false
			}
		},

		methods: {
			open() {
				this.$refs.modal.show();
			},

			close() {
				this.$refs.modal.hide();
				setTimeout(() => {
					this.tabIndex = 0;
				}, 1000);
			},

			handleReason(reason) {
				this.tabIndex = 2;

				axios.post('/i/report', {
					id: this.status.id,
					report: reason,
					type: 'post'
				}).then(res => {
					this.tabIndex = 3;
				}).catch(err => {
					this.tabIndex = 5;
				});
			}
		}
	}
</script>
