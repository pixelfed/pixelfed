<style scoped>
 span {
  font-size: 14px;
 }
 .comment-text {
 }
 .comment-text p {
  display: inline;
 }
</style>

<template>
<div>
  <div class="postCommentsLoader text-center">
    <div class="lds-ring"><div></div><div></div><div></div><div></div></div> 
  </div>
  <div class="postCommentsContainer d-none">
    <p class="mb-1 text-center load-more-link d-none"><a href="#" class="text-muted" v-on:click="loadMore">Load more comments</a></p>
    <div class="comments" data-min-id="0" data-max-id="0">
      <p v-for="(reply, index) in results" class="mb-0 d-flex justify-content-between align-items-top read-more" style="overflow-y: hidden;">
        <span>
          <a class="text-dark font-weight-bold mr-1" :href="reply.account.url" v-bind:title="reply.account.username">{{truncate(reply.account.username,15)}}</a>
          <span class="text-break" v-html="reply.content"></span>
        </span>
        <span class="pl-2" style="min-width:38px">
          <span v-on:click="likeStatus(reply, $event)"><i v-bind:class="[reply.favourited ? 'fas fa-heart fa-sm text-danger':'far fa-heart fa-sm text-lighter']"></i></span>
            <post-menu :status="reply" :profile="user" :size="'sm'" :modal="'true'" class="d-inline-block pl-2" v-on:deletePost="deleteComment(reply.id, index)"></post-menu>
        </span>
      </p>
    </div>
  </div>
</div>
</template>

<style type="text/css" scoped>
  .text-lighter {
    color:#B8C2CC !important;
  }
  .text-break {
    word-break: break-all !important;
  }
</style>

<script>

export default {
    props: ['post-id', 'post-username', 'user'],
    data() {
        return {
            results: null,
            pagination: {},
            min_id: 0,
            max_id: 0,
            reply_to_profile_id: 0,
          }
    },
    mounted() {
      this.fetchData();
    },
    updated() {
      pixelfed.readmore();
    },
    methods: {
      embed(e) {
          //pixelfed.embed.build(e);
      },
      deleteComment(id, i) {
        axios.post('/i/delete', {
          type: 'comment',
          item: id
        }).then(res => {
          this.results.splice(i, 1);
        }).catch(err => {
          swal('Something went wrong!', 'Please try again later', 'error');
        });
      },
      l(e) {
        let len = e.length;
        if(len < 10) { return e; } 
        return e.substr(0, 10)+'...';
      },
      reply(e) {
          this.reply_to_profile_id = e.account.id;
          $('.comment-form input[name=comment]').val('@'+e.account.username+' ');
          $('.comment-form input[name=comment]').focus();
      },
      fetchData() {
          let url = '/api/v2/comments/'+this.postUsername+'/status/'+this.postId;
          axios.get(url)
            .then(response => {
                let self = this;
                this.results = _.reverse(response.data.data);
                this.pagination = response.data.meta.pagination;
                if(this.results.length > 0) {
                  $('.load-more-link').removeClass('d-none');
                }
                $('.postCommentsLoader').addClass('d-none');
                $('.postCommentsContainer').removeClass('d-none');
            }).catch(error => {
              if(!error.response) {
                $('.postCommentsLoader .lds-ring')
                  .attr('style','width:100%')
                  .addClass('pt-4 font-weight-bold text-muted')
                  .text('An error occurred, cannot fetch comments. Please try again later.');
              } else {
                switch(error.response.status) {
                  case 401:
                    $('.postCommentsLoader .lds-ring')
                      .attr('style','width:100%')
                      .addClass('pt-4 font-weight-bold text-muted')
                      .text('Please login to view.');
                  break;

                  default:
                    $('.postCommentsLoader .lds-ring')
                      .attr('style','width:100%')
                      .addClass('pt-4 font-weight-bold text-muted')
                      .text('An error occurred, cannot fetch comments. Please try again later.');
                  break;
                }
              }
            });
      },
      loadMore(e) {
          e.preventDefault();
          if(this.pagination.total_pages == 1 || this.pagination.current_page == this.pagination.total_pages) {
            $('.load-more-link').addClass('d-none');
            return;
          }
          $('.postCommentsLoader').removeClass('d-none');
          let next = this.pagination.links.next;
          axios.get(next)
            .then(response => {
                let self = this;
                let res =  response.data.data;
                $('.postCommentsLoader').addClass('d-none');
                for(let i=0; i < res.length; i++) {
                  this.results.unshift(res[i]);
                }
                this.pagination = response.data.meta.pagination;
            });
      },
      likeStatus(status, $event) {
        if($('body').hasClass('loggedIn') == false) {
          return;
        }
        
        axios.post('/i/like', {
          item: status.id
        }).then(res => {
          status.favourites_count = res.data.count;
          if(status.favourited == true) {
            status.favourited = false;
          } else {
            status.favourited = true;
          }
        }).catch(err => {
          swal('Error', 'Something went wrong, please try again later.', 'error');
        });
      },
      truncate(str,lim) {
        return _.truncate(str,{
          length: lim
        });
      }
    },

}
</script>