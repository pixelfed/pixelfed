window._ = require('lodash');
window.Popper = require('popper.js').default;
import swal from 'sweetalert';
try {
    window.pixelfed = {};
    window.$ = window.jQuery = require('jquery');
    require('bootstrap');
    window.InfiniteScroll = require('infinite-scroll');
    window.filesize = require('filesize');
    window.typeahead = require('./lib/typeahead');
    window.Bloodhound = require('./lib/bloodhound');
    window.Vue = require('vue');

    require('./components/localstorage');
    require('./components/likebutton');
    require('./components/commentform');
    require('./components/searchform');
    require('./components/bookmarkform');
    require('./components/statusform');

    Vue.component(
        'follow-suggestions',
        require('./components/FollowSuggestions.vue')
    );
} catch (e) {}

$('[data-toggle="tooltip"]').tooltip();
window.axios = require('axios');

window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';
let token = document.head.querySelector('meta[name="csrf-token"]');
if (token) {
    window.axios.defaults.headers.common['X-CSRF-TOKEN'] = token.content;
} else {
    console.error('CSRF token not found: https://laravel.com/docs/csrf#csrf-x-csrf-token');
}

