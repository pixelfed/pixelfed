require('./polyfill');
window._ = require('lodash');
window.Popper = require('popper.js').default;
window.pixelfed = window.pixelfed || {};
window.$ = window.jQuery = require('jquery');
require('bootstrap');
window.axios = require('axios');
window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';
require('readmore-js');
window.blurhash = require("blurhash");

let token = document.head.querySelector('meta[name="csrf-token"]');
if (token) {
    window.axios.defaults.headers.common['X-CSRF-TOKEN'] = token.content;
} else {
    console.error('CSRF token not found.');
}

window.App = window.App || {};

window.App.redirect = function() {
	document.querySelectorAll('a').forEach(function(i,k) { 
		let a = i.getAttribute('href');
		if(a && a.length > 5 && a.startsWith('https://')) {
			let url = new URL(a);
			if(url.host !== window.location.host && url.pathname !== '/i/redirect') {
				i.setAttribute('href', '/i/redirect?url=' + encodeURIComponent(a));
			}
		}
	});
}

window.App.boot = function() {
	new Vue({ el: '#content'});
}

window.addEventListener("load", () => {
  if ("serviceWorker" in navigator) {
    navigator.serviceWorker.register("/sw.js");
  }
});

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
			['Luke','filter-luke'], 
			['Minotaur','filter-minotaur'], 
			['Magma','filter-magma'], 
			['Spell','filter-spell'], 
			['Dimension','filter-dimension'], 
			['Fixie','filter-fixie'], 
			['Berlock','filter-berlock'], 
			['Bells','filter-bells'], 
			['Welcome','filter-welcome'], 
			['Abner','filter-abner'], 
			['Woodstock','filter-woodstock'], 
			['Fields','filter-fields'], 
			['Dinner','filter-dinner'], 
			['Boss','filter-boss'], 
			['Handbasket','filter-handbasket'], 
			['River','filter-river'], 
			['Quill','filter-quill'], 
			['Frosty','filter-frosty'], 
			['Hera','filter-hera'], 
			['Birdsong','filter-birdsong'], 
			['Canada','filter-canada'], 
			['Wendy','filter-wendy'], 
			['Shamus','filter-shamus'], 
			['Marylebone','filter-marylebone'], 
			['Luna','filter-luna'], 
			['Chattanooga','filter-chattanooga'], 
			['Felicity','filter-felicity'], 
			['Jazz','filter-jazz'], 
			['Doggett','filter-doggett'], 
			['Ascend','filter-ascend'], 
			['Tango','filter-tango'], 
			['Cherry','filter-cherry'], 
			['Nemo','filter-nemo'], 
			['Suitable','filter-suitable'], 
			['Mayor','filter-mayor'], 
			['Bread','filter-bread'], 
			['Iberia','filter-iberia'], 
			['Folded','filter-folded'], 
			['Cabin','filter-cabin'], 
			['Crayon','filter-crayon'], 
			['Expert','filter-expert']
	],
	filterCss: {
		'filter-luke': 'sepia(.5) hue-rotate(-30deg) saturate(1.4)',
		'filter-minotaur': 'sepia(.2) brightness(1.15) saturate(1.4)',
		'filter-magma': 'sepia(.35) contrast(1.1) brightness(1.2) saturate(1.3)',
		'filter-spell': 'sepia(.5) contrast(1.2) saturate(1.8)',
		'filter-dimension': 'sepia(.4) contrast(1.25) brightness(1.1) saturate(.9) hue-rotate(-2deg)',
		'filter-fixie': 'sepia(.25) contrast(1.25) brightness(1.25) hue-rotate(5deg)',
		'filter-berlock': 'sepia(.25) contrast(1.25) brightness(1.25) saturate(1.35) hue-rotate(-5deg)',
		'filter-bells': 'sepia(.15) contrast(1.25) brightness(1.25) hue-rotate(5deg)',
		'filter-welcome': 'sepia(.5) contrast(1.25) brightness(1.15) saturate(.9) hue-rotate(-2deg)',
		'filter-abner': 'sepia(.35) saturate(1.1) contrast(1.5)',
		'filter-woodstock': 'sepia(.25) contrast(1.25) brightness(1.15) saturate(.9) hue-rotate(-5deg)',
		'filter-fields': 'contrast(1.1) brightness(1.1)',
		'filter-dinner': 'sepia(.25) contrast(1.15) brightness(1.2) saturate(1.35) hue-rotate(-5deg)',
		'filter-boss': 'sepia(.4) contrast(1.5) brightness(1.2) saturate(1.4) hue-rotate(-10deg)',
		'filter-handbasket': 'sepia(.5) contrast(1.05) brightness(1.05) saturate(1.35)',
		'filter-river': 'sepia(.25) contrast(1.2) brightness(1.2) saturate(1.05) hue-rotate(-15deg)',
		'filter-quill': 'brightness(1.25) contrast(.85) grayscale(1)',
		'filter-frosty': 'sepia(.15) contrast(1.5) brightness(1.1) hue-rotate(-10deg)',
		'filter-hera': 'sepia(.35) contrast(1.15) brightness(1.15) saturate(1.8)',
		'filter-birdsong': 'sepia(.25) contrast(1.2) brightness(1.3) saturate(1.25)',
		'filter-canada': 'saturate(1.1) contrast(1.5)',
		'filter-wendy': 'sepia(.25) contrast(1.05) brightness(1.05) saturate(2)',
		'filter-shamus': 'sepia(.35) contrast(1.05) brightness(1.05) saturate(1.75)',
		'filter-marylebone': 'contrast(1.1) brightness(1.15) saturate(1.1)',
		'filter-luna': 'brightness(1.4) contrast(.95) saturate(0) sepia(.35)',
		'filter-chattanooga': 'sepia(.25) contrast(1.5) brightness(.9) hue-rotate(-15deg)',
		'filter-felicity': 'contrast(1.1) brightness(1.25) saturate(1.1)',
		'filter-jazz': 'sepia(.15) brightness(1.2)',
		'filter-doggett': 'sepia(.75) contrast(.75) brightness(1.25) saturate(1.4)',
		'filter-ascend': 'sepia(.25) contrast(1.25) brightness(1.2) saturate(.9)',
		'filter-tango': 'sepia(.25) contrast(1.5) brightness(.9) hue-rotate(-15deg)',
		'filter-cherry': 'sepia(.15) contrast(1.25) brightness(1.25) saturate(1.2)',
		'filter-nemo': 'sepia(.35) contrast(1.25) saturate(1.25)',
		'filter-suitable': 'sepia(.35) contrast(1.25) brightness(1.1) saturate(1.25)',
		'filter-mayor': 'sepia(.4) contrast(1.2) brightness(.9) saturate(1.4) hue-rotate(-10deg)',
		'filter-bread': 'sepia(.25) contrast(1.5) brightness(.95) hue-rotate(-15deg)',
		'filter-iberia': 'sepia(.25) contrast(1.1) brightness(1.1)',
		'filter-folded': 'sepia(.35) contrast(1.15) brightness(1.2) saturate(1.3)',
		'filter-cabin': 'sepia(.35) contrast(.8) brightness(1.25) saturate(1.4)',
		'filter-crayon': 'brightness(1.2) contrast(.85) saturate(.05) sepia(.2)',
		'filter-expert': 'sepia(.45) contrast(1.25) brightness(1.75) saturate(1.3) hue-rotate(-5deg)'
	},
	emoji: [
		'😂','💯','❤️','🙌','👏','👌','😍','😯','😢','😅','😁','🙂','😎','😀','🤣','😃','😄','😆','😉','😊','😋','😘','😗','😙','😚','🤗','🤩','🤔','🤨','😐','😑','😶','🙄','😏','😣','😥','😮','🤐','😪','😫','😴','😌','😛','😜','😝','🤤','😒','😓','😔','😕','🙃','🤑','😲','🙁','😖','😞','😟','😤','😭','😦','😧','😨','😩','🤯','😬','😰','😱','😳','🤪','😵','😡','😠','🤬','😷','🤒','🤕','🤢','🤮','🤧','😇','🤠','🤡','🤥','🤫','🤭','🧐','🤓','😈','👿','👹','👺','💀','👻','👽','🤖','💩','😺','😸','😹','😻','😼','😽','🙀','😿','😾','🤲','👐','🤝','👍','👎','👊','✊','🤛','🤜','🤞','✌️','🤟','🤘','👈','👉','👆','👇','☝️','✋','🤚','🖐','🖖','👋','🤙','💪','🖕','✍️','🙏','💍','💄','💋','👄','👅','👂','👃','👣','👁','👀','🧠','🗣','👤','👥'
	],
	embed: {
		post: (function(url, caption = true, likes = false, layout = 'full') {
			let u = url + '/embed?';
			u += caption ? 'caption=true&' : 'caption=false&';
			u += likes ? 'likes=true&' : 'likes=false&';
			u += layout == 'compact' ? 'layout=compact' : 'layout=full';
			return '<iframe src="'+u+'" class="pixelfed__embed" style="max-width: 100%; border: 0" width="400" allowfullscreen="allowfullscreen"></iframe><script async defer src="'+window.location.origin +'/embed.js"><\/script>';
		}),
		profile: (function(url) {
			let u = url + '/embed';
			return '<iframe src="'+u+'" class="pixelfed__embed" style="max-width: 100%; border: 0" width="400" allowfullscreen="allowfullscreen"></iframe><script async defer src="'+window.location.origin +'/embed.js"><\/script>';
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
			.attr('width', 38).attr('height', 38);
	})

};
