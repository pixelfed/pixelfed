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

window.App.boot = function() {
	new Vue({ el: '#content'});
}

window.App.util = {
	time: (function() { 
		return new Date; 
	}),
	version: (function() {
		return 1;
	}),
	format: {
		count: (function(count = 0) {
			if(count < 1) {
				return 0;
			}
			return new Intl.NumberFormat('en-GB', { notation: "compact" , compactDisplay: "short" }).format(count);
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
		emoji: ['😂','💯','❤️','🙌','👏','👌','😍','😯','😢','😅','😁','🙂','😎','😀','🤣','😃','😄','😆','😉','😊','😋','😘','😗','😙','😚','🤗','🤩','🤔','🤨','😐','😑','😶','🙄','😏','😣','😥','😮','🤐','😪','😫','😴','😌','😛','😜','😝','🤤','😒','😓','😔','😕','🙃','🤑','😲','🙁','😖','😞','😟','😤','😭','😦','😧','😨','😩','🤯','😬','😰','😱','😳','🤪','😵','😡','😠','🤬','😷','🤒','🤕','🤢','🤮','🤧','😇','🤠','🤡','🤥','🤫','🤭','🧐','🤓','😈','👿','👹','👺','💀','👻','👽','🤖','💩','😺','😸','😹','😻','😼','😽','🙀','😿','😾','🤲','👐','🤝','👍','👎','👊','✊','🤛','🤜','🤞','✌️','🤟','🤘','👈','👉','👆','👇','☝️','✋','🤚','🖐','🖖','👋','🤙','💪','🖕','✍️','🙏','💍','💄','💋','👄','👅','👂','👃','👣','👁','👀','🧠','🗣','👤','👥'],
};