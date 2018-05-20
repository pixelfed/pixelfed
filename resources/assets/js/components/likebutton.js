$(document).ready(function() {
  if(!ls.get('likes')) {
    ls.set('likes', []);
  }

  $('.like-form').submit(function(e) {
    e.preventDefault();
    var el = $(this);
    var id = el.data('id');
    var res = axios.post('/i/like', {item: id});
    var likes = ls.get('likes');
    var action = false;
    var counter = el.parent().parent().find('.like-count');
    var count = parseInt(counter.text());
    if(likes.indexOf(id) > -1) {
      likes.splice(id, 1);
      count--;
      counter.text(count);
      action = 'unlike';
    } else {
      likes.push(id);
      count++;
      counter.text(count);
      action = 'like';
    }
    ls.set('likes', likes);
    console.log(action + ' - ' + $(this).data('id') + ' like event');
  });
});