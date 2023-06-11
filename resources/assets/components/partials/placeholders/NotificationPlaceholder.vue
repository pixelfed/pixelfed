<template>
	<div v-if="small" class="ph-item border-0 mb-0 p-0" style="border-radius:15px;margin-left:-14px;">
		<div class="ph-col-12 mb-0">
			<div class="ph-row align-items-center mt-0">
				<div class="ph-avatar mr-2 d-flex" style="min-width: 32px;width:32px!important;height:32px!important;border-radius: 40px;"></div>
				<div class="ph-col-6"></div>
			</div>
		</div>
	</div>

	<div v-else class="ph-item border-0 shadow-sm p-1" style="border-radius:15px;margin-bottom: 1rem;">
		<div class="ph-col-12">
			<div class="ph-row align-items-center mt-0">
				<div class="ph-avatar mr-3 d-flex" style="min-width: 40px;width:40px!important;height:40px!important;border-radius: 15px;"></div>
				<div class="ph-col-6 big"></div>
			</div>
		</div>
	</div>
</template>

<script type="text/javascript">
	export default {
		props: {
			small: {
				type: Boolean,
				default: false
			}
		}
	}
</script>
