require('./polyfill');
window._ = require('lodash');
window.Popper = require('popper.js').default;
window.pixelfed = window.pixelfed || {};
window.$ = window.jQuery = require('jquery');
require('bootstrap');
window.axios = require('axios');
window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';
require('readmore-js');
window.filesize = require('filesize');
window.Cookies = require('js-cookie');
require('jquery.scrollbar');
require('jquery-scroll-lock');
window.Chart = require('chart.js');
require('./lib/argon.js');

Chart.defaults.global.defaultFontFamily = "-apple-system,BlinkMacSystemFont,Segoe UI,Roboto,Helvetica,Arial,sans-serif";

// fix bs4 custom paginators inherited from core app
Array.from(document.querySelectorAll('.pagination .page-link'))
.filter(el => el.textContent === '« Previous' || el.textContent === 'Next »')
.forEach(el => el.textContent = (el.textContent === 'Next »' ? '›' :'‹'));

Vue.component(
    'admin-autospam',
    require('./../components/admin/AdminAutospam.vue').default
);

Vue.component(
    'admin-directory',
    require('./../components/admin/AdminDirectory.vue').default
);

Vue.component(
    'admin-reports',
    require('./../components/admin/AdminReports.vue').default
);

Vue.component(
    'instances-component',
    require('./../components/admin/AdminInstances.vue').default
);

Vue.component(
    'hashtag-component',
    require('./../components/admin/AdminHashtags.vue').default
);
