$(document).ready(function() {

  pixelfed.fetchLikes = () => {
      axios.get('/api/v1/likes')
      .then(function (res) {
        ls.set('likes', res.data);
      })
      .catch(function (res) {
        ls.set('likes', []);
      })
  }


  pixelfed.hydrateLikes = () => {
    var likes = ls.get('likes');
    $('.like-form').each(function(i, el) {
      var el = $(el);
      var id = el.data('id');
      var heart = el.find('.status-heart');

      if(likes.indexOf(id) != -1) {
        heart.removeClass('text-dark').addClass('text-primary');
      } else {
        heart.removeClass('text-primary').addClass('text-dark');
      }
    });
  };

  pixelfed.fetchLikes();
  pixelfed.hydrateLikes();

  $(document).on('submit', '.like-form', function(e) {
    e.preventDefault();
    var el = $(this);
    var id = el.data('id');
    axios.post('/i/like', {item: id})
    .then(function (res) {
      pixelfed.fetchLikes();
      pixelfed.hydrateLikes();
      var likes = ls.get('likes');
      var action = false;
      var counter = el.parents().eq(1).find('.like-count');
      var count = res.data.count;
      var heart = el.find('.status-heart');

      if(likes.indexOf(id) > -1) {
        heart.removeClass('text-primary').addClass('text-dark');
        likes = likes.filter(function(item) { 
            return item !== id
        });
        counter.text(count);
        action = 'unlike';
      } else {
        heart.removeClass('text-dark').addClass('text-primary');
        likes.push(id);
        counter.text(count);
        action = 'like';
      }

      ls.set('likes', likes);
      console.log(action + ' - ' + id + ' like event');
    });
  });
});