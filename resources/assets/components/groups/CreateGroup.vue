<template>
	<div class="create-group-component col-12 col-md-9" style="height: 100vh - 51px !important;overflow:hidden">
        <div v-if="!hide" class="row h-100 bg-lighter">
			<div class="col-12 col-md-8 border-left">

                <div class="bg-dark p-5 mx-n3">
                    <p class="h1 font-weight-bold text-light mb-2">Create Group</p>
                    <p class="text-lighter mb-0">Create a new federated Group that is compatible with other Pixelfed and Lemmy servers</p>
                </div>
				<div class="px-2 mb-5">
					<div class="mt-4">
                        <text-input
                            label="Group Name"
                            :value="name"
                            :hasLimit="true"
                            :maxLimit="limit.name.max"
                            placeholder="Add your group name"
                            helpText="Alphanumeric characters only, you can change this later."
                            :largeInput="true"
                            @update="handleUpdate('name', $event)"
                        />

                        <hr>

                        <select-input
                            label="Group Type"
                            :value="membership"
                            :categories="membershipCategories"
                            placeholder="Select a type"
                            helpText="Select the membership type, you can change this later."
                            @update="handleUpdate('membership', $event)"
                        />

                        <hr>

                        <select-input
                            label="Group Category"
                            :value="category"
                            :categories="categories"
                            placeholder="Select a category"
                            helpText="Choose the most relevant category to improve discovery and visibility"
                            @update="handleUpdate('category', $event)"
                        />

						<hr>

                        <text-area-input
                            label="Group Description"
                            :value="description"
                            :hasLimit="true"
                            :maxLimit="limit.description.max"
                            placeholder="Describe your groups purpose in a few words"
                            helpText="Describe your groups purpose in a few words, you can change this later."
                            @update="handleUpdate('description', $event)"
                        />

                        <hr>

                        <checkbox-input
                            label="Adult Content"
                            inputText="Allow Adult Content"
                            :value="configuration.adult"
                            helpText="Groups that allow adult content should enable this or risk suspension or deletion by instance admins. Illegal content is prohibited. You can change this later."
                        />

                        <hr>

                        <checkbox-input
                            label=""
                            inputText="I agree to the the Community Guidelines and Terms of Use and will administrate this group according to the rules set by this server. I understand that failure to abide by these terms may lead to the suspension of this group, and my account."
                            :value="hasConfirmed"
                            :strongText="false"
                            @update="handleUpdate('hasConfirmed', $event)"
                        />

						<!-- <div class="form-group row">
							<div class="col-sm-10 offset-sm-2">
								<div class="form-check">
									<input class="form-check-input" type="checkbox" id="gridCheck1" v-model="hasConfirmed">
									<label class="form-check-label" for="gridCheck1">
										I agree to the <a href="#">Community Guidelines</a> and <a href="#">Terms of Use</a>
									</label>
								</div>
							</div>
						</div> -->

                        <button
                            class="btn btn-primary btn-block font-weight-bold rounded-pill mt-4"
                            @click="createGroup"
                            :disabled="!hasConfirmed">
                            Create Group
                        </button>
					</div>
				</div>
			</div>
			<div class="col-12 col-md-4 bg-white">
				<!-- <div>
					<button
						v-if="page <= 4"
						class="btn btn-primary btn-block font-weight-bold rounded-pill mt-4"
						@click="nextPage"
						:disabled="!membership">
						Next
					</button>

					<button
						v-if="page == 5"
						class="btn btn-primary btn-block font-weight-bold rounded-pill mt-4"
						@click="createGroup"
						:disabled="!hasConfirmed">
						Create Group
					</button>

					<button
						v-if="page >= 2"
						class="btn btn-light btn-block font-weight-bold rounded-pill border mt-4"
						@click="prevPage">
						Back
					</button>

					<div v-if="maxPage > 2" class="mt-4">
						<p v-if="name && name.length">
							<span class="text-lighter">Name:</span>
							<span class="font-weight-bold">{{ name }}</span>
						</p>

						<p v-if="description && description.length">
							<span class="text-lighter">Description:</span>
							<span>{{ description }}</span>
						</p>

						<p v-if="membership && membership.length">
							<span class="text-lighter">Membership:</span>
							<span class="text-capitalize">{{ membership }}</span>
						</p>

						<p v-if="category && category.length">
							<span class="text-lighter">Category:</span>
							<span class="text-capitalize">{{ category }}</span>
						</p>
					</div>
				</div> -->
			</div>
		</div>
     </div>
</template>

<script type="text/javascript">
    import TextInput from '@/groups/partials/CreateForm/TextInput.vue';
    import SelectInput from '@/groups/partials/CreateForm/SelectInput.vue';
    import TextAreaInput from '@/groups/partials/CreateForm/TextAreaInput.vue';
    import CheckboxInput from '@/groups/partials/CreateForm/CheckboxInput.vue';

	export default {
        components: {
            "text-input": TextInput,
            "select-input": SelectInput,
            "text-area-input": TextAreaInput,
            "checkbox-input": CheckboxInput,
        },

		data() {
			return {
				hide: true,
				name: null,
				page: 1,
				maxPage: 1,
				description: null,
				membership: "placeholder",
				submitting: false,
				categories: [],
				category: "",
				limit: {
					name: {
						max: 60
					},
					description: {
						max: 500
					}
				},
				configuration: {
					types: {
						text: true,
						photos: true,
						videos: true,
						// events: false,
						polls: true
					},
					federation: true,
					adult: false,
					discoverable: false,
					autospam: false,
					dms: false,
					slowjoin: {
						enabled: false,
						age: 90,
						limit: {
							post: 1,
							comment: 20,
							threads: 2,
							likes: 5,
							hashtags: 5,
							mentions: 1,
							autolinks: 1
						}
					}
				},
				hasConfirmed: false,
				permissionChecked: false,
                membershipCategories: [
                    { key: 'Public', value: 'public' },
                    // { key: 'Private', value: 'private' },
                    // { key: 'Local', value: 'local' },
                ],
			}
		},

		mounted() {
			this.permissionCheck();
			this.fetchCategories();
		},

		methods: {
			permissionCheck() {
				axios.post('/api/v0/groups/permission/create')
				.then(res => {
					if(res.data.permission == false) {
						swal('Limit reached', 'You cannot create any more groups', 'error');
						this.hide = true;
					} else {
						this.hide = false;
					}
					this.permissionChecked = true;
				});
			},

			submit($event) {
				$event.preventDefault();
				this.submitting = true;

				axios.post('/api/v0/groups/create', {
					name: this.name,
					description: this.description,
					membership: this.membership
				}).then(res => {
					console.log(res.data);
					window.location.href = res.data.url;
				}).catch(err => {
					console.log(err.response);

				})
			},

			fetchCategories() {
				axios.get('/api/v0/groups/categories/list')
				.then(res => {
					this.categories = res.data.map(c => {
                        return {
                            key: c,
                            value: c
                        }
                    });
				})
			},

			createGroup() {
				axios.post('/api/v0/groups/create', {
					name: this.name,
					description: this.description,
					membership: this.membership,
					configuration: this.configuration
				})
				.then(res => {
					console.log(res.data);
					location.href = res.data.url;
				})
			},

            handleUpdate(key, val) {
                this[key] = val;
            }
		}
	}
</script>

<style lang="scss">
	.create-group-component {
		.submit-button {
			width: 130px;
		}

		.multistep {
			margin-top: 30px;
			margin-bottom: 30px;
			overflow: hidden;
			counter-reset: step;
			text-align: center;
			padding-left: 0;
		}

		.multistep li {
			list-style-type: none;
			text-transform: uppercase;
			font-size: 9px;
			font-weight: 700;
			width: 20%;
			float: left;
			position: relative;
			color: #B8C2CC;
		}

		.multistep li.active {
			color: #000;
		}

		.multistep li:before {
			content: counter(step);
			counter-increment: step;
			width: 24px;
			height: 24px;
			line-height: 26px;
			display: block;
			font-size: 12px;
			color: #B8C2CC;
			background: #F3F4F6;
			border-radius: 25px;
			margin: 0 auto 10px auto;
			transition: background 400ms;
		}

		.multistep li:after {
			content: '';
			width: 100%;
			height: 2px;
			background: #dee2e6;
			position: absolute;
			left: -50%;
			top: 11px;
			z-index: -1;
			transition: background 400ms;
		}

		.multistep li:first-child:after {
			content: none;
		}

		.multistep li.active:before,
		.multistep li.active:after {
			background: #2c78bf;
			color: white;
			transition: background 400ms;
		}

		.col-form-label {
			font-weight: 600;
			text-align: right;
		}
	}
</style>
