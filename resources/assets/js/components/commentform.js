$(document).ready(function() {

  $('.status-card > .card-footer').each(function() {
    $(this).addClass('d-none');
  });

  $(document).on('click', '.status-comment-focus', function(el) {
    var form = $(this).parents().eq(2).find('.card-footer');
    form.removeClass('d-none');
    var el = $(this).parents().eq(2).find('input[name="comment"]');
    el.focus();
  });

  $(document).on('submit', '.comment-form', function(e, data) {
    e.preventDefault();

    let el = $(this);
    let id = el.data('id');
    let commentform = el.find('input[name="comment"]');
    let commenttext = commentform.val();
    let item = {item: id, comment: commenttext};

    commentform.prop('disabled', true);
    axios.post('/i/comment', item)
    .then(function (res) {

      var username = res.data.username;
      var permalink = res.data.url;
      var profile = res.data.profile;
      var reply = res.data.comment;

      if($('.status-container').length == 1) {
        var comments = el.parents().eq(3).find('.comments');
      } else {
        var comments = el.parents().eq(1).find('.comments');
      }

      var comment = '<p class="mb-0"><span class="font-weight-bold pr-1"><bdi><a class="text-dark" href="' + profile + '">' + username + '</a></bdi></span><span class="comment-text">'+ reply + '</span></p>';

      comments.prepend(comment);
      
      commentform.val('');
      commentform.blur();
      commentform.prop('disabled', false);

    })
    .catch(function (res) {
      
    });
  });
});