<template>
    <nav class="navbar navbar-expand-lg navbar-dark fixed-top">
        <div class="container" style="max-width: 600px;">
            <router-link to="/" class="navbar-brand">
                <img src="/img/pixelfed-icon-color.svg" width="40" height="40" alt="Logo">
                <span class="mr-3">{{ name }}</span>
            </router-link>
            <ul class="navbar-nav mr-auto">
            </ul>
            <div class="my-2 my-lg-0">
                <a class="btn btn-outline-light btn-sm rounded-pill font-weight-bold px-4" href="/login">Login</a>

                <a v-if="config.open_registration || config.curated_onboarding" class="ml-2 btn btn-primary btn-primary-alt btn-sm rounded-pill font-weight-bold px-4" :href="regLink">Sign up</a>
            </div>
        </div>
    </nav>
</template>

<script type="text/javascript">
    export default {
        data() {
            return {
                config: window.pfl,
                name: window.pfl.name,
            }
        },
        computed: {
            regLink: {
                get() {
                    if(this.config.open_registration) {
                        return '/register';
                    }

                    if(this.config.curated_onboarding) {
                        return '/auth/sign_up';
                    }
                }
            }
        },
        mounted() {
            $(window).scroll(function(){
                $('nav').toggleClass('bg-black', $(this).scrollTop() > 20);
            });
        }
    }
</script>
