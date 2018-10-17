<style type="text/css">
.b-dropdown > button {
  padding:0 !important;
}
</style>
<style scoped>
 span {
  font-size: 14px;
 }
 .comment-text {
  word-break: break-all;
 }
 .b-dropdown {
    padding:0 !important;
 }
.b-dropdown < button {
 }
 .lds-ring {
  display: inline-block;
  position: relative;
  width: 64px;
  height: 64px;
}
.lds-ring div {
  box-sizing: border-box;
  display: block;
  position: absolute;
  width: 51px;
  height: 51px;
  margin: 6px;
  border: 6px solid #6c757d;
  border-radius: 50%;
  animation: lds-ring 1.2s cubic-bezier(0.5, 0, 0.5, 1) infinite;
  border-color: #6c757d transparent transparent transparent;
}
.lds-ring div:nth-child(1) {
  animation-delay: -0.45s;
}
.lds-ring div:nth-child(2) {
  animation-delay: -0.3s;
}
.lds-ring div:nth-child(3) {
  animation-delay: -0.15s;
}
@keyframes lds-ring {
  0% {
    transform: rotate(0deg);
  }
  100% {
    transform: rotate(360deg);
  }
}

</style>

<template>
<div>
  <div class="lwrapper text-center">
    <div class="lds-ring"><div></div><div></div><div></div><div></div></div> 
  </div>
  <div class="cwrapper d-none">
    <p class="mb-1 text-center load-more-link"><a href="#" class="text-muted" v-on:click="loadMore">Load more comments</a></p>
    <div class="comments" data-min-id="0" data-max-id="0">
      <p class="mb-2 d-flex justify-content-between align-items-center" v-for="(comment, index) in results" :data-id="comment.id" v-bind:key="comment.id">
        <span class="pr-3">
          <span class="font-weight-bold pr-1"><bdi><a class="text-dark" :href="comment.account.url">{{comment.account.username}}</a></bdi></span>
          <span class="comment-text" v-html="comment.content"></span>
        </span>
        <b-dropdown :id="comment.uri" variant="link" no-caret class="float-right">
          <template slot="button-content">
              <i class="fas fa-ellipsis-v text-muted"></i><span class="sr-only">Options</span>
          </template>
          <b-dropdown-item class="font-weight-bold" v-on:click="reply(comment)">Reply</b-dropdown-item>
          <b-dropdown-item class="font-weight-bold" :href="comment.url">Permalink</b-dropdown-item>
          <b-dropdown-item class="font-weight-bold" v-on:click="embed(comment)">Embed</b-dropdown-item>
          <b-dropdown-item class="font-weight-bold" :href="comment.account.url">Profile</b-dropdown-item>
          <b-dropdown-divider></b-dropdown-divider>
          <b-dropdown-item class="font-weight-bold" :href="'/i/report?type=post&id='+comment.id">Report</b-dropdown-item>
        </b-dropdown>
      </p>
    </div>
  </div>
</div>
</template>

<script>
export default {
    props: ['post-id', 'post-username'],
    data() {
        return {
            results: {},
            pagination: {},
            min_id: 0,
            max_id: 0,
            reply_to_profile_id: 0,
          }
    },
    mounted() {
      this.fetchData();
    },
    methods: {
      embed(e) {
          pixelfed.embed.build(e);
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
                $('.lwrapper').addClass('d-none');
                $('.cwrapper').removeClass('d-none');
            }).catch(error => {
                $('.lds-ring').attr('style','width:100%').addClass('pt-4 font-weight-bold text-muted').text('An error occured, cannot fetch comments. Please try again later.');
                console.log(error);
            });
      },
      loadMore(e) {
          e.preventDefault();
          if(this.pagination.total_pages == 1 || this.pagination.current_page == this.pagination.total_pages) {
            $('.load-more-link').addClass('d-none');
            return;
          }
          $('.cwrapper').addClass('d-none');
          $('.lwrapper').removeClass('d-none');
          let next = this.pagination.links.next;
          axios.get(next)
            .then(response => {
                let self = this;
                let res =  response.data.data;
                $('.lwrapper').addClass('d-none');
                for(let i=0; i < res.length; i++) {
                  this.results.unshift(res[i]);
                }
                $('.cwrapper').removeClass('d-none');
                this.pagination = response.data.meta.pagination;
            });
      }
    }
}
</script>