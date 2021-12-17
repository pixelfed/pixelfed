let mix = require('laravel-mix');

mix.sass('resources/assets/sass/app.scss', 'public/css')
.sass('resources/assets/sass/appdark.scss', 'public/css')
.sass('resources/assets/sass/admin.scss', 'public/css')
.sass('resources/assets/sass/landing.scss', 'public/css').version();

mix.js('resources/assets/js/app.js', 'public/js')
.js('resources/assets/js/activity.js', 'public/js')
.js('resources/assets/js/components.js', 'public/js')
.js('resources/assets/js/discover.js', 'public/js')
.js('resources/assets/js/profile.js', 'public/js')
.js('resources/assets/js/status.js', 'public/js')
.js('resources/assets/js/timeline.js', 'public/js')
.js('resources/assets/js/compose.js', 'public/js')
.js('resources/assets/js/compose-classic.js', 'public/js')
.js('resources/assets/js/search.js', 'public/js')
.js('resources/assets/js/developers.js', 'public/js')
.js('resources/assets/js/loops.js', 'public/js')
.js('resources/assets/js/hashtag.js', 'public/js')
.js('resources/assets/js/collectioncompose.js', 'public/js')
.js('resources/assets/js/collections.js', 'public/js')
.js('resources/assets/js/profile-directory.js', 'public/js')
.js('resources/assets/js/story-compose.js', 'public/js')
 .js('resources/assets/js/direct.js', 'public/js')
.js('resources/assets/js/admin.js', 'public/js')
.js('resources/assets/js/rempro.js', 'public/js')
.js('resources/assets/js/rempos.js', 'public/js')

.extract([
	'lodash',
	'popper.js',
	'jquery',
	'axios',
	'bootstrap',
	'vue',
	'readmore-js'
])
.version();
