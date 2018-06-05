$(document).ready(function() {
  $('.pagination').hide();
  $('.page-load-status').show();
  let elem = document.querySelector('.timeline-feed');
  let infScroll = new InfiniteScroll( elem, {
    path: '.pagination__next',
    append: '.timeline-feed',
    status: '.page-load-status',
    history: false,
  });
  infScroll.on( 'append', function( response, path, items ) {
    pixelfed.hydrateLikes();
  });
});
