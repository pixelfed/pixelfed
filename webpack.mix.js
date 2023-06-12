let mix = require('laravel-mix');
const fs = require("fs");

mix.before(() => {
	fs.rmSync('public/js', { recursive: true, force: true });
});


mix.sass('resources/assets/sass/app.scss', 'public/css')
.sass('resources/assets/sass/appdark.scss', 'public/css')
.sass('resources/assets/sass/admin.scss', 'public/css')
.sass('resources/assets/sass/portfolio.scss', 'public/css')
.sass('resources/assets/sass/spa.scss', 'public/css')
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
.js('resources/assets/js/hashtag.js', 'public/js')
.js('resources/assets/js/collectioncompose.js', 'public/js')
.js('resources/assets/js/collections.js', 'public/js')
.js('resources/assets/js/profile-directory.js', 'public/js')
.js('resources/assets/js/story-compose.js', 'public/js')
.js('resources/assets/js/direct.js', 'public/js')
.js('resources/assets/js/admin.js', 'public/js')
.js('resources/assets/js/spa.js', 'public/js')
.js('resources/assets/js/stories.js', 'public/js')
.js('resources/assets/js/portfolio.js', 'public/js')
.js('resources/assets/js/account-import.js', 'public/js')
.js('resources/assets/js/admin_invite.js', 'public/js')
.js('resources/assets/js/landing.js', 'public/js')
.vue({ version: 2 });

mix.extract();
mix.version();

const TerserPlugin = require('terser-webpack-plugin');

mix.options({
	processCssUrls: false,
	terser: {
		parallel: true,
		terserOptions: {
			compress: true,
			output: {
				comments: false
			}
		}
	}
})
mix.webpackConfig({
	optimization: {
		providedExports: false,
		sideEffects: false,
		usedExports: false,
		minimize: true,
		minimizer: [ new TerserPlugin({
			extractComments: false,
		})]
	},
	output: {
		chunkFilename: 'js/[name].[chunkhash].js',
	}
});
mix.autoload({
	jquery: ['$', 'jQuery', 'window.jQuery']
});
