window._ = require('lodash');
window.Popper = require('popper.js').default;
window.pixelfed = window.pixelfed || {};
window.$ = window.jQuery = require('jquery');
require('bootstrap');
window.axios = require('axios');
window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';
require('readmore-js');

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
			let interval = Math.floor(seconds / 31536000);
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
		})
	}, 
	filters: [
			['1977','filter-1977'], 
			['Aden','filter-aden'], 
			['Amaro','filter-amaro'], 
			['Ashby','filter-ashby'], 
			['Brannan','filter-brannan'], 
			['Brooklyn','filter-brooklyn'], 
			['Charmes','filter-charmes'], 
			['Clarendon','filter-clarendon'], 
			['Crema','filter-crema'], 
			['Dogpatch','filter-dogpatch'], 
			['Earlybird','filter-earlybird'], 
			['Gingham','filter-gingham'], 
			['Ginza','filter-ginza'], 
			['Hefe','filter-hefe'], 
			['Helena','filter-helena'], 
			['Hudson','filter-hudson'], 
			['Inkwell','filter-inkwell'], 
			['Kelvin','filter-kelvin'], 
			['Kuno','filter-juno'], 
			['Lark','filter-lark'], 
			['Lo-Fi','filter-lofi'], 
			['Ludwig','filter-ludwig'], 
			['Maven','filter-maven'], 
			['Mayfair','filter-mayfair'], 
			['Moon','filter-moon'], 
			['Nashville','filter-nashville'], 
			['Perpetua','filter-perpetua'], 
			['Poprocket','filter-poprocket'], 
			['Reyes','filter-reyes'], 
			['Rise','filter-rise'], 
			['Sierra','filter-sierra'], 
			['Skyline','filter-skyline'], 
			['Slumber','filter-slumber'], 
			['Stinson','filter-stinson'], 
			['Sutro','filter-sutro'], 
			['Toaster','filter-toaster'], 
			['Valencia','filter-valencia'], 
			['Vesper','filter-vesper'], 
			['Walden','filter-walden'], 
			['Willow','filter-willow'], 
			['X-Pro II','filter-xpro-ii']
	],
	emoji: ['😂','💯','❤️','🙌','👏','👌','😍','😯','😢','😅','😁','🙂','😎','😀','🤣','😃','😄','😆','😉','😊','😋','😘','😗','😙','😚','🤗','🤩','🤔','🤨','😐','😑','😶','🙄','😏','😣','😥','😮','🤐','😪','😫','😴','😌','😛','😜','😝','🤤','😒','😓','😔','😕','🙃','🤑','😲','🙁','😖','😞','😟','😤','😭','😦','😧','😨','😩','🤯','😬','😰','😱','😳','🤪','😵','😡','😠','🤬','😷','🤒','🤕','🤢','🤮','🤧','😇','🤠','🤡','🤥','🤫','🤭','🧐','🤓','😈','👿','👹','👺','💀','👻','👽','🤖','💩','😺','😸','😹','😻','😼','😽','🙀','😿','😾','🤲','👐','🤝','👍','👎','👊','✊','🤛','🤜','🤞','✌️','🤟','🤘','👈','👉','👆','👇','☝️','✋','🤚','🖐','🖖','👋','🤙','💪','🖕','✍️','🙏','💍','💄','💋','👄','👅','👂','👃','👣','👁','👀','🧠','🗣','👤','👥'
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
	}

};