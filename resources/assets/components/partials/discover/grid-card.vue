<template>
	<div class="discover-grid-card">
		<div
			class="discover-grid-card-body"
			:class="{ 'dark': dark, 'small': small }"
			>

			<div class="section-copy">
				<p class="subtitle">{{ subtitle }}</p>
				<h1 class="title">{{ title }}</h1>
				<button v-if="buttonText" class="btn btn-outline-dark rounded-pill py-1" @click.prevent="handleLink()">{{ buttonText }}</button>
			</div>

			<div class="section-icon">
				<i :class="iconClass"></i>
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
			},

			dark: {
				type: Boolean,
				default: false
			},

			subtitle: {
				type: String
			},

			title: {
				type: String
			},

			buttonText: {
				type: String
			},

			buttonLink: {
				type: String
			},

			buttonEvent: {
				type: Boolean,
				default: false
			},

			iconClass: {
				type: String
			}
		},

		methods: {
			handleLink() {
				if(this.buttonEvent == true) {
					this.$emit('btn-click');
					return;
				}

				if(!this.buttonLink || this.buttonLink == undefined) {
					swal('Oops', 'This is embarassing, we cannot redirect you to the proper page at the moment', 'warning');
					return;
				}

				this.$router.push(this.buttonLink);
			}
		}
	}
</script>

<style lang="scss">
	.discover-grid-card {
		width: 100%;

		&-body {
			width: 100%;
			padding: 3rem 3rem 0;
			border-radius: 8px;
			text-align: center;
			color: #212529;
			background: #f8f9fa;
			overflow: hidden;

			.section-copy {
				margin-top: 1rem;
				margin-bottom: 1rem;
				padding-top: 1rem;
				padding-bottom: 1rem;

				.subtitle {
					font-size: 16px;
					margin-bottom: 0;
					color: #6c757d;
					font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
					letter-spacing: -0.7px;
				}

				.title,
				.btn {
					font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
					letter-spacing: -0.7px;
				}
			}

			.section-icon {
				display: flex;
				justify-content: center;
				align-items: center;
				margin-left: auto;
				margin-right: auto;
				width: 80%;
				height: 300px;
				border-radius: 21px 21px 0 0;
				background: #232526;
				background: linear-gradient(to right, #414345, #232526);
				box-shadow: 0 0.125rem 0.25rem rgb(0 0 0 / 8%) !important;

				i {
					color: #fff;
					font-size: 10rem;
				}
			}

			&.small {
				.section-icon {
					height: 120px;

					i {
						font-size: 4rem;
					}
				}
			}

			&.dark {
				color: #fff;
				background: #232526;
				background: linear-gradient(to right, #414345, #232526);

				.section-icon {
					color: #fff;
					background: #f8f9fa;

					i {
						color: #232526;
					}
				}

				.btn-outline-dark {
					color: #f8f9fa;
					border-color: #f8f9fa;
				}
			}
		}
	}
</style>
