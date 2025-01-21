<template>
	<canvas ref="canvas" :width="parseNumber(width)" :height="parseNumber(height)" />
</template>

<script type="text/javascript">
	import { decode } from 'blurhash';

	export default {
		props: {
			hash: {
				type: String,
				required: true
			},

			width: {
				type: [Number, String],
				default: 32
			},

			height: {
				type: [Number, String],
				default: 32
			},

			punch: {
				type: Number,
				default: 1
			}
		},

		mounted() {
			this.draw();
		},

		updated() {
			// this.draw();
		},

		beforeDestroy() {
			// this.hash = null;
			// this.$refs.canvas = null;
		},

		methods: {
			parseNumber(val) {
				return typeof val === 'number' ? val : parseInt(val, 10);
			},

			draw() {
				const width = this.parseNumber(this.width);
				const height = this.parseNumber(this.height);
				const punch = this.parseNumber(this.punch);
				const pixels = decode(this.hash, width, height, punch);
				const ctx = this.$refs.canvas.getContext('2d');
				const imageData = ctx.createImageData(width, height);
				imageData.data.set(pixels);
				ctx.putImageData(imageData, 0, 0);
			},
		}
	}
</script>
