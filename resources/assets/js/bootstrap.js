window._ = require('lodash');
window.Popper = require('popper.js').default;
import swal from 'sweetalert';

window.pixelfed = {};
window.$ = window.jQuery = require('jquery');
require('bootstrap');
window.Vue = require('vue');
import BootstrapVue from 'bootstrap-vue'
Vue.use(BootstrapVue);

try {
    window.InfiniteScroll = require('infinite-scroll');
    window.filesize = require('filesize');
    window.typeahead = require('./lib/typeahead');
    window.Bloodhound = require('./lib/bloodhound');
    require('./components/localstorage');
    require('./components/likebutton');
    require('./components/commentform');
    require('./components/searchform');
    require('./components/bookmarkform');
    require('./components/statusform');
    // require('./components/embed');
    // require('./components/shortcuts');

    Vue.component(
        'follow-suggestions',
        require('./components/FollowSuggestions.vue')
    );

    // Vue.component(
    //     'circle-panel',
    //     require('./components/CirclePanel.vue')
    // );

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

} catch (e) {}

$(document).ready(function() {
  $(function () {
    $('[data-toggle="tooltip"]').tooltip()
  });
});

window.axios = require('axios');

window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';
let token = document.head.querySelector('meta[name="csrf-token"]');
if (token) {
    window.axios.defaults.headers.common['X-CSRF-TOKEN'] = token.content;
} else {
    console.error('CSRF token not found: https://laravel.com/docs/csrf#csrf-x-csrf-token');
}

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