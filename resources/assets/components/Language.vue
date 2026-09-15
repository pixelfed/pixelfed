<template>
	<div class="web-wrapper">
		<div v-if="isLoaded" class="container-fluid mt-3">
			<div class="row">
				<div class="col-md-3 d-md-block">
					<sidebar :user="profile" />
				</div>

				<div class="col-md-6">
					<div class="jumbotron shadow-sm bg-white">
						<div class="text-center">
							<h1 class="font-weight-bold mb-0">Language</h1>
						</div>
					</div>

					<div class="card shadow-sm mb-3">
						<div class="card-body">
							<div class="locale-changer form-group">
								<label>Language</label>
								<select class="form-control" v-model="locale">
									<option v-for="(lang, i) in langs" :key="`Lang${i}`" :value="lang">
										{{ fullName(lang) }}
										<template v-if="fullName(lang) != localeName(lang)"> · {{ localeName(lang) }}</template>
									</option>
								</select>
							</div>
						</div>
					</div>
				</div>
			</div>
		</div>
		<drawer />
	</div>
</template>

<script type="text/javascript">
	import Drawer from './partials/drawer.vue';
	import Sidebar from './partials/sidebar.vue';

	export default {
		components: {
			"drawer": Drawer,
            "sidebar": Sidebar,
        },

        data() {
        	return {
        		isLoaded: false,
        		profile: undefined,
        		locale: 'en-US',
        		// Populated from loaded i18n locales in mounted() so the list
        		// always matches the lang/ folders (locale codes, e.g. de-DE).
        		langs: []
        	}
        },

        mounted() {
			this.profile = window._sharedData.user;
			this.isLoaded = true;
			this.locale = this.$i18n.locale;
			this.langs = this.$i18n.availableLocales.slice().sort();
			// [i18n-debug] remove after diagnosis.
			console.group('[i18n-debug] Language.vue');
			console.log('$i18n.locale =', this.$i18n.locale);
			console.log('availableLocales =', this.$i18n.availableLocales);
			console.log('dropdown langs =', this.langs);
			console.groupEnd();
        },

        watch: {
        	locale: function(val) {
        		this.loadLang(val);
        	}
        },

        methods: {
        	fullName(val) {
        		const factory = new Intl.DisplayNames([val], { type: 'language' });
        		return factory.of(val);
        	},

        	localeName(val) {
        		const factory = new Intl.DisplayNames([this.$i18n.locale], { type: 'language' });
        		return factory.of(val);
        	},

        	loadLang(lang) {
        		// [i18n-debug] remove after diagnosis.
        		console.log('[i18n-debug] loadLang submitting l =', JSON.stringify(lang));
        		axios.post('/api/pixelfed/web/change-language.json', {
        			v: 0.1,
        			l: lang
        		})
        		.then(res => {
        			console.log('[i18n-debug] change-language OK, response =', res.data, '→ setting $i18n.locale =', lang, '| has messages?', Object.prototype.hasOwnProperty.call(this.$i18n.messages, lang));
        			this.$i18n.locale = lang;
        		})
        		.catch(err => {
        			console.error('[i18n-debug] change-language FAILED', err.response ? err.response.status : err, err.response ? err.response.data : '');
        		})
        	}
        }
	}
</script>
