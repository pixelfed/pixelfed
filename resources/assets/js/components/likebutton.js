$(document).ready(function() {

  pixelfed.fetchLikes = () => {
      let ts = Date.now();
      let offset =  ts - 900000;
      let updated = ls.get('likesUpdated');

      if(updated != null && ls.get('likes').length > 0 || offset < updated) {
        return;
      }

      axios.get('/api/v1/likes')
      .then(function (res) {
        ls.set('likes', res.data);
        ls.set('likesUpdated', ts);
      })
      .catch(function (res) {
        ls.set('likes', []);
        ls.set('likesUpdated', ts);
      })
  }


  pixelfed.hydrateLikes = () => {
    var likes = ls.get('likes');
    $('.like-form').each(function(i, el) {
      var el = $(el);
      var id = el.data('id');
      var heart = el.find('.status-heart');

      if(likes.indexOf(id) != -1) {
        heart.removeClass('far text-dark').addClass('fas text-danger');
      } else {
        heart.removeClass('fas text-danger').addClass('far text-dark');
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
        heart.removeClass('fas text-danger').addClass('far text-dark');
        likes = likes.filter(function(item) { 
            return item !== id
        });
        counter.text(count);
        action = 'unlike';
      } else {
        heart.removeClass('far text-dark').addClass('fas text-danger');
        likes.push(id);
        counter.text(count);
        action = 'like';
      }

      ls.set('likes', likes);
      ls.set('likesUpdated', Date.now());
      console.log(action + ' - ' + id + ' like event');
    });
  });
});