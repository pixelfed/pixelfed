<style scoped>
.status-comments,
.reactions,
.col-md-4 {
  background: #fff;
}
.postPresenterContainer {
  background: #fff;
}
@media(min-width: 720px) {
  .postPresenterContainer {
    min-height: 600px;
  }
}
</style>
<template>
<div class="postComponent d-none">
  <div class="container px-0">
    <div class="card card-md-rounded-0 status-container orientation-unknown">
      <div class="row px-0 mx-0">
      <div class="d-flex d-md-none align-items-center justify-content-between card-header bg-white w-100">
        <a :href="statusProfileUrl" class="d-flex align-items-center status-username text-truncate" data-toggle="tooltip" data-placement="bottom" :title="statusUsername">
          <div class="status-avatar mr-2">
            <img :src="statusAvatar" width="24px" height="24px" style="border-radius:12px;">
          </div>
          <div class="username">
            <span class="username-link font-weight-bold text-dark">{{ statusUsername }}</span>
          </div>
        </a>
        <div v-if="user != false" class="float-right">
          <div class="post-actions">
          <div class="dropdown">
            <button class="btn btn-link text-dark no-caret dropdown-toggle" type="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false" title="Post options">
            <span class="fas fa-ellipsis-v text-muted"></span>
            </button>
            <div class="dropdown-menu dropdown-menu-right" aria-labelledby="dropdownMenuButton">
                <div v-if="!owner()">
                  <a class="dropdown-item font-weight-bold" :href="reportUrl()">Report</a>
                  <a class="dropdown-item font-weight-bold" v-on:click="muteProfile()">Mute Profile</a>
                  <a class="dropdown-item font-weight-bold" v-on:click="blockProfile()">Block Profile</a>
                </div>
                <div v-if="ownerOrAdmin()">
                  <!-- <a class="dropdown-item font-weight-bold" :href="editUrl()">Disable Comments</a> -->
                  <a class="dropdown-item font-weight-bold" :href="editUrl()">Edit</a>
                  <a class="dropdown-item font-weight-bold text-danger" v-on:click="deletePost(status)">Delete</a>
                </div>
              </div>
            </div>
          </div>
        </div>
       </div>
        <div class="col-12 col-md-8 px-0 mx-0">
            <div class="postPresenterLoader text-center">
              <div class="lds-ring"><div></div><div></div><div></div><div></div></div>
            </div>
            <div class="postPresenterContainer d-none d-flex justify-content-center align-items-center">
              <div v-if="status.pf_type === 'photo'" class="w-100">
                <photo-presenter :status="status" v-on:lightbox="lightbox"></photo-presenter>
              </div>

              <div v-else-if="status.pf_type === 'video'" class="w-100">
                <video-presenter :status="status"></video-presenter>
              </div>

              <div v-else-if="status.pf_type === 'photo:album'" class="w-100">
                <photo-album-presenter :status="status" v-on:lightbox="lightbox"></photo-album-presenter>
              </div>

              <div v-else-if="status.pf_type === 'video:album'" class="w-100">
                <video-album-presenter :status="status"></video-album-presenter>
              </div>

              <div v-else-if="status.pf_type === 'photo:video:album'" class="w-100">
                <mixed-album-presenter :status="status" v-on:lightbox="lightbox"></mixed-album-presenter>
              </div>

              <div v-else class="w-100">
                <p class="text-center p-0 font-weight-bold text-white">Error: Problem rendering preview.</p>
              </div>
            </div>
        </div>

        <div class="col-12 col-md-4 px-0 d-flex flex-column border-left border-md-left-0">
          <div class="d-md-flex d-none align-items-center justify-content-between card-header py-3 bg-white">
            <a :href="statusProfileUrl" class="d-flex align-items-center status-username text-truncate" data-toggle="tooltip" data-placement="bottom" :title="statusUsername">
              <div class="status-avatar mr-2">
                <img :src="statusAvatar" width="24px" height="24px" style="border-radius:12px;">
              </div>
              <div class="username">
                <span class="username-link font-weight-bold text-dark">{{ statusUsername }}</span>
              </div>
            </a>
              <div class="float-right">
                <div class="post-actions">
                <div class="dropdown">
                  <button class="btn btn-link text-dark no-caret dropdown-toggle" type="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false" title="Post options">
                  <span class="fas fa-ellipsis-v text-muted"></span>
                  </button>
                      <div class="dropdown-menu dropdown-menu-right" aria-labelledby="dropdownMenuButton">
                        <span class="menu-user d-none">
                          <a class="dropdown-item font-weight-bold" :href="reportUrl()">Report</a>
                          <a class="dropdown-item font-weight-bold" v-on:click="muteProfile">Mute Profile</a>
                          <a class="dropdown-item font-weight-bold" v-on:click="blockProfile">Block Profile</a>
                        </span>
                        <span class="menu-author d-none">
                          <!-- <a class="dropdown-item font-weight-bold" :href="editUrl()">Mute Comments</a>
                          <a class="dropdown-item font-weight-bold" :href="editUrl()">Disable Comments</a> -->
                          <a class="dropdown-item font-weight-bold" :href="editUrl()">Edit</a>
                          <a class="dropdown-item font-weight-bold text-danger" v-on:click="deletePost">Delete</a>
                        </span>
                      </div>
                  </div>
                </div>
              </div>
          </div>
          <div class="d-flex flex-md-column flex-column-reverse h-100">
            <div class="card-body status-comments">
              <div class="status-comment">
                <p class="mb-1 read-more" style="overflow: hidden;">
                  <span class="font-weight-bold pr-1">{{statusUsername}}</span>
                  <span class="comment-text" :id="status.id + '-status-readmore'" v-html="status.content"></span>
                </p>
                <post-comments :user="this.user" :post-id="statusId" :post-username="statusUsername"></post-comments>
              </div>
            </div>
            <div class="card-body flex-grow-0 py-1">
              <div class="reactions my-1">
                <h3 v-bind:class="[reactions.liked ? 'fas fa-heart text-danger pr-3 m-0' : 'far fa-heart pr-3 m-0 like-btn']" title="Like" v-on:click="likeStatus"></h3>
                <h3 class="far fa-comment pr-3 m-0" title="Comment" v-on:click="commentFocus"></h3>
                <h3 v-bind:class="[reactions.shared ? 'far fa-share-square pr-3 m-0 text-primary' : 'far fa-share-square pr-3 m-0 share-btn']" title="Share" v-on:click="shareStatus"></h3>
                <h3 v-bind:class="[reactions.bookmarked ? 'fas fa-bookmark text-warning m-0 float-right' : 'far fa-bookmark m-0 float-right']" title="Bookmark" v-on:click="bookmarkStatus"></h3>
              </div>
              <div class="reaction-counts font-weight-bold mb-0">
                <span style="cursor:pointer;" v-on:click="likesModal">
                  <span class="like-count">{{status.favourites_count || 0}}</span> likes
                </span>
                <span class="float-right" style="cursor:pointer;" v-on:click="sharesModal">
                  <span class="share-count pl-4">{{status.reblogs_count || 0}}</span> shares
                </span>
              </div>
              <div class="timestamp">
                <a v-bind:href="statusUrl" class="small text-muted">
                  {{timestampFormat()}}
                </a>
              </div>
            </div>
          </div>
          <div class="card-footer bg-white sticky-md-bottom">
            <div class="comment-form-guest">
              <a href="/login">Login</a> to like or comment.
            </div>
            <form class="comment-form d-none" method="post" action="/i/comment" :data-id="statusId" data-truncate="false">
              <input type="hidden" name="_token" value="">
              <input type="hidden" name="item" :value="statusId">
              <input class="form-control" name="comment" placeholder="Add a comment…" autocomplete="off">
              <input type="submit" value="Send" class="btn btn-primary comment-submit" />
            </form>
          </div>
        </div>

      </div>
    </div>
  </div>

  <b-modal ref="likesModal"
    id="l-modal"
    hide-footer
    centered
    title="Likes"
    body-class="list-group-flush p-0">
    <div class="list-group">
      <div class="list-group-item border-0" v-for="(user, index) in likes" :key="'modal_likes_'+index">
        <div class="media">
          <a :href="user.url">
            <img class="mr-3 rounded-circle box-shadow" :src="user.avatar" :alt="user.username + '’s avatar'" width="30px">
          </a>
          <div class="media-body">
            <p class="mb-0" style="font-size: 14px">
              <a :href="user.url" class="font-weight-bold text-dark">
                {{user.username}}
              </a>
            </p>
            <p class="text-muted mb-0" style="font-size: 14px">
                {{user.display_name}}
              </a>
            </p>
          </div>
        </div>
      </div>
      <infinite-loading @infinite="infiniteLikesHandler" spinner="spiral">
        <div slot="no-more"></div>
        <div slot="no-results"></div>
      </infinite-loading>
    </div>
  </b-modal>
  <b-modal ref="sharesModal"
    id="s-modal"
    hide-footer
    centered
    title="Shares"
    body-class="list-group-flush p-0">
    <div class="list-group">
      <div class="list-group-item border-0" v-for="(user, index) in shares" :key="'modal_shares_'+index">
        <div class="media">
          <a :href="user.url">
            <img class="mr-3 rounded-circle box-shadow" :src="user.avatar" :alt="user.username + '’s avatar'" width="30px">
          </a>
          <div class="media-body">
            <div class="d-inline-block">
              <p class="mb-0" style="font-size: 14px">
                <a :href="user.url" class="font-weight-bold text-dark">
                  {{user.username}}
                </a>
              </p>
              <p class="text-muted mb-0" style="font-size: 14px">
                  {{user.display_name}}
                </a>
              </p>
            </div>
            <p class="float-right"><!-- <a class="btn btn-primary font-weight-bold py-1" href="#">Follow</a> --></p>
          </div>
        </div>
      </div>
      <infinite-loading @infinite="infiniteSharesHandler" spinner="spiral">
        <div slot="no-more"></div>
        <div slot="no-results"></div>
      </infinite-loading>
    </div>
  </b-modal>
  <b-modal 
    id="lightbox" 
    ref="lightboxModal"
    :hide-header="true"
    :hide-footer="true"
    centered
    size="lg"
    body-class="p-0"
    >
    <div v-if="lightboxMedia" :class="lightboxMedia.filter_class">
      <img :src="lightboxMedia.url" class="img-fluid">
    </div>
  </b-modal>
</div>
</template>

<script>

pixelfed.postComponent = {};

export default {
    props: ['status-id', 'status-username', 'status-template', 'status-url', 'status-profile-url', 'status-avatar'],
    data() {
        return {
            status: false,
            media: {},
            user: false,
            reactions: {
              liked: false,
              shared: false
            },
            likes: [],
            likesPage: 1,
            shares: [],
            sharesPage: 1,
            lightboxMedia: false
          }
    },

    mounted() {
      this.fetchData();
      this.authCheck();
      let token = $('meta[name="csrf-token"]').attr('content');
      $('input[name="_token"]').each(function(k, v) {
          let el = $(v);
          el.val(token);
      });
    },

    updated() {
      $('.carousel').carousel();

      if(this.reactions) {
        if(this.reactions.bookmarked == true) {
          $('.postComponent .far.fa-bookmark').removeClass('far').addClass('fas text-warning');
        }
        if(this.reactions.shared == true) {
          $('.postComponent .far.fa-share-square').addClass('text-primary');
        }
        if(this.reactions.liked == true) {
          $('.postComponent .far.fa-heart').removeClass('far text-dark').addClass('fas text-danger');
        }
      }

    },

    methods: {
      authCheck() {
        let authed = $('body').hasClass('loggedIn');
        if(authed == true) {
          $('.comment-form-guest').addClass('d-none');
          $('.comment-form').removeClass('d-none');
        }
      },

      showMuteBlock() {
        let sid = this.status.account.id;
        let uid = this.user.id;
        if(sid == uid) {
          $('.post-actions .menu-author').removeClass('d-none');
        } else {
          $('.post-actions .menu-user').removeClass('d-none');
        }
      },

      reportUrl() {
        return '/i/report?type=post&id=' + this.status.id;
      },

      editUrl() {
        return this.status.url + '/edit';
      },

      timestampFormat() {
          let ts = new Date(this.status.created_at);
          return ts.toDateString() + ' ' + ts.toLocaleTimeString();
      },

      fetchData() {
          let loader = this.$loading.show({
            'opacity': 0,
            'background-color': '#f5f8fa'
          });
          axios.get('/api/v2/profile/'+this.statusUsername+'/status/'+this.statusId)
            .then(response => {
                let self = this;
                self.status = response.data.status;
                self.user = response.data.user;
                self.media = self.status.media_attachments;
                self.reactions = response.data.reactions;
                self.likes = response.data.likes;
                self.shares = response.data.shares;
                self.likesPage = 2;
                self.sharesPage = 2;
                //this.buildPresenter();
                this.showMuteBlock();
                loader.hide();
                pixelfed.readmore();
                $('.postComponent').removeClass('d-none');
                $('.postPresenterLoader').addClass('d-none');
                $('.postPresenterContainer').removeClass('d-none');
                $('head title').text(this.status.account.username + ' posted a photo: ' + this.status.favourites_count + ' likes');
            }).catch(error => {
              if(!error.response) {
                $('.postPresenterLoader .lds-ring').attr('style','width:100%').addClass('pt-4 font-weight-bold text-muted').text('An error occurred, cannot fetch media. Please try again later.');
              } else {
                switch(error.response.status) {
                  case 401:
                    $('.postPresenterLoader .lds-ring')
                      .attr('style','width:100%')
                      .addClass('pt-4 font-weight-bold text-muted')
                      .text('Please login to view.');
                  break;

                  default:
                  $('.postPresenterLoader .lds-ring').attr('style','width:100%').addClass('pt-4 font-weight-bold text-muted').text('An error occurred, cannot fetch media. Please try again later.');
                  break;
                }
              }
            });
      },

      commentFocus() {
        $('.comment-form input[name="comment"]').focus();
      },

      likesModal() {
        if(this.status.favourites_count == 0 || $('body').hasClass('loggedIn') == false) {
          return;
        }
        this.$refs.likesModal.show();
      },

      sharesModal() {
        if(this.status.reblogs_count == 0 || $('body').hasClass('loggedIn') == false) {
          return;
        }
        this.$refs.sharesModal.show();
      },

      infiniteLikesHandler($state) {
        let api = '/api/v2/likes/profile/'+this.statusUsername+'/status/'+this.statusId;
        axios.get(api, {
          params: {
            page: this.likesPage,
          },
        }).then(({ data }) => {
          if (data.data.length > 0) {
            this.likes.push(...data.data);
            this.likesPage++;
            $state.loaded();
          } else {
            $state.complete();
          }
        });
      },

      infiniteSharesHandler($state) {
        axios.get('/api/v2/shares/profile/'+this.statusUsername+'/status/'+this.statusId, {
          params: {
            page: this.sharesPage,
          },
        }).then(({ data }) => {
          if (data.data.length > 0) {
            this.shares.push(...data.data);
            this.sharesPage++;
            $state.loaded();
          } else {
            $state.complete();
          }
        });
      },

      likeStatus(event) {
        if($('body').hasClass('loggedIn') == false) {
          return;
        }

        axios.post('/i/like', {
          item: this.status.id
        }).then(res => {
          this.status.favourites_count = res.data.count;
          if(this.reactions.liked == true) {
            this.reactions.liked = false;
            let user = this.user.id;
            this.likes = this.likes.filter(function(like) {
              return like.id !== user;
            });
          } else {
            this.reactions.liked = true;
            let user = this.user;
            this.likes.push(user);
          }
        }).catch(err => {
          console.error(err);
          swal('Error', 'Something went wrong, please try again later.', 'error');
        });
      },

      shareStatus() {
        if($('body').hasClass('loggedIn') == false) {
          return;
        }

        axios.post('/i/share', {
          item: this.status.id
        }).then(res => {
          this.status.reblogs_count = res.data.count;
          if(this.reactions.shared == true) {
            this.reactions.shared = false;
            let user = this.user.id;
            this.shares = this.shares.filter(function(reaction) {
              return reaction.id !== user;
            });
          } else {
            this.reactions.shared = true;
            let user = this.user;
            this.shares.push(user);
          }
        }).catch(err => {
          console.error(err);
          swal('Error', 'Something went wrong, please try again later.', 'error');
        });
      },

      bookmarkStatus() {
        if($('body').hasClass('loggedIn') == false) {
          return;
        }

        axios.post('/i/bookmark', {
          item: this.status.id
        }).then(res => {
          if(this.reactions.bookmarked == true) {
            this.reactions.bookmarked = false;
          } else {
            this.reactions.bookmarked = true;
          }
        }).catch(err => {
          swal('Error', 'Something went wrong, please try again later.', 'error');
        });
      },

      muteProfile() {
        if($('body').hasClass('loggedIn') == false) {
          return;
        }

        axios.post('/i/mute', {
          type: 'user',
          item: this.status.account.id
        }).then(res => {
          swal('Success', 'You have successfully muted ' + this.status.account.acct, 'success');
        }).catch(err => {
          swal('Error', 'Something went wrong. Please try again later.', 'error');
        });
      },

      blockProfile() {
        if($('body').hasClass('loggedIn') == false) {
          return;
        }

        axios.post('/i/block', {
          type: 'user',
          item: this.status.account.id
        }).then(res => {
          swal('Success', 'You have successfully blocked ' + this.status.account.acct, 'success');
        }).catch(err => {
          swal('Error', 'Something went wrong. Please try again later.', 'error');
        });
      },

      deletePost(status) {
        var result = confirm('Are you sure you want to delete this post?');
        if (result) {
            if($('body').hasClass('loggedIn') == false) {
            return;
            }
            axios.post('/i/delete', {
              type: 'status',
              item: this.status.id
            }).then(res => {
              swal('Success', 'You have successfully deleted this post', 'success');
              window.location.href = '/';
            }).catch(err => {
              swal('Error', 'Something went wrong. Please try again later.', 'error');
            });
        }
      },

      owner() {
        return this.user.id === this.status.account.id;
      },

      admin() {
        return this.user.is_admin == true;
      },

      ownerOrAdmin() {
        return this.owner() || this.admin();
      },

      lightbox(src) {
        this.lightboxMedia = src;
        this.$refs.lightboxModal.show();
      }

    },
}
</script>
