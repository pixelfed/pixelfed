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

  hydrateLikes();

  function hydrateLikes() {
    var likes = ls.get('likes');
    $('.like-form').each(function(i, el) {
      var el = $(el);
      var id = el.data('id');
      var heart = el.find('.status-heart');

      if(likes.indexOf(id) != -1) {
        heart.addClass('fas fa-heart').removeClass('far fa-heart');
      }
    });
  }

  $('.like-form').submit(function(e) {
    e.preventDefault();
    var el = $(this);
    var id = el.data('id');
    var res = axios.post('/i/like', {item: id});
    var likes = ls.get('likes');
    var action = false;
    var counter = el.parents().eq(2).find('.like-count');
    var count = parseInt(counter.text());
    var heart = el.find('.status-heart');

    if(likes.indexOf(id) > -1) {
      heart.addClass('far fa-heart').removeClass('fas fa-heart');
      likes = likes.filter(function(item) { 
          return item !== id
      });
      count = count == 0 ? 0 : count--;
      counter.text(count);
      action = 'unlike';
    } else {
      heart.addClass('fas fa-heart').removeClass('far fa-heart');
      likes.push(id);
      count++;
      counter.text(count);
      action = 'like';
    }

    ls.set('likes', likes);
    console.log(action + ' - ' + $(this).data('id') + ' like event');
  });
});