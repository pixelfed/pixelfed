$(document).ready(function() {
  $('.pagination').hide();
  $('.container.timeline-container').removeClass('d-none');
  let elem = document.querySelector('.timeline-feed');
  pixelfed.fetchLikes();
  $('video').on('play', function() {
    activated = this;
    $('video').each(function() {
      if(this != activated) this.pause();
    });
  });
  let infScroll = new InfiniteScroll( elem, {
    path: '.pagination__next',
    append: '.timeline-feed',
    status: '.page-load-status',
    history: false,
  });
  
  infScroll.on( 'append', function( response, path, items ) {
    pixelfed.hydrateLikes();
    $('.status-card > .card-footer').each(function() {
      var el = $(this);
      if(!el.hasClass('d-none') && !el.find('input[name="comment"]').val()) {
        $(this).addClass('d-none');
      }
    });
    $('video').on('play', function() {
      activated = this;
      $('video').each(function() {
        if(this != activated) this.pause();
      });
    });
  });


});

$(document).on("DOMContentLoaded", function() {

  var active = false;

  var lazyLoad = function() {
    if (active === false) {
      active = true;

        var lazyImages = [].slice.call(document.querySelectorAll("img.lazy"));
        lazyImages.forEach(function(lazyImage) {
          if ((lazyImage.getBoundingClientRect().top <= window.innerHeight && lazyImage.getBoundingClientRect().bottom >= 0) && getComputedStyle(lazyImage).display !== "none") {
            lazyImage.src = lazyImage.dataset.src;
            lazyImage.srcset = lazyImage.dataset.srcset;
            lazyImage.classList.remove("lazy");

            lazyImages = lazyImages.filter(function(image) {
              return image !== lazyImage;
            });
          }
        });

        active = false;
    };
  }
  document.addEventListener("scroll", lazyLoad);
  window.addEventListener("resize", lazyLoad);
  window.addEventListener("orientationchange", lazyLoad);
});
