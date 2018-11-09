window.Vue = require('vue');
import BootstrapVue from 'bootstrap-vue'
Vue.use(BootstrapVue);

window.InfiniteScroll = require('infinite-scroll');
window.filesize = require('filesize');
import swal from 'sweetalert';

require('./components/localstorage');
require('./components/likebutton');
require('./components/commentform');
require('./components/searchform');
require('./components/bookmarkform');
require('./components/statusform');
require('./components/embed');
require('./components/notifications');

// import Echo from "laravel-echo"

// window.io = require('socket.io-client');

// window.pixelfed.bootEcho = function() {
//     window.Echo = new Echo({
//         broadcaster: 'socket.io',
//         host: window.location.hostname + ':2096',
//         auth: {
//             headers: {
//                 Authorization: 'Bearer ' + token.content,
//             },
//         },
//     });
// }

Vue.component(
    'follow-suggestions',
    require('./components/FollowSuggestions.vue')
);

Vue.component(
    'discover-component',
    require('./components/DiscoverComponent.vue')
);

// Vue.component(
//     'circle-panel',
//     require('./components/CirclePanel.vue')
// );

Vue.component(
    'post-component',
    require('./components/PostComponent.vue')
);

Vue.component(
    'post-presenter',
    require('./components/PostPresenter.vue')
);

Vue.component(
    'post-comments',
    require('./components/PostComments.vue')
);

Vue.component(
    'passport-clients',
    require('./components/passport/Clients.vue')
);

Vue.component(
    'passport-authorized-clients',
    require('./components/passport/AuthorizedClients.vue')
);

Vue.component(
    'passport-personal-access-tokens',
    require('./components/passport/PersonalAccessTokens.vue')
);

window.pixelfed.copyToClipboard = (str) => {
  const el = document.createElement('textarea');
  el.value = str;
  el.setAttribute('readonly', '');
  el.style.position = 'absolute';                 
  el.style.left = '-9999px';
  document.body.appendChild(el);
  const selected = 
    document.getSelection().rangeCount > 0
      ? document.getSelection().getRangeAt(0)
      : false;
  el.select();
  document.execCommand('copy');
  document.body.removeChild(el);
  if (selected) {
    document.getSelection().removeAllRanges();
    document.getSelection().addRange(selected);
  }
};

$(document).ready(function() {
  $(function () {
    $('[data-toggle="tooltip"]').tooltip()
  });
});