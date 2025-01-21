<template>
    <div class="my-groups-component">
        <div class="list-container">
            <div v-if="isLoaded">
                <div class="list-group">
                    <a
                        v-for="(group, index) in groups" :key="'rec:'+group.id+':'+index"
                        class="list-group-item text-decoration-none"
                        :href="group.url">
                        <group-list-card
                        	:group="group"
                        	:truncateDescriptionLength="140"
                        	:showStats="true" />
                        <!-- <div class="media align-items-center">
                            <img v-if="group.metadata && group.metadata.hasOwnProperty('header')" :src="group.metadata.header.url" class="mr-3 border rounded" style="width: 100px; height: 60px;object-fit: cover;padding:5px;">

                            <div v-else class="mr-3 border rounded" style="width: 100px; height: 60px;padding: 5px;">
                            	<div class="bg-primary d-flex align-items-center justify-content-center" style="width: 100%; height:100%;">
                            		<i class="fal fa-users text-white fa-lg"></i>
                            	</div>
                            </div>

                            <div class="media-body">
                            	<p class="h5 font-weight-bold mb-1 text-dark">
                            		{{ group.name || 'Untitled Group' }}
                            	</p>

                            	<p class="text-muted small mb-1 read-more">
                            		{{ truncate(group.description) }}
                            	</p>

                            	<p class="mb-0">
                            		<span class="text-muted mr-2">
                            			<i class="far fa-users"></i>
                            			<strong class="small">{{ prettyCount(group.member_count) }}</strong>
                            		</span>

                            		<span class="member-label">
                            			{{ group.self.role }}
                            		</span>

                            		<span v-if="!group.local" class="remote-label ml-2">
                            			<i class="fal fa-globe"></i> Remote
                            		</span>
                            	</p>
                            </div>
                        </div> -->
                    </a>
                </div>

                <p v-if="canLoadMore">
                	<button
                		class="btn btn-primary btn-block font-weight-bold mt-3"
                		@click.prevent="loadMore"
                		:disabled="loadingMore">
                		Load more
                	</button>
                </p>
            </div>

            <div v-else class="d-flex justify-content-center">
            	<b-spinner/>
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
                groups: [],
                canLoadMore: false,
                loadingMore: false,
                page: 1
            }
        },

        mounted() {
            this.fetchSelf();
        },

        methods: {
            fetchSelf() {
                axios.get('/api/v0/groups/self/list')
                .then(res => {
                	let data = res.data.filter(g => {
                		return g.hasOwnProperty('id') && g.hasOwnProperty('url');
                	})
                    this.groups = data;
                    this.canLoadMore = res.data.length == 4;
                    this.page++;
                    this.isLoaded = true;
                });
            },

            loadMore() {
            	this.loadingMore = true;
            	axios.get('/api/v0/groups/self/list', {
            		params: {
            			page: this.page
            		}
            	})
                .then(res => {
                	let data = res.data.filter(g => {
                		return g.hasOwnProperty('id') && g.hasOwnProperty('url');
                	})
                    this.groups.push(...data);
                    this.canLoadMore = res.data.length == 4;
                    this.page++;
            		this.loadingMore = false;
                });
            },

            prettyCount(val) {
            	return App.util.format.count(val);
            },

            truncate(str) {
            	if(str.length <= 140) {
            		return str;
            	}

            	return str.substr(0, 140) + ' ...';
            }
        }
    }
</script>

<style lang="scss" scoped>
    .my-groups-component {
    	.list-container {
    		margin-bottom: 30vh;
    	}

		.member-label {
			padding: 2px 5px;
			font-size: 12px;
			color: rgba(75, 119, 190, 1);
			background:rgba(137, 196, 244, 0.2);
			border:1px solid rgba(137, 196, 244, 0.3);
			font-weight:400;
			text-transform: capitalize;
			border-radius: 3px;
		}

		.remote-label {
			padding: 2px 5px;
			font-size: 12px;
			color: #4B5563;
			background: #F3F4F6;
			border:1px solid #E5E7EB;
			font-weight:400;
			text-transform: capitalize;
			border-radius: 3px;
		}
    }
</style>
