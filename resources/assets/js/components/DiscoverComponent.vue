<template>
<div class="container">

  <section class="d-none d-md-flex mb-md-5 pb-md-3 px-2" style="overflow-x: hidden;" v-if="categories.length > 0">
    <a class="bg-dark rounded d-inline-flex align-items-end justify-content-center mr-3 box-shadow card-disc" href="/discover/personal">
      <p class="text-white font-weight-bold" style="text-shadow: 3px 3px 16px #272634;border-bottom: 2px solid #fff;">For You</p>
    </a>

    <div v-show="categoryCursor > 5" class="text-dark d-inline-flex align-items-center pr-3" v-on:click="categoryPrev()">
      <i class="fas fa-chevron-circle-left fa-lg text-muted"></i>
    </div>

    <a v-for="(category, index) in categories" :key="index+'_cat_'" class="bg-dark rounded d-inline-flex align-items-end justify-content-center mr-3 box-shadow card-disc" :href="category.url" :style="'background: linear-gradient(rgba(0, 0, 0, 0.3),rgba(0, 0, 0, 0.3)),url('+category.thumb+');'">
      <p class="text-white font-weight-bold" style="text-shadow: 3px 3px 16px #272634;">{{category.name}}</p>
    </a>

    <div v-show="allCategories.length != categoryCursor" class="text-dark d-flex align-items-center" v-on:click="categoryNext()">
      <i class="fas fa-chevron-circle-right fa-lg text-muted"></i>
    </div>
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
  .card-disc {
    width:160px;
    height:100px;
    background-size: cover !important;
  }
</style>

<script type="text/javascript">
export default {
	data() {
		return {
			people: {},
			posts: {},
			trending: {},
      categories: {},
      allCategories: {},
      categoryCursor: 5,
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
        swal(
          'Whoops! Something went wrong…',
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
		},

    fetchCategories() {
      axios.get('/api/v2/discover/categories')
        .then(res => {
          this.allCategories = res.data;
          this.categories = _.slice(res.data, 0, 5);
      });
    },

    categoryNext() {
      if(this.categoryCursor > this.allCategories.length - 1) {
        return;
      }
      this.categoryCursor++;
      let cursor = this.categoryCursor;
      let start = cursor - 5;
      let end = cursor;
      this.categories = _.slice(this.allCategories, start, end);
    },

    categoryPrev() {
      if(this.categoryCursor == 5) {
        return;
      }
      this.categoryCursor--;
      let cursor = this.categoryCursor;
      let start = cursor - 5;
      let end = cursor;
      this.categories = _.slice(this.allCategories, start, end);
    },
	}
}
</script>
