$(document).ready(function() {
  $('.pagination').hide();
  let elem = document.querySelector('.notification-page .list-group');
  let infScroll = new InfiniteScroll( elem, {
    path: '.pagination__next',
    append: '.notification-page .list-group',
    status: '.page-load-status',
    history: true,
  });
});
