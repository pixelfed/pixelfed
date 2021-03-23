<template>
<div>
	<transition name="fade">
		<div v-if="announcements.length" class="card border shadow-none mb-3">
			<div class="card-header text-muted bg-white">
				<i class="fas fa-bullhorn mr-2"></i> <span class="text-weight-light">COMUNICADOS</span>
				<span class="float-right cursor-pointer" title="Close" @click="close"><i class="fas fa-times text-lighter"></i></span>
			</div>
			<div class="card-body">
				<div class="card-title mb-0">
					<span class="font-weight-bold">{{announcement.title}}</span>
				</div>
				<p class="card-text">
					<span style="font-size:13px;">{{announcement.summary}}</span>
				</p>
				<p class="d-flex align-items-center justify-content-between mb-0">
					<a v-if="announcement.url" :href="announcement.url" class="small font-weight-bold mb-0">Ver mais</a>
					<span v-else></span>
					<span>
						<span :class="[showPrev ? 'btn btn-outline-secondary btn-sm py-0':'btn btn-outline-secondary btn-sm py-0 disabled']" :disabled="showPrev == false" @click="loadPrev()">
							<i class="fas fa-chevron-left fa-sm"></i>
						</span>
						<span class="btn btn-outline-success btn-sm py-0 mx-1" title="Mark as Read" data-toggle="tooltip" data-placement="bottom" @click="markAsRead()">
							<i class="fas fa-check fa-sm"></i>
						</span>
						<span :class="[showNext ? 'btn btn-outline-secondary btn-sm py-0':'btn btn-outline-secondary btn-sm py-0 disabled']" :disabled="showNext == false" @click="loadNext()">
							<i class="fas fa-chevron-right fa-sm"></i>
						</span>
					</span>
				</p>
			</div>
		</div>
	</transition>
</div>
</template>

<style type="text/css" scoped>
.fade-enter-active, .fade-leave-active {
  transition: opacity .5s;
}
.fade-enter, .fade-leave-to {
  opacity: 0;
}
</style>

<script type="text/javascript">
export default {
	data() {
		return {
			announcements: [],
			announcement: {},
			cursor: 0,
			showNext: true,
			showPrev: false
		}
	},

	mounted() {
		this.fetchAnnouncements();
	},

	updated() {
		$('[data-toggle="tooltip"]').tooltip()
	},

	methods: {
		fetchAnnouncements() {
			let self = this;
			let key = 'metro-tips-closed';
			let cached = JSON.parse(window.localStorage.getItem(key));
			axios.get('/api/pixelfed/v1/newsroom/timeline')
			.then(res => {
				self.announcements = res.data.filter(p => {
					if(cached) {
						return cached.indexOf(p.id) == -1;
					} else {
						return true;
					}
				});
				self.announcement = self.announcements[0]
				if(self.announcements.length == 1) {
					self.showNext = false;
				}
			})
		},

		loadNext() {
			if(!this.showNext) {
				return;
			}
			this.cursor += 1;
			this.announcement = this.announcements[this.cursor];
			if((this.cursor + 1) == this.announcements.length) {
				this.showNext = false;
			}
			if(this.cursor >= 1) {
				this.showPrev = true;
			}
		},

		loadPrev() {
			if(!this.showPrev) {
				return;
			}
			this.cursor -= 1;
			this.announcement = this.announcements[this.cursor];
			if(this.cursor == 0) {
				this.showPrev = false;
			}
			if(this.cursor < this.announcements.length) {
				this.showNext = true;
			}
		},

		closeNewsroomPost(id, index) {
			let key = 'metro-tips-closed';
			let ctx = [];
			let cached = window.localStorage.getItem(key);
			if(cached) {
				ctx = JSON.parse(cached);
			}
			ctx.push(id);
			window.localStorage.setItem(key, JSON.stringify(ctx));
			this.newsroomPosts = this.newsroomPosts.filter(res => {
				return res.id !== id
			});
			if(this.newsroomPosts.length == 0) {
				this.showTips = false;
			} else {
				this.newsroomPost = [ this.newsroomPosts[0] ];
			}
		},

		close() {
			window.localStorage.setItem('metro-tips', false);
			this.$emit('show-tips', false);
		},

		markAsRead() {
			let vm = this;
			axios.post('/api/pixelfed/v1/newsroom/markasread', {
				id: this.announcement.id
			})
			.then(res => {
				let cur = vm.cursor;
				vm.announcements.splice(cur, 1);
				vm.announcement = vm.announcements[0];
				vm.cursor = 0;
				vm.showPrev = false;
				vm.showNext = vm.announcements.length > 1;
			})
			.catch(err => {
				swal('Oops, Something went wrong', 'There was a problem with your request, please try again later.', 'error');
			});
		}
	}
}
</script>
