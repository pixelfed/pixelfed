<style>
#l-modal .modal-body,
#s-modal .modal-body {
  max-height: 70vh;
  overflow-y: scroll;
}
</style>
<template>
<div class="postComponent d-none">
  <div class="container px-0 mt-md-4">
    <div class="card card-md-rounded-0 status-container orientation-unknown">
      <div class="row mx-0">
      <div class="d-flex d-md-none align-items-center justify-content-between card-header bg-white w-100">
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
                  <a class="dropdown-item font-weight-bold" :href="editUrl()">Edit</a>
                  <a class="dropdown-item font-weight-bold text-danger" v-on:click="deletePost">Delete</a>
                </span>
              </div>
            </div>
          </div>
        </div>
       </div>
        <div class="col-12 col-md-8 status-photo px-0">
            <div class="postPresenterLoader text-center">
              <div class="lds-ring"><div></div><div></div><div></div><div></div></div> 
            </div>
            <div class="postPresenterContainer d-none">

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
              <input class="form-control" name="comment" placeholder="Add a comment..." autocomplete="off">
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
      <div class="list-group-item border-0" v-for="user in likes">
        <div class="media">
          <a :href="user.url">
            <img class="mr-3 rounded-circle box-shadow" :src="user.avatar" :alt="user.username + '\'s avatar'" width="30px">
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
      <div class="list-group-item border-0" v-for="user in shares">
        <div class="media">
          <a :href="user.url">
            <img class="mr-3 rounded-circle box-shadow" :src="user.avatar" :alt="user.username + '\'s avatar'" width="30px">
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
      <infinite-loading @infinite="infiniteSharesHandler" spinner="spiral">
        <div slot="no-more"></div>
        <div slot="no-results"></div>
      </infinite-loading>
    </div>
  </b-modal>
</div>
</template>

<script>

pixelfed.postComponent = {};

pixelfed.presenter = {
  show: {
    image: function(container, media, status) {
      $('.status-container')
        .removeClass('orientation-unknown')
        .addClass('orientation-' + media[0]['orientation']);
      let wrapper = $('<div>');
      wrapper.addClass(media[0]['filter_class']);
      let el = $('<img>');
      el.attr('src', media[0]['url']);
      el.attr('title', media[0]['description']);
      wrapper.append(el);
      if(status.sensitive == true) {
        let spoilerText = status.spoiler_text ? status.spoiler_text : 'CW / NSFW / Hidden Media';
        let cw = $('<details>').addClass('details-animated');
        let summary = $('<summary>');
        let text = $('<p>').addClass('mb-0 lead font-weight-bold').text(spoilerText);
        let direction = $('<p>').addClass('font-weight-light').text('(click to show)');
        summary.append(text, direction);
        cw.append(summary, wrapper);
        container.append(cw);
      } else {
        container.append(wrapper);
      }
    },

    video: function(container, media, status) {
      let wrapper = $('<div>');
      wrapper.addClass('');
      let el = $('<video>');
      el.addClass('embed-responsive-item');
      el.attr('controls', '');
      el.attr('loop', '');
      el.attr('src', media[0]['url']);
      el.attr('title', media[0]['description']);
      wrapper.append(el);
      if(status.sensitive == true) {
        let spoilerText = status.spoiler_text ? status.spoiler_text : 'CW / NSFW / Hidden Media';
        let cw = $('<details>').addClass('details-animated');
        let summary = $('<summary>');
        let text = $('<p>').addClass('mb-0 lead font-weight-bold').text(spoilerText);
        let direction = $('<p>').addClass('font-weight-light').text('(click to show)');
        summary.append(text, direction);
        cw.append(summary, wrapper);
        container.append(cw);
      } else {
        container.append(wrapper);
      }
      
      const player = new Plyr(el, {
        controls: [
            'restart', // Restart playback
            'play', // Play/pause playback
            'progress', // The progress bar and scrubber for playback and buffering
            'current-time', // The current time of playback
            'duration', // The full duration of the media
            'volume', // Volume control
            'captions', // Toggle captions
            'settings', // Settings menu
            'fullscreen', // Toggle fullscreen
        ]
      });
      player.volume = 0.75;
    },

    imageAlbum: function(container, media, status) {
      $('.status-container')
        .removeClass('orientation-unknown')
        .addClass('orientation-' + media[0]['orientation']);
      let id = 'photo-carousel-wrapper-' + status.id;
      let wrapper = $('<div>');
      wrapper.addClass('carousel slide carousel-fade');
      wrapper.attr('data-ride', 'carousel');
      wrapper.attr('id', id);
      let indicators = $('<ol>');
      indicators.addClass('carousel-indicators');
      let prev = $('<a>');
      prev.addClass('carousel-control-prev');
      prev.attr('href', '#' + id);
      prev.attr('role', 'button');
      prev.attr('data-slide', 'prev');
      let prevIcon = $('<span>').addClass('carousel-control-prev-icon').attr('aria-hidden', 'true');
      let prevSr = $('<span>').addClass('sr-only');
      prev.append(prevIcon, prevSr);
      let next = $('<a>');
      next.addClass('carousel-control-next');
      next.attr('href', '#' + id);
      next.attr('role', 'button');
      next.attr('data-slide', 'next');
      let nextIcon = $('<span>').addClass('carousel-control-next-icon').attr('aria-hidden', 'true');
      let nextSr = $('<span>').addClass('sr-only');
      let inner = $('<div>').addClass('carousel-inner');
      next.append(nextIcon, nextSr);
      for(let i = 0; i < media.length; i++) {
        let li = $('<li>');
        li.attr('data-target', '#' + id);
        li.attr('data-slide-to', i);
        if(i == 0) {
          li.addClass('active');
        }
        indicators.append(li);
        let item = media[i];
        let carouselItem = $('<div>').addClass('carousel-item');
        if(i == 0) {
          carouselItem.addClass('active');
        }
        let figure = $('<figure>');
        if(item['filter_class']) {
          figure.addClass(item['filter_class']);
        }

        let badge = $('<span>');
        badge.addClass('float-right mr-3 badge badge-dark');
        badge.style = 'position:fixed;top:8px;right:0;margin-bottom:-20px;';
        badge.text(i+1 + '/' + media.length);

        let img = $('<img>');
        img.addClass('d-block w-100');
        img.attr('src', item['url']);

        figure.append(badge, img);
        carouselItem.append(figure);

        inner.append(carouselItem);
      }
      wrapper.append(indicators, inner, prev, next);
      if(status.sensitive == true) {
        let spoilerText = status.spoiler_text ? status.spoiler_text : 'CW / NSFW / Hidden Media';
        let cw = $('<details>').addClass('details-animated');
        let summary = $('<summary>');
        let text = $('<p>').addClass('mb-0 lead font-weight-bold').text(spoilerText);
        let direction = $('<p>').addClass('font-weight-light').text('(click to show)');
        summary.append(text, direction);
        cw.append(summary, wrapper);
        container.append(cw);
      } else {
        container.append(wrapper);
      }
    }
  }
};

export default {
    props: ['status-id', 'status-username', 'status-template', 'status-url', 'status-profile-url', 'status-avatar'],
    data() {
        return {
            status: {},
            media: {},
            user: {},
            reactions: {
              liked: false,
              shared: false
            },
            likes: {},
            likesPage: 1,
            shares: {},
            sharesPage: 1,
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
          $('.far.fa-bookmark').removeClass('far').addClass('fas text-warning');
        }
        if(this.reactions.shared == true) {
          $('.far.fa-share-square').addClass('text-primary');
        }
        if(this.reactions.liked == true) {
          $('.far.fa-heart ').removeClass('far text-dark').addClass('fas text-danger');
        }
      }

      if(this.status) {
        let title = this.status.account.username + ' posted a photo: ' + this.status.favourites_count + ' likes';
        $('head title').text(title);
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
                this.buildPresenter();
                this.showMuteBlock();
                loader.hide();
                $('.postComponent').removeClass('d-none');
            }).catch(error => {
              if(!error.response) {
                $('.postPresenterLoader .lds-ring').attr('style','width:100%').addClass('pt-4 font-weight-bold text-muted').text('An error occured, cannot fetch media. Please try again later.');
              } else {
                switch(error.response.status) {
                  case 401:
                    $('.postPresenterLoader .lds-ring')
                      .attr('style','width:100%')
                      .addClass('pt-4 font-weight-bold text-muted')
                      .text('Please login to view.');
                  break;

                  default:
                  $('.postPresenterLoader .lds-ring').attr('style','width:100%').addClass('pt-4 font-weight-bold text-muted').text('An error occured, cannot fetch media. Please try again later.');
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
          if (data.data.length) {
            this.likesPage += 1;
            this.likes.push(...data.data);
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
          if (data.data.length) {
            this.sharesPage += 1;
            this.shares.push(...data.data);
            $state.loaded();
          } else {
            $state.complete();
          }
        });
      },

      buildPresenter() {
        let container = $('.postPresenterContainer');
        let status = this.status;
        let media = this.media;

        $('input[name="item"]').each(function(k, v) {
            let el = $(v);
            el.val(status.account.id);
        });

        if(container.children().length != 0) {
          return;
        }

        let template = this.status.pf_type ? this.status.pf_type : this.statusTemplate;
        switch(template) {
          case 'image':
          case 'photo':
            pixelfed.presenter.show.image(container, media, this.status);
          break;

          case 'album':
          case 'photo:album':
            pixelfed.presenter.show.imageAlbum(container, media, this.status);
          break;

          case 'video':
            pixelfed.presenter.show.video(container, media, this.status);
          break;

          case 'video:album':
          case 'photo:video:album':
              $('.postPresenterLoader .lds-ring').attr('style','width:100%').addClass('pt-4 font-weight-bold text-muted').text('We cannot load this post properly. We\'re working on a fix!');
                return;
          break;

          default:
              $('.postPresenterLoader .lds-ring').attr('style','width:100%').addClass('pt-4 font-weight-bold text-muted').text('An error occured, cannot fetch media. Please try again later.');
          break;

        }
        if(container.children().length == 0) {
          $('.postPresenterLoader .lds-ring').attr('style','width:100%').addClass('pt-4 font-weight-bold text-muted').text('An error occured, cannot fetch media. Please try again later.');
          return;
        }
        pixelfed.readmore();
        $('.postPresenterLoader').addClass('d-none');
        $('.postPresenterContainer').removeClass('d-none');
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
          } else {
            this.reactions.liked = true;
          }
        }).catch(err => {
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
          } else {
            this.reactions.shared = true;
          }
        }).catch(err => {
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

      deletePost() {
        if($('body').hasClass('loggedIn') == false) {
          return;
        }

        axios.post('/i/delete', {
          type: 'status',
          item: this.status.id
        }).then(res => {
          swal('Success', 'You have successfully deleted this post', 'success');
        }).catch(err => {
          swal('Error', 'Something went wrong. Please try again later.', 'error');
        });
      }
    }
}
</script>