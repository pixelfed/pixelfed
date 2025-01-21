require('./polyfill');
import Vue from 'vue';
window.Vue = Vue;
import VueRouter from "vue-router";
import Vuex from "vuex";
import { sync } from "vuex-router-sync";
import BootstrapVue from 'bootstrap-vue'
import InfiniteLoading from 'vue-infinite-loading';
import Loading from 'vue-loading-overlay';
import VueTimeago from 'vue-timeago';
import VueCarousel from 'vue-carousel';
import VueBlurHash from 'vue-blurhash';
import VueI18n from 'vue-i18n';
window.pftxt = require('twitter-text');
import 'vue-blurhash/dist/vue-blurhash.css'
window.filesize = require('filesize');
import swal from 'sweetalert';
window._ = require('lodash');
window.Popper = require('popper.js').default;
window.pixelfed = window.pixelfed || {};
window.$ = window.jQuery = require('jquery');
require('bootstrap');
window.axios = require('axios');
window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';
require('readmore-js');
window.blurhash = require("blurhash");

$('[data-toggle="tooltip"]').tooltip()
let token = document.head.querySelector('meta[name="csrf-token"]');
if (token) {
	window.axios.defaults.headers.common['X-CSRF-TOKEN'] = token.content;
} else {
	console.error('CSRF token not found.');
}

Vue.use(VueRouter);
Vue.use(Vuex);
Vue.use(VueBlurHash);
Vue.use(VueCarousel);
Vue.use(BootstrapVue);
Vue.use(InfiniteLoading);
Vue.use(Loading);
Vue.use(VueI18n);
Vue.use(VueTimeago, {
  name: 'Timeago',
  locale: 'en'
});

Vue.component(
	'photo-presenter',
	require('./../components/presenter/PhotoPresenter.vue').default
);

Vue.component(
	'video-presenter',
	require('./../components/presenter/VideoPresenter.vue').default
);

Vue.component(
	'photo-album-presenter',
	require('./../components/presenter/PhotoAlbumPresenter.vue').default
);

Vue.component(
	'video-album-presenter',
	require('./../components/presenter/VideoAlbumPresenter.vue').default
);

Vue.component(
	'mixed-album-presenter',
	require('./../components/presenter/MixedAlbumPresenter.vue').default
);

Vue.component(
	'navbar',
	require('./../components/landing/sections/nav.vue').default
);

Vue.component(
	'footer-component',
	require('./../components/landing/sections/footer.vue').default
);

import IndexComponent from "./../components/landing/Index.vue";
import DirectoryComponent from "./../components/landing/Directory.vue";
import ExploreComponent from "./../components/landing/Explore.vue";
import NotFoundComponent from "./../components/landing/NotFound.vue";

const router = new VueRouter({
	mode: "history",
	linkActiveClass: "",
	linkExactActiveClass: "active",

	routes: [
		{
			path: "/",
			component: IndexComponent
		},
		{
			path: "/web/directory",
			component: DirectoryComponent
		},
		{
			path: "/web/explore",
			component: ExploreComponent
		},
		{
			path: "/*",
			component: NotFoundComponent,
			props: true
		},
	],

	scrollBehavior(to, from, savedPosition) {
		if (to.hash) {
			return {
				selector: `[id='${to.hash.slice(1)}']`
			};
		} else {
			return { x: 0, y: 0 };
		}
	}
});

function lss(name, def) {
	let key = 'pf_m2s.' + name;
	let ls = window.localStorage;
	if(ls.getItem(key)) {
		let val = ls.getItem(key);
		if(['pl', 'color-scheme'].includes(name)) {
			return val;
		}
		return ['true', true].includes(val);
	}
	return def;
}

const store = new Vuex.Store({
	state: {
		version: 1,
		hideCounts: true,
		autoloadComments: false,
		newReactions: false,
		fixedHeight: false,
		profileLayout: 'grid',
		showDMPrivacyWarning: true,
		relationships: {},
		emoji: [],
		colorScheme: lss('color-scheme', 'system'),
	},

	getters: {
		getVersion: state => {
			return state.version;
		},

		getHideCounts: state => {
			return state.hideCounts;
		},

		getAutoloadComments: state => {
			return state.autoloadComments;
		},

		getNewReactions: state => {
			return state.newReactions;
		},

		getFixedHeight: state => {
			return state.fixedHeight;
		},

		getProfileLayout: state => {
			return state.profileLayout;
		},

		getRelationship: (state) => (id) => {
			return state.relationships[id];
		},

		getCustomEmoji: state => {
			return state.emoji;
		},

		getColorScheme: state => {
			return state.colorScheme;
		},

		getShowDMPrivacyWarning: state => {
			return state.showDMPrivacyWarning;
		}
	},

	mutations: {
		setVersion(state, value) {
			state.version = value;
		},

		setHideCounts(state, value) {
			localStorage.setItem('pf_m2s.hc', value);
			state.hideCounts = value;
		},

		setAutoloadComments(state, value) {
			localStorage.setItem('pf_m2s.ac', value);
			state.autoloadComments = value;
		},

		setNewReactions(state, value) {
			localStorage.setItem('pf_m2s.nr', value);
			state.newReactions = value;
		},

		setFixedHeight(state, value) {
			localStorage.setItem('pf_m2s.fh', value);
			state.fixedHeight = value;
		},

		setProfileLayout(state, value) {
			localStorage.setItem('pf_m2s.pl', value);
			state.profileLayout = value;
		},

		updateRelationship(state, relationships) {
			relationships.forEach((relationship) => {
				Vue.set(state.relationships, relationship.id, relationship)
			})
		},

		updateCustomEmoji(state, emojis) {
			state.emoji = emojis;
		},

		setColorScheme(state, value) {
			if(state.colorScheme == value) {
				return;
			}
			localStorage.setItem('pf_m2s.color-scheme', value);
			state.colorScheme = value;
			const name = value == 'system' ? '' : (value == 'light' ? 'force-light-mode' : 'force-dark-mode');
			document.querySelector("body").className = name;
			if(name != 'system') {
				const payload = name == 'force-dark-mode' ? { dark_mode: 'on' } : {};
				axios.post('/settings/labs', payload);
			}
		},

		setShowDMPrivacyWarning(state, value) {
			localStorage.setItem('pf_m2s.dmpwarn', value);
			state.showDMPrivacyWarning = value;
		}
	},
});

let i18nMessages = {
	en: require('./i18n/en.json'),
	ar: require('./i18n/ar.json'),
	ca: require('./i18n/ca.json'),
	de: require('./i18n/de.json'),
	el: require('./i18n/el.json'),
	es: require('./i18n/es.json'),
	eu: require('./i18n/eu.json'),
	fr: require('./i18n/fr.json'),
	he: require('./i18n/he.json'),
	gd: require('./i18n/gd.json'),
	gl: require('./i18n/gl.json'),
	id: require('./i18n/id.json'),
	it: require('./i18n/it.json'),
	ja: require('./i18n/ja.json'),
	nl: require('./i18n/nl.json'),
	pl: require('./i18n/pl.json'),
	pt: require('./i18n/pt.json'),
	ru: require('./i18n/ru.json'),
	uk: require('./i18n/uk.json'),
	vi: require('./i18n/vi.json'),
};

let locale = document.querySelector('html').getAttribute('lang');

const i18n = new VueI18n({
  locale: locale, // set locale
  fallbackLocale: 'en',
  messages: i18nMessages
});

sync(store, router);

const App = new Vue({
	el: '#content',
	i18n,
	router,
	store
});
