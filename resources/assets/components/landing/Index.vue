<template>
	<div class="landing-index-component">
		<section class="page-wrapper">
			<div class="container container-compact">
				<div class="card bg-bluegray-900" style="border-radius: 10px;">
					<div class="card-header bg-bluegray-800 nav-menu" style="border-top-left-radius: 10px; border-top-right-radius: 10px;">
						<ul class="nav justify-content-around">
						  <li class="nav-item">
							<router-link to="/" class="nav-link">About</router-link>
							</li>
							<li v-if="config.show_directory" class="nav-item">
								<router-link to="/web/directory" class="nav-link">Directory</router-link>
							</li>
							<li v-if="config.show_explore_feed" class="nav-item">
								<router-link to="/web/explore" class="nav-link">Explore</router-link>
							</li>
						</ul>
					</div>

					<div class="card-img-top p-2">
						<img
							:src="config.about.banner_image"
							class="img-fluid rounded"
							style="width: 100%;max-height: 200px;object-fit: cover;"
							alt="Server banner image"
							height="200"
							onerror="this.src='/storage/headers/default.jpg';this.onerror=null;">
					</div>

					<div class="card-body">
						<div class="server-header">
							<p class="server-header-domain">{{ config.domain }}</p>
							<p class="server-header-attribution">
								Decentralized photo sharing social media powered by <a href="https://pixelfed.org" target="_blank">Pixelfed</a>
							</p>
						</div>

						<div class="server-stats">
							<div class="list-group">
								<div class="list-group-item bg-transparent">
									<p class="stat-value">{{ formatCount(config.stats.posts_count) }}</p>
									<p class="stat-label">Posts</p>
								</div>
								<div class="list-group-item bg-transparent">
									<p class="stat-value">{{ formatCount(config.stats.active_users) }}</p>
									<p class="stat-label">Active Users</p>
								</div>
								<div class="list-group-item bg-transparent">
									<p class="stat-value">{{ formatCount(config.stats.total_users) }}</p>
									<p class="stat-label">Total Users</p>
								</div>
							</div>
						</div>

						<div class="server-admin">
							<div class="list-group">
								<div v-if="config.contact.account" class="list-group-item bg-transparent">
									<p class="item-label">Managed By</p>
									<a :href="config.contact.account.url" class="admin-card" target="_blank">
										<div class="d-flex">
											<img
												:src="config.contact.account.avatar"
												width="45"
												height="45"
												class="avatar"
												:alt="`${config.contact.account.username}'s avatar`"
												onerror="this.src='/storage/avatars/default.jpg';this.onerror=null;"
												>

											<div class="user-info">
												<p class="display-name">{{ config.contact.account.display_name }}</p>
												<p class="username">&commat;{{ config.contact.account.username }}</p>
											</div>
										</div>
									</a>
								</div>

								<div v-if="config.contact.email" class="list-group-item bg-transparent">
									<p class="item-label">Contact</p>
									<a :href="`mailto:${config.contact.email}?subject=Regarding ${config.domain}`" class="admin-email" target="_blank">{{ config.contact.email }}</a>
								</div>
							</div>
						</div>

						<div class="accordion" id="accordion">
						  <div class="card bg-bluegray-700">
						    <div class="card-header bg-bluegray-800" id="headingOne">
						      <h2 class="mb-0">
						        <button class="btn btn-link btn-block" type="button" data-toggle="collapse" data-target="#collapseOne" aria-controls="collapseOne" @click="toggleAccordion(0)">
						        	<span class="text-white h5">
							        	<i class="far fa-info-circle mr-2 text-muted"></i>
							          	About
						        	</span>
						        	<i class="far" :class="[ accordionTab === 0 ? 'fa-chevron-left text-primary': 'fa-chevron-down']"></i>
						        </button>
						      </h2>
						    </div>

						    <div id="collapseOne" class="collapse" aria-labelledby="headingOne" data-parent="#accordion">
						      <div class="card-body about-text">
						        <p v-html="config.about.description"></p>
						      </div>
						    </div>
						  </div>

						  <div class="card bg-bluegray-700">
						    <div class="card-header bg-bluegray-800" id="headingTwo">
						      <h2 class="mb-0">
						        <button class="btn btn-link btn-block text-left collapsed" type="button" data-toggle="collapse" data-target="#collapseTwo" aria-expanded="false" aria-controls="collapseTwo" @click="toggleAccordion(1)">
						        	<span class="text-white h5">
							        	<i class="far fa-list mr-2 text-muted"></i>
						          		Server Rules
						          	</span>
						        	<i class="far" :class="[ accordionTab === 1 ? 'fa-chevron-left text-primary': 'fa-chevron-down']"></i>
						        </button>
						      </h2>
						    </div>
						    <div id="collapseTwo" class="collapse" aria-labelledby="headingTwo" data-parent="#accordion">
						      <div class="card-body">
						        <div class="list-group list-group-rules">
						        	<div v-for="rule in config.rules" class="list-group-item bg-bluegray-900">
						        		<div class="rule-id">{{ rule.id }}</div>
						        		<div class="rule-text">{{ rule.text }}</div>
						        	</div>
						        </div>
						      </div>
						    </div>
						  </div>

						  <div class="card bg-bluegray-700">
						    <div class="card-header bg-bluegray-800" id="headingThree">
						      <h2 class="mb-0">
						        <button class="btn btn-link btn-block text-left collapsed" type="button" data-toggle="collapse" data-target="#collapseThree" aria-expanded="false" aria-controls="collapseThree" @click="toggleAccordion(2)">
						        	<span class="text-white h5">
							        	<i class="far fa-sparkles mr-2 text-muted"></i>
						          		Supported Features
						          	</span>
						        	<i class="far" :class="[ accordionTab === 2 ? 'fa-chevron-left text-primary': 'fa-chevron-down']"></i>
						        </button>
						      </h2>
						    </div>
						    <div id="collapseThree" class="collapse" aria-labelledby="headingThree" data-parent="#accordion">
						      <div class="card-body card-features">
						      	<div class="card-features-cloud">
						      		<div class="badge badge-success"><i class="far fa-check-circle"></i> Photo Posts</div>
						      		<div class="badge badge-success"><i class="far fa-check-circle"></i> Photo Albums</div>
						      		<div class="badge badge-success"><i class="far fa-check-circle"></i> Photo Filters</div>
						      		<div class="badge badge-success"><i class="far fa-check-circle"></i> Collections</div>
						      		<div class="badge badge-success"><i class="far fa-check-circle"></i> Comments</div>
						      		<div class="badge badge-success"><i class="far fa-check-circle"></i> Hashtags</div>
						      		<div class="badge badge-success"><i class="far fa-check-circle"></i> Likes</div>
						      		<div class="badge badge-success"><i class="far fa-check-circle"></i> Notifications</div>
						      		<div class="badge badge-success"><i class="far fa-check-circle"></i> Shares</div>
						      	</div>

						      	<div class="py-3">
						      		<p class="lead">
						      			<span>You can share up to <span class="font-weight-bold">{{ config.uploader.album_limit }}</span> photos*</span>
						      			<span v-if="config.features.video">or <span class="font-weight-bold">1</span> video*</span>
						      			<span>at a time with a max caption length of <span class="font-weight-bold">{{ config.uploader.max_caption_length }}</span> characters.</span>
									</p>
									<p class="small opacity-50">* - Maximum file size is {{ formatBytes(config.uploader.max_photo_size) }}</p>
						      	</div>

						        <div class="list-group list-group-features">
						        	<div class="list-group-item bg-bluegray-900">
						        		<div class="feature-label">Federation</div>
						        		<i class="far fa-lg" :class="[config.features.federation ? 'fa-check-circle' : 'fa-times-circle' ]"></i>
						        	</div>

						        	<div class="list-group-item bg-bluegray-900">
						        		<div class="feature-label">Mobile App Support</div>
						        		<i class="far fa-lg" :class="[config.features.mobile_apis ? 'fa-check-circle' : 'fa-times-circle' ]"></i>
						        	</div>

						        	<div class="list-group-item bg-bluegray-900">
						        		<div class="feature-label">Stories</div>
						        		<i class="far fa-lg" :class="[config.features.stories ? 'fa-check-circle' : 'fa-times-circle' ]"></i>
						        	</div>

						        	<div class="list-group-item bg-bluegray-900">
						        		<div class="feature-label">Videos</div>
						        		<i class="far fa-lg" :class="[config.features.video ? 'fa-check-circle' : 'fa-times-circle' ]"></i>
						        	</div>
						        </div>
						      </div>
						    </div>
						  </div>
						</div>
					</div>
				</div>
			</div>

			<footer-component />
		</section>
	</div>
</template>

<script type="text/javascript">
	export default {
		data() {
			return {
				config: window.pfl,
				accordionTab: undefined
			}
		},

		methods: {
			toggleAccordion(idx) {
				if(this.accordionTab == idx) {
					this.accordionTab = undefined;
					return;
				}
				this.accordionTab = idx;
			},

			formatCount(val) {
				if(!val) {
					return 0;
				}

				return val.toLocaleString('en-CA', { compactDisplay: "short", notation: "compact"});
			},

			formatBytes(bytes, unit = 'megabyte') {
				const units = ['byte', 'kilobyte', 'megabyte', 'gigabyte', 'terabyte'];
				const navigatorLocal = navigator.languages && navigator.languages.length >= 0 ? navigator.languages[0] : 'en-US';
				const unitIndex = Math.max(0, Math.min(Math.floor(Math.log(bytes) / Math.log(1024)), units.length - 1));
				return Intl.NumberFormat(navigatorLocal, {
				    style: 'unit',
					unit : units[unitIndex],
					useGrouping: false,
					maximumFractionDigits: 0,
					roundingMode: 'ceil'
				}).format(bytes / (1024 ** unitIndex))
			}
		}
	}
</script>
