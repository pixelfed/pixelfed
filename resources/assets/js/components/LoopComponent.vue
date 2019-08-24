<template>
	<div class="w-100 h-100">
		<div v-if="loading" style="min-height: 400px;" class="d-flex justify-content-center align-items-center">
			<img src="/img/pixelfed-icon-grey.svg" class="">
		</div>
		<div v-else>
			<div class="mb-4">
				<p class="text-center">
					<!-- <a :class="[tab == 'popular'? 'btn font-weight-bold py-0 btn-success' : 'btn font-weight-bold py-0 btn-outline-success']" href="#" @click.prevent="setTab('popular')">Popular</a> -->
					<a :class="[tab == 'new'? 'btn font-weight-bold py-0 btn-success' : 'btn font-weight-bold py-0 btn-outline-success']" href="#" @click.prevent="setTab('new')">New</a>
					<!-- <a :class="[tab == 'random'? 'btn font-weight-bold py-0 btn-success' : 'btn font-weight-bold py-0 btn-outline-success']" href="#" @click.prevent="setTab('random')">Random</a> -->
					<a :class="[tab == 'about'? 'btn font-weight-bold py-0 btn-success' : 'btn font-weight-bold py-0 btn-outline-success']" href="#" @click.prevent="setTab('about')">About</a>
				</p>
			</div>
			<div v-if="tab != 'about'" class="row loops-container">
				<div class="col-12 col-md-4 mb-3" v-for="(loop, index) in loops">
					<div class="card border border-success">
						<div class="embed-responsive embed-responsive-1by1">
							<video class="embed-responsive-item" :src="videoSrc(loop)" preload="none" width="100%" height="100%" loop @click="toggleVideo(loop, $event)" :poster="posterSrc(loop)"></video>
						</div>
						<div class="card-body">
							<p class="username font-weight-bolder lead d-flex justify-content-between">
								<a :href="loop.account.url" :title="loop.account.acct">{{truncate(loop.account.acct)}}</a> 
								<a :href="loop.url">{{timestamp(loop)}}</a></p>
							<p class="small text-muted text-truncate" v-html="getTitle(loop)"></p>
							<div class="small text-muted d-flex justify-content-between mb-0">
								<span>{{loop.favourites_count}} Likes</span>
								<span>{{loop.reblogs_count}} Shares</span>
								<span>{{loop.reply_count}} Comments</span>
							</div>
						</div>
					</div>
				</div>
			</div>
			<div v-else class="col-12">
				<div class="card">
					<div class="card-body">
						<p class="lead text-center mb-0">Loops are an exciting new way to explore short videos on Pixelfed.</p>
					</div>
				</div>
			</div>
		</div>
	</div>
</template>

<style type="text/css">
	.loops-container .card {
		box-shadow: none;
	}
	.loops-container .card .card-img-top{
		border-radius: 0;
	}
	.loops-container a {
		color: #343a40;
	}
	a.hashtag,
	.loops-container .card-body a:hover {
		color: #28a745 !important;
	}
</style>

<script type="text/javascript">
Object.defineProperty(HTMLMediaElement.prototype, 'playing', {
    get: function(){
        return !!(this.currentTime > 0 && !this.paused && !this.ended && this.readyState > 2);
    }
})
export default {
	data() {
		return {
			loading: true,
			version: 1,
			loops: [],
			tab: 'new'
		}
	},

	mounted() {
		axios.get('/api/v2/loops')
			.then(res => {
				this.loops = res.data;
				this.loading = false;
			})
	}, 

	methods: {
		videoSrc(loop) {
			return loop.media_attachments[0].url;
		},
		posterSrc(loop) {
			return loop.media_attachments[0].preview_url;
		},
		setTab(tab) {
			this.tab = tab;
		},
		toggleVideo(loop, $event) {
			let el = $event.target;
			$('video').each(function() {
				if(el.src != $(this)[0].src) {
					$(this)[0].pause();
				}
			});
			if(!el.playing) {
				el.play();
				//this.incrementLoop(loop);
			} else {
				el.pause();
			}
		},
		incrementLoop(loop) {
			// axios.post('/api/v2/loops/watch', {
			// 	id: loop.id
			// }).then(res => {
			// 	console.log(res.data);
			// });
		},
		timestamp(loop) {
			let ts = new Date(loop.created_at);
			return ts.toLocaleDateString();
		},
		getTitle(loop) {
			let content = loop.content ? loop.content : 'Untitled';
			return content.trim();
		},
		truncate(str, len = 15) {
			return _.truncate(str, {length: len});
		}
	}
}
</script>