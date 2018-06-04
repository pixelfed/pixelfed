$(document).ready(function() {
  $('.pagination').hide();
  let elem = document.querySelector('.timeline-feed');
  let infScroll = new InfiniteScroll( elem, {
    path: '.pagination__next',
    append: '.timeline-feed',
    history: false,
  });
  infScroll.on( 'append', function( response, path, items ) {
    pixelfed.hydrateLikes();
  });
});