import Vue from 'vue';
import VueI18n from 'vue-i18n';

Vue.use(VueI18n);

// Load every locale JSON in ./i18n/ automatically, keyed by locale code
// (the filename, e.g. en-US, pt-PT). New languages are picked up on rebuild.
let i18nMessages = {};
const i18nContext = require.context('./i18n', false, /\.json$/);
i18nContext.keys().forEach((key) => {
    const locale = key.replace(/^\.\//, '').replace(/\.json$/, '');
    i18nMessages[locale] = i18nContext(key);
});
let locale = document.querySelector('html').getAttribute('lang');

const i18n = new VueI18n({
    locale: locale, // set locale
    fallbackLocale: 'en-US',
messages: i18nMessages
});

new Vue({
    el: '#content',
    i18n
});
