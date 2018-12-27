<style>
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
      <p class="mb-1" v-for="(comment, index) in results" :data-id="comment.id" :key="comment.id">
        <span class="d-flex justify-content-between align-items-center">
          <span class="pr-3" style="overflow: hidden;">
            <div class="font-weight-bold pr-1"><bdi><a class="text-dark" :href="comment.account.url" :title="comment.account.username">{{l(comment.account.username)}}</a></bdi>
            </div>
            <div class="read-more" style="overflow: hidden;" :id="comment.id + '-reply-readmore'">
              <span class="comment-text" v-html="comment.content" style="overflow: hidden;"></span>
            </div>
          </span>
          <b-dropdown :id="comment.uri" variant="link" no-caret right class="float-right">
            <template slot="button-content">
                <i class="fas fa-ellipsis-v text-muted"></i><span class="sr-only">Options</span>
            </template>
            <b-dropdown-item class="font-weight-bold" v-on:click="reply(comment)">Reply</b-dropdown-item>
            <b-dropdown-item class="font-weight-bold" :href="comment.url">Permalink</b-dropdown-item>
            <!-- <b-dropdown-item class="font-weight-bold" v-on:click="embed(comment)">Embed</b-dropdown-item> -->
            <b-dropdown-item class="font-weight-bold" :href="comment.account.url">Profile</b-dropdown-item>
            <b-dropdown-divider></b-dropdown-divider>
            <b-dropdown-item class="font-weight-bold" :href="'/i/report?type=post&id='+comment.id">Report</b-dropdown-item>
            <b-dropdown-item class="font-weight-bold" v-on:click="deleteComment(comment.id, index)" v-if="comment.account.id == user.id">Delete</b-dropdown-item>
          </b-dropdown>
        </span>
      </p>
    </div>
  </div>
</div>
</template>

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
                this.results = response.data.data;
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
      }
    }
}
</script>