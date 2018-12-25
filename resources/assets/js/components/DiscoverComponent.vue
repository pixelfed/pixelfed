<template>
<div class="container">
  <!-- <section class="mb-5 section-people">
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
            <button class="btn btn-primary font-weight-bold px-4 py-0" v-on:click="followUser(profile.id, $event)">Follow</button>
          </div>
        </div>
      </div>
    </div>
  </section> -->
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
            </div>
          </a>
        </div>
      </div>
     </div>
  </section>
  <section class="mb-5">
  	<p class="lead text-center">To view more posts, check the <a href="/" class="font-weight-bold">home</a> or <a href="/timeline/public" class="font-weight-bold">local</a> timelines.</p>
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
    
    followUser(id, event) {
      axios.post('/i/follow', {
        item: id
      }).then(res => {
        let el = $(event.target);
        el.addClass('btn-outline-secondary').removeClass('btn-primary');
        el.text('Unfollow');
      }).catch(err => {
        swal(
          'Whoops! Something went wrong...',
          'An error occurred, please try again later.',
          'error'
        );
      });
    },

		fetchData() {
      // axios.get('/api/v2/discover/people')
      // .then((res) => {
      //   let data = res.data;
      //   this.people = data.people;

      //   if(this.people.length > 1) {
      //     $('.section-people .loader').hide();
      //     $('.section-people .row.d-none').removeClass('d-none');
      //   }
      // });

      axios.get('/api/v2/discover/posts')
      .then((res) => {
        let data = res.data;
        this.posts = data.posts;

        if(this.posts.length > 1) {
          $('.section-explore .loader').hide();
          $('.section-explore .row.d-none').removeClass('d-none');
        }
      });
		}
	}
}
</script>