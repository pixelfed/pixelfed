window.Vue = require('vue');

Vue.component(
    'landing-page',
    require('./components/LandingPage.vue').default
);

new Vue({
	el: '#content'
});