import Vue from 'vue';
window.Vue = Vue;
import BootstrapVue from 'bootstrap-vue';
import VueBlurHash from 'vue-blurhash';
import 'vue-blurhash/dist/vue-blurhash.css'
Vue.use(BootstrapVue);
Vue.use(VueBlurHash);

Vue.component(
    'portfolio-post',
    require('./components/PortfolioPost.vue').default
);

Vue.component(
    'portfolio-profile',
    require('./components/PortfolioProfile.vue').default
);

Vue.component(
    'portfolio-settings',
    require('./components/PortfolioSettings.vue').default
);
