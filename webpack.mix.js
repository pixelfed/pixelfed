let mix = require('laravel-mix');

/*
|--------------------------------------------------------------------------
| Mix Asset Management
|--------------------------------------------------------------------------
|
| Mix provides a clean, fluent API for defining some Webpack build steps
| for your Laravel application. By default, we are compiling the Sass
| file for the application as well as bundling up all the JS files.
|
*/

mix.js('resources/assets/js/app.js', 'public/js')
.js('resources/assets/js/activity.js', 'public/js')
.js('resources/assets/js/components.js', 'public/js')

// Discover component
.js('resources/assets/js/discover.js', 'public/js')

// Profile component
.js('resources/assets/js/profile.js', 'public/js')

// Status component
.js('resources/assets/js/status.js', 'public/js')

// Timeline component
.js('resources/assets/js/timeline.js', 'public/js')

// MicroUI component
.js('resources/assets/js/micro.js', 'public/js')

.sass('resources/assets/sass/app.scss', 'public/css', {
	implementation: require('node-sass')
})
.sass('resources/assets/sass/appdark.scss', 'public/css', {
	implementation: require('node-sass')
})
.version();
