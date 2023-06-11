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
import VueMasonry from 'vue-masonry-css';
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
Vue.use(VueMasonry);
Vue.use(VueI18n);
Vue.use(VueTimeago, {
  name: 'Timeago',
  locale: 'en'
});

Vue.component(
	'navbar',
	require('./../components/partials/navbar.vue').default
);

Vue.component(
	'notification-card',
	require('./components/NotificationCard.vue').default
);

Vue.component(
	'photo-presenter',
	require('./components/presenter/PhotoPresenter.vue').default
);

Vue.component(
	'video-presenter',
	require('./components/presenter/VideoPresenter.vue').default
);

Vue.component(
	'photo-album-presenter',
	require('./components/presenter/PhotoAlbumPresenter.vue').default
);

Vue.component(
	'video-album-presenter',
	require('./components/presenter/VideoAlbumPresenter.vue').default
);

Vue.component(
	'mixed-album-presenter',
	require('./components/presenter/MixedAlbumPresenter.vue').default
);

Vue.component(
	'post-menu',
	require('./components/PostMenu.vue').default
);

// Vue.component(
// 	'announcements-card',
// 	require('./components/AnnouncementsCard.vue').default
// );

Vue.component(
	'story-component',
	require('./components/StoryTimelineComponent.vue').default
);

const HomeComponent = () => import(/* webpackChunkName: "home.chunk" */ "./../components/Home.vue");
const ComposeComponent = () => import(/* webpackChunkName: "compose.chunk" */ "./../components/Compose.vue");
const PostComponent = () => import(/* webpackChunkName: "post.chunk" */ "./../components/Post.vue");
const ProfileComponent = () => import(/* webpackChunkName: "profile.chunk" */ "./../components/Profile.vue");
const MemoriesComponent = () => import(/* webpackChunkName: "discover~memories.chunk" */ "./../components/discover/Memories.vue");
const MyHashtagComponent = () => import(/* webpackChunkName: "discover~myhashtags.chunk" */ "./../components/discover/Hashtags.vue");
const AccountInsightsComponent = () =>  import(/* webpackChunkName: "daci.chunk" */ "./../components/discover/Insights.vue");
const DiscoverFindFriendsComponent = () =>  import(/* webpackChunkName: "discover~findfriends.chunk" */ "./../components/discover/FindFriends.vue");
const DiscoverServerFeedComponent = () =>  import(/* webpackChunkName: "discover~serverfeed.chunk" */ "./../components/discover/ServerFeed.vue");
const DiscoverSettingsComponent = () =>  import(/* webpackChunkName: "discover~settings.chunk" */ "./../components/discover/Settings.vue");
const DiscoverComponent = () => import(/* webpackChunkName: "discover.chunk" */ "./../components/Discover.vue");
const NotificationsComponent = () => import(/* webpackChunkName: "notifications.chunk" */ "./../components/Notifications.vue");
const DirectComponent = () => import(/* webpackChunkName: "dms.chunk" */ "./../components/Direct.vue");
const DirectMessageComponent = () => import(/* webpackChunkName: "dms~message.chunk" */  "./../components/DirectMessage.vue");
const ProfileFollowersComponent = () => import(/* webpackChunkName: "profile~followers.bundle" */ "./../components/ProfileFollowers.vue");
const ProfileFollowingComponent = () => import(/* webpackChunkName: "profile~following.bundle" */ "./../components/ProfileFollowing.vue");
const HashtagComponent = () => import(/* webpackChunkName: "discover~hashtag.bundle" */ "./../components/Hashtag.vue");
const NotFoundComponent = () => import(/* webpackChunkName: "error404.bundle" */ "./../components/NotFound.vue");
// const HelpComponent = () => import(/* webpackChunkName: "help.bundle" */ "./../components/HelpComponent.vue");
// const KnowledgebaseComponent = () => import(/* webpackChunkName: "kb.bundle" */ "./../components/Knowledgebase.vue");
// const AboutComponent = () => import(/* webpackChunkName: "about.bundle" */ "./../components/About.vue");
// const ContactComponent = () => import(/* webpackChunkName: "contact.bundle" */ "./../components/Contact.vue");
const LanguageComponent = () => import(/* webpackChunkName: "i18n.bundle" */ "./../components/Language.vue");
// const PrivacyComponent = () => import(/* webpackChunkName: "static~privacy.bundle" */ "./../components/Privacy.vue");
// const TermsComponent = () => import(/* webpackChunkName: "static~tos.bundle" */ "./../components/Terms.vue");
const ChangelogComponent = () => import(/* webpackChunkName: "changelog.bundle" */ "./../components/Changelog.vue");

// import LiveComponent from "./../components/Live.vue";
// import LivestreamsComponent from "./../components/Livestreams.vue";
// import LivePlayerComponent from "./../components/LivePlayer.vue";
// import LiveHelpComponent from "./../components/LiveHelp.vue";

// import DriveComponent from "./../components/Drive.vue";
// import SettingsComponent from "./../components/Settings.vue";
// import ProfileComponent from "./components/ProfileNext.vue";
// import VideosComponent from "./../components/Videos.vue";
// import GroupsComponent from "./../components/Groups.vue";

const router = new VueRouter({
	mode: "history",
	linkActiveClass: "active",

	routes: [
		{
			path: "/i/web/timeline/:scope",
			name: 'timeline',
			component: HomeComponent,
			props: true
		},
		// {
		// 	path: "/i/web/timeline/local",
		// 	component: LocalTimeline
		// },
		// {
		// 	path: "/i/web/timeline/global",
		// 	component: GlobalTimeline
		// },
		// {
		// 	path: "/i/web/drive",
		// 	name: 'drive',
		// 	component: DriveComponent,
		// 	props: true
		// },
		// {
		// 	path: "/i/web/groups",
		// 	name: 'groups',
		// 	component: GroupsComponent,
		// 	props: true
		// },
		{
			path: "/i/web/post/:id",
			name: 'post',
			component: PostComponent,
			props: true
		},
		// {
		// 	path: "/i/web/profile/:id/live",
		// 	component: LivePlayerComponent,
		// 	props: true
		// },
        {
            path: "/i/web/profile/:id/followers",
            name: 'profile-followers',
            component: ProfileFollowersComponent,
            props: true
        },
        {
            path: "/i/web/profile/:id/following",
            name: 'profile-following',
            component: ProfileFollowingComponent,
            props: true
        },
		{
			path: "/i/web/profile/:id",
			name: 'profile',
			component: ProfileComponent,
			props: true
		},
		// {
		// 	path: "/i/web/videos",
		// 	component: VideosComponent
		// },
		{
			path: "/i/web/discover",
			component: DiscoverComponent
		},
		// {
		// 	path: "/i/web/stories",
		// 	component: HomeComponent
		// },
		// {
		// 	path: "/i/web/settings/*",
		// 	component: SettingsComponent,
		// 	props: true
		// },
		// {
		// 	path: "/i/web/settings",
		// 	component: SettingsComponent
		// },
		{
			path: "/i/web/compose",
			component: ComposeComponent
		},
		{
			path: "/i/web/notifications",
			component: NotificationsComponent
		},
		{
			path: "/i/web/direct/thread/:accountId",
			component: DirectMessageComponent,
			props: true
		},
		{
			path: "/i/web/direct",
			component: DirectComponent
		},
		// {
		// 	path: "/i/web/kb/:id",
		// 	name: "kb",
		// 	component: KnowledgebaseComponent,
		// 	props: true
		// },
		{
			path: "/i/web/hashtag/:id",
			name: "hashtag",
			component: HashtagComponent,
			props: true
		},
		// {
		// 	path: "/i/web/help",
		// 	component: HelpComponent
		// },
		// {
		// 	path: "/i/web/about",
		// 	component: AboutComponent
		// },
		// {
		// 	path: "/i/web/contact",
		// 	component: ContactComponent
		// },
		{
			path: "/i/web/language",
			component: LanguageComponent
		},
		// {
		// 	path: "/i/web/privacy",
		// 	component: PrivacyComponent
		// },
		// {
		// 	path: "/i/web/terms",
		// 	component: TermsComponent
		// },
		{
			path: "/i/web/whats-new",
			component: ChangelogComponent
		},
		{
			path: "/i/web/discover/my-memories",
			component: MemoriesComponent
		},
		{
			path: "/i/web/discover/my-hashtags",
			component: MyHashtagComponent
		},
		{
			path: "/i/web/discover/account-insights",
			component: AccountInsightsComponent
		},
		{
			path: "/i/web/discover/find-friends",
			component: DiscoverFindFriendsComponent
		},
		{
			path: "/i/web/discover/server-timelines",
			component: DiscoverServerFeedComponent
		},
		{
			path: "/i/web/discover/settings",
			component: DiscoverSettingsComponent
		},
		// {
		// 	path: "/i/web/livestreams",
		// 	component: LivestreamsComponent
		// },
		// {
		// 	path: "/i/web/live/help",
		// 	component: LiveHelpComponent
		// },
		// {
		// 	path: "/i/web/live/player",
		// 	component: LivePlayerComponent
		// },
		// {
		// 	path: "/i/web/live",
		// 	component: LiveComponent
		// },

		{
			path: "/i/web",
			component: HomeComponent,
			props: true
		},
		{
			path: "/i/web/*",
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
		hideCounts: lss('hc', false),
		autoloadComments: lss('ac', true),
		newReactions: lss('nr', true),
		fixedHeight: lss('fh', false),
		profileLayout: lss('pl', 'grid'),
		showDMPrivacyWarning: lss('dmpwarn', true),
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
			// let rel = state.relationships[id];
			// if(!rel || !rel.hasOwnProperty('id')) {
			// 	return axios.get('/api/pixelfed/v1/accounts/relationships', {
			// 		params: {
			// 			'id[]': id
			// 		}
			// 	})
			// 	.then(res => {
			// 		let relationship = res.data;
			// 		// Vue.set(state.relationships, relationship.id, relationship);
			// 		state.commit('updateRelationship', res.data[0]);
			// 		return res.data[0];
			// 	})
			// 	.catch(err => {
			// 		return {};
			// 	})
			// } else {
			// 	return state.relationships[id];
			// }
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

axios.get('/api/v1/custom_emojis')
.then(res => {
	if(res && res.data && res.data.length) {
		store.commit('updateCustomEmoji', res.data);
	}
});

if(store.state.colorScheme) {
	const name = store.state.colorScheme == 'system' ? '' : (store.state.colorScheme == 'light' ? 'force-light-mode' : 'force-dark-mode');
	if(name != 'system') {
		document.querySelector("body").className = name;
	}
}

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

window.App = window.App || {};

// window.App.redirect = function() {
// 	document.querySelectorAll('a').forEach(function(i,k) {
// 		let a = i.getAttribute('href');
// 		if(a && a.length > 5 && a.startsWith('https://')) {
// 			let url = new URL(a);
// 			if(url.host !== window.location.host && url.pathname !== '/i/redirect') {
// 				i.setAttribute('href', '/i/redirect?url=' + encodeURIComponent(a));
// 			}
// 		}
// 	});
// }

// window.App.boot = function() {
// 	new Vue({ el: '#content'});
// }

// window.addEventListener("load", () => {
//   if ("serviceWorker" in navigator) {
//     navigator.serviceWorker.register("/sw.js");
//   }
// });

window.App.util = {
	compose: {
		post: (function() {
			let path = window.location.pathname;
			let whitelist = [
				'/',
				'/timeline/public'
			];
			if(whitelist.includes(path)) {
				$('#composeModal').modal('show');
			} else {
				window.location.href = '/?a=co';
			}
		}),
		circle: (function() {
			console.log('Unsupported method.');
		}),
		collection: (function() {
			console.log('Unsupported method.');
		}),
		loop: (function() {
			console.log('Unsupported method.');
		}),
		story: (function() {
			console.log('Unsupported method.');
		}),
	},
	time: (function() {
		return new Date;
	}),
	version: 1,
	format: {
		count: (function(count = 0, locale = 'en-GB', notation = 'compact') {
			if(count < 1) {
				return 0;
			}
			return new Intl.NumberFormat(locale, { notation: notation , compactDisplay: "short" }).format(count);
		}),
		timeAgo: (function(ts) {
			let date = Date.parse(ts);
			let seconds = Math.floor((new Date() - date) / 1000);
			let interval = Math.floor(seconds / 63072000);
			if (interval < 0) {
				return "0s";
			}
			if (interval >= 1) {
				return interval + "y";
			}
			interval = Math.floor(seconds / 604800);
			if (interval >= 1) {
				return interval + "w";
			}
			interval = Math.floor(seconds / 86400);
			if (interval >= 1) {
				return interval + "d";
			}
			interval = Math.floor(seconds / 3600);
			if (interval >= 1) {
				return interval + "h";
			}
			interval = Math.floor(seconds / 60);
			if (interval >= 1) {
				return interval + "m";
			}
			return Math.floor(seconds) + "s";
		}),
		timeAhead: (function(ts, short = true) {
			let date = Date.parse(ts);
			let diff = date - Date.parse(new Date());
			let seconds = Math.floor((diff) / 1000);
			let interval = Math.floor(seconds / 63072000);
			if (interval >= 1) {
				return interval + (short ? "y" : " years");
			}
			interval = Math.floor(seconds / 604800);
			if (interval >= 1) {
				return interval + (short ? "w" : " weeks");
			}
			interval = Math.floor(seconds / 86400);
			if (interval >= 1) {
				return interval + (short ? "d" : " days");
			}
			interval = Math.floor(seconds / 3600);
			if (interval >= 1) {
				return interval + (short ? "h" : " hours");
			}
			interval = Math.floor(seconds / 60);
			if (interval >= 1) {
				return interval + (short ? "m" : " minutes");
			}
			return Math.floor(seconds) + (short ? "s" : " seconds");
		}),
		rewriteLinks: (function(i) {

			let tag = i.innerText;

			if(i.href.startsWith(window.location.origin)) {
				return i.href;
			}

			if(tag.startsWith('#') == true) {
				tag = '/discover/tags/' + tag.substr(1) +'?src=rph';
			} else if(tag.startsWith('@') == true) {
				tag = '/' + i.innerText + '?src=rpp';
			} else {
				tag = '/i/redirect?url=' + encodeURIComponent(tag);
			}

			return tag;
		})
	},
	filters: [
			['1984','filter-1977'],
			['Azen','filter-aden'],
			['Astairo','filter-amaro'],
			['Grassbee','filter-ashby'],
			['Bookrun','filter-brannan'],
			['Borough','filter-brooklyn'],
			['Farms','filter-charmes'],
			['Hairsadone','filter-clarendon'],
			['Cleana ','filter-crema'],
			['Catpatch','filter-dogpatch'],
			['Earlyworm','filter-earlybird'],
			['Plaid','filter-gingham'],
			['Kyo','filter-ginza'],
			['Yefe','filter-hefe'],
			['Goddess','filter-helena'],
			['Yards','filter-hudson'],
			['Quill','filter-inkwell'],
			['Rankine','filter-kelvin'],
			['Juno','filter-juno'],
			['Mark','filter-lark'],
			['Chill','filter-lofi'],
			['Van','filter-ludwig'],
			['Apache','filter-maven'],
			['May','filter-mayfair'],
			['Ceres','filter-moon'],
			['Knoxville','filter-nashville'],
			['Felicity','filter-perpetua'],
			['Sandblast','filter-poprocket'],
			['Daisy','filter-reyes'],
			['Elevate','filter-rise'],
			['Nevada','filter-sierra'],
			['Futura','filter-skyline'],
			['Sleepy','filter-slumber'],
			['Steward','filter-stinson'],
			['Savoy','filter-sutro'],
			['Blaze','filter-toaster'],
			['Apricot','filter-valencia'],
			['Gloming','filter-vesper'],
			['Walter','filter-walden'],
			['Poplar','filter-willow'],
			['Xenon','filter-xpro-ii']
	],
	filterCss: {
		'filter-1977': 'sepia(.5) hue-rotate(-30deg) saturate(1.4)',
		'filter-aden': 'sepia(.2) brightness(1.15) saturate(1.4)',
		'filter-amaro': 'sepia(.35) contrast(1.1) brightness(1.2) saturate(1.3)',
		'filter-ashby': 'sepia(.5) contrast(1.2) saturate(1.8)',
		'filter-brannan': 'sepia(.4) contrast(1.25) brightness(1.1) saturate(.9) hue-rotate(-2deg)',
		'filter-brooklyn': 'sepia(.25) contrast(1.25) brightness(1.25) hue-rotate(5deg)',
		'filter-charmes': 'sepia(.25) contrast(1.25) brightness(1.25) saturate(1.35) hue-rotate(-5deg)',
		'filter-clarendon': 'sepia(.15) contrast(1.25) brightness(1.25) hue-rotate(5deg)',
		'filter-crema': 'sepia(.5) contrast(1.25) brightness(1.15) saturate(.9) hue-rotate(-2deg)',
		'filter-dogpatch': 'sepia(.35) saturate(1.1) contrast(1.5)',
		'filter-earlybird': 'sepia(.25) contrast(1.25) brightness(1.15) saturate(.9) hue-rotate(-5deg)',
		'filter-gingham': 'contrast(1.1) brightness(1.1)',
		'filter-ginza': 'sepia(.25) contrast(1.15) brightness(1.2) saturate(1.35) hue-rotate(-5deg)',
		'filter-hefe': 'sepia(.4) contrast(1.5) brightness(1.2) saturate(1.4) hue-rotate(-10deg)',
		'filter-helena': 'sepia(.5) contrast(1.05) brightness(1.05) saturate(1.35)',
		'filter-hudson': 'sepia(.25) contrast(1.2) brightness(1.2) saturate(1.05) hue-rotate(-15deg)',
		'filter-inkwell': 'brightness(1.25) contrast(.85) grayscale(1)',
		'filter-kelvin': 'sepia(.15) contrast(1.5) brightness(1.1) hue-rotate(-10deg)',
		'filter-juno': 'sepia(.35) contrast(1.15) brightness(1.15) saturate(1.8)',
		'filter-lark': 'sepia(.25) contrast(1.2) brightness(1.3) saturate(1.25)',
		'filter-lofi': 'saturate(1.1) contrast(1.5)',
		'filter-ludwig': 'sepia(.25) contrast(1.05) brightness(1.05) saturate(2)',
		'filter-maven': 'sepia(.35) contrast(1.05) brightness(1.05) saturate(1.75)',
		'filter-mayfair': 'contrast(1.1) brightness(1.15) saturate(1.1)',
		'filter-moon': 'brightness(1.4) contrast(.95) saturate(0) sepia(.35)',
		'filter-nashville': 'sepia(.25) contrast(1.5) brightness(.9) hue-rotate(-15deg)',
		'filter-perpetua': 'contrast(1.1) brightness(1.25) saturate(1.1)',
		'filter-poprocket': 'sepia(.15) brightness(1.2)',
		'filter-reyes': 'sepia(.75) contrast(.75) brightness(1.25) saturate(1.4)',
		'filter-rise': 'sepia(.25) contrast(1.25) brightness(1.2) saturate(.9)',
		'filter-sierra': 'sepia(.25) contrast(1.5) brightness(.9) hue-rotate(-15deg)',
		'filter-skyline': 'sepia(.15) contrast(1.25) brightness(1.25) saturate(1.2)',
		'filter-slumber': 'sepia(.35) contrast(1.25) saturate(1.25)',
		'filter-stinson': 'sepia(.35) contrast(1.25) brightness(1.1) saturate(1.25)',
		'filter-sutro': 'sepia(.4) contrast(1.2) brightness(.9) saturate(1.4) hue-rotate(-10deg)',
		'filter-toaster': 'sepia(.25) contrast(1.5) brightness(.95) hue-rotate(-15deg)',
		'filter-valencia': 'sepia(.25) contrast(1.1) brightness(1.1)',
		'filter-vesper': 'sepia(.35) contrast(1.15) brightness(1.2) saturate(1.3)',
		'filter-walden': 'sepia(.35) contrast(.8) brightness(1.25) saturate(1.4)',
		'filter-willow': 'brightness(1.2) contrast(.85) saturate(.05) sepia(.2)',
		'filter-xpro-ii': 'sepia(.45) contrast(1.25) brightness(1.75) saturate(1.3) hue-rotate(-5deg)'
	},
	emoji: ['😂','💯','❤️','🙌','👏','👌','😍','😯','😢','😅','😁','🙂','😎','😀','🤣','😃','😄','😆','😉','😊','😋','😘','😗','😙','😚','🤗','🤩','🤔','🤨','😐','😑','😶','🙄','😏','😣','😥','😮','🤐','😪','😫','😴','😌','😛','😜','😝','🤤','😒','😓','😔','😕','🙃','🤑','😲','🙁','😖','😞','😟','😤','😭','😦','😧','😨','😩','🤯','😬','😰','😱','😳','🤪','😵','😡','😠','🤬','😷','🤒','🤕','🤢','🤮','🤧','😇','🤠','🤡','🤥','🤫','🤭','🧐','🤓','😈','👿','👹','👺','💀','👻','👽','🤖','💩','😺','😸','😹','😻','😼','😽','🙀','😿','😾','🤲','👐','🤝','👍','👎','👊','✊','🤛','🤜','🤞','✌️','🤟','🤘','👈','👉','👆','👇','☝️','✋','🤚','🖐','🖖','👋','🤙','💪','🖕','✍️','🙏','💍','💄','💋','👄','👅','👂','👃','👣','👁','👀','🧠','🗣','👤','👥'
	],
	embed: {
		post: (function(url, caption = true, likes = false, layout = 'full') {
			let u = url + '/embed?';
			u += caption ? 'caption=true&' : 'caption=false&';
			u += likes ? 'likes=true&' : 'likes=false&';
			u += layout == 'compact' ? 'layout=compact' : 'layout=full';
			return '<iframe title="Pixelfed Post Embed" src="'+u+'" class="pixelfed__embed" style="max-width: 100%; border: 0" width="400" allowfullscreen="allowfullscreen"></iframe><script async defer src="'+window.location.origin +'/embed.js"><\/script>';
		}),
		profile: (function(url) {
			let u = url + '/embed';
			return '<iframe title="Pixelfed Profile Embed" src="'+u+'" class="pixelfed__embed" style="max-width: 100%; border: 0" width="400" allowfullscreen="allowfullscreen"></iframe><script async defer src="'+window.location.origin +'/embed.js"><\/script>';
		})
	},

	clipboard: (function(data) {
		return navigator.clipboard.writeText(data);
	}),

	navatar: (function() {
		$('#navbarDropdown .far').addClass('d-none');
			$('#navbarDropdown img').attr('src',window._sharedData.curUser.avatar)
			.removeClass('d-none')
			.addClass('rounded-circle border shadow')
			.attr('width', 34).attr('height', 34);
	})
};

const warningTitleCSS = 'color:red; font-size:60px; font-weight: bold; -webkit-text-stroke: 1px black;';
const warningDescCSS = 'font-size: 18px;';
console.log('%cStop!', warningTitleCSS);
console.log("%cThis is a browser feature intended for developers. If someone told you to copy and paste something here to enable a Pixelfed feature or \"hack\" someone's account, it is a scam and will give them access to your Pixelfed account.", warningDescCSS);
