import Vue from 'vue';
window.Vue = Vue;
import BootstrapVue from 'bootstrap-vue'
import InfiniteLoading from 'vue-infinite-loading';
import Loading from 'vue-loading-overlay';
import VueTimeago from 'vue-timeago';
import VueCarousel from 'vue-carousel';
import VueBlurHash from 'vue-blurhash'
import 'vue-blurhash/dist/vue-blurhash.css'
  
Vue.use(VueBlurHash);
Vue.use(VueCarousel);
Vue.use(BootstrapVue);
Vue.use(InfiniteLoading);
Vue.use(Loading);
Vue.use(VueTimeago, {
  name: 'Timeago',
  locale: 'en'
});

pixelfed.readmore = () => {
  $('.read-more').each(function(k,v) {
      let el = $(this);
      let attr = el.attr('data-readmore');
      if(typeof attr !== typeof undefined && attr !== false) {
        return;
      }
      el.readmore({
        collapsedHeight: 45,
        heightMargin: 48,
        moreLink: '<a href="#" class="d-block small font-weight-bold text-dark text-center">Show more</a>',
        lessLink: '<a href="#" class="d-block small font-weight-bold text-dark text-center">Show less</a>',
      });
  });
};

try {
    document.createEvent("TouchEvent");
    $('body').addClass('touch');
} catch (e) {
}

window.filesize = require('filesize');
import swal from 'sweetalert';

$('[data-toggle="tooltip"]').tooltip()

const warningTitleCSS = 'color:red; font-size:60px; font-weight: bold; -webkit-text-stroke: 1px black;';
const warningDescCSS = 'font-size: 18px;';
console.log('%cStop!', warningTitleCSS);
console.log("%cThis is a browser feature intended for developers. If someone told you to copy and paste something here to enable a Pixelfed feature or \"hack\" someone's account, it is a scam and will give them access to your Pixelfed account.", warningDescCSS);
