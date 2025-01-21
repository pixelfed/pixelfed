<template>
	<div class="read-more-component" style="word-break: break-all;">
		<div v-if="status.content.length < 200" v-html="content"></div>
		<div v-else>
			<span v-html="content"></span>
			<a
				v-if="cursor == 200 || fullContent.length > cursor"
				class="font-weight-bold text-muted" href="#"
				style="display: block;white-space: nowrap;"
				@click.prevent="readMore">
				<i class="d-none fas fa-caret-down"></i> Read more...
			</a>
		</div>
	</div>
</template>

<script type="text/javascript">
	export default {
		props: {
			status: {
				type: Object
			},

			cursorLimit: {
				type: Number,
				default: 200
			}
		},

		data() {
			return {
				fullContent: null,
				content: null,
				cursor: 200
			}
		},

		mounted() {
			this.cursor = this.cursorLimit;
			this.fullContent = this.status.content;
			this.content = this.status.content.substr(0, this.cursor);
		},

		methods: {
			readMore() {
				this.cursor = this.cursor + 200;
				this.content = this.fullContent.substr(0, this.cursor);
			}
		}
	}
</script>
