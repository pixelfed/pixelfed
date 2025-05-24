import Vue from 'vue';
import VueI18n from 'vue-i18n';

Vue.use(VueI18n);

import en from './i18n/en.json';
import pt from './i18n/pt.json';
let locale = document.querySelector('html').getAttribute('lang');

const i18n = new VueI18n({
    locale: locale, // set locale
    fallbackLocale: 'en',
    messages: { en, pt }
});

new Vue({
    el: '#content',
    i18n
});
