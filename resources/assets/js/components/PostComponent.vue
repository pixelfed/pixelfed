<style>
.carousel {
  position: relative;
  width: 600px;
  height: 400px;
  overflow: hidden;
  margin: 0 auto;
}
.carousel:hover .slide:after,
.carousel:hover .counter,
.carousel:hover .slide:before {
  opacity: 1;
}
.slide {
  float: right;
  position: absolute;
  z-index: 1;
  width: 600px;
  height: 400px;
  background-color: #fff;
  text-align: center;
  transition: opacity 0.4s;
  opacity: 1;
}
.slide:before {
  content: attr(annot);
  display: block;
  position: absolute;
  left: 20px;
  bottom: 20px;
  color: #fff;
  font-size: 14px;
  font-weight: bold;
  z-index: 12;
  opacity: 0;
  transition: opacity 0.3s;
}
.slide:after {
  content: attr(slide);
  display: block;
  position: absolute;
  bottom: 0;
  transition: opacity 0.3s;
  width: 100%;
  height: 80px;
  opacity: 0;
  background-image: linear-gradient(transparent, rgba(0,0,0,0.2));
  text-align: left;
  text-indent: 549px;
  line-height: 101px;
  font-size: 13px;
  color: #fff;
  font-weight: bold;
}
.counter {
  position: absolute;
  bottom: 20px;
  right: 2px;
  height: 20px;
  width: 60px;
  z-index: 2;
  text-align: center;
  color: #fff;
  line-height: 21px;
  font-size: 13px;
  font-weight: bold;
  opacity: 0;
  transition: opacity 0.3s;
}
.carousel-slide {
  top: 0;
  right: 0;
  float: right;
  position: absolute;
  margin-top: 0;
  z-index: 9;
  height: 100%;
  width: 100%;
  opacity: 0;
  cursor: pointer;
}
.carousel-slide:checked {
  z-index: 8;
}
.carousel-slide:checked + .slide {
  opacity: 0;
}
.carousel-slide:checked:nth-child(1):checked {
  z-index: 9;
}
.carousel-slide:nth-child(1):checked {
  float: left;
  z-index: 9;
}
.carousel-slide:nth-child(1):checked + .slide {
  opacity: 1;
}
.carousel-slide:nth-child(1):checked ~ .carousel-slide {
  float: left;
  z-index: 8;
}
.carousel-slide:nth-child(1):checked ~ .carousel-slide + .slide {
  opacity: 0;
}
.carousel-slide:nth-child(1):checked ~ .carousel-slide:checked {
  z-index: 9;
}
.carousel-slide:nth-child(1):checked ~ .carousel-slide:checked + .slide {
  opacity: 1;
}
</style>
<template>
<div class="postComponent">
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
          <!-- <div class="dropdown">
            <button class="btn btn-link text-dark no-caret dropdown-toggle" type="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false" title="Post options">
            <span class="fas fa-ellipsis-v text-muted"></span>
            </button>
            <div class="dropdown-menu dropdown-menu-right" aria-labelledby="dropdownMenuButton">
              <a class="dropdown-item font-weight-bold" href="#">View Exif</a>
              <a class="dropdown-item font-weight-bold" href="{{$status->reportUrl()}}">Report</a>
            </div>
          </div> -->
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
                <div class="dropdown">
                  <button class="btn btn-link text-dark no-caret dropdown-toggle" type="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false" title="Post options">
                  <span class="fas fa-ellipsis-v text-muted"></span>
                  </button>
                  <div class="dropdown-menu dropdown-menu-right" aria-labelledby="dropdownMenuButton">
                    <a class="dropdown-item font-weight-bold show-exif">Show Exif</a>
                    <a class="dropdown-item font-weight-bold" href="#">Report</a>
                  </div>
                </div>
              </div>
          </div>
          <div class="d-flex flex-md-column flex-column-reverse h-100">
            <div class="card-body status-comments">
              <div class="status-comment">
                <p class="mb-1 read-more" style="overflow: hidden;">
                  <span class="font-weight-bold pr-1">{{statusUsername}}</span>
                  <span class="comment-text"></span>
                </p>
                <post-comments :post-id="statusId" :post-username="statusUsername"></post-comments>
              </div>
            </div>
            <div class="card-body flex-grow-0 py-1">
              <div class="reactions my-1">
                <form class="d-inline-flex pr-3 like-form" method="post" action="/i/like" style="display: inline;" :data-id="statusId" data-action="like">
                  <input type="hidden" name="_token" value="">
                  <input type="hidden" name="item" :value="statusId">
                  <button class="btn btn-link text-dark p-0 border-0" type="submit" title="Like!">
                    <h3 class="status-heart m-0 far fa-heart text-dark"></h3>
                  </button>
                </form>
                <h3 class="far fa-comment pr-3 m-0" title="Comment"></h3>
                <form class="d-inline-flex share-form pr-3" method="post" action="/i/share" style="display: inline;" data-id="11todo" data-action="share" data-count="status.favourite_count">
                  <input type="hidden" name="_token" value="">
                  <input type="hidden" name="item" :value="statusId">
                  <button class="btn btn-link text-dark p-0" type="submit" title="Share">
                    <h3 class="m-0 far fa-share-square"></h3>
                  </button>
                </form>

                <span class="float-right">
                  <form class="d-inline-flex" method="post" action="/i/bookmark" style="display: inline;" data-id="#" data-action="bookmark" onclick="this.preventDefault()">
                    <input type="hidden" name="_token" value="">
                    <input type="hidden" name="item" value="#">
                    <button class="btn btn-link text-dark p-0 border-0" type="submit" title="Save">
                      <h3 class="m-0 far fa-bookmark"></h3>
                    </button>
                  </form>
                </span>
              </div>
              <div class="likes font-weight-bold mb-0">
                <span class="like-count">{{status.favourites_count || 0}}</span> likes
              </div>
              <div class="timestamp">
                <a v-bind:href="statusUrl" class="small text-muted">
                  November 1, 2018
                </a>
              </div>
            </div>
          </div>
          <div class="card-footer bg-white sticky-md-bottom">
            <form class="comment-form" method="post" action="/i/comment" :data-id="statusId" data-truncate="false">
              <input type="hidden" name="_token" value="">
              <input type="hidden" name="item" :value="statusId">

              <input class="form-control" name="comment" placeholder="Add a comment..." autocomplete="off">
            </form>
          </div>
        </div>

      </div>
    </div>
  </div>
</div>
</template>

<script>

pixelfed.postComponent = {};
pixelfed.presenter = {
  show: {
    image: function(container, media) { console.log(234);
      let wrapper = $('<div>');
      wrapper.addClass(media[0]['filter_class']);
      let el = $('<img>');
      el.attr('src', media[0]['url']);
      el.attr('title', media[0]['description']);
      wrapper.append(el);
      container.append(wrapper);
    },

    video: function(container, media) {
      let wrapper = $('<div>');
      wrapper.addClass('embed-responsive embed-responsive-4by3');
      let el = $('<video>');
      el.addClass('embed-responsive-item');
      el.attr('controls', '');
      el.attr('src', media[0]['url']);
      el.attr('title', media[0]['description']);
      wrapper.append(el);
      container.append(wrapper);
    },

    imageAlbum: function(container, media) {
      let wrapper = $('<div>');
      wrapper.addClass('carousel');
      let counter = $('<div>');
      counter.attr('class', 'counter');
      counter.attr('count', media.length);
      counter.text('  / ' + media.length);
      for(var i = media.length - 1; i >= 0; i--) {
        let item = media[i];
        let carouselItem = $('<div>').addClass('slide d-flex align-items-center');
        carouselItem.attr('slide', i + 1);
        carouselItem.attr('annot', item.description);
        let check = $('<input>');
        check.attr('type', 'checkbox');
        check.attr('class', 'carousel-slide');
        let img = $('<img>');
        img.addClass('img-fluid');
        img.attr('src', item['url']);
        carouselItem.append(img);
        wrapper.append(check);
        wrapper.append(carouselItem);
      }
      wrapper.append(counter);
      container.append(wrapper);
    }
  }
};

export default {
    props: ['status-id', 'status-username', 'status-template', 'status-url', 'status-profile-url', 'status-avatar'],
    data() {
        return {
            status: {},
            media: {}
          }
    },
    mounted() {
      let token = $('meta[name="csrf-token"]').attr('content');
      $('input[name="_token"]').each(function(k, v) {
          let el = $(v);
          el.val(token);
      });
      this.fetchData();
      pixelfed.hydrateLikes();
    },
    methods: {
      fetchData() {
          let url = '/api/v2/profile/'+this.statusUsername+'/status/'+this.statusId;
          axios.get(url)
            .then(response => {
                let self = this;
                self.status = response.data.status;
                self.media = self.status.media_attachments;
                this.buildPresenter();
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
      buildPresenter() {
        let container = $('.postPresenterContainer');
        let status = this.status;
        let media = this.media;

        $('.status-comment .comment-text').html(status.content);

        if(container.children().length != 0) {
          return;
        }
        switch(this.statusTemplate) {
          case 'image':
            pixelfed.presenter.show.image(container, media);
          break;

          case 'album':
            pixelfed.presenter.show.imageAlbum(container, media);
            $('.carousel').carousel();
          break;

          case 'video':
            pixelfed.presenter.show.video(container, media);
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
    }
}
</script>