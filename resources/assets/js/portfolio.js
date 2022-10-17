import Vue from 'vue';
window.Vue = Vue;
import BootstrapVue from 'bootstrap-vue'
Vue.use(BootstrapVue);

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
