<template>
	<div class="group-list-card">
		<div class="media">
			<div class="media align-items-center">
				<img
					v-if="group.metadata && group.metadata.hasOwnProperty('header')"
					:src="group.metadata.header.url"
					class="mr-3 border rounded group-header-img"
					:class="{ compact: compact }">

				<div
					v-else
					class="mr-3 border rounded group-header-img"
					:class="{ compact: compact }">
					<div
						class="bg-primary d-flex align-items-center justify-content-center rounded"
						style="width: 100%; height:100%;">
						<i class="fal fa-users text-white fa-lg"></i>
					</div>
				</div>

				<div class="media-body">
					<p class="font-weight-bold mb-0 text-dark" style="font-size: 16px;">
						{{ truncate(group.name || 'Untitled Group', titleLength) }}
					</p>

					<p class="text-muted mb-1" style="font-size: 12px;">
						{{ truncate(group.short_description, descriptionLength) }}
					</p>

					<p v-if="showStats" class="mb-0 small text-lighter">
						<span>
							<i class="far fa-users"></i>
							<span class="small font-weight-bold">{{ prettyCount(group.member_count) }}</span>
						</span>

						<span v-if="!group.local" class="remote-label ml-3">
							<i class="fal fa-globe"></i> Remote
						</span>

						<span v-if="group.hasOwnProperty('admin') && group.admin.hasOwnProperty('username')" class="ml-3">
							<i class="fal fa-user-crown"></i>
							<span class="small font-weight-bold">
								&commat;{{ group.admin.username }}
							</span>
						</span>
					</p>
				</div>
			</div>
		</div>
	</div>
</template>

<script type="text/javascript">
	export default {
		props: {
			group: {
				type: Object
			},

			compact: {
				type: Boolean,
				default: false
			},

			showStats: {
				type: Boolean,
				default: false
			},

			truncateTitleLength: {
				type: Number,
				default: 19
			},

			truncateDescriptionLength: {
				type: Number,
				default: 22
			}
		},

		data() {
			return {
				titleLength: 40,
				descriptionLength: 60
			}
		},

		mounted() {
			if(this.compact) {
				this.titleLength = 19;
				this.descriptionLength = 22;
			}

			if(this.truncateTitleLength != 19) {
				this.titleLength = this.truncateTitleLength;
			}

			if(this.truncateDescriptionLength != 22) {
				this.descriptionLength = this.truncateDescriptionLength;
			}
		},

		methods: {
			prettyCount(val) {
				return App.util.format.count(val);
			},

			truncate(str, limit = 140) {
				if(str.length <= limit) {
					return str;
				}

				return str.substr(0, limit) + ' ...';
			}
		}
	}
</script>

<style lang="scss" scoped>
	.group-list-card {
		.member-label {
			padding: 2px 5px;
			font-size: 9px;
			color: rgba(75, 119, 190, 1);
			background: rgba(137, 196, 244, 0.2);
			border: 1px solid rgba(137, 196, 244, 0.3);
			font-weight: 500;
			text-transform: capitalize;
			border-radius: 3px;
		}

		.remote-label {
			padding: 2px 5px;
			font-size: 9px;
			color: #B45309;
			background: #FEF3C7;
			border: 1px solid #FCD34D;
			font-weight: 500;
			text-transform: capitalize;
			border-radius: 3px;
		}

		.group-header-img {
			width: 60px;
			height: 60px;
			object-fit: cover;
			padding:0px;

			&.compact {
				width: 42.5px;
				height: 42.5px;
			}
		}
	}
</style>
