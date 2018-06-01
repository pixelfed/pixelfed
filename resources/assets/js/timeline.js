$(document).ready(function() {
  $('.pagination').hide();
  let elem = document.querySelector('.timeline-feed');
  let infScroll = new InfiniteScroll( elem, {
    path: '.pagination__next',
    append: '.timeline-feed',
    history: false,
  });
  $("#modal-post").addClass("modal fade");
  $("#modal-dialog").addClass("modal-dialog");
  $("#modal-text").removeClass("d-none");
});
