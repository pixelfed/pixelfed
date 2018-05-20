$(document).ready(function() {

  $('.comment-form').submit(function(e, data) {
    e.preventDefault();

    let el = $(this);
    let id = el.data('id');
    let commentform = el.find('input[name="comment"]');
    let commenttext = commentform.val();
    let item = {item: id, comment: commenttext};
    try {
      axios.post('/i/comment', item);
      var comments = el.parent().parent().find('.comments');
      var comment = '<p class="mb-0"><span class="font-weight-bold pr-1">' + pixelfed.user.username + '</span><span class="comment-text">'+ commenttext + '</span></p>';
      comments.prepend(comment);

      commentform.val('');
      commentform.blur();
      return true;
    } catch(e) {
      return false;
    }
  });

});