$(document).ready(function() {

  if(!ls.get('likes')) {
    axios.get('/api/v1/likes')
    .then(function (res) {
      ls.set('likes', res.data);
      console.log(res);
    })
    .catch(function (res) {
      ls.set('likes', []);
    })
  }


  pixelfed.hydrateLikes = function() {
    var likes = ls.get('likes');
    $('.like-form').each(function(i, el) {
      var el = $(el);
      var id = el.data('id');
      var heart = el.find('.status-heart');

      if(likes.indexOf(id) != -1) {
        heart.removeClass('far fa-heart').addClass('fas fa-heart liked');
      }
    });
  };

  pixelfed.hydrateLikes();

  $(document).on('submit', '.like-form', function(e) {
    e.preventDefault();
    var el = $(this);
    var id = el.data('id');
    axios.post('/i/like', {item: id})
    .then(function (res) {
      var likes = ls.get('likes');
      var action = false;
      var counter = el.parents().eq(1).find('.like-count');
      var count = res.data.count;
      var heart = el.find('.status-heart');

      if(likes.indexOf(id) > -1) {
        heart.removeClass('fas fa-heart liked').addClass('far fa-heart');
        likes = likes.filter(function(item) { 
            return item !== id
        });
        counter.text(count);
        action = 'unlike';
      } else {
        heart.removeClass('far fa-heart').addClass('fas fa-heart liked');
        likes.push(id);
        counter.text(count);
        action = 'like';
      }

      ls.set('likes', likes);
      console.log(action + ' - ' + id + ' like event');
    });
  });
});
