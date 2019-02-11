require('./bootstrap');

window.Vue = require('vue');
import BootstrapVue from 'bootstrap-vue'
import InfiniteLoading from 'vue-infinite-loading';
import Loading from 'vue-loading-overlay';
import VueTimeago from 'vue-timeago';
//import {Howl, Howler} from 'howler';

Vue.use(BootstrapVue);
Vue.use(InfiniteLoading);
Vue.use(Loading);
Vue.use(VueTimeago);

pixelfed.readmore = () => {
  $('.read-more').each(function(k,v) {
      let el = $(this);
      let attr = el.attr('data-readmore');
      if(typeof attr !== typeof undefined && attr !== false) {
        return;
      }
      el.readmore({
        collapsedHeight: 44,
        heightMargin: 20,
        moreLink: '<a href="#" class="font-weight-bold small">Read more</a>',
        lessLink: '<a href="#" class="font-weight-bold small">Hide</a>',
      });
  });
};

try {
    document.createEvent("TouchEvent");
    $('body').addClass('touch');
} catch (e) {
}

Vue.component(
    'micro',
    require('./components/Micro.vue').default
);