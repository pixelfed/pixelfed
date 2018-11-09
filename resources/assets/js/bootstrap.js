window._ = require('lodash');
window.Popper = require('popper.js').default;
import swal from 'sweetalert';
window.pixelfed = window.pixelfed || {};
window.$ = window.jQuery = require('jquery');
require('bootstrap');
window.InfiniteScroll = require('infinite-scroll');
window.filesize = require('filesize');
window.typeahead = require('./lib/typeahead');
window.Bloodhound = require('./lib/bloodhound');
window.Vue = require('vue');
import BootstrapVue from 'bootstrap-vue'
Vue.use(BootstrapVue);
window.axios = require('axios');
window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';

let token = document.head.querySelector('meta[name="csrf-token"]');
if (token) {
    window.axios.defaults.headers.common['X-CSRF-TOKEN'] = token.content;
} else {
    console.error('CSRF token not found: https://laravel.com/docs/csrf#csrf-x-csrf-token');
}