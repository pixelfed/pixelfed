$(document).ready(function() {
  $(document).on('submit', '.bookmark-form', function(e) {
    e.preventDefault();
    var el = $(this);
    var id = el.data('id');
    var res = axios.post('/i/bookmark', {item: id});
  });
});