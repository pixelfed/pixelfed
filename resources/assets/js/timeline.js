$(document).ready(function() {
  $('.pagination').hide();
  let elem = document.querySelector('.timeline-feed');
  let infScroll = new InfiniteScroll( elem, {
    path: '.pagination__next',
    append: '.timeline-feed',
    status: '.page-load-status',
    history: false,
  });

  $("#modal-post").addClass("modal fade");
  $("#modal-dialog").addClass("modal-dialog");
  $("#modal-text").removeClass("d-none");

  infScroll.on( 'append', function( response, path, items ) {
    pixelfed.hydrateLikes();
  });
});
