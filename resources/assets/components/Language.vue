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
        		locale: 'en',
        		// langs: ["af","ar","ca","cs","cy","da","de","el","en","eo","es","eu","fa","fi","fr","gl","he","hu","id","it","ja","ko","ms","nl","no","oc","pl","pt","ro","ru","sr","sv","th","tr","uk","vi","zh","zh-cn","zh-tw"]
        		langs: [
        			"en",
        			"ar",
        			"ca",
        			"de",
        			"el",
        			"es",
        			"eu",
        			"fr",
        			"he",
        			"gd",
        			"gl",
        			"id",
        			"it",
        			"ja",
        			"nl",
        			"pl",
        			"pt",
        			"ru",
        			"uk",
        			"vi"
        		]
        	}
        },

        mounted() {
			this.profile = window._sharedData.user;
			this.isLoaded = true;
			this.locale = this.$i18n.locale;
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
        		axios.post('/api/pixelfed/web/change-language.json', {
        			v: 0.1,
        			l: lang
        		})
        		.then(res => {
        			this.$i18n.locale = lang;
        		})
        	}
        }
	}
</script>
