<template>
	<div class="self-discover-component col-12 col-md-9 bg-lighter border-left mb-4">
		<div class="px-5">
            <div class="jumbotron my-4 text-light bg-mantle">
            	<div class="container">
            		<h1 class="display-4">Discover</h1>
                	<p class="lead mb-0">Explore group communities and topics</p>
            		<!-- <p class="lead">
            			<button class="btn btn-outline-light">Browse Categories</button>
            		</p> -->
            	</div>
            </div>
        </div>

		<div v-if="tab === 'home'" class="px-5">
            <div class="row mb-4 pt-5">
				<div class="col-12 col-md-4">
					<h4 class="font-weight-bold">Popular</h4>
					<div class="list-group list-group-scroll">
						<a v-for="(group, index) in popularGroups"
							class="list-group-item p-1"
							:href="group.url">
							<group-list-card :group="group" :compact="true" />
						</a>
					</div>
				</div>

				<div class="col-12 col-md-4">
					<div class="card card-body shadow-none bg-mantle text-light" style="margin-top: 33px;">
						<h3 class="mb-4 font-weight-lighter">Discover communities and topics based on your interests</h3>
						<p class="mb-0">
							<button class="btn btn-outline-light font-weight-light btn-block" @click="toggleTab('categories')">Browse Categories</button>
						</p>
					</div>

					<div class="card card-body shadow-none bg-light text-dark border" style="margin-top: 20px;">
						<p class="lead mb-4 text-muted font-weight-lighter mb-1">Browse Public Groups</p>
						<!-- <p class="lead mb-4 text-muted font-weight-lighter">Tips for growing your group membership</p> -->
						<!-- <h5 class="mb-4 text-muted font-weight-lighter">Create and easily organize events</h5> -->
						<p class="mb-0">
							<button class="btn btn-light border font-weight-light btn-block">Group Directory</button>
						</p>
					</div>
				</div>

				<div class="col-12 col-md-4">
					<h4 class="font-weight-bold">New</h4>
					<div class="list-group list-group-scroll">
						<a v-for="(group, index) in newGroups"
							class="list-group-item p-1"
							:href="group.url">
							<group-list-card :group="group" :compact="true" />
						</a>
					</div>
				</div>
			</div>

			<div class="jumbotron mb-4 text-light bg-black" style="margin-top: 5rem;">
            	<div class="container">
            		<h1 class="display-4">Across the Fediverse</h1>
                	<p class="mb-0">
                		<button
                			class="btn btn-outline-light"
                			@click="toggleTab('fediverseGroups')"
                			>
                			Explore fediverse groups <i class="fal fa-chevron-right ml-2"></i>
                		</button>
                	</p>
                	<hr class="my-4">
                	<p class="lead">We're in the early stages of Group federation, and working with other projects to support cross-platform compatibility. <a href="#">Learn more about group federation <i class="fal fa-chevron-right ml-2 fa-sm"></i></a></p>
            	</div>
            </div>

            <div class="row my-4 py-5">
            	<div class="col-12 col-md-4">
            		<div class="card card-body shadow-none bg-light" style="border:1px solid #E5E7EB;">
            			<p class="text-center text-lighter">
            				<i class="fal fa-lightbulb fa-4x"></i>
            			</p>
            			<p class="text-center lead mb-0">What's New</p>
            		</div>
            	</div>

            	<div class="col-12 col-md-4">
            		<div class="card card-body shadow-none bg-light" style="border:1px solid #E5E7EB;">
            			<p class="text-center text-lighter">
            				<i class="fal fa-clipboard-list-check fa-4x"></i>
            			</p>
            			<p class="text-center lead mb-0">User Guide</p>
            		</div>
            	</div>

            	<div class="col-12 col-md-4">
            		<div class="card card-body shadow-none bg-light" style="border:1px solid #E5E7EB;">
            			<p class="text-center text-lighter">
            				<i class="fal fa-question-circle fa-4x"></i>
            			</p>
            			<p class="text-center lead mb-0">Groups Help</p>
            		</div>
            	</div>
            </div>

            <p class="text-lighter" style="font-size:9px">
            	<span class="font-weight-bold mr-1">Groups v0.0.1</span>
            </p>

			<!-- <div class="my-4 pt-5">
				<p class="h4 font-weight-bold mb-1">Suggested for You</p>
				<p class="lead text-muted mb-0">Groups you might be interested in</p>
			</div>

			<div class="row mb-4">
				<div v-for="(group, index) in recommended.slice(recommendedStart, recommendedEnd)" :key="'rec:'+group.id+':'+index" class="col-12 col-md-4 slide-fade">
					<div class="card shadow-sm border text-decoration-none text-dark">
						<img v-if="group.metadata && group.metadata.hasOwnProperty('header')" :src="group.metadata.header.url" class="card-img-top" style="width: 100%; height: auto;object-fit: cover;max-height: 160px;">
						<div v-else class="bg-primary" style="width:100%;height:160px;"></div>
						<div class="card-body">
							<div class="lead font-weight-bold d-flex align-items-top" style="height: 60px;">
								{{ group.name }}
								<span v-if="group.verified" class="fa-stack ml-n2 mt-n2">
									<i class="fas fa-circle fa-stack-1x fa-lg" style="color: #22a7f0cc;font-size:18px"></i>
									<i class="fas fa-check fa-stack-1x text-white" style="font-size:10px"></i>
								</span>
							</div>
							<div class="text-muted font-weight-light d-flex justify-content-between">
								<span>{{group.member_count}} Members</span>
							</div>
							<hr>
							<p class="mb-0">
								<a class="btn btn-light btn-block border rounded-lg font-weight-bold" :href="group.url">View Group</a>
							</p>
						</div>
					</div>
					<div v-if="index == 0 && recommended.length > 3 && recommendedStart != 0" style="position: absolute; top: 45%; left: 0px;transform:translateY(-55%);">
						<button class="btn btn-light shadow-lg btn-lg rounded-circle border d-flex align-items-center justify-content-center" style="width: 50px;height:50px;" @click="recommendedPrev()">
							<i class="fas fa-chevron-left fa-lg"></i>
						</button>
					</div>
					<div v-if="index == 2 && recommended.length > 3" style="position: absolute; top: 45%; right: 0px;transform:translateY(-55%);">
						<button class="btn btn-light shadow-lg btn-lg rounded-circle border d-flex align-items-center justify-content-center" style="width: 50px;height:50px;" @click="recommendedNext()">
							<i class="fas fa-chevron-right fa-lg"></i>
						</button>
					</div>
				</div>
			</div>

			<div class="py-2 mb-2">
				<hr>
			</div> -->

			<!-- <div class="px-4 pb-4">
				<p class="h4 font-weight-bold mb-1">Friends' Groups</p>
				<p class="lead text-muted mb-0">Groups your mutuals are in.</p>
			</div> -->

			<!-- <div class="px-4 py-2">
				<hr>
			</div> -->

			<!-- <div class="pb-4">
				<p class="h4 font-weight-bold mb-1">Categories</p>
				<p class="lead text-muted mb-0">Find a group by browsing top categories</p>
			</div>

			<div class="row mb-4">
				<div v-for="(group, index) in categories.slice(categoriesStart, categoriesEnd)" :key="'rec:'+group.id+':'+index" class="col-12 col-md-2 slide-fade">
					<div class="card card-body rounded-lg shadow-sm border text-decoration-none bg-primary p-2 text-white d-flex justify-content-end" style="width: 150px; height:150px;  background: linear-gradient(45deg, #ff512f, #dd2476);">
						<p class="mb-0 font-weight-bold" style="font-size:15px">{{group}}</p>
					</div>
					<div v-if="index == 0 && categories.length > 3 && categoriesStart != 0" style="position: absolute; top: 50%; left: -10px;transform:translateY(-50%);">
						<button class="btn btn-light shadow-lg btn-lg rounded-circle border d-flex align-items-center justify-content-center" style="width: 50px;height:50px;" @click="categoriesPrev()">
							<i class="fas fa-chevron-left fa-lg"></i>
						</button>
					</div>
					<div v-if="index == 5 && categories.length > 3" style="position: absolute; top: 50%; right: -10px;transform:translateY(-50%);">
						<button class="btn btn-light shadow-lg btn-lg rounded-circle border d-flex align-items-center justify-content-center" style="width: 50px;height:50px;" @click="categoriesNext()">
							<i class="fas fa-chevron-right fa-lg"></i>
						</button>
					</div>
				</div>
			</div> -->

			<!-- <div class="py-2 mb-2">
				<hr>
			</div> -->

			<!-- <div class="pb-4 my-4">
				<p class="h4 font-weight-bold mb-1">My Groups</p>
				<p class="lead text-muted mb-0">Groups you are a member of</p>
			</div>

			<div class="row mb-4">
				<div v-for="(group, index) in selfGroups.slice(selfGroupsStart, selfGroupsEnd)" :key="'rec:'+group.id+':'+index" class="col-12 col-md-4 slide-fade">
					<div class="card shadow-sm border text-decoration-none text-dark">
						<img v-if="group.metadata && group.metadata.hasOwnProperty('header')" :src="group.metadata.header.url" class="card-img-top" style="width: 100%; height: auto;object-fit: cover;max-height: 160px;">
						<div v-else class="bg-primary" style="width:100%;height:160px;"></div>
						<div class="card-body">
							<div class="lead font-weight-bold d-flex align-items-top" style="height: 60px;">
								{{ group.name }}
								<span v-if="group.verified" class="fa-stack ml-n2 mt-n2">
									<i class="fas fa-circle fa-stack-1x fa-lg" style="color: #22a7f0cc;font-size:18px"></i>
									<i class="fas fa-check fa-stack-1x text-white" style="font-size:10px"></i>
								</span>
							</div>
							<div class="text-muted font-weight-light d-flex justify-content-between">
								<span>{{group.member_count}} Members</span>
							</div>
							<hr>
							<p class="mb-0">
								<a class="btn btn-light btn-block border rounded-lg font-weight-bold" :href="group.url">View Group</a>
							</p>
						</div>
					</div>
					<div v-if="index == 0 && selfGroups.length > 3 && selfGroupsStart != 0" style="position: absolute; top: 50%; left: -10px;transform:translateY(-50%);">
						<button class="btn btn-light shadow-lg btn-lg rounded-circle border d-flex align-items-center justify-content-center" style="width: 50px;height:50px;" @click="selfGroupsPrev()">
							<i class="fas fa-chevron-left fa-lg"></i>
						</button>
					</div>
					<div v-if="index == 2 && selfGroups.length > 3" style="position: absolute; top: 50%; right: -10px;transform:translateY(-50%);">
						<button class="btn btn-light shadow-lg btn-lg rounded-circle border d-flex align-items-center justify-content-center" style="width: 50px;height:50px;" @click="selfGroupsNext()">
							<i class="fas fa-chevron-right fa-lg"></i>
						</button>
					</div>
				</div>
			</div> -->
		</div>

		<div v-if="tab === 'categories'" class="px-5">
			<div class="row my-4 justify-content-center">
				<div class="col-12 col-md-6">
					<div class="title mb-4">
						<span>Categories</span>
						<button class="btn btn-light font-weight-bold" @click="toggleTab('home')">Go Back</button>
					</div>

					<div class="list-group">
						<div
							v-for="(group, index) in categories"
							:key="'rec:'+group.id+':'+index"
							class="list-group-item"
							@click="selectCategory(index)">
							<p class="mb-0 font-weight-bold">
								{{group}}
								<span class="float-right">
									<i class="fal fa-chevron-right"></i>
								</span>
							</p>
						</div>
					</div>
				</div>
			</div>
		</div>

		<div v-if="tab === 'category'" class="px-5">
			<div class="row my-4 justify-content-center">
				<div class="col-12 col-md-6">
					<div class="title mb-4">
						<div>
							<div class="mb-n2 small text-uppercase text-lighter">Categories</div>
							<span>{{ categories[activeCategoryIndex] }}</span>
						</div>
						<button class="btn btn-light font-weight-bold" @click="toggleTab('categories')">Go Back</button>
					</div>

					<div v-if="categoryGroupsLoaded">
						<div class="list-group">
							<a v-for="(group, index) in categoryGroups"
								class="list-group-item p-1"
								:href="group.url">
								<group-list-card :group="group" :showStats="true" />
							</a>

							<div
								v-if="categoryGroupsCanLoadMore"
								class="list-group-item">

								<button
									class="btn btn-light font-weight-bold btn-block"
									@click="fetchCategoryGroups">
									Load more
								</button>
							</div>
						</div>

						<div v-if="categoryGroups.length === 0" class="mt-3">
							<div class="bg-white border text-center p-3">
								<p class="font-weight-light mb-0">No groups found in this category</p>
							</div>
						</div>
					</div>
					<div v-else>
						<div class="card card-body shadow-none border justify-content-center flex-row">
							<b-spinner />
						</div>
					</div>
				</div>
			</div>
		</div>

		<div v-if="tab === 'fediverseGroups'" class="px-5">
			<div class="row my-4 justify-content-center">
				<div class="col-12 col-md-6">
					<div class="title mb-4">
						<span>Fediverse Groups</span>
						<button class="btn btn-light font-weight-bold" @click="toggleTab('home')">Go Back</button>
					</div>
					<div class="mt-3">
						<div class="bg-white border text-center p-3">
							<p class="font-weight-light mb-0">No fediverse groups found</p>
						</div>
					</div>
				</div>
			</div>
		</div>
	</div>
</template>

<script type="text/javascript">
	import GroupListCard from './GroupListCard.vue';

	export default {
		props: {
			profile: {
				type: Object
			}
		},

		components: {
			"group-list-card": GroupListCard
		},

		data() {
			return {
				isLoaded: false,
				tab: 'home',
				popularGroups: [],
				newGroups: [],
				activeCategoryIndex: undefined,
				activeCategoryPage: 1,
				categories: [],
				categoriesStart: 0,
				categoriesEnd: 6,
				categoryGroups: [],
				categoryGroupsLoaded: false,
				categoryGroupsCanLoadMore: false,
				// selfGroups: [],
				// selfGroupsStart: 0,
				// selfGroupsEnd: 3
			}
		},

		mounted() {
			this.fetchPopular();
			this.fetchCategories();
			// this.fetchSelf();
		},

		methods: {
			fetchPopular() {
				axios.get('/api/v0/groups/discover/popular')
				.then(res => {
					this.popularGroups = res.data;
					this.fetchNew();
				})
			},

			fetchNew() {
				axios.get('/api/v0/groups/discover/new')
				.then(res => {
					this.newGroups = res.data;
				})
			},

			fetchCategories() {
				axios.get('/api/v0/groups/categories/list')
				.then(res => {
					this.categories = res.data;
				})
			},

			toggleTab(tab) {
				window.scrollTo(0, 0);
				this.tab = tab;
			},

			selectCategory(index) {
				window.scrollTo(0, 0);
				if(index !== this.activeCategoryIndex) {
					this.activeCategoryPage = 1;
				}
				this.activeCategoryIndex = index;
				this.fetchCategoryGroups();
			},

			fetchCategoryGroups() {
				if(this.activeCategoryPage == 1) {
					this.categoryGroupsLoaded = false;
				}

				axios.get('/api/v0/groups/category/list', {
					params: {
						name: this.categories[this.activeCategoryIndex],
						page: this.activeCategoryPage
					}
				})
				.then(res => {
					this.tab = 'category';
					if(this.activeCategoryPage == 1) {
						this.categoryGroups = res.data;
					} else {
						this.categoryGroups.push(...res.data);
					}
					if(res.data.length == 6) {
						this.categoryGroupsCanLoadMore = true;
						this.activeCategoryPage++;
					} else {
						this.categoryGroupsCanLoadMore = false;
					}
					setTimeout(() => {
						this.categoryGroupsLoaded = true;
					}, 600);
				})
			}
		}
	}
</script>

<style lang="scss">
	.self-discover-component {
		.list-group-item {
			text-decoration: none;

			&:hover {
				background-color: #F3F4F6;
			}
		}

		.bg-mantle {
			background: linear-gradient(45deg, #24c6dc, #514a9d);
		}

		.bg-black {
			background-color: #000;

			hr {
				border-top: 1px solid rgba(255, 255, 255, 0.12);
			}
		}

		.title {
			display: flex;
			justify-content: space-between;
			align-items: center;

			span {
				font-size: 24px;
				font-weight: 600;
			}

			.btn {
				border: 1px solid #E5E7EB;
			}
		}
	}
</style>
