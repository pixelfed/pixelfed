<template>
<div class="container">
  <section class="mb-5 section-people">
    <p class="lead text-muted font-weight-bold mb-0">Discover People</p>
    <div class="loader text-center">
    	<div class="lds-ring"><div></div><div></div><div></div><div></div></div>
    </div>
    <div class="row d-none">
      <div class="col-4 p-0 p-sm-2 p-md-3" v-for="profile in people">
        <div class="card card-md-border-0">
          <div class="card-body p-4 text-center">
            <div class="avatar pb-3">
              <a :href="profile.url">
                <img :src="profile.avatar" class="img-thumbnail rounded-circle" width="64px">
              </a>
            </div>
            <p class="lead font-weight-bold mb-0 text-truncate"><a :href="profile.url" class="text-dark">{{profile.username}}</a></p>
            <p class="text-muted text-truncate">{{profile.name}}</p>
            <form class="follow-form" method="post" action="/i/follow" data-id="#" data-action="follow">
              <input type="hidden" name="item" value="#">
              <button class="btn btn-primary font-weight-bold px-4 py-0" type="submit">Follow</button>
            </form>
          </div>
        </div>
      </div>
    </div>
  </section>
  <section class="mb-5 section-explore">
    <p class="lead text-muted font-weight-bold mb-0">Explore</p>
    <div class="profile-timeline">
	    <div class="loader text-center">
	    	<div class="lds-ring"><div></div><div></div><div></div><div></div></div>
	    </div>
      <div class="row d-none">
        <div class="col-4 p-0 p-sm-2 p-md-3" v-for="post in posts">
          <a class="card info-overlay card-md-border-0" :href="post.url">
            <div class="square filter_class">
              <div class="square-content" v-bind:style="{ 'background-image': 'url(' + post.thumb + ')' }"></div>
              <div class="info-overlay-text">
                <h5 class="text-white m-auto font-weight-bold">
                  <span class="pr-4">
                  <span class="far fa-heart fa-lg pr-1"></span> {{post.likes_count}}
                  </span>
                  <span>
                  <span class="far fa-comment fa-lg pr-1"></span> {{post.comments_count}}
                  </span>
                </h5>
              </div>
            </div>
          </a>
        </div>
      </div>
     </div>
  </section>
  <section class="mb-5">
  	<p class="lead text-center">To view more posts, check the <a href="#" class="font-weight-bold">home</a>, <a href="#" class="font-weight-bold">local</a> or <a href="#" class="font-weight-bold">federated</a> timelines.</p>
  </section>
</div>
</template>

<script type="text/javascript">
export default {
	data() {
		return {
			people: {},
			posts: {},
			trending: {}
		}
	},
	mounted() {
		this.fetchData();
	},

	methods: {
		fetchData() {
			axios.get('/api/v2/discover')
			.then((res) => {
				let data = res.data;
				this.people = data.people;
				this.posts = data.posts;

				if(this.people.length > 1) {
					$('.section-people .lds-ring').hide();
					$('.section-people .row.d-none').removeClass('d-none');
				}

				if(this.posts.length > 1) {
					$('.section-explore .lds-ring').hide();
					$('.section-explore .row.d-none').removeClass('d-none');
				}
			});
		}
	}
}
</script>