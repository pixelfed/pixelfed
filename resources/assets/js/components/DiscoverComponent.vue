<template>
<div class="container">

  <section class="d-none d-md-flex mb-md-2 pt-2 discover-bar" style="width:auto; overflow: auto hidden;" v-if="categories.length > 0">
    <a class="text-decoration-none bg-transparent border border-success rounded d-inline-flex align-items-center justify-content-center mr-3 card-disc" href="/discover/loops">
      <p class="text-success lead font-weight-bold mb-0">Loops</p>
    </a>
    <!-- <a class="text-decoration-none rounded d-inline-flex align-items-center justify-content-center mr-3 box-shadow card-disc" href="/discover/personal" style="background: rgb(255, 95, 109);">
      <p class="text-white lead font-weight-bold mb-0">For You</p>
    </a> -->

    <a v-for="(category, index) in categories" :key="index+'_cat_'" class="bg-dark rounded d-inline-flex align-items-end justify-content-center mr-3 box-shadow card-disc" :href="category.url" :style="'background: linear-gradient(rgba(0, 0, 0, 0.3),rgba(0, 0, 0, 0.3)),url('+category.thumb+');'">
      <p class="text-white font-weight-bold" style="text-shadow: 3px 3px 16px #272634;">{{category.name}}</p>
    </a>

  </section>
  <section class="mb-5 section-explore">
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

<style type="text/css" scoped>
  .discover-bar::-webkit-scrollbar { 
      display: none; 
  }
  .card-disc {
    flex: 0 0 160px;
    width:160px;
    height:100px;
    background-size: cover !important;
  }
</style>

<script type="text/javascript">
export default {
	data() {
		return {
			posts: {},
			trending: {},
      categories: {},
      allCategories: {},
		}
	},
	mounted() {
    this.fetchData();
    this.fetchCategories();
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
        if(err.response.data.message) {
          swal('Error', err.response.data.message, 'error');
        }
      });
    },

		fetchData() {
      axios.get('/api/v2/discover/posts')
      .then((res) => {
        let data = res.data;
        this.posts = data.posts;

        if(this.posts.length > 1) {
          $('.section-explore .loader').hide();
          $('.section-explore .row.d-none').removeClass('d-none');
        }
      });
		},

    fetchCategories() {
      axios.get('/api/v2/discover/categories')
        .then(res => {
          this.allCategories = res.data;
          this.categories = res.data;
      });
    },
	}
}
</script>
