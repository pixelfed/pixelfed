$(document).ready(function() {

  $('.status-comment-focus').on('click', function(el) {
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

    axios.post('/i/comment', item)
    .then(function (res) {

      var username = res.data.username;
      var permalink = res.data.url;
      var profile = res.data.profile;

      if($('.status-container').length == 1) {
        var comments = el.parents().eq(3).find('.comments');
      } else {
        var comments = el.parents().eq(2).find('.comments');
      }

      var comment = '<p class="mb-0"><span class="font-weight-bold pr-1"><bdi><a class="text-dark" href="' + profile + '">' + username + '</a></bdi></span><span class="comment-text">'+ commenttext + '</span><span class="float-right"><a href="' + permalink + '" class="text-dark small font-weight-bold">1s</a></span></p>';

      comments.prepend(comment);
      
      commentform.val('');
      commentform.blur();

    })
    .catch(function (res) {
      
    });
 
  });

});